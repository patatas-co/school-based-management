"""SBM dimension-improvement model and compatibility classifiers.

The improvement model is trained from the MySQL history bridge.  Its target is
the next finalized cycle's dimension score change, while every feature comes
from the current or earlier cycles only.  The legacy maturity/priority
signatures remain available to score_analyzer.py and use the synthetic
fallback when no real model has been trained yet.
"""
from __future__ import annotations

import argparse
import json
import logging
import os
import subprocess
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import joblib
import numpy as np
from dotenv import load_dotenv
from sklearn.ensemble import RandomForestClassifier
from sklearn.impute import SimpleImputer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, f1_score, recall_score
from sklearn.model_selection import TimeSeriesSplit
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.tree import DecisionTreeClassifier

logger = logging.getLogger(__name__)
BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / ".env", override=True)
load_dotenv(BASE_DIR.parent / ".env", override=True)
MODEL_PATH = BASE_DIR / "models" / "dimension_improvement.joblib"
DATA_BRIDGE = BASE_DIR / "dimension_history_data.php"
MIN_ROWS = 60
DEFAULT_THRESHOLD = 1.0
DATA_SOURCE = os.getenv("ML_DATA_SOURCE", "real")
FEATURE_NAMES = [
    "dimension_score",
    "previous_dimension_score",
    "trend_vs_previous",
    "score_vs_own_history",
    "teacher_school_head_gap",
    "gap_missing",
    "indicator_mean_rating",
    "indicator_min_rating",
    "indicator_low_rating_share",
    "dimension_no",
]


def _synthetic_maturity_training_data():
    rng = np.random.default_rng(42)
    X, y = [], []

    def add(n, score_rng, slope_rng, weak_rng, label):
        for _ in range(n):
            X.append([
                rng.uniform(*score_rng),
                rng.uniform(*slope_rng),
                rng.uniform(*weak_rng),
            ])
            y.append(label)

    add(300, (0, 62.5), (-8, 6), (0.40, 1.00), "Developing")
    add(50, (0, 30), (-10, -2), (0.70, 1.00), "Developing")
    add(50, (50, 62.5), (4, 10), (0.20, 0.50), "Developing")
    add(300, (62.5, 87.5), (-4, 8), (0.10, 0.45), "Maturing")
    add(50, (62.5, 70), (-5, 0), (0.30, 0.60), "Maturing")
    add(50, (80, 87.5), (5, 12), (0.05, 0.20), "Maturing")
    add(300, (87.5, 100), (0, 10), (0.00, 0.20), "Advanced")
    add(50, (87.5, 92), (-4, 0), (0.10, 0.30), "Advanced")
    add(50, (93, 100), (5, 15), (0.00, 0.05), "Advanced")
    add(60, (87.5, 92), (-10, -4), (0.35, 0.60), "Maturing")
    add(40, (62.5, 68), (-2, 2), (0.50, 0.75), "Developing")
    return np.asarray(X, dtype=float), np.asarray(y)


def _synthetic_priority_training_data():
    rng = np.random.default_rng(42)
    X, y = [], []

    def add(n, score_rng, gap_rng, weight_rng, weak_rng, slope_rng, label):
        for _ in range(n):
            X.append([
                rng.uniform(*score_rng),
                rng.uniform(*gap_rng),
                rng.uniform(*weight_rng),
                int(rng.integers(*weak_rng)),
                rng.uniform(*slope_rng),
            ])
            y.append(label)

    add(250, (0, 45), (15, 45), (1.0, 1.2), (7, 20), (-8, 0), "high")
    add(60, (46, 58), (12, 28), (1.2, 1.2), (5, 15), (-5, 2), "high")
    add(40, (0, 38), (10, 22), (0.9, 1.0), (10, 20), (-6, 0), "high")
    add(30, (55, 75), (8, 20), (1.0, 1.2), (5, 12), (-8, -3), "high")
    add(250, (40, 70), (5, 18), (0.9, 1.2), (2, 8), (-3, 5), "medium")
    add(60, (55, 72), (3, 12), (1.2, 1.2), (3, 8), (-2, 4), "medium")
    add(40, (30, 52), (5, 15), (0.9, 1.0), (3, 7), (0, 6), "medium")
    add(30, (62, 78), (2, 8), (0.9, 1.2), (1, 5), (-4, -1), "medium")
    add(250, (65, 100), (-12, 5), (0.9, 1.2), (0, 3), (0, 10), "low")
    add(60, (55, 78), (-5, 3), (0.9, 1.0), (0, 2), (3, 10), "low")
    add(40, (72, 100), (-15, 0), (0.9, 1.2), (0, 1), (5, 12), "low")
    add(30, (60, 100), (-20, -5), (0.9, 1.2), (0, 4), (-2, 10), "low")
    return np.asarray(X, dtype=float), np.asarray(y)


