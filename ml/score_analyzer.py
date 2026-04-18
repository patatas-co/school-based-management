"""
score_analyzer.py
Analyzes SBM dimension scores to find gaps, predict maturity,
and cluster weak indicators.

Maturity classification and intervention priority are now powered by
sklearn.tree.DecisionTreeClassifier (see ml_classifier.py) instead of
hardcoded numeric thresholds. The DT uses 3 features for maturity and
5 features for priority, capturing trend and weak-indicator density that
the old rules ignored.

Phase 2 upgrade path: replace synthetic training data in ml_classifier.py
with real historical school data from the database.
"""
import logging
import numpy as np

from ml_classifier import get_classifier

logger = logging.getLogger(__name__)

# ── Reference data ──────────────────────────────────────────────────────────────

# Kept for fallback only (used if sklearn is unavailable)
_MATURITY_BANDS = [
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

# DepEd-aligned dimension weights (Dims 1 & 2 are core academic/safety)
DIMENSION_WEIGHTS = {
    1: 1.2,  # Curriculum and Teaching
    2: 1.2,  # Learning Environment
    3: 1.0,  # Leadership and Governance
    4: 1.0,  # Accountability and Continuous Improvement
    5: 0.9,  # Human Resource Development
    6: 0.9,  # Finance and Resource Management
}


# ── Maturity classification (Decision Tree) ──────────────────────────────────────

def get_maturity(
    score: float,
    slope: float = 0.0,
    weak_ratio: float = 0.0,
) -> str:
    """
    Predicts the SBM maturity band using a trained Decision Tree.

    Unlike the previous hardcoded threshold approach, this considers:
      - score:      the raw SBM percentage
      - slope:      historical improvement trend (+ve = improving each cycle)
      - weak_ratio: density of low-rated indicators (pulls maturity down)

    Falls back to DepEd band rules if the classifier is unavailable.

    Args:
        score:      SBM percentage for this dimension (0–100).
        slope:      Score change per cycle from linear regression forecast.
                    Defaults to 0.0 when no history is available.
        weak_ratio: Fraction of dimension indicators rated < 2.5 (0.0–1.0).
                    Defaults to 0.0 when indicator data is unavailable.

    Returns:
        One of: 'Beginning', 'Developing', 'Maturing', 'Advanced'
    """
    try:
        clf = get_classifier()
        return clf.predict_maturity(float(score), float(slope), float(weak_ratio))
    except Exception as exc:
        logger.warning("[SBM-ML] DT maturity failed (%s) — using rule fallback", exc)
        for low, high, label in _MATURITY_BANDS:
            if low <= score <= high:
                return label
        return "Beginning"


def get_maturity_with_confidence(
    score: float,
    slope: float = 0.0,
    weak_ratio: float = 0.0,
) -> dict:
    """
    Returns maturity prediction + confidence probabilities from the DT.
    Use this when you want to surface borderline assessments to the School Head.

    Returns:
        {
          "maturity":      "Maturing",
          "confidence":    0.82,
          "probabilities": {"Beginning": 0.01, ...}
        }
    """
    try:
        clf = get_classifier()
        return clf.predict_maturity_with_confidence(
            float(score), float(slope), float(weak_ratio)
        )
    except Exception as exc:
        logger.warning("[SBM-ML] DT confidence failed (%s) — returning simple label", exc)
        label = get_maturity(score, slope, weak_ratio)
        return {"maturity": label, "confidence": 1.0, "probabilities": {label: 1.0}}


# ── Gap analysis (Decision Tree priority) ────────────────────────────────────────

def analyze_gaps(dim_scores: dict) -> dict:
    """
    Simple gap analysis — used when indicator-level data is unavailable.
    Priority classification uses the Decision Tree with available features.

    dim_scores: {dimension_no (int): percentage (float)}
    """
    if not dim_scores:
        return {}

    avg = sum(dim_scores.values()) / len(dim_scores)
    clf = get_classifier()
    gaps = []

    for dim_no, score in dim_scores.items():
        score    = float(score)
        dim_int  = int(dim_no)
        weight   = DIMENSION_WEIGHTS.get(dim_int, 1.0)
        raw_gap  = avg - score
        wgap     = raw_gap * weight

        # Decision Tree priority (slope=0, weak_count=0 — limited context)
        priority = clf.predict_priority(
            score=score,
            weighted_gap=wgap,
            weight=weight,
            weak_count=0,
            slope=0.0,
        )
        # Maturity (slope=0, weak_ratio=0 — limited context)
        maturity = get_maturity(score)

        gaps.append({
            "dimension_no":   dim_int,
            "dimension_name": DIMENSION_NAMES.get(dim_int, f"Dimension {dim_int}"),
            "score":          round(score, 2),
            "gap_from_avg":   round(raw_gap, 2),
            "priority":       priority,
            "maturity":       maturity,
        })

    gaps.sort(key=lambda x: -x["gap_from_avg"])
    return {
        "average_score":      round(avg, 2),
        "overall_maturity":   get_maturity(avg),
        "weakest_dimensions": [g for g in gaps if g["priority"] == "high"],
        "all_dimensions":     gaps,
    }


def weighted_gap_analysis(
    dim_scores: dict,
    weights: dict = None,
    slope: float = 0.0,
    weak_counts: dict = None,
    total_counts: dict = None,
) -> dict:
    """
    Full weighted gap analysis using Decision Tree for both maturity and priority.

    Args:
        dim_scores:   {dimension_no: score_pct}
        weights:      DepEd dimension weights (defaults to DIMENSION_WEIGHTS)
        slope:        School-level improvement slope from forecast (threads context
                      into the priority classifier for better accuracy)
        weak_counts:  {dimension_no: count_of_weak_indicators} — used by DT
        total_counts: {dimension_no: total_indicator_count}    — to compute weak_ratio
    """
    if not dim_scores:
        return {}

    if weights is None:
        weights = DIMENSION_WEIGHTS
    if weak_counts is None:
        weak_counts = {}
    if total_counts is None:
        total_counts = {}

    total_weight  = sum(weights.get(int(d), 1.0) for d in dim_scores)
    weighted_avg  = (
        sum(float(dim_scores[d]) * weights.get(int(d), 1.0) for d in dim_scores)
        / total_weight
    ) if total_weight > 0 else 0.0

    clf  = get_classifier()
    gaps = []

    for dim_no, score in dim_scores.items():
        score      = float(score)
        dim_int    = int(dim_no)
        weight     = weights.get(dim_int, 1.0)
        raw_gap    = weighted_avg - score
        wgap       = raw_gap * weight

        # Weak indicator context for this dimension
        wc         = int(weak_counts.get(dim_int, 0))
        tc         = int(total_counts.get(dim_int, 0))
        weak_ratio = (wc / tc) if tc > 0 else 0.0

        # ── Decision Tree: priority ──────────────────────────────────
        priority = clf.predict_priority(
            score=score,
            weighted_gap=wgap,
            weight=weight,
            weak_count=wc,
            slope=slope,
        )

        # ── Decision Tree: maturity (with trend + weak density) ──────
        maturity_result = get_maturity_with_confidence(score, slope, weak_ratio)
        maturity        = maturity_result["maturity"]
        confidence      = maturity_result["confidence"]

        gaps.append({
            "dimension_no":    dim_int,
            "dimension_name":  DIMENSION_NAMES.get(dim_int, f"Dimension {dim_int}"),
            "score":           round(score, 2),
            "gap_from_avg":    round(raw_gap, 2),
            "weighted_gap":    round(wgap, 2),
            "weight":          weight,
            "priority":        priority,
            "maturity":        maturity,
            "maturity_confidence": round(confidence, 3),
            "weak_count":      wc,
            "weak_ratio":      round(weak_ratio, 3),
        })

    gaps.sort(key=lambda x: -x["weighted_gap"])

    # Overall school maturity (uses school-level weak ratio)
    total_weak  = sum(weak_counts.values())
    total_ind   = sum(total_counts.values())
    school_weak_ratio = (total_weak / total_ind) if total_ind > 0 else 0.0
    overall_mat = get_maturity(weighted_avg, slope, school_weak_ratio)

    return {
        "average_score":      round(weighted_avg, 2),
        "overall_maturity":   overall_mat,
        "weakest_dimensions": [g for g in gaps if g["priority"] == "high"],
        "all_dimensions":     gaps,
    }


# ── Weak indicator analysis ──────────────────────────────────────────────────────

def analyze_weak_indicators(indicators: list) -> dict:
    """
    Groups weak indicators (avg_rating < 2.5) by dimension.

    indicators: list of {indicator_code, indicator_text, dimension_no, avg_rating}
    """
    weak   = [i for i in indicators if float(i.get("avg_rating", 4)) < 2.5]
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

    for dim in by_dim:
        by_dim[dim].sort(key=lambda x: x["rating"])

    return {
        "total_weak": len(weak),
        "by_dimension": by_dim,
        "critical": [i for i in weak if float(i.get("avg_rating", 4)) < 1.5],
    }


# ── Maturity forecasting ─────────────────────────────────────────────────────────

def forecast_maturity(history: list) -> dict:
    """
    Linear trend forecast for next cycle using NumPy polyfit.

    history: list of {cycle_year (int), overall_score (float)} sorted ascending.

    Returns slope used as a feature by the DT classifiers in full_analysis().
    Phase 2 upgrade: replace with sklearn Ridge or LinearRegression.
    """
    if len(history) < 2:
        return {"forecast": None, "trend": "insufficient_data", "slope_per_cycle": 0.0}

    years  = np.array([h["cycle_year"]    for h in history], dtype=float)
    scores = np.array([h["overall_score"] for h in history], dtype=float)

    years -= years[0]
    slope  = float(np.polyfit(years, scores, 1)[0])

    forecast_score = float(np.clip(scores[-1] + slope, 0, 100))

    if slope > 3:
        trend = "improving"
    elif slope < -3:
        trend = "declining"
    else:
        trend = "stable"

    return {
        "forecast":          round(forecast_score, 1),
        "maturity_forecast": get_maturity(forecast_score, slope),
        "trend":             trend,
        "slope_per_cycle":   round(slope, 2),
        "data_points":       len(history),
    }


# ── Master pipeline ──────────────────────────────────────────────────────────────

def full_analysis(payload: dict) -> dict:
    """
    Master function called by the Flask endpoint /api/analyze/scores.
    Threads slope and weak-indicator counts into the DT classifiers
    so all predictions use the richest available context.

    payload keys: dim_scores, indicators, by_rating, history
    """
    dim_scores = payload.get("dim_scores", {})

    # JSON sends string keys — normalize to int
    if dim_scores and all(isinstance(k, str) for k in dim_scores):
        dim_scores = {int(k): v for k, v in dim_scores.items()}

    indicators = payload.get("indicators", [])

    # ── Step 1: Forecast (gets slope for DT context) ─────────────────
    forecast_result = forecast_maturity(payload.get("history", []))
    slope           = forecast_result.get("slope_per_cycle", 0.0)

    # ── Step 2: Count weak indicators per dimension ───────────────────
    # These feed the priority DT as weak_count and weak_ratio features.
    weak_counts:  dict = {}
    total_counts: dict = {}
    for ind in indicators:
        dim = int(ind.get("dimension_no", 0))
        if dim == 0:
            continue
        total_counts[dim] = total_counts.get(dim, 0) + 1
        if float(ind.get("avg_rating", 4)) < 2.5:
            weak_counts[dim] = weak_counts.get(dim, 0) + 1

    # ── Step 3: Gap analysis with full DT context ─────────────────────
    gap_result = weighted_gap_analysis(
        dim_scores,
        slope=slope,
        weak_counts=weak_counts,
        total_counts=total_counts,
    )

    return {
        "gap_analysis":    gap_result,
        "weak_indicators": analyze_weak_indicators(indicators),
        "forecast":        forecast_result,
        "by_rating":       payload.get("by_rating", {}),
        "history":         payload.get("history", []),
    }