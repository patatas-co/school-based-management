"""Held-out in-memory stress test for subtle teacher-rating anomalies.

This script never reads or writes the database, saved model, or production data.
The generator and anomaly definitions are intentionally fixed before evaluation.
Its output is a synthetic stress test, not accuracy on real schools.
"""
from __future__ import annotations

import json
import sys
from pathlib import Path
from typing import Any

import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parent))
from teacher_outlier import (  # noqa: E402
    _metrics,
    build_features,
    card_eligibility,
    fit_model,
    score_matrix,
    score_with_threshold,
)

SEED = 20261001
FRESH_SEED = 20261002
TEACHERS = 5
INDICATORS = 21
HEAD_INDICATORS = 42
CYCLES = 26
ANOMALY_TYPES = ["mostly_low", "mostly_high", "mild_lenient", "rubber_stamp", "fatigue", "alternating"]


def _clip(values: np.ndarray) -> np.ndarray:
    return np.clip(np.rint(values), 1, 4).astype(float)


def _normal_teacher(rng: np.random.Generator, teacher_id: int, cycle: int) -> np.ndarray:
    bias = [-0.75, -0.35, 0.0, 0.35, 0.75][teacher_id]
    difficulty = np.linspace(-0.35, 0.35, INDICATORS)
    seasonal = ((cycle % 4) - 1.5) * 0.04
    return _clip(2.5 + bias - difficulty + seasonal + rng.normal(0, 0.65, INDICATORS))


def _school_head(rng: np.random.Generator, cycle: int) -> np.ndarray:
    return _clip(2.5 + rng.normal(0, 0.8, HEAD_INDICATORS) + (cycle % 3 - 1) * 0.05)


def _inject(
    anomaly_type: str,
    normal: np.ndarray,
    school_head: np.ndarray,
    rng: np.random.Generator,
) -> np.ndarray:
    if anomaly_type == "mostly_low":
        values = np.ones(INDICATORS)
        values[rng.choice(INDICATORS, size=2, replace=False)] = 2
        return values
    if anomaly_type == "mostly_high":
        values = np.full(INDICATORS, 4.0)
        values[rng.choice(INDICATORS, size=2, replace=False)] = 3
        return values
    if anomaly_type == "mild_lenient":
        result = normal.copy()
        indices = rng.choice(INDICATORS, size=13, replace=False)
        result[indices] = np.minimum(4, result[indices] + 1)
        return result
    if anomaly_type == "rubber_stamp":
        result = school_head[:INDICATORS].copy()
        differences = rng.choice(INDICATORS, size=2, replace=False)
        result[differences] = np.clip(result[differences] + rng.choice([-1, 1], size=2), 1, 4)
        return result
    if anomaly_type == "fatigue":
        result = normal.copy()
        result[10:] = 2
        return result
    if anomaly_type == "alternating":
        return np.asarray([1 if index % 2 == 0 else 4 for index in range(INDICATORS)], dtype=float)
    raise ValueError(f"Unknown anomaly type: {anomaly_type}")


