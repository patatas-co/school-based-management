import os, sys
from pathlib import Path

# Add ml directory to sys.path
ml_dir = Path(r"c:\xampp\htdocs\sbm\ml")
sys.path.append(str(ml_dir))

from recommendation_engine import generate_recommendations

# Mock analysis data
analysis = {
    "gap_analysis": {
        "average_score": 75.0,
        "overall_maturity": "Developing",
        "weakest_dimensions": [
            {"dimension_name": "Curriculum", "score": 60.0, "maturity": "Emerging"}
        ]
    },
    "weak_indicators": {
        "by_dimension": {
            "1": [{"code": "1.1", "text": "Indicator 1.1", "rating": 1.5}]
        }
    },
    "by_rating": {
        "1": [{"code": "1.1", "text": "Indicator 1.1", "dimension_name": "Curriculum"}],
        "2": []
    },
    "comment_summary": {
        "top_topics": ["ICT"],
        "has_urgent": False
    }
}

print("Testing generate_recommendations with rule_based backend...")
result = generate_recommendations(analysis, "Test School", "2024-2025", backend="rule_based")
print("Success!")
print(f"Backend used: {result['backend_used']}")
print(f"Recommendations length: {len(result['recommendations'])}")
if result.get("error"):
    print(f"Error: {result['error']}")
    sys.exit(1)

print("\nTesting prompt building...")
# This will trigger _build_prompt which had the NameError
from recommendation_engine import _build_prompt
try:
    prompt = _build_prompt(analysis, "Test School", "2024-2025")
    print("Prompt built successfully!")
except NameError as e:
    print(f"Caught NameError: {e}")
    sys.exit(1)
except Exception as e:
    print(f"Caught unexpected error: {e}")
    sys.exit(1)

print("\nAll local tests passed!")
