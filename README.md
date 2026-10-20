
# Timestamp convention

Application timestamps are written in Asia/Manila local time, configured in `config/db.php` through PHP's timezone and the PDO MySQL session (`+08:00`). The exception is `ai_suggestion_usage.last_generated_at`, stored in UTC because the cooldown compares it with `time()`; the AI quota day itself follows the Asia/Manila calendar day. MySQL `TIMESTAMP` columns are stored internally as UTC and converted according to the session timezone, while `DATETIME` columns retain literal values. Standalone scripts must load `config/db.php`; the installed `php.ini` default is `Europe/Berlin`.

## Active school year

School Heads can manually set any non-archived school year as active from
Settings → School Years. That selection persists across requests and takes
precedence over the date-based active-year selection. Creating a new school
year continues to make it active; if a saved manual selection is no longer
valid, the application removes it and resumes date-based selection.

## Teacher rating anomaly detector

The existing two decision trees in `ml/ml_classifier.py` use synthetic reference
data for maturity and priority classification. They are fitted in memory and are
not saved. The separate teacher outlier detector in `ml/teacher_outlier.py` is
an unsupervised IsolationForest trained from the seeded teacher and School Head
assessment history; it does not use teacher names or seeded labels as features.
Run `ml/venv64/Scripts/python.exe ml/teacher_outlier.py --retrain` to create a
local model, or `--evaluate` to create the tracked JSON evaluation report.
The evaluation data is synthetic, so its results demonstrate detection of the
generated patterns and are not accuracy on real schools.

### Learning to rank AI suggestions

The configured LLM remains responsible for writing suggestions. Groq is the
default backend; set `LLM_BACKEND` in `ml/.env` to select another backend.
The ML service must be restarted to reload environment changes. Using Groq sends
school assessment content to an external provider. The response includes a
privacy notice when Groq or OpenAI is selected. A separate CatBoost classifier
can rank generated dimension suggestions using prior **real, linked**
improvement-plan decisions; it does not fine-tune Groq or the LLM. Groq
suggestion requests use JSON response mode and default to an 8,192-token output
budget (`GROQ_MAX_TOKENS`, configurable from 1 to 65,536) to leave room for
reasoning plus the structured suggestions. Empty or truncated Groq responses
are reported as generation failures instead of being mistaken for valid JSON.
The read-only
training bridge in `ml/suggestion_ranker_data.php` excludes synthetic data and
rule-based fallback generations, as well as unresolved drafts/submissions. A
currently `finalized` plan is a positive label; a plan still `returned` is a
negative workflow-outcome label. Returned plans can later change status, so
these labels describe the current recorded decision, not a judgment that the
underlying recommendation was inherently good or bad.

The ranker stays inactive until there are at least 100 labeled suggestions and
20 in each outcome class. It validates by holding out complete assessment
cycles, so examples from a cycle cannot appear in both training and test folds;
each fold must contain both outcome classes and at least two cycles must be
available. It must reach balanced accuracy of at least 0.55 and beat the
majority baseline by at least 0.05. Otherwise it leaves the current suggestion
order unchanged. Its score is an estimated workflow-approval likelihood, not a
calibrated probability or a measure of school outcomes.

For isolated CatBoost testing, `ml/seed_catboost_demo.php` creates tagged
synthetic examples from the existing school's dimensions, indicators, and one
existing active teacher. It does not create schools, users, dimensions, or
indicators. Synthetic generations remain marked `is_synthetic = 1` and are
excluded from normal training. Only the explicit
`--retrain --include-demo` option passes `catboost_demo_v1` rows through the
bridge; this trains a separate `suggestion_ranker_demo.joblib` file and marks
the output/model metadata with `demo_data: true`. Demo models are not used for
normal inference. To test one explicitly, set
`SUGGESTION_RANKER_USE_DEMO=1` in the ML service environment; its response is
marked as demo-trained and is not reliable for production. Use
`ml/cleanup_catboost_demo.php` to remove the tagged database rows and the demo
model without deleting a real trained model. Seed and cleanup scripts are
review-only until explicitly run.

After installing `ml/requirements.txt`, evaluate and train from the ML service's
configured database connection with:

```text
ml/venv64/Scripts/python.exe ml/suggestion_ranker.py --retrain
```

The synthetic demo-only equivalent is:

```text
php ml/seed_catboost_demo.php
ml/venv64/Scripts/python.exe ml/suggestion_ranker.py --retrain --include-demo
php ml/cleanup_catboost_demo.php
```

Review the JSON report before deploying. The saved model is loaded when each
recommendation is ranked, so a service restart is not required. Retraining is
deliberately explicit rather than triggered after every generation, so newly
stored AI text is not mistaken for a successful training example.

The post-hoc evaluation reports the detector and the card-trigger rules
separately. all_low, all_high, mostly_low, mostly_high, and erratic require a
mean absolute peer difference of at least 1.0 rating point. The constant card
uses a deterministic, non-trained rule: at least 10 identical ratings,
standard deviation 0, and rating value 2 or 3; values 1 and 4 take precedence
as all_low and all_high. Derived mostly_low/mostly_high labels were added
because the first stress test had no card type for near-all-low ratings.

### Changes after first evaluation

The first report used pairwise teacher-to-peer absolute differences when
constructing the peer feature. The corrected evaluator uses the specified
leave-one-out per-indicator peer mean. That changed the peer feature and
therefore the 20-seed headline at contamination 0.05 from 6.15 TP / 1.80 FP
to 6.40 TP / 1.35 FP. The first report is retained as
`ml/reports/teacher_outlier_evaluation_v1.json`.

