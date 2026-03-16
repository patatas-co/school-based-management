"""
app.py  —  SBM ML microservice
Run: python app.py   (defaults to port 5000)
"""
import os, json, logging
from pathlib import Path
from flask import Flask, request, jsonify
from dotenv import load_dotenv

from comment_analyzer      import batch_analyze, analyze_comment
from score_analyzer        import full_analysis
from recommendation_engine import generate_recommendations

BASE_DIR = Path(__file__).resolve().parent
# Load local ml/.env first, then optional project-root .env.
# This makes local dev less error-prone when the server is started
# from a different working directory.
load_dotenv(BASE_DIR / ".env")
load_dotenv(BASE_DIR.parent / ".env")
app = Flask(__name__)
logging.basicConfig(level=logging.INFO)

ML_SECRET = os.getenv("ML_SECRET", "sbm-ml-secret-change-in-production")
LLM_BACKEND = os.getenv("LLM_BACKEND", "rule_based")  # "rule_based" / "ollama" / "openai" / "groq"


def auth(req) -> bool:
    return req.headers.get("X-ML-Secret") == ML_SECRET


@app.route("/health")
def health():
    return jsonify({
        "status": "ok",
        "backend": LLM_BACKEND,
        "groq_key_present": bool(os.getenv("GROQ_API_KEY")),
    })


@app.route("/api/analyze/comments", methods=["POST"])
def analyze_comments():
    if not auth(request):
        return jsonify({"error": "unauthorized"}), 401
    data = request.get_json(force=True)
    comments = data.get("comments", [])
    result = batch_analyze(comments)
    return jsonify(result)


@app.route("/api/analyze/scores", methods=["POST"])
def analyze_scores():
    if not auth(request):
        return jsonify({"error": "unauthorized"}), 401
    data   = request.get_json(force=True)
    result = full_analysis(data)
    return jsonify(result)


@app.route("/api/recommend", methods=["POST"])
def recommend():
    if not auth(request):
        return jsonify({"error": "unauthorized"}), 401
    data   = request.get_json(force=True)
    result = generate_recommendations(
        analysis    = data.get("analysis", {}),
        school_name = data.get("school_name", "School"),
        sy_label    = data.get("sy_label", "2024-2025"),
        backend     = LLM_BACKEND,
    )
    return jsonify(result)


@app.route("/api/full_pipeline", methods=["POST"])
def full_pipeline():
    """
    Single endpoint called after assessment finalization.
    PHP sends everything; Python returns everything.
    """
    if not auth(request):
        return jsonify({"error": "unauthorized"}), 401

    data = request.get_json(force=True)

    # Step 1: Score analysis
    score_result = full_analysis({
        "dim_scores":  data.get("dim_scores", {}),
        "indicators":  data.get("indicators", []),
        "history":     data.get("history", []),
    })

    # Step 2: Comment analysis
    comment_result = batch_analyze(data.get("comments", []))

    # Step 3: Combine and generate recommendations
    merged_analysis = {**score_result, "comment_summary": comment_result}
    recs = generate_recommendations(
        analysis    = merged_analysis,
        school_name = data.get("school_name", "School"),
        sy_label    = data.get("sy_label", "2024-2025"),
        backend     = LLM_BACKEND,
    )

    return jsonify({
        "score_analysis":   score_result,
        "comment_analysis": comment_result,
        "recommendations":  recs,
    })


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=False)