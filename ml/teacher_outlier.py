"""Unsupervised teacher rating anomaly detector and synthetic-data evaluator.

The detector never uses seed_ground_truth, anomaly labels, or teacher names.
The production prior is a fixed 0.05 IsolationForest contamination value:
it is a conservative operational prior, not a value estimated from labels.
"""
from __future__ import annotations

import argparse
import json
import logging
import math
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import joblib
import numpy as np
from sklearn.ensemble import IsolationForest
from sklearn.metrics import average_precision_score, precision_recall_fscore_support, roc_auc_score
from sklearn.preprocessing import StandardScaler

LOGGER = logging.getLogger(__name__)
BASE_DIR = Path(__file__).resolve().parent
MODEL_DIR = BASE_DIR / "models"
MODEL_PATH = MODEL_DIR / "teacher_outlier.joblib"
DATA_BRIDGE = BASE_DIR / "teacher_outlier_data.php"
MODEL_VERSION = "teacher-outlier-v1"
DEFAULT_CONTAMINATION = 0.05
FEATURE_NAMES = [
    "mean_rating",
    "rating_std",
    "distinct_rating_count",
    "mode_share",
    "share_ones",
    "share_fours",
    "peer_mean_abs_difference",
    "school_head_mean_abs_difference",
    "school_head_correlation",
]


def _numbers(values: Any) -> list[float]:
    return [float(value) for value in (values or []) if value is not None]


def _safe_correlation(left: list[float], right: list[float]) -> float:
    if len(left) < 2 or len(set(left)) < 2 or len(set(right)) < 2:
        return 0.0
    corr = float(np.corrcoef(np.asarray(left), np.asarray(right))[0, 1])
    return 0.0 if not math.isfinite(corr) else corr


def anomaly_type(features: dict[str, float]) -> str:
    if features["share_ones"] == 1.0:
        return "all_low"
    if features["share_fours"] == 1.0:
        return "all_high"
    if features["share_ones"] >= 0.8:
        return "mostly_low"
    if features["share_fours"] >= 0.8:
        return "mostly_high"
    if features["distinct_rating_count"] == 1:
        return "constant"
    if features["rating_std"] > 0.9:
        return "erratic"
    return "unclassified"


def card_eligibility(
    row: dict[str, Any],
    detector_flagged: bool,
    require_detector: bool = False,
) -> tuple[bool, str]:
    """Shared production/evaluation card decision; type rules are deterministic."""
    kind = row["anomaly_type"]
    features = row["features"]
    if kind == "constant":
        eligible = (
            row.get("rating_count", 0) >= 10
            and features["rating_std"] == 0.0
            and row.get("constant_value") in (2.0, 3.0)
        )
        return bool(eligible), "constant_rule"
    if kind in {"all_low", "all_high", "mostly_low", "mostly_high", "erratic"}:
        return bool(
            (detector_flagged if require_detector else True)
            and features["peer_mean_abs_difference"] >= 1.0
        ), "peer_gap_rule"
    return False, "unclassified"


