"""CatBoost ranker for school-improvement suggestions.

Only finalized or currently returned, non-synthetic plans linked to an LLM
suggestion are training labels. Validation holds out complete assessment cycles
so suggestions from the same cycle cannot leak across training and test folds.
"""
from __future__ import annotations

import argparse
import json
import logging
import os
import re
import subprocess
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import joblib
import numpy as np
import pandas as pd
from catboost import CatBoostError
from sklearn.dummy import DummyClassifier
from sklearn.metrics import balanced_accuracy_score
from sklearn.model_selection import GroupKFold

logger = logging.getLogger(__name__)
BASE_DIR = Path(__file__).resolve().parent
DATA_BRIDGE = BASE_DIR / "suggestion_ranker_data.php"
MODEL_PATH = BASE_DIR / "models" / "suggestion_ranker.joblib"
DEMO_MODEL_PATH = BASE_DIR / "models" / "suggestion_ranker_demo.joblib"
MIN_ROWS = 100
MIN_ROWS_PER_LABEL = 20
MIN_BALANCED_ACCURACY = 0.55
MIN_GAIN_OVER_BASELINE = 0.05
CATEGORICAL_FEATURES = ["dimension_no", "dimension_name"]
FEATURE_NAMES = [
    "dimension_no",
    "dimension_name",
    "dimension_score",
    "title_word_count",
    "body_word_count",
    "action_count",
    "bullet_count",
    "numeric_target_count",
    "responsibility_term_count",
    "resource_term_count",
]


def suggestion_features(suggestion: dict[str, Any], dimension: dict[str, Any]) -> dict[str, Any]:
    title = str(suggestion.get("title", ""))
    body = str(suggestion.get("text", suggestion.get("body_text", "")))
    body_words = re.findall(r"\b[\w'-]+\b", body, flags=re.UNICODE)
    actions = re.findall(r"(?m)^\s*[-*]\s+\S", body)
    dimension_no = suggestion.get("dimension_no", dimension.get("dimension_no", 0))
    dimension_name = suggestion.get("dimension_name", dimension.get("dimension_name", ""))
    score = dimension.get(
        "score",
        dimension.get("dimension_score", dimension.get("percentage")),
    )
    try:
        numeric_score = float(score) if score is not None else np.nan
    except (TypeError, ValueError):
        numeric_score = np.nan

    lowered = body.casefold()
    return {
        "dimension_no": str(dimension_no),
        "dimension_name": str(dimension_name),
        "dimension_score": numeric_score,
        "title_word_count": len(re.findall(r"\b[\w'-]+\b", title, flags=re.UNICODE)),
        "body_word_count": len(body_words),
        "action_count": int(suggestion.get("action_count", len(actions))),
        "bullet_count": len(actions),
        "numeric_target_count": len(re.findall(r"\b\d+(?:\.\d+)?\s*%?", body)),
        "responsibility_term_count": sum(
            lowered.count(term)
            for term in ("responsible", "assign", "team", "coordinate", "principal", "teacher")
        ),
        "resource_term_count": sum(
            lowered.count(term)
            for term in ("budget", "fund", "resource", "material", "partner")
        ),
    }


def _frame(rows: list[dict[str, Any]]) -> pd.DataFrame:
    frame = pd.DataFrame(rows, columns=FEATURE_NAMES)
    for name in CATEGORICAL_FEATURES:
        frame[name] = frame[name].fillna("").astype(str)
    for name in set(FEATURE_NAMES) - set(CATEGORICAL_FEATURES):
        frame[name] = pd.to_numeric(frame[name], errors="coerce")
    return frame


def _records_from_bridge(include_demo: bool = False) -> list[dict[str, Any]]:
    command = ["php", str(DATA_BRIDGE)]
    if include_demo:
        command.append("--include-demo")
    result = subprocess.run(
        command,
        cwd=BASE_DIR.parent,
        check=True,
        capture_output=True,
        text=True,
    )
    payload = json.loads(result.stdout)
    rows = payload.get("rows")
    if not isinstance(rows, list):
        raise ValueError("Suggestion ranker data bridge returned no rows array.")
    return [
        row for row in rows
        if isinstance(row, dict)
        and row.get("label") in (0, 1)
        and row.get("workflow_status") in ("finalized", "returned")
        and row.get("school_id") is not None
        and row.get("cycle_id") is not None
    ]


