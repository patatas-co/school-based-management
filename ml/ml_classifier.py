"""
ml_classifier.py
Decision Tree classifiers for SBM dimension analysis.

Replaces hardcoded thresholds in score_analyzer.py with trained sklearn models
for more nuanced, multi-feature classification.

Two models:
  1. maturity_model   — predicts maturity band using score + trend + weak density
  2. priority_model   — predicts intervention priority from 5 dimension features

Training data: Synthetically generated but encodes DepEd Order No. 007 domain
knowledge, including the effect of historical trend (slope) and weak indicator
density on maturity and priority assessments.

Phase 2 upgrade: Replace synthetic training with real school historical data
from the database once 2+ full SBM cycles are recorded.
"""

import logging
import numpy as np
from sklearn.tree import DecisionTreeClassifier

logger = logging.getLogger(__name__)


# ── Training Data ───────────────────────────────────────────────────────────────

def _maturity_training_data():
    """
    Synthetic training data for maturity level classification.

    Features (3):
      [0] score        — SBM dimension/overall percentage (0–100)
      [1] slope        — score change per assessment cycle (+ve = improving)
      [2] weak_ratio   — fraction of indicators rated < 2.5 (0.0–1.0)

    Labels: 'Beginning' | 'Developing' | 'Maturing' | 'Advanced'

    Key nuance vs. hardcoded rules:
      - score=78%, slope=-7, weak_ratio=0.5  → Maturing  (not Advanced — declining)
      - score=24%, slope=+12, weak_ratio=0.2 → Beginning  (but noted as recovering)
      - score=51%, week_ratio=0.6            → Developing (many weak indicators drag it down)
    """
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

    # ── Beginning (0–25) ───────────────────────────────────────────
    add(200, (0,  25),  (-8,  3),  (0.65, 1.00), "Beginning")   # typical
    add(30,  (0,  10),  (-10, -2), (0.85, 1.00), "Beginning")   # critical decline
    add(20,  (20, 25),  (3,   8),  (0.50, 0.75), "Beginning")   # recovering but still Beginning

    # ── Developing (26–50) ─────────────────────────────────────────
    add(200, (26, 50),  (-5,  6),  (0.30, 0.70), "Developing")  # typical
    add(30,  (26, 35),  (-5,  0),  (0.55, 0.80), "Developing")  # low-end, struggling
    add(20,  (45, 50),  (5,  10),  (0.20, 0.40), "Developing")  # high-end, positive trend

    # ── Maturing (51–75) ───────────────────────────────────────────
    add(200, (51, 75),  (-3,  8),  (0.10, 0.40), "Maturing")    # typical
    add(30,  (51, 60),  (-5,  0),  (0.35, 0.55), "Maturing")    # low-end, watch out
    add(20,  (70, 75),  (5,  10),  (0.05, 0.15), "Maturing")    # approaching Advanced

    # ── Advanced (76–100) ──────────────────────────────────────────
    add(200, (76, 100), (0,  10),  (0.00, 0.20), "Advanced")    # typical
    add(30,  (76, 80),  (-4,  0),  (0.15, 0.30), "Advanced")    # borderline but still Advanced
    add(20,  (90, 100), (5,  12),  (0.00, 0.05), "Advanced")    # exemplary

    # ── Cross-boundary nuance: regression risk ──────────────────────
    # High score + negative trend + many weak indicators → Maturing (not Advanced)
    add(50,  (76, 83),  (-10, -4), (0.40, 0.65), "Maturing")

    # Low score + strong positive trend → still in category, but "best case"
    add(20,  (22, 25),  (8,  15),  (0.30, 0.50), "Beginning")
    add(20,  (47, 50),  (8,  15),  (0.10, 0.30), "Developing")

    # ── Borderline score + heavy weak indicators → pull down one level ──
    add(30,  (51, 56),  (-2,  2),  (0.55, 0.80), "Developing")  # 51–56 but weak

    return np.array(X, dtype=float), np.array(y)