def build_features(records: list[dict[str, Any]]) -> tuple[np.ndarray, list[dict[str, Any]]]:
    """Build feature rows from neutral rating maps and peer/SH ratings."""
    output: list[dict[str, Any]] = []
    for record in records:
        ratings = {str(k): float(v) for k, v in (record.get("ratings") or {}).items()}
        values = list(ratings.values())
        if not values:
            continue
        counts = {value: values.count(value) for value in set(values)}
        mode_share = max(counts.values()) / len(values)
        peer_values = _numbers(record.get("peer_differences"))
        sh_ratings = {
            str(k): float(v)
            for k, v in (record.get("school_head_ratings") or {}).items()
            if str(k) in ratings
        }
        shared = sorted(sh_ratings)
        teacher_shared = [ratings[key] for key in shared]
        head_shared = [sh_ratings[key] for key in shared]
        features = {
            "mean_rating": float(np.mean(values)),
            "rating_std": float(np.std(values)),
            "distinct_rating_count": float(len(set(values))),
            "mode_share": float(mode_share),
            "share_ones": float(sum(value == 1 for value in values) / len(values)),
            "share_fours": float(sum(value == 4 for value in values) / len(values)),
            "peer_mean_abs_difference": float(np.mean(peer_values)) if peer_values else 0.0,
            "school_head_mean_abs_difference": (
                float(np.mean([abs(a - b) for a, b in zip(teacher_shared, head_shared)]))
                if shared else 0.0
            ),
            "school_head_correlation": _safe_correlation(teacher_shared, head_shared),
        }
        item = {
            "cycle_id": int(record["cycle_id"]),
            "cycle_year": str(record.get("cycle_year", "")),
            "teacher_id": int(record["teacher_id"]),
            "features": features,
            "anomaly_type": anomaly_type(features),
            "overall_score": record.get("overall_score"),
            "other_teachers_mean_rating": record.get("other_teachers_mean_rating"),
            "rating_count": len(values),
            "constant_value": values[0] if len(set(values)) == 1 else None,
        }
        output.append(item)
    matrix = np.asarray(
        [[row["features"][name] for name in FEATURE_NAMES] for row in output],
        dtype=float,
    )
    return matrix, output


def make_records(data: dict[str, Any]) -> list[dict[str, Any]]:
    teacher_rows = data.get("teacher_responses", [])
    head_rows = data.get("sbm_responses", [])
    head_by_cycle: dict[int, dict[str, float]] = {}
    for row in head_rows:
        head_by_cycle.setdefault(int(row["cycle_id"]), {})[str(row["indicator_id"])] = float(row["rating"])
    overall_by_cycle = {
        int(row["cycle_id"]): (
            float(row["overall_score"]) if row.get("overall_score") is not None else None
        )
        for row in data.get("cycles", [])
    }

    by_cycle_teacher: dict[tuple[int, int], dict[str, Any]] = {}
    for row in teacher_rows:
        key = (int(row["cycle_id"]), int(row["teacher_id"]))
        item = by_cycle_teacher.setdefault(key, {
            "cycle_id": key[0],
            "cycle_year": row.get("school_year_label", ""),
            "teacher_id": key[1],
            "ratings": {},
            "overall_score": overall_by_cycle.get(key[0]),
        })
        item["ratings"][str(row["indicator_id"])] = float(row["rating"])

    by_cycle: dict[int, list[dict[str, Any]]] = {}
    for item in by_cycle_teacher.values():
        by_cycle.setdefault(int(item["cycle_id"]), []).append(item)
    for cycle_id, teachers in by_cycle.items():
        for teacher in teachers:
            differences = []
            peer_means = []
            for peer in teachers:
                if peer is teacher:
                    continue
                shared = set(teacher["ratings"]) & set(peer["ratings"])
                peer_means.extend(peer["ratings"][key] for key in shared)
            shared_indicators = set(teacher["ratings"])
            for indicator_id in shared_indicators:
                peer_values = [
                    peer["ratings"][indicator_id]
                    for peer in teachers
                    if peer is not teacher and indicator_id in peer["ratings"]
                ]
                if peer_values:
                    differences.append(
                        abs(teacher["ratings"][indicator_id] - float(np.mean(peer_values)))
                    )
            teacher["peer_differences"] = differences
            teacher["other_teachers_mean_rating"] = (
                float(np.mean(peer_means)) if peer_means else None
            )
            teacher["school_head_ratings"] = head_by_cycle.get(cycle_id, {})
    return list(by_cycle_teacher.values())


def load_database_data(include_ground_truth: bool = False) -> dict[str, Any]:
    command = ["php", str(DATA_BRIDGE)]
    if include_ground_truth:
        command.append("--ground-truth")
    result = subprocess.run(command, cwd=BASE_DIR.parent, check=True, capture_output=True, text=True)
    return json.loads(result.stdout)


def fit_model(matrix: np.ndarray, contamination: float, random_state: int) -> dict[str, Any]:
    if len(matrix) < 2:
        raise ValueError("At least two teacher-cycles are required to fit the detector.")
    scaler = StandardScaler()
    scaled = scaler.fit_transform(matrix)
    model = IsolationForest(
        contamination=contamination,
        random_state=random_state,
        n_estimators=32,
        n_jobs=1,
    )
    model.fit(scaled)
    return {"scaler": scaler, "model": model}


