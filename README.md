
# Timestamp convention

Application timestamps are written in Asia/Manila local time, configured in `config/db.php` through PHP's timezone and the PDO MySQL session (`+08:00`). The exception is `ai_suggestion_usage.last_generated_at`, stored in UTC because the cooldown compares it with `time()`; the AI quota day itself follows the Asia/Manila calendar day. MySQL `TIMESTAMP` columns are stored internally as UTC and converted according to the session timezone, while `DATETIME` columns retain literal values. Standalone scripts must load `config/db.php`; the installed `php.ini` default is `Europe/Berlin`.

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