def _priority_training_data():
    """
    Synthetic training data for dimension intervention priority.

    Features (5):
      [0] score         — dimension percentage (0–100)
      [1] weighted_gap  — gap_from_avg × dimension_weight (can be negative)
      [2] weight        — DepEd dimension weight (0.9 | 1.0 | 1.2)
      [3] weak_count    — # indicators rated < 2.5 in this dimension
      [4] slope         — school-level improvement slope this cycle

    Labels: 'high' | 'medium' | 'low'

    Key nuance vs. hardcoded rules:
      - score=57%, weighted_gap=18, weight=1.2 → high  (Dim 1 or 2 with big gap)
      - score=70%, weighted_gap=6,  slope=-5   → medium (declining, needs watch)
      - score=80%, weighted_gap=-3, weak_count=0 → low (healthy dimension)
    """
    rng = np.random.default_rng(42)
    X, y = [], []

    def add(n, s_rng, wg_rng, w_rng, wc_rng, sl_rng, label):
        for _ in range(n):
            X.append([
                rng.uniform(*s_rng),
                rng.uniform(*wg_rng),
                rng.uniform(*w_rng),
                int(rng.integers(*wc_rng)),
                rng.uniform(*sl_rng),
            ])
            y.append(label)

    # ── HIGH priority ───────────────────────────────────────────────
    # Low score + large gap + high-weight dimension + many weak indicators
    add(250, (0,  45),  (15, 45),  (1.0, 1.2), (7, 20), (-8,  0), "high")
    # Medium score but critical high-weight dimension (Dim 1 or 2)
    add(60,  (46, 58),  (12, 28),  (1.2, 1.2), (5, 15), (-5,  2), "high")
    # Any dimension with very weak indicators and decline
    add(40,  (0,  38),  (10, 22),  (0.9, 1.0), (10, 20),(-6,  0), "high")
    # Regression case: was good, now declining sharply
    add(30,  (55, 75),  (8,  20),  (1.0, 1.2), (5, 12), (-8, -3), "high")

    # ── MEDIUM priority ─────────────────────────────────────────────
    add(250, (40, 70),  (5,  18),  (0.9, 1.2), (2,  8), (-3,  5), "medium")
    # High-weight dimension but acceptable score
    add(60,  (55, 72),  (3,  12),  (1.2, 1.2), (3,  8), (-2,  4), "medium")
    # Lower score but improving strongly
    add(40,  (30, 52),  (5,  15),  (0.9, 1.0), (3,  7), (0,   6), "medium")
    # Good score but slight decline (watch-list)
    add(30,  (62, 78),  (2,  8),   (0.9, 1.2), (1,  5), (-4, -1), "medium")

    # ── LOW priority ────────────────────────────────────────────────
    add(250, (65, 100), (-12, 5),  (0.9, 1.2), (0,  3), (0,  10), "low")  # strong dimension
    add(60,  (55, 78),  (-5,  3),  (0.9, 1.0), (0,  2), (3,  10), "low")  # improving well
    add(40,  (72, 100), (-15, 0),  (0.9, 1.2), (0,  1), (5,  12), "low")  # exemplary
    # Negative gap (above avg) always low priority
    add(30,  (60, 100), (-20, -5), (0.9, 1.2), (0,  4), (-2, 10), "low")

    return np.array(X, dtype=float), np.array(y)


# ── Classifier Class ────────────────────────────────────────────────────────────