def score_matrix(bundle: dict[str, Any], matrix: np.ndarray) -> tuple[np.ndarray, np.ndarray]:
    scaled = bundle["scaler"].transform(matrix)
    model = bundle["model"]
    raw_scores = -model.decision_function(scaled)
    flagged = model.predict(scaled) == -1
    return raw_scores, flagged


def score_with_threshold(
    bundle: dict[str, Any], matrix: np.ndarray, threshold: float
) -> tuple[np.ndarray, np.ndarray]:
    scaled = bundle["scaler"].transform(matrix)
    raw_scores = -bundle["model"].decision_function(scaled)
    return raw_scores, raw_scores >= threshold


def load_saved_model() -> dict[str, Any]:
    if not MODEL_PATH.is_file():
        raise FileNotFoundError(f"Teacher outlier model is missing: {MODEL_PATH}")
    return joblib.load(MODEL_PATH)


def retrain(contamination: float = DEFAULT_CONTAMINATION, random_state: int = 42) -> dict[str, Any]:
    data = load_database_data()
    records = make_records(data)
    matrix, rows = build_features(records)
    bundle = fit_model(matrix, contamination, random_state)
    metadata = {
        "version": MODEL_VERSION,
        "trained_at": datetime.now(timezone.utc).isoformat(),
        "contamination": contamination,
        "random_state": random_state,
        "cycles_used": sorted({row["cycle_id"] for row in rows}),
        "feature_names": FEATURE_NAMES,
        "teacher_cycle_count": len(rows),
        "data_note": "Training uses teacher_responses and sbm_responses only.",
    }
    MODEL_DIR.mkdir(parents=True, exist_ok=True)
    joblib.dump({**bundle, "metadata": metadata}, MODEL_PATH)
    return metadata


def _truth_set(data: dict[str, Any]) -> dict[tuple[int, int], str]:
    return {
        (int(row["cycle_id"]), int(row["evaluator_id"])): (
            "erratic" if str(row["anomaly_type"]) == "random"
            else str(row["anomaly_type"])
        )
        for row in data.get("seed_ground_truth", [])
    }


def _metrics(y_true: np.ndarray, flagged: np.ndarray, scores: np.ndarray) -> dict[str, Any]:
    precision, recall, f1, _ = precision_recall_fscore_support(
        y_true, flagged, pos_label=1, average="binary", zero_division=0
    )
    return {
        "true_positives": int(np.sum((y_true == 1) & (flagged == 1))),
        "false_positives": int(np.sum((y_true == 0) & (flagged == 1))),
        "false_negatives": int(np.sum((y_true == 1) & (flagged == 0))),
        "true_negatives": int(np.sum((y_true == 0) & (flagged == 0))),
        "precision": float(precision),
        "recall": float(recall),
        "f1": float(f1),
        "roc_auc": float(roc_auc_score(y_true, scores)) if len(set(y_true)) == 2 else None,
        "average_precision": float(average_precision_score(y_true, scores)),
    }


def _metrics_by_type(
    rows: list[dict[str, Any]],
    truth: dict[tuple[int, int], str],
    flagged: np.ndarray,
    scores: np.ndarray,
) -> dict[str, dict[str, Any]]:
    result: dict[str, dict[str, Any]] = {}
    for anomaly_type in sorted(set(truth.values())):
        indices = [
            index for index, row in enumerate(rows)
            if truth.get((row["cycle_id"], row["teacher_id"])) == anomaly_type
            or row["anomaly_type"] == anomaly_type
        ]
        if not indices:
            continue
        y_type = np.asarray([
            1 if truth.get((rows[index]["cycle_id"], rows[index]["teacher_id"])) == anomaly_type else 0
            for index in indices
        ])
        result[anomaly_type] = _metrics(
            y_type, flagged[indices], scores[indices]
        )
    return result