def _new_model():
    from catboost import CatBoostClassifier

    return CatBoostClassifier(
        iterations=300,
        depth=4,
        learning_rate=0.03,
        loss_function="Logloss",
        eval_metric="BalancedAccuracy",
        auto_class_weights="Balanced",
        l2_leaf_reg=5.0,
        random_seed=42,
        thread_count=1,
        verbose=False,
        allow_writing_files=False,
    )


def _cycle_group_splits(labels: np.ndarray, cycle_ids: np.ndarray) -> list[tuple[np.ndarray, np.ndarray]]:
    cycle_count = len(np.unique(cycle_ids))
    if cycle_count < 2:
        raise ValueError("At least 2 assessment cycles are required for cycle-grouped validation.")

    splitter = GroupKFold(n_splits=min(5, cycle_count))
    splits = list(splitter.split(np.zeros(len(labels)), labels, groups=cycle_ids))
    for train_idx, test_idx in splits:
        if len(np.unique(labels[train_idx])) != 2:
            raise ValueError("A cycle-grouped training fold contains only one outcome class.")
        if len(np.unique(labels[test_idx])) != 2:
            raise ValueError("A cycle-grouped test fold contains only one outcome class.")
    return splits


def _score_folds(frame: pd.DataFrame, labels: np.ndarray, cycle_ids: np.ndarray) -> dict[str, Any]:
    splits = _cycle_group_splits(labels, cycle_ids)
    model_scores = []
    baseline_scores = []
    for train_idx, test_idx in splits:
        model = _new_model()
        model.fit(
            frame.iloc[train_idx],
            labels[train_idx],
            cat_features=CATEGORICAL_FEATURES,
        )
        model_prediction = model.predict(frame.iloc[test_idx]).reshape(-1).astype(int)
        baseline = DummyClassifier(strategy="most_frequent")
        baseline.fit(frame.iloc[train_idx], labels[train_idx])
        baseline_prediction = baseline.predict(frame.iloc[test_idx])
        model_scores.append(float(balanced_accuracy_score(labels[test_idx], model_prediction)))
        baseline_scores.append(float(balanced_accuracy_score(labels[test_idx], baseline_prediction)))
    return {
        "fold_count": len(splits),
        "grouping": "assessment_cycle",
        "catboost_balanced_accuracy": round(float(np.mean(model_scores)), 4),
        "majority_baseline_balanced_accuracy": round(float(np.mean(baseline_scores)), 4),
        "fold_scores": [round(score, 4) for score in model_scores],
    }


def _score_saved_model(
    bundle: dict[str, Any],
    frame: pd.DataFrame,
    labels: np.ndarray,
    cycle_ids: np.ndarray,
) -> float:
    scores = []
    for _, test_idx in _cycle_group_splits(labels, cycle_ids):
        prediction = bundle["model"].predict(frame.iloc[test_idx]).reshape(-1).astype(int)
        scores.append(float(balanced_accuracy_score(labels[test_idx], prediction)))
    return float(np.mean(scores))


def _not_promoted(
    reason: str,
    data_info: dict[str, Any],
    validation: dict[str, Any] | None = None,
    model_path: Path = MODEL_PATH,
):
    result = {
        "replaced": False,
        "model_source": "catboost" if model_path.is_file() else "rule_fallback",
        "active_model_retained": model_path.is_file(),
        "model_file": model_path.name if model_path.is_file() else None,
        **data_info,
        "reason": reason,
    }
    if validation is not None:
        result["validation"] = validation
    return result


