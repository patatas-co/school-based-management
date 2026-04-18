"""Quick smoke test for the new Decision Tree classifiers."""
from ml_classifier import get_classifier
from score_analyzer import full_analysis

clf = get_classifier()

print("=" * 60)
print("MATURITY DECISION TREE TESTS")
print("=" * 60)

maturity_tests = [
    (90.0,  5.0,  0.02, "exemplary school"),
    (78.0, -7.0,  0.50, "high score but declining + many weak"),
    (55.0,  3.0,  0.25, "typical Maturing"),
    (51.0,  0.0,  0.65, "Maturing score but heavy weak indicators"),
    (35.0,  0.0,  0.55, "typical Developing"),
    (15.0, -3.0,  0.85, "critical Beginning"),
    (24.0, 12.0,  0.30, "Beginning but recovering fast"),
]

for score, slope, wr, note in maturity_tests:
    r = clf.predict_maturity_with_confidence(score, slope, wr)
    print(
        f"  score={score:5.1f} slope={slope:+5.1f} weak={wr:.2f}"
        f"  =>  {r['maturity']:12s}  conf={r['confidence']:.2f}   [{note}]"
    )

print()
print("=" * 60)
print("PRIORITY DECISION TREE TESTS")
print("=" * 60)

priority_tests = [
    (40.0, 20.0, 1.2,  8, -3.0, "Dim1: low score + big gap + declining"),
    (57.0, 18.0, 1.2,  6, -1.0, "Dim1 medium score + high-weight + gap"),
    (65.0,  6.0, 1.0,  3,  2.0, "medium gap, stable"),
    (80.0, -5.0, 0.9,  0,  4.0, "above avg, improving strongly"),
    (72.0,  7.0, 1.2,  5, -5.0, "high-weight dim, declining sharply"),
]

for score, wgap, weight, wc, slope, note in priority_tests:
    p = clf.predict_priority(score, wgap, weight, wc, slope)
    print(
        f"  score={score:.1f} wgap={wgap:.1f} w={weight} wc={wc} slope={slope:+.1f}"
        f"  =>  priority={p:8s}   [{note}]"
    )

print()
print("=" * 60)
print("FULL PIPELINE TEST (score_analyzer.full_analysis)")
print("=" * 60)

payload = {
    "dim_scores": {
        "1": 72.5,  # Curriculum
        "2": 85.0,  # Learning Env
        "3": 55.0,  # Leadership
        "4": 48.0,  # Accountability
        "5": 61.0,  # HRD
        "6": 90.0,  # Finance
    },
    "indicators": [
        {"dimension_no": 1, "indicator_code": "1.1", "indicator_text": "Lesson planning", "avg_rating": 2.1},
        {"dimension_no": 1, "indicator_code": "1.2", "indicator_text": "SLM usage",       "avg_rating": 1.8},
        {"dimension_no": 1, "indicator_code": "1.3", "indicator_text": "Differentiation", "avg_rating": 3.5},
        {"dimension_no": 4, "indicator_code": "4.1", "indicator_text": "SIP alignment",   "avg_rating": 2.0},
        {"dimension_no": 4, "indicator_code": "4.2", "indicator_text": "Action plan",     "avg_rating": 1.5},
        {"dimension_no": 3, "indicator_code": "3.1", "indicator_text": "SGC meetings",    "avg_rating": 3.2},
    ],
    "by_rating": {},
    "history": [
        {"cycle_year": 2022, "overall_score": 60.0},
        {"cycle_year": 2023, "overall_score": 65.5},
        {"cycle_year": 2024, "overall_score": 68.5},
    ],
}

result = full_analysis(payload)
gap = result["gap_analysis"]
forecast = result["forecast"]

print(f"\nOverall score:   {gap['average_score']}%")
print(f"Overall maturity: {gap['overall_maturity']}")
print(f"Forecast: {forecast['forecast']}% ({forecast['trend']}, slope={forecast['slope_per_cycle']}/cycle)")
print()
print("Dimension breakdown:")
for d in gap["all_dimensions"]:
    print(
        f"  [{d['priority'].upper():6s}] Dim {d['dimension_no']} - {d['dimension_name'][:35]:35s}"
        f"  score={d['score']:5.1f}%  maturity={d['maturity']:12s}"
        f"  conf={d['maturity_confidence']:.2f}  weak={d['weak_count']}  wgap={d['weighted_gap']:+.1f}"
    )

print()
print("HIGH priority dimensions:")
for d in gap["weakest_dimensions"]:
    print(f"  -> {d['dimension_name']} ({d['score']}% / {d['maturity']})")

print()
print("All tests passed!")