class SBMDecisionTreeClassifier:
    """
    Wraps two trained DecisionTreeClassifier models for SBM analysis.

    Usage:
        clf = get_classifier()
        maturity = clf.predict_maturity(score=72.5, slope=-3.0, weak_ratio=0.4)
        priority = clf.predict_priority(score=55.0, weighted_gap=18.0,
                                        weight=1.2, weak_count=7, slope=-2.0)
    """

    MATURITY_ORDER = ["Beginning", "Developing", "Maturing", "Advanced"]

    def __init__(self):
        self.maturity_model = DecisionTreeClassifier(
            max_depth=7,
            min_samples_leaf=5,
            criterion="gini",
            class_weight="balanced",
            random_state=42,
        )
        self.priority_model = DecisionTreeClassifier(
            max_depth=8,
            min_samples_leaf=3,
            criterion="gini",
            class_weight="balanced",
            random_state=42,
        )
        self._trained = False
        self._train()

    def _train(self):
        logger.info("[SBM-ML] Training Decision Tree classifiers on synthetic SBM data...")

        X_m, y_m = _maturity_training_data()
        self.maturity_model.fit(X_m, y_m)

        X_p, y_p = _priority_training_data()
        self.priority_model.fit(X_p, y_p)

        self._trained = True
        logger.info(
            "[SBM-ML] Maturity DT ready — depth=%d, nodes=%d | "
            "Priority DT ready — depth=%d, nodes=%d",
            self.maturity_model.get_depth(),
            self.maturity_model.tree_.node_count,
            self.priority_model.get_depth(),
            self.priority_model.tree_.node_count,
        )

    # ── Maturity prediction ─────────────────────────────────────────

    def predict_maturity(
        self,
        score: float,
        slope: float = 0.0,
        weak_ratio: float = 0.0,
    ) -> str:
        """
        Predicts maturity label using Decision Tree.

        Args:
            score:      SBM percentage (0–100).
            slope:      Score change per cycle from linear regression.
                        Pass 0.0 if no historical data available.
            weak_ratio: Fraction of this dimension's indicators rated < 2.5.
                        Pass 0.0 if indicator-level data is unavailable.

        Returns:
            One of: 'Beginning', 'Developing', 'Maturing', 'Advanced'
        """
        x = np.array([[float(score), float(slope), float(weak_ratio)]])
        return str(self.maturity_model.predict(x)[0])

    def predict_maturity_with_confidence(
        self,
        score: float,
        slope: float = 0.0,
        weak_ratio: float = 0.0,
    ) -> dict:
        """
        Returns the maturity prediction along with class probabilities.
        Useful for surfacing borderline cases to the School Head.

        Returns:
            {
              "maturity":      "Maturing",
              "confidence":    0.82,
              "probabilities": {"Beginning": 0.01, "Developing": 0.17,
                                "Maturing": 0.82, "Advanced": 0.0}
            }
        """
        x = np.array([[float(score), float(slope), float(weak_ratio)]])
        proba   = self.maturity_model.predict_proba(x)[0]
        classes = self.maturity_model.classes_
        label   = str(classes[int(np.argmax(proba))])
        return {
            "maturity":      label,
            "confidence":    round(float(np.max(proba)), 3),
            "probabilities": {
                c: round(float(p), 3)
                for c, p in zip(classes, proba)
            },
        }

    # ── Priority prediction ─────────────────────────────────────────

    def predict_priority(
        self,
        score: float,
        weighted_gap: float,
        weight: float,
        weak_count: int = 0,
        slope: float = 0.0,
    ) -> str:
        """
        Predicts intervention priority for a single dimension.

        Args:
            score:        Dimension percentage (0–100).
            weighted_gap: gap_from_avg × dimension_weight (can be negative
                          if dimension is performing above average).
            weight:       DepEd dimension weight — 0.9, 1.0, or 1.2.
            weak_count:   Number of indicators in this dimension rated < 2.5.
            slope:        School-level score change per cycle (from forecast).

        Returns:
            One of: 'high', 'medium', 'low'
        """
        x = np.array([[
            float(score),
            float(weighted_gap),
            float(weight),
            int(weak_count),
            float(slope),
        ]])
        return str(self.priority_model.predict(x)[0])


# ── Singleton ────────────────────────────────────────────────────────────────────

_instance: SBMDecisionTreeClassifier | None = None


def get_classifier() -> SBMDecisionTreeClassifier:
    """
    Returns the lazily-initialized singleton classifier.
    The models are trained on first call and reused for all subsequent calls.
    """
    global _instance
    if _instance is None:
        _instance = SBMDecisionTreeClassifier()
    return _instance
