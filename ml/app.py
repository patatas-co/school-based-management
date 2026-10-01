"""
app.py  —  SBM ML microservice
Run: python app.py   (defaults to port 5001)
"""
import os, json, logging
import numpy as np
from pathlib import Path
from flask import Flask, request, jsonify
from dotenv import load_dotenv

from comment_analyzer      import batch_analyze, analyze_comment
from score_analyzer        import full_analysis
from recommendation_engine import generate_recommendations, generate_ip_field, extract_form_from_document
from teacher_outlier import (
    build_features, card_eligibility, load_saved_model, score_matrix,
    FEATURE_NAMES, DEFAULT_CONTAMINATION,
)

BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / ".env", override=True)
load_dotenv(BASE_DIR.parent / ".env", override=True)

from ml_classifier import dimension_model_metadata, predict_dimension_outlook
# Debug: confirm key loaded
import sys
_key = os.getenv("GROQ_API_KEY", "")
_backend = os.getenv("LLM_BACKEND", "rule_based")
print(f"[STARTUP] LLM_BACKEND={_backend}, GROQ_API_KEY={'SET ('+_key[:8]+'...)' if _key else 'MISSING'}", flush=True)
app = Flask(__name__)
logging.basicConfig(level=logging.INFO)

ML_SECRET = os.getenv("ML_SECRET", "")
LLM_BACKEND = os.getenv("LLM_BACKEND", "rule_based")  # "rule_based" / "ollama" / "openai" / "groq"


def auth(req) -> bool:
    return req.headers.get("X-ML-Secret") == ML_SECRET


@app.route("/api/teacher_outliers", methods=["POST"])
def teacher_outliers():
    if not auth(request):
        return jsonify({"error": "unauthorized"}), 401
    data = request.get_json(force=True) or {}
    records = data.get("teacher_cycles", [])
    if not isinstance(records, list) or len(records) < 3:
        return jsonify({"available": False, "reason": "too_few_teachers", "outliers": []})
    try:
        matrix, rows = build_features(records)
        if len(rows) < 3:
            return jsonify({"available": False, "reason": "too_few_teacher_cycles", "outliers": []})
        model_available = True
        try:
            bundle = load_saved_model()
            scores, flagged = score_matrix(bundle, matrix)
        except FileNotFoundError:
            logging.warning("Teacher outlier model is missing; applying deterministic rules")
            model_available = False
            scores = np.full(len(rows), np.nan)
            flagged = np.zeros(len(rows), dtype=bool)
        outliers = []
        for row, score, is_flagged in zip(rows, scores, flagged):
            eligible, gate_rule = card_eligibility(
                row, True if not model_available else bool(is_flagged)
            )
            if eligible:
                outliers.append({
                    "teacher_id": row["teacher_id"],
                    "cycle_id": row["cycle_id"],
                    "anomaly_score": None if not model_available else float(score),
                    "anomaly_type": row["anomaly_type"],
                    "supporting_numbers": row["features"],
                    "gate_rule": gate_rule,
                })
        return jsonify({
            "available": True,
            "model_available": model_available,
            "contamination": DEFAULT_CONTAMINATION,
            "feature_names": FEATURE_NAMES,
            "outliers": outliers,
        })
    except Exception as exc:
        logging.exception("Teacher outlier scoring failed")
        return jsonify({"available": False, "reason": "scoring_failed", "error": str(exc)}), 503


@app.route("/health")
def health():
    model_metadata = dimension_model_metadata()
    return jsonify({
        "status": "ok",
        "backend": LLM_BACKEND,
        "groq_key_present": bool(os.getenv("GROQ_API_KEY")),
        **model_metadata,
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
    analysis = dict(data.get("analysis", {}))
    gap_analysis = dict(analysis.get("gap_analysis", {}))
    dimension_rows = analysis.get("dimension_rows")
    if not isinstance(dimension_rows, list):
        dimension_rows = gap_analysis.get("all_dimensions", [])
    outlook = predict_dimension_outlook(dimension_rows)
    analysis["dimension_outlook"] = outlook
    result = generate_recommendations(
        analysis    = analysis,
        school_name = data.get("school_name", "School"),
        sy_label    = data.get("sy_label", "2024-2025"),
        backend     = LLM_BACKEND,
    )
    return jsonify(result)


@app.route("/api/generate_ip_field", methods=["POST"])
def generate_ip_field_route():
    if not auth(request):
        return jsonify({"error": "unauthorized"}), 401
    data   = request.get_json(force=True)
    result = generate_ip_field(
        field_type      = data.get("field_type", ""),
        indicator_code  = data.get("indicator_code", ""),
        indicator_text  = data.get("indicator_text", ""),
        dimension_name  = data.get("dimension_name", ""),
        snippet         = data.get("snippet", ""),
        backend         = LLM_BACKEND,
    )
    return jsonify(result)


@app.route("/api/parse_form_document", methods=["POST"])
def parse_form_document():
    if not auth(request):
        return jsonify({"error": "unauthorized"}), 401
    data   = request.get_json(force=True)
    result = extract_form_from_document(
        raw_text = data.get("text", ""),
        backend  = LLM_BACKEND,
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
        "by_rating":   data.get("by_rating", {}),
        "history":     data.get("history", []),
    })

    # Step 2: Comment analysis
    comment_result = batch_analyze(data.get("comments", []))

    # Step 3: Combine — pass by_rating and history explicitly for the prompt
    merged_analysis = {
        **score_result,
        "comment_summary": comment_result,
        "by_rating":       data.get("by_rating", {}),
        "history":         data.get("history", []),
    }
    dimension_rows = []
    for dimension_no, details in (data.get("dim_details", {}) or {}).items():
        dimension_rows.append({
            "dimension_no": dimension_no,
            "dimension_name": details.get("dimension_name", ""),
            "score": details.get("percentage"),
        })
    merged_analysis["dimension_outlook"] = predict_dimension_outlook(dimension_rows)

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
    app.run(host="127.0.0.1", port=5001, debug=True)