def _records_from_bridge() -> list[dict[str, Any]]:
    result = subprocess.run(
        ["php", str(DATA_BRIDGE)],
        cwd=BASE_DIR.parent,
        check=True,
        capture_output=True,
        text=True,
    )
    payload = json.loads(result.stdout)
    return [
        row for row in payload.get("rows", [])
        if row.get("target_improved") is not None
    ]


def _indicator_features(row: dict[str, Any]) -> tuple[float, float, float]:
    ratings = [
        float(item["school_head_rating"])
        for item in row.get("indicator_ratings", [])
        if item.get("school_head_rating") is not None
    ]
    if not ratings:
        return np.nan, np.nan, np.nan
    return (
        float(np.mean(ratings)),
        float(np.min(ratings)),
        float(np.mean(np.asarray(ratings) < 2.5)),
    )


def row_to_features(row: dict[str, Any]) -> list[float]:
    indicator_mean, indicator_min, low_share = _indicator_features(row)
    gap_missing = row.get("teacher_school_head_gap") is None
    def numeric(value: Any) -> float:
        return np.nan if value is None else float(value)

    return [
        numeric(row.get("dimension_score")),
        numeric(row.get("previous_dimension_score")),
        numeric(row.get("trend_vs_previous")),
        numeric(row.get("score_vs_own_history")),
        numeric(row.get("teacher_school_head_gap")),
        float(gap_missing),
        indicator_mean,
        indicator_min,
        low_share,
        float(row.get("dimension_no", np.nan)),
    ]


def _outlook_feature_rows(rows: list[dict[str, Any]]) -> list[dict[str, Any]]:
    history: dict[int, list[float]] = {}
    prepared = []
    for row in sorted(
        rows,
        key=lambda item: (
            int(item.get("school_year_order", item.get("school_year", 0)) or 0),
            int(item.get("dimension_no", 0)),
        ),
    ):
        item = dict(row)
        dimension_no = int(item.get("dimension_no", 0))
        score = item.get("dimension_score", item.get("score"))
        if score is None:
            continue
        score = float(score)
        prior = history.setdefault(dimension_no, [])
        item["dimension_score"] = score
        item["score_vs_own_history"] = (
            score - float(np.mean(prior)) if prior else np.nan
        )
        prior.append(score)
        prepared.append(item)

    latest_by_dimension = {}
    for item in prepared:
        latest_by_dimension[int(item["dimension_no"])] = item
    return [
        latest_by_dimension[dimension_no]
        for dimension_no in sorted(latest_by_dimension)
    ]


def _rule_priority(score: float, history_gap: float | None) -> str:
    if score < 37.5 or (history_gap is not None and history_gap <= -15):
        return "high"
    if score < 62.5 or (history_gap is not None and history_gap <= -5):
        return "medium"
    return "low"


def _rule_decline_risk(history_gap: float | None) -> str:
    if history_gap is None:
        return "medium"
    return "high" if history_gap > 0 else "low"


def _top_factors(bundle: dict[str, Any]) -> list[str]:
    importances = bundle.get("metadata", {}).get("feature_importances", {})
    return [
        name for name, _ in sorted(
            importances.items(), key=lambda item: abs(float(item[1])), reverse=True
        )[:3]
    ]


