"""
score_analyzer.py
Analyzes SBM dimension scores to find gaps, predict maturity,
and cluster weak indicators. Designed for Phase 1 (rule-based)
with Year 2 upgrade path to sklearn models.
"""
import json
import numpy as np
from pathlib import Path

# Maturity thresholds per DepEd Order No. 007, s. 2024
MATURITY_BANDS = [
    (76, 100, "Advanced"),
    (51, 75,  "Maturing"),
    (26, 50,  "Developing"),
    (0,  25,  "Beginning"),
]

DIMENSION_NAMES = {
    1: "Curriculum and Teaching",
    2: "Learning Environment",
    3: "Leadership and Governance",
    4: "Accountability and Continuous Improvement",
    5: "Human Resource Development",
    6: "Finance and Resource Management",
}

DIMENSION_WEIGHT = {1: 1.0, 2: 1.0, 3: 1.0, 4: 1.0, 5: 1.0, 6: 1.0}


def get_maturity(score: float) -> str:
    for low, high, label in MATURITY_BANDS:
        if low <= score <= high:
            return label
    return "Beginning"


def analyze_gaps(dim_scores: dict) -> dict:
    """
    dim_scores: {dimension_no (int): percentage (float)}
    Returns gap analysis with priority ranking.
    """
    if not dim_scores:
        return {}

    avg = sum(dim_scores.values()) / len(dim_scores)
    gaps = []

    for dim_no, score in dim_scores.items():
        gap = avg - score
        priority = "high" if gap > 15 else ("medium" if gap > 5 else "low")
        gaps.append({
            "dimension_no": dim_no,
            "dimension_name": DIMENSION_NAMES.get(dim_no, f"Dimension {dim_no}"),
            "score": round(score, 2),
            "gap_from_avg": round(gap, 2),
            "priority": priority,
            "maturity": get_maturity(score),
        })

    gaps.sort(key=lambda x: -x["gap_from_avg"])
    return {
        "average_score": round(avg, 2),
        "overall_maturity": get_maturity(avg),
        "weakest_dimensions": [g for g in gaps if g["priority"] == "high"],
        "all_dimensions": gaps,
    }


def analyze_weak_indicators(indicators: list) -> dict:
    """
    indicators: list of {indicator_code, indicator_text,
                          dimension_no, avg_rating}
    Groups weak indicators (rating < 2.5) by dimension.
    """
    weak = [i for i in indicators if float(i.get("avg_rating", 4)) < 2.5]
    by_dim: dict = {}

    for ind in weak:
        dim = ind["dimension_no"]
        if dim not in by_dim:
            by_dim[dim] = []
        by_dim[dim].append({
            "code":   ind["indicator_code"],
            "text":   ind["indicator_text"],
            "rating": round(float(ind["avg_rating"]), 2),
            "gap":    round(4.0 - float(ind["avg_rating"]), 2),
        })

    # Sort each dimension's indicators by worst first
    for dim in by_dim:
        by_dim[dim].sort(key=lambda x: x["rating"])

    return {
        "total_weak": len(weak),
        "by_dimension": by_dim,
        "critical": [i for i in weak if float(i.get("avg_rating", 4)) < 1.5],
    }


def forecast_maturity(history: list) -> dict:
    """
    history: list of {cycle_year (int), overall_score (float)}
             sorted ascending by year
    Returns simple linear trend forecast for next cycle.
    Phase 2: replace with sklearn LinearRegression or Ridge.
    """
    if len(history) < 2:
        return {"forecast": None, "trend": "insufficient_data", "slope": 0}

    years  = np.array([h["cycle_year"]  for h in history], dtype=float)
    scores = np.array([h["overall_score"] for h in history], dtype=float)

    # Normalize years to 0-based index
    years -= years[0]
    slope = float(np.polyfit(years, scores, 1)[0])

    next_year_idx  = years[-1] + 1
    forecast_score = float(np.clip(scores[-1] + slope, 0, 100))

    if slope > 3:
        trend = "improving"
    elif slope < -3:
        trend = "declining"
    else:
        trend = "stable"

    return {
        "forecast": round(forecast_score, 1),
        "maturity_forecast": get_maturity(forecast_score),
        "trend": trend,
        "slope_per_cycle": round(slope, 2),
        "data_points": len(history),
    }


# DepEd-aligned dimension weights (adjust these as priorities change)
# Higher value = more weight in gap scoring
DIMENSION_WEIGHTS = {
    1: 1.2,  # Curriculum and Teaching — core academic outcome
    2: 1.2,  # Learning Environment — learner safety and wellbeing
    3: 1.0,  # Leadership and Governance
    4: 1.0,  # Accountability and Continuous Improvement
    5: 0.9,  # Human Resource Development
    6: 0.9,  # Finance and Resource Management
}


def weighted_gap_analysis(dim_scores: dict, weights: dict = None) -> dict:
    """
    Computes gap analysis using DepEd-priority weights per dimension.
    Falls back to equal weights if none provided.
    """
    if not dim_scores:
        return {}

    if weights is None:
        weights = DIMENSION_WEIGHTS

    total_weight   = sum(weights.get(int(d), 1.0) for d in dim_scores)
    weighted_avg   = sum(
        float(dim_scores[d]) * weights.get(int(d), 1.0)
        for d in dim_scores
    ) / total_weight if total_weight > 0 else 0

    gaps = []
    for dim_no, score in dim_scores.items():
        score      = float(score)
        dim_no_int = int(dim_no)
        weight     = weights.get(dim_no_int, 1.0)
        raw_gap    = weighted_avg - score
        # Weighted gap: gaps in high-priority dimensions feel larger
        weighted_gap = raw_gap * weight
        priority   = "high" if weighted_gap > 15 else (
                     "medium" if weighted_gap > 5 else "low")

        gaps.append({
            "dimension_no":        dim_no_int,
            "dimension_name":      DIMENSION_NAMES.get(dim_no_int, f"Dimension {dim_no_int}"),
            "score":               round(score, 2),
            "gap_from_avg":        round(raw_gap, 2),
            "weighted_gap":        round(weighted_gap, 2),
            "weight":              weight,
            "priority":            priority,
            "maturity":            get_maturity(score),
        })

    gaps.sort(key=lambda x: -x["weighted_gap"])

    return {
        "average_score":      round(weighted_avg, 2),
        "overall_maturity":   get_maturity(weighted_avg),
        "weakest_dimensions": [g for g in gaps if g["priority"] == "high"],
        "all_dimensions":     gaps,
    }


def full_analysis(payload: dict) -> dict:
    """
    Master function called by Flask endpoint.
    payload keys: dim_scores, indicators, by_rating, history
    """
    dim_scores = payload.get("dim_scores", {})

    # Convert string keys to int keys if needed (JSON sends string keys)
    if dim_scores and all(isinstance(k, str) for k in dim_scores):
        dim_scores = {int(k): v for k, v in dim_scores.items()}

    return {
        "gap_analysis":    weighted_gap_analysis(dim_scores),
        "weak_indicators": analyze_weak_indicators(payload.get("indicators", [])),
        "forecast":        forecast_maturity(payload.get("history", [])),
        "by_rating":       payload.get("by_rating", {}),
        "history":         payload.get("history", []),
    }

    