def retrain(include_demo: bool = False) -> dict[str, Any]:
    model_path = DEMO_MODEL_PATH if include_demo else MODEL_PATH
    records = _records_from_bridge(include_demo=include_demo)
    # An item can be linked to several plans. The PHP bridge exports its latest
    # workflow decision; this guard also protects custom bridges and test data.
    latest_by_item: dict[int, dict[str, Any]] = {}
    for record in records:
        item_id = int(record.get("item_id", 0))
        previous = latest_by_item.get(item_id)
        if previous is None or str(record.get("outcome_updated_at", "")) > str(
            previous.get("outcome_updated_at", "")
        ):
            latest_by_item[item_id] = record
    records = list(latest_by_item.values())
    labels = np.asarray([int(row["label"]) for row in records], dtype=int)
    school_ids = np.asarray([int(row["school_id"]) for row in records], dtype=int)
    cycle_ids = np.asarray([int(row["cycle_id"]) for row in records], dtype=int)
    counts = np.bincount(labels, minlength=2) if len(labels) else np.zeros(2, dtype=int)
    data_info = {
        "row_count": int(len(records)),
        "positive_count": int(counts[1]),
        "negative_count": int(counts[0]),
        "school_count": int(len(np.unique(school_ids))),
        "cycle_count": int(len(np.unique(cycle_ids))),
        "demo_data": include_demo,
    }
    requirements = (
        (len(records) >= MIN_ROWS, f"Need at least {MIN_ROWS} labeled suggestions."),
        (int(counts[0]) >= MIN_ROWS_PER_LABEL, f"Need at least {MIN_ROWS_PER_LABEL} returned-plan labels."),
        (int(counts[1]) >= MIN_ROWS_PER_LABEL, f"Need at least {MIN_ROWS_PER_LABEL} finalized-plan labels."),
    )
    unmet = [message for satisfied, message in requirements if not satisfied]
    if unmet:
        return _not_promoted(" ".join(unmet), data_info, model_path=model_path)

    feature_rows = [
        {
            "dimension_no": row["dimension_no"],
            "dimension_name": row["dimension_name"],
            "dimension_score": row.get("dimension_score"),
            "title": row["title"],
            "body_text": row["body_text"],
        }
        for row in records
    ]
    frame = _frame([
        suggestion_features(row, row)
        for row in feature_rows
    ])
    try:
        validation = _score_folds(frame, labels, cycle_ids)
    except ValueError as exc:
        return _not_promoted(
            f"Cycle-grouped validation could not be completed: {exc}",
            data_info,
            model_path=model_path,
        )
    gain = (
        validation["catboost_balanced_accuracy"]
        - validation["majority_baseline_balanced_accuracy"]
    )
    if (
        validation["catboost_balanced_accuracy"] < MIN_BALANCED_ACCURACY
        or gain < MIN_GAIN_OVER_BASELINE
    ):
        return _not_promoted(
            (
                "CatBoost did not meet the minimum cycle-grouped balanced "
                "accuracy and gain over the majority baseline; no model was activated."
            ),
            data_info,
            validation,
            model_path,
        )

    if model_path.is_file():
        try:
            previous_bundle = joblib.load(model_path)
            previous_score = _score_saved_model(
                previous_bundle,
                frame,
                labels,
                cycle_ids,
            )
            if validation["catboost_balanced_accuracy"] <= previous_score + 0.01:
                validation["saved_model_balanced_accuracy"] = round(previous_score, 4)
                return _not_promoted(
                    "The candidate did not beat the saved model by at least 0.01 "
                    "balanced-accuracy points on the same cycle-grouped folds.",
                    data_info,
                    validation,
                    model_path,
                )
        except (CatBoostError, KeyError, OSError, ValueError) as exc:
            logger.exception("Could not evaluate the saved suggestion ranker.")
            logger.warning("Replacing the unreadable saved ranker after candidate validation: %s", exc)

    model = _new_model()
    model.fit(frame, labels, cat_features=CATEGORICAL_FEATURES)
    metadata = {
        **data_info,
        "training_date": datetime.now(timezone.utc).isoformat(),
        "validation": validation,
        "minimum_row_count": MIN_ROWS,
        "validation_grouping": "assessment_cycle",
        "label_definition": "finalized=1; currently returned=0",
        "feature_names": FEATURE_NAMES,
        "model_source": "catboost",
        "demo_data": include_demo,
    }
    model_path.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump({"model": model, "metadata": metadata}, model_path)
    return {"replaced": True, "model_file": model_path.name, **metadata}