def predict_dimension_outlook(rows: list[dict[str, Any]]) -> list[dict[str, Any]]:
    """Predict current dimension outlook without using any next-cycle fields."""
    prepared = _outlook_feature_rows(rows)
    bundle = None
    if MODEL_PATH.is_file():
        try:
            bundle = joblib.load(MODEL_PATH)
        except Exception as exc:
            logger.warning("Could not load dimension improvement model: %s", exc)

    outlook = []
    for row in prepared:
        score = float(row["dimension_score"])
        history_gap = row.get("score_vs_own_history")
        if np.isnan(history_gap):
            history_gap = None
        if bundle is not None:
            values = bundle["imputer"].transform(
                np.asarray([row_to_features(row)], dtype=float)
            )
            probabilities = bundle["model"].predict_proba(values)[0]
            classes = bundle["model"].classes_
            decline_index = list(classes).index(0) if 0 in classes else 0
            decline_risk = float(probabilities[decline_index])
            confidence = float(np.max(probabilities))
            predicted_improved = bool(classes[int(np.argmax(probabilities))] == 1)
            item = {
                "priority": "high" if not predicted_improved else "medium",
                "decline_risk": (
                    "high" if decline_risk >= 0.67
                    else ("medium" if decline_risk >= 0.34 else "low")
                ),
                "confidence": round(confidence, 3),
                "model_source": "trained_model",
                "top_factors": _top_factors(bundle),
            }
        else:
            item = {
                "priority": _rule_priority(score, history_gap),
                "decline_risk": _rule_decline_risk(history_gap),
                "confidence": 0.55,
                "model_source": "rule_fallback",
                "top_factors": (
                    ["score_vs_own_history"] if history_gap is not None
                    else ["dimension_score"]
                ),
            }
        outlook.append({
            "dimension_no": int(row.get("dimension_no", 0)),
            "dimension_name": row.get("dimension_name", ""),
            **item,
        })
    return outlook


def dimension_model_metadata() -> dict[str, Any]:
    if not MODEL_PATH.is_file():
        return {
            "model_source": "rule_fallback",
            "training_date": None,
            "row_count": 0,
            "data_source": DATA_SOURCE,
        }
    try:
        metadata = joblib.load(MODEL_PATH).get("metadata", {})
        return {
            "model_source": "trained_model",
            "training_date": metadata.get("training_date"),
            "row_count": int(metadata.get("row_count", 0)),
            "data_source": metadata.get("data_source", DATA_SOURCE),
        }
    except Exception as exc:
        logger.warning("Could not load dimension model metadata: %s", exc)
        return {
            "model_source": "rule_fallback",
            "training_date": None,
            "row_count": 0,
            "data_source": DATA_SOURCE,
        }


def _prepare_data(
    rows: list[dict[str, Any]],
    threshold: float,
) -> tuple[np.ndarray, np.ndarray, np.ndarray, list[str]]:
    usable = [
        row for row in rows
        if row.get("target_improved") is not None
    ]
    history: dict[int, list[float]] = {}
    ordered_rows = sorted(
        rows,
        key=lambda row: (
            int(str(row["school_year_label"])[:4]),
            int(row.get("dimension_no", 0)),
        ),
    )
    for row in ordered_rows:
        dimension_no = int(row["dimension_no"])
        prior_scores = history.setdefault(dimension_no, [])
        current_score = float(row["dimension_score"])
        row["score_vs_own_history"] = (
            current_score - float(np.mean(prior_scores))
            if prior_scores else np.nan
        )
        prior_scores.append(current_score)
    X = np.asarray([row_to_features(row) for row in usable], dtype=float)
    deltas = np.asarray([
        float(row["next_dimension_score"]) - float(row["dimension_score"])
        for row in usable
    ])
    y = (deltas >= threshold).astype(int)
    years = np.asarray([
        int(str(row["school_year_label"])[:4]) for row in usable
    ])
    return X, y, years, [str(row["school_year_label"]) for row in usable]


def _folds_by_year(years: np.ndarray, min_folds: int = 5):
    unique_years = np.array(sorted(set(int(year) for year in years)))
    if len(unique_years) < min_folds + 1:
        raise ValueError(
            f"At least {min_folds + 1} school years are required for expanding validation."
        )
    splitter = TimeSeriesSplit(n_splits=min_folds)
    positions = np.arange(len(unique_years))
    for train_year_positions, test_year_positions in splitter.split(positions):
        train_years = set(unique_years[train_year_positions])
        test_years = set(unique_years[test_year_positions])
        train_indices = np.flatnonzero(np.isin(years, list(train_years)))
        test_indices = np.flatnonzero(np.isin(years, list(test_years)))
        if len(set(years[test_indices])) != len(test_years):
            raise RuntimeError("A validation fold split a school year.")
        yield train_indices, test_indices, sorted(train_years), sorted(test_years)


def _metrics(y_true: np.ndarray, predictions: np.ndarray) -> dict[str, float]:
    return {
        "accuracy": float(accuracy_score(y_true, predictions)),
        "balanced_accuracy": float(recall_score(
            y_true,
            predictions,
            labels=[0, 1],
            average="macro",
            zero_division=0,
        )),
        "f1": float(f1_score(y_true, predictions, zero_division=0)),
    }