def _flag_counts_by_truth_type(
    rows: list[dict[str, Any]],
    truth: dict[tuple[int, int], str],
    flags_by_seed: list[np.ndarray],
) -> dict[str, dict[str, Any]]:
    result = {}
    for anomaly_type in ["all_ones", "all_fours", "constant", "erratic"]:
        indices = [
            index for index, row in enumerate(rows)
            if truth.get((row["cycle_id"], row["teacher_id"])) == anomaly_type
        ]
        counts = [int(np.sum(flags[indices])) for flags in flags_by_seed]
        result[anomaly_type] = {
            "truth_count": len(indices),
            "recall_mean": float(np.mean([
                count / len(indices) for count in counts
            ])) if indices else None,
            "flagged_count_across_20_seeds": int(sum(counts)),
            "flagged_count_min": min(counts) if counts else 0,
            "flagged_count_max": max(counts) if counts else 0,
        }
    return result


def _apply_type_aware_gate(rows: list[dict[str, Any]], flagged: np.ndarray) -> np.ndarray:
    return np.asarray([int(card_eligibility(row, bool(flag))[0])
                       for row, flag in zip(rows, flagged)])


def _rule_only_flags(rows: list[dict[str, Any]]) -> np.ndarray:
    return np.asarray([
        int(card_eligibility(row, True)[0]) for row in rows
    ])


def _summary(seed_metrics: list[dict[str, Any]]) -> dict[str, Any]:
    keys = list(seed_metrics[0])
    return {
        "mean": {
            key: float(np.mean([item[key] for item in seed_metrics]))
            for key in keys
        },
        "spread": {
            key: {
                "min": float(min(item[key] for item in seed_metrics)),
                "max": float(max(item[key] for item in seed_metrics)),
                "std": float(np.std([item[key] for item in seed_metrics])),
            }
            for key in keys
        },
        "seeds": seed_metrics,
    }