def generate_records(seed: int = SEED, include_mostly_high: bool = False) -> tuple[list[dict[str, Any]], dict[tuple[int, int], str]]:
    rng = np.random.default_rng(seed)
    records: list[dict[str, Any]] = []
    truth: dict[tuple[int, int], str] = {}
    injections = {
        (1, 1): "mostly_low",
        (3, 2): "mostly_low",
        (5, 3): "mostly_low",
        (7, 4): "mild_lenient",
        (9, 5): "mild_lenient",
        (11, 1): "mild_lenient",
        (13, 2): "rubber_stamp",
        (15, 3): "rubber_stamp",
        (17, 4): "rubber_stamp",
        (19, 5): "fatigue",
        (21, 1): "fatigue",
        (23, 2): "fatigue",
        (2, 3): "alternating",
        (4, 4): "alternating",
        (6, 5): "alternating",
    }
    if include_mostly_high:
        injections.update({
            (8, 1): "mostly_high",
            (10, 2): "mostly_high",
            (12, 3): "mostly_high",
        })
    for cycle in range(CYCLES):
        cycle_id = 10000 + cycle
        year = "stress-year-a" if cycle < CYCLES // 2 else "stress-year-b"
        school_head = _school_head(rng, cycle)
        cycle_rows: list[dict[str, Any]] = []
        for teacher_id in range(1, TEACHERS + 1):
            normal = _normal_teacher(rng, teacher_id - 1, cycle)
            anomaly_type = injections.get((cycle, teacher_id))
            ratings = _inject(anomaly_type, normal, school_head, rng) if anomaly_type else normal
            if anomaly_type:
                truth[(cycle_id, teacher_id)] = anomaly_type
            cycle_rows.append({
                "cycle_id": cycle_id,
                "cycle_year": year,
                "teacher_id": teacher_id,
                "ratings": {str(index + 1): float(value) for index, value in enumerate(ratings)},
                "school_head_ratings": {
                    str(index + 1): float(value) for index, value in enumerate(school_head)
                },
            })
        for teacher in cycle_rows:
            peer_differences = []
            peer_means = []
            for indicator in range(1, INDICATORS + 1):
                peers = [
                    peer["ratings"][str(indicator)]
                    for peer in cycle_rows
                    if peer["teacher_id"] != teacher["teacher_id"]
                ]
                peer_mean = float(np.mean(peers))
                peer_means.append(peer_mean)
                peer_differences.append(abs(teacher["ratings"][str(indicator)] - peer_mean))
            teacher["peer_differences"] = peer_differences
            teacher["other_teachers_mean_rating"] = float(np.mean(peer_means))
        records.extend(cycle_rows)
    return records, truth


def _one_vs_rest_by_type(
    rows: list[dict[str, Any]],
    truth: dict[tuple[int, int], str],
    flags: np.ndarray,
    scores: np.ndarray,
) -> dict[str, dict[str, Any]]:
    result = {}
    for anomaly_type in sorted(set(truth.values()), key=ANOMALY_TYPES.index):
        y = np.asarray([
            int(truth.get((row["cycle_id"], row["teacher_id"])) == anomaly_type)
            for row in rows
        ])
        result[anomaly_type] = _metrics(y, flags, scores)
    return result


def _direct_type_counts(
    rows: list[dict[str, Any]],
    truth: dict[tuple[int, int], str],
    flags: np.ndarray,
) -> dict[str, dict[str, int | float]]:
    result = {}
    for anomaly_type in sorted(set(truth.values()), key=ANOMALY_TYPES.index):
        indices = [
            index for index, row in enumerate(rows)
            if truth.get((row["cycle_id"], row["teacher_id"])) == anomaly_type
        ]
        caught = sum(int(flags[index]) for index in indices)
        result[anomaly_type] = {
            "caught": caught,
            "total": len(indices),
            "recall": float(caught / len(indices)) if indices else 0.0,
        }
    return result


def _assert_type_counts_match_overall(
    rows: list[dict[str, Any]],
    truth: dict[tuple[int, int], str],
    flags: np.ndarray,
    method: str,
    seed: int,
) -> None:
    direct = _direct_type_counts(rows, truth, flags)
    caught_total = sum(item["caught"] for item in direct.values())
    overall_true_positives = sum(
        int(flag)
        for row, flag in zip(rows, flags)
        if (row["cycle_id"], row["teacher_id"]) in truth
    )
    if caught_total != overall_true_positives:
        raise RuntimeError(
            f"{method} seed {seed}: per-type caught total {caught_total} "
            f"does not match overall TP {overall_true_positives}"
        )