def _majority_prediction(y_train: np.ndarray, count: int) -> np.ndarray:
    majority = int(np.bincount(y_train, minlength=2).argmax())
    return np.full(count, majority, dtype=int)


def _rule_prediction(X_train: np.ndarray, X_test: np.ndarray, y_train: np.ndarray) -> np.ndarray:
    median_score = float(np.nanmedian(X_train[:, 0]))
    return (X_test[:, 0] < median_score).astype(int)


def _fit_estimator(kind: str):
    if kind == "decision_tree":
        return DecisionTreeClassifier(
            max_depth=4,
            min_samples_leaf=5,
            class_weight="balanced",
            random_state=42,
        )
    if kind == "random_forest":
        return RandomForestClassifier(
            n_estimators=200,
            max_depth=5,
            min_samples_leaf=5,
            class_weight="balanced",
            random_state=42,
            n_jobs=1,
        )
    if kind == "logistic_regression":
        return make_pipeline(
            StandardScaler(),
            LogisticRegression(class_weight="balanced", C=1.0, random_state=42),
        )
    raise ValueError(f"Unknown estimator kind: {kind}")


def _evaluate_kind(X: np.ndarray, y: np.ndarray, years: np.ndarray, kind: str):
    fold_results = []
    for fold_no, (train, test, train_years, test_years) in enumerate(
        _folds_by_year(years), start=1
    ):
        imputer = SimpleImputer(strategy="median", add_indicator=False)
        X_train = imputer.fit_transform(X[train])
        X_test = imputer.transform(X[test])
        if kind == "baseline":
            predictions = _majority_prediction(y[train], len(test))
        elif kind == "simple_rule":
            predictions = _rule_prediction(X_train, X_test, y[train])
        elif kind == "own_history_rule":
            predictions = (X_test[:, 3] < 0).astype(int)
        else:
            estimator = _fit_estimator(kind)
            estimator.fit(X_train, y[train])
            predictions = estimator.predict(X_test)
        fold_results.append({
            "fold": fold_no,
            "train_years": [int(year) for year in train_years],
            "test_years": [int(year) for year in test_years],
            "row_count": int(len(test)),
            **_metrics(y[test], predictions),
        })
    return fold_results


def _summary(folds: list[dict[str, Any]]) -> dict[str, Any]:
    return {
        "folds": folds,
        "mean": {
            metric: float(np.mean([fold[metric] for fold in folds]))
            for metric in ("accuracy", "balanced_accuracy", "f1")
        },
        "std": {
            metric: float(np.std([fold[metric] for fold in folds]))
            for metric in ("accuracy", "balanced_accuracy", "f1")
        },
    }


def evaluate_history(threshold: float = DEFAULT_THRESHOLD) -> dict[str, Any]:
    rows = _records_from_bridge()
    X, y, years, _ = _prepare_data(rows, threshold)
    if len(X) < MIN_ROWS:
        raise ValueError(
            f"Only {len(X)} labeled rows are available; MIN_ROWS={MIN_ROWS}."
        )
    class_counts = {
        "declined_or_flat": int(np.sum(y == 0)),
        "improved": int(np.sum(y == 1)),
    }
    comparisons = {}
    for kind in (
        "baseline",
        "simple_rule",
        "own_history_rule",
        "decision_tree",
        "random_forest",
        "logistic_regression",
    ):
        comparisons[kind] = _summary(_evaluate_kind(X, y, years, kind))
    rule_names = ("simple_rule", "own_history_rule")
    rule_score = max(
        comparisons[name]["mean"]["balanced_accuracy"] for name in rule_names
    )
    baseline_score = comparisons["baseline"]["mean"]["balanced_accuracy"]
    best_rule_by_fold = [
        max(comparisons[name]["folds"][fold]["balanced_accuracy"] for name in rule_names)
        for fold in range(len(comparisons["baseline"]["folds"]))
    ]
    paired_wins = {}
    for name, result in comparisons.items():
        paired_wins[name] = sum(
            fold["balanced_accuracy"] > best_rule
            for fold, best_rule in zip(result["folds"], best_rule_by_fold)
        )
    candidate_names = ("decision_tree", "random_forest", "logistic_regression")
    selected_candidates = [
        name for name in candidate_names
        if (
            comparisons[name]["mean"]["balanced_accuracy"] > baseline_score
            and all(
                comparisons[name]["mean"]["balanced_accuracy"]
                > comparisons[rule]["mean"]["balanced_accuracy"]
                for rule in rule_names
            )
            and paired_wins[name] > len(best_rule_by_fold) / 2
        )
    ]
    selected = max(
        selected_candidates,
        key=lambda name: comparisons[name]["mean"]["balanced_accuracy"],
        default=None,
    )
    return {
        "training_date": datetime.now(timezone.utc).isoformat(),
        "row_count": int(len(X)),
        "label_threshold": float(threshold),
        "class_balance": class_counts,
        "feature_names": FEATURE_NAMES,
        "comparisons": comparisons,
        "best_rule_mean_balanced_accuracy": float(rule_score),
        "paired_wins_over_best_rule": paired_wins,
        "selected_model": selected,
        "minimum_row_count": MIN_ROWS,
        "data_source": DATA_SOURCE,
    }