def evaluate() -> dict[str, Any]:
    data = load_database_data(include_ground_truth=True)
    records = make_records(data)
    matrix, rows = build_features(records)
    truth = _truth_set(data)
    years = sorted({row["cycle_year"] for row in rows})
    contaminations = [0.02, 0.05, 0.08, 0.10]
    seeds = list(range(20))
    y_true = np.asarray([
        1 if (row["cycle_id"], row["teacher_id"]) in truth else 0 for row in rows
    ])
    results: dict[str, Any] = {}
    all_runs: dict[tuple[float, int], dict[str, Any]] = {}

    def run_seed(contamination: float, seed: int) -> dict[str, Any]:
        all_scores = np.zeros(len(rows))
        all_flagged = np.zeros(len(rows), dtype=int)
        for year in years:
            test_indices = [i for i, row in enumerate(rows) if row["cycle_year"] == year]
            train_indices = [i for i, row in enumerate(rows) if row["cycle_year"] != year]
            if not train_indices or not test_indices:
                continue
            bundle = fit_model(matrix[train_indices], DEFAULT_CONTAMINATION, seed)
            train_scores, _ = score_matrix(bundle, matrix[train_indices])
            threshold = float(np.quantile(train_scores, 1.0 - contamination))
            scores, flagged = score_with_threshold(
                bundle, matrix[test_indices], threshold
            )
            all_scores[test_indices] = scores
            all_flagged[test_indices] = flagged.astype(int)
        gated = _apply_type_aware_gate(rows, all_flagged)
        return {
            "scores": all_scores,
            "ungated": all_flagged,
            "gated": gated,
            "rule_augmented": gated.copy(),
        }

    for contamination in contaminations:
        ungated_metrics = []
        gated_metrics = []
        augmented_metrics = []
        flags_by_seed = []
        for seed in seeds:
            run = run_seed(contamination, seed)
            all_runs[(contamination, seed)] = run
            run["rule_augmented"] = _apply_type_aware_gate(rows, run["ungated"])
            ungated_metrics.append(_metrics(y_true, run["ungated"], run["scores"]))
            gated_metrics.append(_metrics(y_true, run["gated"], run["scores"]))
            augmented_metrics.append(_metrics(y_true, run["rule_augmented"], run["scores"]))
            flags_by_seed.append(run["ungated"])
        results[str(contamination)] = {
            "ungated_detector": _summary(ungated_metrics),
            "gated_post_hoc": _summary(gated_metrics),
            "rule_augmented_post_hoc": _summary(augmented_metrics),
            "per_truth_type_ungated": _flag_counts_by_truth_type(rows, truth, flags_by_seed),
        }

    production = run_seed(DEFAULT_CONTAMINATION, 42)
    production_scores = production["scores"]
    production_flagged = production["ungated"]
    gated_flagged = production["gated"]
    augmented_flagged = production["rule_augmented"]
    detailed_rows = []
    for index, row in enumerate(rows):
        key = (row["cycle_id"], row["teacher_id"])
        if bool(production_flagged[index]) != (key in truth):
            detailed_rows.append({
                "cycle_id": row["cycle_id"],
                "school_year_label": row["cycle_year"],
                "teacher_label": f"Teacher {row['teacher_id']}",
                "teacher_id": row["teacher_id"],
                "anomaly_type": row["anomaly_type"],
                "truth_anomaly_type": truth.get(key),
                "result": "false_positive" if production_flagged[index] else "missed",
                "anomaly_score": float(production_scores[index]),
                "overall_score": row["overall_score"],
                "other_teachers_mean_rating": row["other_teachers_mean_rating"],
                "peer_mean_abs_difference": row["features"]["peer_mean_abs_difference"],
                "features": row["features"],
            })
    constant_rule_flags = np.asarray([
        int(
            row["anomaly_type"] == "constant"
            and row["rating_count"] >= 10
            and row["features"]["rating_std"] == 0.0
            and row["constant_value"] in (2.0, 3.0)
        )
        for row in rows
    ])
    constant_rule_non_truth = [
        f"Teacher {rows[index]['teacher_id']} cycle {rows[index]['cycle_id']}"
        for index, flag in enumerate(constant_rule_flags)
        if flag and (rows[index]["cycle_id"], rows[index]["teacher_id"]) not in truth
    ]
    baseline_flagged = np.asarray([row["features"]["share_ones"] == 1.0 for row in rows], dtype=int)
    baseline = _metrics(y_true, baseline_flagged, baseline_flagged.astype(float))
    rule_only = _rule_only_flags(rows)
    def error_rows(flags: np.ndarray) -> list[dict[str, Any]]:
        return [
            {
                "cycle_id": row["cycle_id"],
                "school_year_label": row["cycle_year"],
                "teacher_label": f"Teacher {row['teacher_id']}",
                "teacher_id": row["teacher_id"],
                "anomaly_type": row["anomaly_type"],
                "truth_anomaly_type": truth.get((row["cycle_id"], row["teacher_id"])),
                "result": "false_positive" if flags[index] else "missed",
                "anomaly_score": float(production_scores[index]),
                "overall_score": row["overall_score"],
                "other_teachers_mean_rating": row["other_teachers_mean_rating"],
                "peer_mean_abs_difference": row["features"]["peer_mean_abs_difference"],
                "features": row["features"],
            }
            for index, row in enumerate(rows)
            if bool(flags[index]) != ((row["cycle_id"], row["teacher_id"]) in truth)
        ]
    cycle39_index = next(
        index for index, row in enumerate(rows)
        if row["cycle_id"] == 39 and row["teacher_id"] == 13
    )
    cycle39_flags = {
        str(contamination): sum(
            int(all_runs[(contamination, seed)]["ungated"][cycle39_index])
            for seed in seeds
        )
        for contamination in contaminations
    }
    initial_errors = detailed_rows
    low_year_errors = sum(
        1 for item in initial_errors
        if item["result"] == "false_positive" and item["overall_score"] is not None
        and item["overall_score"] < 50
    )
    false_positive_count = sum(item["result"] == "false_positive" for item in initial_errors)
    hypothesis = (
        "supported for a cluster but rejected as a universal rule"
        if low_year_errors < false_positive_count
        else "supported"
    )
    return {
        "synthetic_data_warning": (
            "This evaluation uses synthetic seeded assessment patterns. "
            "These results show detection of generated patterns, not accuracy on real schools."
        ),
        "configuration": {
            "model": "IsolationForest",
            "standardization": "StandardScaler",
            "production_contamination": DEFAULT_CONTAMINATION,
            "contamination_values_evaluated": contaminations,
            "seeds": seeds,
            "leave_one_year_out": True,
            "feature_names": FEATURE_NAMES,
        },
        "dataset": {
            "teacher_cycles": len(rows),
            "cycles": sorted({row["cycle_id"] for row in rows}),
            "years": years,
            "seed_anomalies": len(truth),
        },
        "baseline_all_ones": baseline,
        "rule_only_baseline": _metrics(y_true, rule_only, rule_only.astype(float)),
        "forest_adds_beyond_rules": {
            "comparison": "The forest-plus-type-gate is compared with the deterministic rule-only baseline.",
            "answer": (
                "The forest adds no additional eligible cards on this synthetic dataset."
                if np.array_equal(rule_only, augmented_flagged)
                else "The forest changes the eligible-card set on this synthetic dataset."
            ),
            "rule_only_by_type": _metrics_by_type(rows, truth, rule_only, rule_only.astype(float)),
            "rule_only_metrics": _metrics(y_true, rule_only, rule_only.astype(float)),
            "forest_plus_gate_seed_42_metrics": _metrics(
                y_true, augmented_flagged, production_scores
            ),
        },
        "baseline_all_ones_by_anomaly_type": _metrics_by_type(
            rows, truth, baseline_flagged, baseline_flagged.astype(float)
        ),
        "headline_ungated_detector_results": results,
        "type_aware_gate_and_rule_notes": {
            "post_hoc": True,
            "peer_gate": "all_low, all_high, mostly_low, mostly_high, and erratic require peer_mean_abs_difference >= 1.0; detector score is confidence-only",
            "constant_rule": "constant requires at least 10 identical ratings, standard deviation 0, and rating value 2 or 3; it is deterministic and not trained or tuned on these results.",
        },
        "constant_rule_evaluation": {
            "teacher_cycles_flagged": int(np.sum(constant_rule_flags)),
            "not_in_seed_ground_truth_count": len(constant_rule_non_truth),
            "not_in_seed_ground_truth": constant_rule_non_truth,
        },
        "production_seed_42_same_run_details": {
            "ungated": _metrics(y_true, production_flagged, production_scores),
            "gated_post_hoc": _metrics(y_true, gated_flagged, production_scores),
            "rule_augmented_post_hoc": _metrics(y_true, augmented_flagged, production_scores),
            "ungated_errors": error_rows(production_flagged),
            "gated_errors": error_rows(gated_flagged),
            "rule_augmented_errors": error_rows(augmented_flagged),
        },
        "cycle_39_teacher_13": {
            "peer_mean_abs_difference": rows[cycle39_index]["features"]["peer_mean_abs_difference"],
            "flags_across_20_seeds_by_contamination": cycle39_flags,
        },
        "initial_error_hypothesis": {
            "false_positive_count": false_positive_count,
            "low_scoring_false_positive_count": low_year_errors,
            "conclusion": hypothesis,
            "computed_from": "production_seed_42_same_run_details.ungated_errors",
        },
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--retrain", action="store_true")
    parser.add_argument("--evaluate", action="store_true")
    args = parser.parse_args()
    logging.basicConfig(level=logging.INFO)
    if args.retrain:
        print(json.dumps(retrain(), indent=2))
        return 0
    if args.evaluate:
        report = evaluate()
        report_dir = BASE_DIR / "reports"
        report_dir.mkdir(parents=True, exist_ok=True)
        report_path = report_dir / "teacher_outlier_evaluation.json"
        report_path.write_text(json.dumps(report, indent=2), encoding="utf-8")
        print(json.dumps(report, indent=2))
        return 0
    parser.error("Choose --retrain or --evaluate.")
    return 2


if __name__ == "__main__":
    sys.exit(main())