def run_scenario(
    seed: int,
    include_mostly_high: bool,
    require_detector_for_gate: bool,
    require_detector_for_rule: bool,
    historical_types: bool,
) -> dict[str, Any]:
    records, truth = generate_records(seed, include_mostly_high)
    matrix, rows = build_features(records)
    labels = np.asarray([
        int((row["cycle_id"], row["teacher_id"]) in truth) for row in rows
    ])
    years = sorted({row["cycle_year"] for row in rows})
    seed_results: list[dict[str, Any]] = []
    def eligible(row: dict[str, Any], detector_flagged: bool, require_detector: bool) -> bool:
        if historical_types and row["anomaly_type"] in {"mostly_low", "mostly_high"}:
            return False
        return card_eligibility(
            row, detector_flagged, require_detector=require_detector
        )[0]

    rule_flags = np.asarray([
        int(eligible(row, True, require_detector_for_rule)) for row in rows
    ])
    rule_scores = np.zeros(len(rows), dtype=float)
    for model_seed in range(20):
        scores = np.zeros(len(rows), dtype=float)
        flags = np.zeros(len(rows), dtype=int)
        for year in years:
            test = np.asarray([i for i, row in enumerate(rows) if row["cycle_year"] == year])
            train = np.asarray([i for i, row in enumerate(rows) if row["cycle_year"] != year])
            bundle = fit_model(matrix[train], 0.05, model_seed)
            train_scores, _ = score_matrix(bundle, matrix[train])
            threshold = float(np.quantile(train_scores, 0.95))
            test_scores, test_flags = score_with_threshold(bundle, matrix[test], threshold)
            scores[test] = test_scores
            flags[test] = test_flags.astype(int)
        gated = np.asarray([
            int(eligible(row, bool(flag), require_detector_for_gate))
            for row, flag in zip(rows, flags)
        ])
        for method, method_flags in (
            ("rule_only", rule_flags),
            ("ungated_forest", flags),
            ("forest_plus_type_aware_gate", gated),
        ):
            _assert_type_counts_match_overall(rows, truth, method_flags, method, model_seed)
        seed_results.append({
            "seed": model_seed,
            "rule_only": _metrics(labels, rule_flags, rule_scores),
            "ungated_forest": _metrics(labels, flags, scores),
            "forest_plus_type_aware_gate": _metrics(labels, gated, scores),
            "ungated_by_anomaly_type": _one_vs_rest_by_type(rows, truth, flags, scores),
            "gated_by_anomaly_type": _one_vs_rest_by_type(rows, truth, gated, scores),
            "direct_caught_by_anomaly_type": {
                "rule_only": _direct_type_counts(rows, truth, rule_flags),
                "ungated_forest": _direct_type_counts(rows, truth, flags),
                "forest_plus_type_aware_gate": _direct_type_counts(rows, truth, gated),
            },
        })
    methods = ["rule_only", "ungated_forest", "forest_plus_type_aware_gate"]
    summary: dict[str, Any] = {}
    for method in methods:
        metrics = [run[method] for run in seed_results]
        summary[method] = {
            "mean": {
                key: float(np.mean([item[key] for item in metrics]))
                for key in metrics[0]
            },
            "spread": {
                key: {
                    "min": float(min(item[key] for item in metrics)),
                    "max": float(max(item[key] for item in metrics)),
                    "std": float(np.std([item[key] for item in metrics])),
                }
                for key in metrics[0]
            },
        }
    type_summary = {}
    type_summary["rule_only"] = {}
    for anomaly_type in sorted(set(truth.values()), key=ANOMALY_TYPES.index):
        y = np.asarray([
            int(truth.get((row["cycle_id"], row["teacher_id"])) == anomaly_type)
            for row in rows
        ])
        type_summary["rule_only"][anomaly_type] = {
            "mean": _metrics(y, rule_flags, rule_scores),
            "spread": None,
        }
    for method_key in ("ungated_by_anomaly_type", "gated_by_anomaly_type"):
        type_summary[method_key] = {}
        for anomaly_type in sorted(set(truth.values()), key=ANOMALY_TYPES.index):
            metrics = [run[method_key][anomaly_type] for run in seed_results]
            type_summary[method_key][anomaly_type] = {
                "mean": {
                    key: float(np.mean([item[key] for item in metrics]))
                    for key in metrics[0]
                },
                "spread": {
                    key: {
                        "min": float(min(item[key] for item in metrics)),
                        "max": float(max(item[key] for item in metrics)),
                        "std": float(np.std([item[key] for item in metrics])),
                    }
                    for key in metrics[0]
                },
            }
    direct_type_summary = {}
    for method in methods:
        direct_type_summary[method] = {}
        for anomaly_type in sorted(set(truth.values()), key=ANOMALY_TYPES.index):
            values = [
                item["direct_caught_by_anomaly_type"][method][anomaly_type]
                for item in seed_results
            ]
            direct_type_summary[method][anomaly_type] = {
                "caught": float(np.mean([item["caught"] for item in values])),
                "total": values[0]["total"],
                "recall": float(np.mean([item["recall"] for item in values])),
            }
    forest_additional_true_positives = []
    rule_true_positive_keys = {
        (row["cycle_id"], row["teacher_id"])
        for row, flag in zip(rows, rule_flags)
        if flag and (row["cycle_id"], row["teacher_id"]) in truth
    }
    for model_seed in range(20):
        scores = np.zeros(len(rows), dtype=float)
        flags = np.zeros(len(rows), dtype=int)
        for year in years:
            test = np.asarray([i for i, row in enumerate(rows) if row["cycle_year"] == year])
            train = np.asarray([i for i, row in enumerate(rows) if row["cycle_year"] != year])
            bundle = fit_model(matrix[train], 0.05, model_seed)
            train_scores, _ = score_matrix(bundle, matrix[train])
            threshold = float(np.quantile(train_scores, 0.95))
            test_scores, test_flags = score_with_threshold(bundle, matrix[test], threshold)
            scores[test] = test_scores
            flags[test] = test_flags.astype(int)
        forest_additional_true_positives.append([
            [row["cycle_id"], row["teacher_id"]]
            for row, flag in zip(rows, flags)
            if flag
            and (row["cycle_id"], row["teacher_id"]) in truth
            and (row["cycle_id"], row["teacher_id"]) not in rule_true_positive_keys
        ])
    return {
        "synthetic_stress_test_warning": (
            "This is a fixed in-memory synthetic stress test, not accuracy on real schools."
        ),
        "generator": {
            "seed": seed,
            "cycles": CYCLES,
            "teachers_per_cycle": TEACHERS,
            "teacher_indicators": INDICATORS,
            "school_head_indicators": HEAD_INDICATORS,
            "anomaly_count": len(truth),
            "anomaly_definitions": {
                "mostly_low": "85% to 95% of ratings are 1, remainder 2",
                "mostly_high": "85% to 95% of ratings are 4, remainder 3",
                "mild_lenient": "+1 on about 60% of indicators, capped at 4",
                "rubber_stamp": "School Head ratings on shared indicators with at most 1 or 2 differences",
                "fatigue": "Normal first half, one repeated rating on the second half",
                "alternating": "Ratings alternate between two values",
            },
            "hard_normal_cases": "Strict and lenient normal teacher biases are included and are not labeled anomalies.",
        },
        "configuration": {
            "seeds": list(range(20)),
            "contamination": 0.05,
            "leave_one_year_out": True,
            "labels_used_only_for_evaluation": True,
        },
        "overall": summary,
        "per_anomaly_type": type_summary,
        "per_anomaly_type_direct_membership": direct_type_summary,
        "forest_catches_anything_rules_miss": {
            "yes": any(forest_additional_true_positives),
            "additional_true_positive_count_by_seed": [
                len(items) for items in forest_additional_true_positives
            ],
            "cases_by_seed": forest_additional_true_positives,
            "note": (
                "No generator, rule, threshold, or feature was changed after seeing "
                "these results."
            ),
        },
    }