def _fit_final_model(X: np.ndarray, y: np.ndarray, kind: str):
    imputer = SimpleImputer(strategy="median")
    transformed = imputer.fit_transform(X)
    estimator = _fit_estimator(kind)
    estimator.fit(transformed, y)
    return {"imputer": imputer, "model": estimator}


def _feature_importances(estimator: Any) -> dict[str, float]:
    if hasattr(estimator, "feature_importances_"):
        values = estimator.feature_importances_
    else:
        values = np.abs(estimator[-1].coef_[0])
    return dict(zip(FEATURE_NAMES, (float(value) for value in values)))


def _saved_score_on_current_folds(bundle: dict[str, Any], X, y, years):
    folds = []
    for fold_no, (train, test, train_years, test_years) in enumerate(
        _folds_by_year(years), start=1
    ):
        predictions = bundle["model"].predict(bundle["imputer"].transform(X[test]))
        folds.append({
            "fold": fold_no,
            "train_years": [int(year) for year in train_years],
            "test_years": [int(year) for year in test_years],
            "row_count": int(len(test)),
            **_metrics(y[test], predictions),
        })
    return _summary(folds)


def retrain(threshold: float = DEFAULT_THRESHOLD) -> dict[str, Any]:
    rows = _records_from_bridge()
    X, y, years, _ = _prepare_data(rows, threshold)
    if len(X) < MIN_ROWS:
        return {
            "replaced": False,
            "data_source": "seeded",
            "reason": f"Real labeled rows {len(X)} < MIN_ROWS {MIN_ROWS}; synthetic fallback retained.",
            "minimum_row_count": MIN_ROWS,
        }
    report = evaluate_history(threshold)
    selected = report["selected_model"]
    if selected is None:
        return {
            "replaced": False,
            **report,
            "reason": "No model beat the majority baseline and both rules on mean balanced accuracy while winning more than half of the folds.",
        }
    new_score = report["comparisons"][selected]["mean"]["balanced_accuracy"]
    if MODEL_PATH.is_file():
        old = joblib.load(MODEL_PATH)
        old_summary = _saved_score_on_current_folds(old, X, y, years)
        old_score = old_summary["mean"]["balanced_accuracy"]
        if new_score <= old_score:
            return {
                "replaced": False,
                **report,
                "saved_model_score": old_summary,
                "reason": "New model did not meet the saved model's mean balanced accuracy on the same folds.",
            }
    final = _fit_final_model(X, y, selected)
    metadata = {
        "training_date": report["training_date"],
        "row_count": int(len(X)),
        "label_threshold": float(threshold),
        "fold_scores": report["comparisons"][selected],
        "baseline_scores": {
            "majority": report["comparisons"]["baseline"],
            "simple_rule": report["comparisons"]["simple_rule"],
        },
        "feature_names": FEATURE_NAMES,
        "feature_importances": _feature_importances(final["model"]),
        "data_source": DATA_SOURCE,
        "minimum_row_count": MIN_ROWS,
        "selected_model": selected,
    }
    MODEL_PATH.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump({**final, "metadata": metadata}, MODEL_PATH)
    return {"replaced": True, **metadata}