The final fresh stress-test decision selects the rules as the card trigger:
the forest flag is not required, while the forest anomaly score remains a
confidence factor and is logged. The forest is still evaluated as an ungated
comparison, but it does not add a production card beyond the peer-gap and
constant rules in this synthetic test.

### Production card requirements

| Derived card type | IsolationForest flag required | Peer gap >= 1.0 required | Other production requirement |
|---|---:|---:|---|
| all_low | No | Yes | None |
| all_high | No | Yes | None |
| mostly_low | No | Yes | At least 80% ratings are 1, but not all |
| mostly_high | No | Yes | At least 80% ratings are 4, but not all |
| erratic | No | Yes | None |
| constant | No | No | At least 10 ratings, all identical, value 2 or 3 |

The constant requirement is deterministic and is shared by production and
evaluation. Values 1 and 4 are classified as all_low and all_high instead.

### Confidence shown on teacher cards

The PHP card builder uses this fixed unusualness formula:

```text
confidence_pct =
  clamp(0, 100,
    round(
      50
      + min(30, abs(anomaly_score) * 300)
      + min(20, peer_mean_abs_difference * 20),
      1
    )
  )
```

The two factors shown in the existing tooltip are:

* `Anomaly score: <score>` — how unusual the rating pattern is to the forest.
* `Peer mean absolute difference: <gap> rating points.` — the average absolute
  difference from leave-one-out peer means.

The score is `-IsolationForest.decision_function(...)`: larger positive values
are more unusual, and a card may be triggered by the deterministic rules even
when the score is at or below the forest flag threshold. The confidence formula
is:

```text
confidence_pct =
  clamp(0, 100,
    round(
      50
      + min(30, max(0, anomaly_score) * 300)
      + min(20, peer_mean_abs_difference * 20),
      1
    )
  )
```

If the model or score is unavailable, the forest contribution is `0`; the
deterministic card trigger is not removed.

The tooltip also says: “This reflects how unusual the ratings are and is not a
probability.” Constant cards are capped at `65%`, which is Moderate Confidence.
This confidence is not rating completeness and the formula was not tuned for
appearance.

The original first-report headline was `6.15 TP / 1.80 FP` at contamination
`0.05`; the corrected report is `6.40 TP / 1.35 FP`. The responsible code
change was in the peer feature construction: the evaluator now uses the
specified leave-one-out, per-indicator peer mean instead of pairwise
teacher-to-peer absolute differences. This changes the feature values and
therefore the forest thresholds. The first-report file was not found in Git
history; `ml/reports/teacher_outlier_evaluation_v1.json` is retained in the
working tree as the available snapshot, but cannot be restored from an
earlier Git commit.

### Held-out subtle-anomaly stress test

`ml/teacher_outlier_stress_test.py` generates 130 teacher-cycles entirely in
memory (5 teachers, 21 teacher indicators, 42 School Head indicators, and 26
cycles), with 15 fixed anomalies across mostly_low, mild_lenient, rubber_stamp,
fatigue, and alternating patterns. It also includes strict and lenient normal
teachers. The generator uses a fixed seed and does not read or write the
database, saved model, or seeded assessment data. Run it with:

```text
ml/venv64/Scripts/python.exe ml/teacher_outlier_stress_test.py
```

The output is `ml/reports/teacher_outlier_stress_test.json`. The first
stress-test snapshot is labeled as the result that motivated mostly_low. Its
direct-membership rule-only counts are 0/0/1/0/3 for
mostly_low/mild_lenient/rubber_stamp/fatigue/alternating, and its overall
rule-only, ungated-forest, and forest-plus-gate caught means are 4.00, 7.00,
and 3.10. Per-type counts are checked automatically against overall true
positives for every method and seed.

For teacher cards, the trigger is deterministic rules: the required peer gap
and the constant-rating rule. The forest score only feeds the confidence value
and logs; the forest did not improve production-card detection on the seeded
data or the stress data. The fresh fairer measure used seed `20261002`, the
same five definitions, and three mostly_high cases (18 anomalies total). It
supports cards for all-ones, all-fours, constant, alternating, mostly_low, and
mostly_high, but not fatigue, rubber-stamping, or mild leniency. Rule-only
caught 9.0 with 0 false positives; forest-plus-gate also caught 9.0 with 0
false positives; the ungated forest caught 10.3 but had 0.8 false positives.
The mostly_low/mostly_high thresholds were chosen knowing the generator ranges,
so these stress results are not independent evidence. These are synthetic
results only and make no accuracy claim about real schools.

The peer-gap threshold of `1.0` and the derived-type definitions were set on
synthetic data and have never been tested on real ratings, so expect more false
cards on real data. Cards are an exploratory prompt for a conversation, not a
judgment about a teacher. The service-down test used an unreachable URL.

## Improvement plans with multiple indicators

An improvement plan can link multiple indicators within the same dimension.
The plan list displays those codes together, while indicators from different
dimensions are saved as separate plans. On existing databases, apply
`config/migration_improvement_plan_indicators.sql` before using this feature;
the migration creates the link table and backfills each existing plan's
indicator.

## Returning an individual teacher submission

On existing databases, apply `config/migration_teacher_submission_returns.sql`
before using per-teacher returns from the coordinator dashboard. It adds the
`returned` submission status and the append-only return/resubmission history
table without deleting existing responses.