def run() -> dict[str, Any]:
    first = run_scenario(SEED, False, True, True, True)
    fresh = run_scenario(FRESH_SEED, True, True, False, False)
    rule = fresh["overall"]["rule_only"]["mean"]
    gated = fresh["overall"]["forest_plus_type_aware_gate"]["mean"]
    use_rules = (
        rule["true_positives"] >= gated["true_positives"]
        and rule["false_positives"] <= gated["false_positives"]
    )
    return {
        "motivating_first_stress_test": {
            "label": "First stress test that motivated mostly_low",
            **first,
        },
        "fresh_fairer_measure": {
            "label": "Fresh fairer measure",
            **fresh,
        },
        "decision": {
            "rule_only_catches_at_least_forest_plus_gate": rule["true_positives"] >= gated["true_positives"],
            "rule_only_has_no_more_false_positives": rule["false_positives"] <= gated["false_positives"],
            "selected_trigger": "rule_only" if use_rules else "forest_plus_type_aware_gate",
            "applied_to_production": False,
        },
    }


if __name__ == "__main__":
    report_path = Path(__file__).resolve().parent / "reports" / "teacher_outlier_stress_test.json"
    report_path.parent.mkdir(parents=True, exist_ok=True)
    report_path.write_text(json.dumps(run(), indent=2) + "\n", encoding="utf-8")
    print(report_path)