class SBMDecisionTreeClassifier:
    """Compatibility facade for score_analyzer.py plus real improvement model."""

    MATURITY_ORDER = ["Developing", "Maturing", "Advanced"]

    def __init__(self):
        self.maturity_model = DecisionTreeClassifier(
            max_depth=7, min_samples_leaf=5, criterion="gini",
            class_weight="balanced", random_state=42,
        )
        self.priority_model = DecisionTreeClassifier(
            max_depth=8, min_samples_leaf=3, criterion="gini",
            class_weight="balanced", random_state=42,
        )
        X_m, y_m = _synthetic_maturity_training_data()
        X_p, y_p = _synthetic_priority_training_data()
        self.maturity_model.fit(X_m, y_m)
        self.priority_model.fit(X_p, y_p)
        self.improvement_bundle = None
        if MODEL_PATH.is_file():
            try:
                self.improvement_bundle = joblib.load(MODEL_PATH)
            except Exception as exc:
                logger.warning("Could not load saved improvement model: %s", exc)

    def predict_maturity(self, score: float, slope: float = 0.0, weak_ratio: float = 0.0) -> str:
        return str(self.maturity_model.predict([[float(score), float(slope), float(weak_ratio)]])[0])

    def predict_maturity_with_confidence(self, score: float, slope: float = 0.0, weak_ratio: float = 0.0) -> dict:
        values = self.maturity_model.predict_proba([[float(score), float(slope), float(weak_ratio)]])[0]
        classes = self.maturity_model.classes_
        index = int(np.argmax(values))
        return {
            "maturity": str(classes[index]),
            "confidence": round(float(values[index]), 3),
            "probabilities": {str(label): round(float(value), 3) for label, value in zip(classes, values)},
        }

    def predict_priority(self, score: float, weighted_gap: float, weight: float, weak_count: int = 0, slope: float = 0.0) -> str:
        return str(self.priority_model.predict([[
            float(score), float(weighted_gap), float(weight), int(weak_count), float(slope)
        ]])[0])

    def predict_improvement(self, features: dict[str, Any]) -> dict[str, Any]:
        if self.improvement_bundle is None:
            return {
                "available": False,
                "confidence": 0.35,
                "data_source": "seeded",
                "reason": "No real-history dimension model is saved.",
            }
        bundle = self.improvement_bundle
        values = np.asarray([[
            float(features.get(name, np.nan)) for name in FEATURE_NAMES
        ]])
        values = bundle["imputer"].transform(values)
        probabilities = bundle["model"].predict_proba(values)[0]
        classes = bundle["model"].classes_
        index = int(np.argmax(probabilities))
        confidence = float(probabilities[index])
        if bundle.get("metadata", {}).get("data_source") != "real":
            confidence *= 0.6
        return {
            "available": True,
            "predicted_improved": bool(classes[index] == 1),
            "decline_risk": float(probabilities[list(classes).index(0)]) if 0 in classes else 0.0,
            "priority": "high" if classes[index] == 0 else "monitor",
            "confidence": round(confidence, 3),
            "feature_importances": bundle.get("metadata", {}).get("feature_importances", {}),
            "data_source": bundle.get("metadata", {}).get("data_source", "seeded"),
        }


_instance: SBMDecisionTreeClassifier | None = None


def get_classifier() -> SBMDecisionTreeClassifier:
    global _instance
    if _instance is None:
        _instance = SBMDecisionTreeClassifier()
    return _instance


def _print_comparison(report: dict[str, Any]) -> None:
    print(json.dumps(report, indent=2))
    print("\ncomparison")
    print("model\tmean_accuracy\tmean_balanced_accuracy\tstd_balanced_accuracy\tmean_f1\tfolds_over_best_rule")
    for name, result in report["comparisons"].items():
        mean = result["mean"]
        print(
            f"{name}\t{mean['accuracy']:.4f}\t{mean['balanced_accuracy']:.4f}\t"
            f"{result['std']['balanced_accuracy']:.4f}\t{mean['f1']:.4f}\t"
            f"{report['paired_wins_over_best_rule'][name]}/"
            f"{len(result['folds'])}"
        )
    print("fold_scores_balanced_accuracy")
    for name, result in report["comparisons"].items():
        scores = ", ".join(f"{fold['balanced_accuracy']:.4f}" for fold in result["folds"])
        print(f"{name}\t{scores}")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--retrain", action="store_true")
    parser.add_argument("--evaluate", action="store_true")
    parser.add_argument("--threshold", type=float, default=DEFAULT_THRESHOLD)
    args = parser.parse_args()
    logging.basicConfig(level=logging.INFO)
    if args.retrain:
        print(json.dumps(retrain(args.threshold), indent=2))
        return 0
    if args.evaluate:
        for threshold in (0.0, args.threshold):
            report = evaluate_history(threshold)
            print(f"\nthreshold={threshold}")
            _print_comparison(report)
        return 0
    parser.error("Choose --retrain or --evaluate.")
    return 2


if __name__ == "__main__":
    raise SystemExit(main())