def rank_suggestions(
    suggestions: list[dict[str, Any]],
    dimensions: list[dict[str, Any]],
) -> tuple[list[dict[str, Any]], dict[str, Any]]:
    use_demo_model = os.getenv("SUGGESTION_RANKER_USE_DEMO") == "1"
    model_path = DEMO_MODEL_PATH if use_demo_model else MODEL_PATH
    if not model_path.is_file():
        return suggestions, {
            "model_source": "rule_fallback",
            "active": False,
            "demo_data": False,
            "reason": (
                "No demo CatBoost ranker is saved."
                if use_demo_model
                else "No validated CatBoost suggestion ranker is saved."
            ),
        }
    try:
        bundle = joblib.load(model_path)
        model = bundle["model"]
        metadata = bundle["metadata"]
        dimension_by_id = {
            int(row["dimension_id"]): row
            for row in dimensions
            if isinstance(row, dict) and str(row.get("dimension_id", "")).isdigit()
        }
        frame = _frame([
            suggestion_features(
                suggestion,
                dimension_by_id.get(int(suggestion["dimension_id"]), suggestion),
            )
            for suggestion in suggestions
        ])
        positive_class = list(model.classes_).index(1)
        scored = []
        for suggestion, probability in zip(suggestions, model.predict_proba(frame)[:, positive_class]):
            item = dict(suggestion)
            item["approval_score"] = round(float(probability), 4)
            scored.append(item)
        scored.sort(key=lambda item: float(item["approval_score"]), reverse=True)
        return scored, {
            "model_source": "catboost",
            "active": True,
            "model_file": model_path.name,
            "label_definition": metadata.get("label_definition"),
            "training_row_count": metadata.get("row_count"),
            "school_count": metadata.get("school_count"),
            "demo_data": bool(metadata.get("demo_data", False)),
            "validation": metadata.get("validation"),
            "score_description": (
                (
                    "DEMO MODEL: trained with synthetic seed examples; for testing only, "
                    "not reliable for production suggestions."
                    if metadata.get("demo_data", False)
                    else "Estimated likelihood of reaching finalized status based on past "
                    "finalized versus currently returned plans; not a calibrated probability."
                )
            ),
        }
    except (CatBoostError, KeyError, OSError, ValueError, TypeError) as exc:
        logger.exception("Could not score suggestions with the saved CatBoost ranker.")
        return suggestions, {
            "model_source": "rule_fallback",
            "active": False,
            "demo_data": use_demo_model,
            "reason": f"Saved suggestion ranker could not be used: {exc}",
        }


def remove_demo_model() -> dict[str, Any]:
    if not DEMO_MODEL_PATH.is_file():
        return {"removed": False, "reason": "No saved suggestion ranker exists.", "demo_data": False}
    bundle = joblib.load(DEMO_MODEL_PATH)
    metadata = bundle.get("metadata", {})
    if metadata.get("demo_data") is not True:
        return {
            "removed": False,
            "reason": "The saved ranker is not marked as demo-trained; it was kept.",
            "demo_data": False,
        }
    DEMO_MODEL_PATH.unlink()
    return {"removed": True, "reason": "Removed the demo-trained CatBoost ranker.", "demo_data": True}


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--retrain",
        action="store_true",
        help="Evaluate and train from eligible linked plan outcomes.",
    )
    parser.add_argument(
        "--include-demo",
        action="store_true",
        help="Explicitly include tagged synthetic demo outcomes for testing only.",
    )
    parser.add_argument(
        "--remove-demo-model",
        action="store_true",
        help="Remove the saved ranker only when its metadata marks it demo-trained.",
    )
    args = parser.parse_args()
    logging.basicConfig(level=logging.INFO)
    if args.remove_demo_model:
        if args.retrain or args.include_demo:
            parser.error("--remove-demo-model cannot be combined with retraining options.")
        print(json.dumps(remove_demo_model(), indent=2))
        return 0
    if not args.retrain:
        parser.error("Choose --retrain.")
    print(json.dumps(retrain(include_demo=args.include_demo), indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
