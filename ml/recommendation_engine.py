"""
recommendation_engine.py
Generates actionable SIP recommendations using an LLM.
Supports: local Llama 3.2 (via Ollama) or OpenAI GPT-4.
Falls back to rule-based templates if LLM unavailable.
"""
import os
import json
import textwrap
from score_analyzer import DIMENSION_NAMES

# ── Rule-based fallback templates (Year 1) ──────────────────────
TEMPLATES = {
    "bullying": (
        "Strengthen the school's anti-bullying program. "
        "Form a dedicated Child Protection Committee task force, conduct "
        "quarterly awareness sessions, and ensure incident reporting is "
        "accessible to all learners."
    ),
    "ict_resources": (
        "Address ICT resource gaps by exploring partnerships with the LGU, "
        "alumni associations, or private sector donors. Prepare an ICT "
        "development plan aligned with the SIP and MOOE utilization."
    ),
    "facilities": (
        "Prioritize infrastructure repair through the Annual Procurement "
        "Plan. Coordinate with the barangay and LGU for supplemental "
        "funding. Document all facility gaps in the SBM annex."
    ),
    "teacher_quality": (
        "Design a targeted LAC session calendar addressing identified "
        "instructional gaps. Nominate teachers for SDO-led training and "
        "track participation in the IPCR."
    ),
    "safety": (
        "Update and re-practice the school DRRM plan. Conduct a safety "
        "audit and address all flagged hazards before the next grading "
        "period. Involve SSG/SPG in drills."
    ),
    "mental_health": (
        "Implement a structured mental wellness program with a guidance "
        "counselor. Establish a referral pathway for learners in crisis "
        "and document all interventions."
    ),
}


def _build_prompt(analysis: dict, school_name: str, sy_label: str) -> str:
    """
    Constructs a structured prompt for the LLM.
    Prioritizes indicator rating data over stakeholder remarks.
    """
    gap      = analysis.get("gap_analysis", {})
    weak     = analysis.get("weak_indicators", {})
    comments = analysis.get("comment_summary", {})
    forecast = analysis.get("forecast", {})
    by_rating = analysis.get("by_rating", {})

    weakest_dims = gap.get("weakest_dimensions", [])
    top_topics   = comments.get("top_topics", [])
    urgent       = comments.get("has_urgent", False)

    dim_lines = "\n".join(
        f"  - {d['dimension_name']} ({d['score']}%, {d['maturity']})"
        for d in weakest_dims[:3]
    )
    
    # Detail weak indicators (Priority 1 & 2)
    weak_ind_list = []
    by_dim = weak.get("by_dimension", {})
    for dim_no, inds in by_dim.items():
        dim_name = DIMENSION_NAMES.get(int(dim_no), f"Dimension {dim_no}")
        for ind in inds[:3]:  # Top 3 per dim
            weak_ind_list.append(f"  - [{ind['code']}] {ind['text']} (Rating: {ind['rating']:.1f})")

    # Also pull from by_rating if available (more complete source)
    if by_rating:
        rating1 = by_rating.get(1, by_rating.get("1", []))
        rating2 = by_rating.get(2, by_rating.get("2", []))
        if rating1 or rating2:
            weak_ind_list = []  # Reset and use by_rating as it's more accurate
            for ind in rating1:
                weak_ind_list.append(
                    f"  - [{ind.get('code','?')}] {ind.get('text','')} "
                    f"(Rating: 1 — Not Yet Manifested)"
                )
            for ind in rating2:
                weak_ind_list.append(
                    f"  - [{ind.get('code','?')}] {ind.get('text','')} "
                    f"(Rating: 2 — Emerging)"
                )

    weak_ind_lines = "\n".join(weak_ind_list[:15])  # Allow up to 15

    # Build rating distribution summary
    # (by_rating is already defined from analysis.get("by_rating", {}) on line 57)
    
    topic_line = ", ".join(top_topics[:5]) if top_topics else "none identified"
    trend_line = (
        f"Trend: {forecast.get('trend','unknown')} "
        f"(forecast: {forecast.get('forecast','N/A')}%)"
        if forecast.get("forecast") else ""
    )

    prompt = textwrap.dedent(f"""
    You are an educational improvement specialist helping a Philippine
    public school prepare its School Improvement Plan (SIP) per
    DepEd Order No. 007, s. 2024.

    School: {school_name}
    School Year: {sy_label}
    Overall SBM Score: {gap.get('average_score', 'N/A')}%
    Maturity Level: {gap.get('overall_maturity', 'N/A')}
    {trend_line}

    IMPORTANT: Base your recommendations PRIMARILY on the weak indicator
    ratings below. Stakeholder remarks are secondary context only.

    Weakest Dimensions (by score):
    {dim_lines if dim_lines else "  (none identified)"}

    ALL Weak Indicators Rated 1 or 2 by teachers/school head:
    {weak_ind_lines if weak_ind_lines else "  (none identified)"}

    Stakeholder Remarks Context (secondary — do not base all recommendations on this):
    Key themes mentioned: {topic_line}
    Urgent issues flagged: {"YES" if urgent else "None"}

    Write 4 to 6 specific, actionable recommendations for the School Head.

    Instructions:
    1. Address the weak indicators and weakest dimensions FIRST. Each
       recommendation must reference a specific indicator code (e.g. 5.2, 3.2).
    2. Suggest concrete DepEd-aligned actions (LAC sessions, SGC meetings,
       DRRM drills, MOOE planning, LGU partnerships, etc.).
    3. Only add a stakeholder/remarks-based recommendation at the end if
       the themes are relevant and not already covered above.
    4. Do NOT write recommendations only about remarks topics if the
       indicator ratings show different weaknesses.

    Format:
    [Assessment Overview]
    (1-sentence summary mentioning the overall score and top weak dimensions)

    [Priority Recommendations]
    1. [Indicator X.X] (Actionable step...)
    2. [Indicator X.X] (Actionable step...)
    3. [Indicator X.X] (Actionable step...)

    [Stakeholder Focus]
    (Only if remarks reveal something not covered by indicator data above)
    """).strip()

    return prompt


def _call_ollama(prompt: str, model: str = "llama3.2:3b") -> str:
    """Call local Ollama server. Requires `ollama serve` running."""
    import ollama  # lazy import — only needed if using local LLM
    response = ollama.chat(
        model=model,
        messages=[{"role": "user", "content": prompt}],
        options={"temperature": 0.4, "num_predict": 512},
    )
    return response["message"]["content"].strip()


def _call_openai(prompt: str, model: str = "gpt-4o-mini") -> str:
    """Call OpenAI API. Requires OPENAI_API_KEY env var."""
    from openai import OpenAI
    client = OpenAI(api_key=os.getenv("OPENAI_API_KEY"))
    response = client.chat.completions.create(
        model=model,
        messages=[{"role": "user", "content": prompt}],
        temperature=0.4,
        max_tokens=600,
    )
    return response.choices[0].message.content.strip()

def _call_groq(prompt: str) -> str:
    """Call Groq API using OpenAI-compatible SDK."""
    from openai import OpenAI

    api_key = os.getenv("GROQ_API_KEY")
    if not api_key:
        raise RuntimeError("GROQ_API_KEY is missing. Set it in the ML service environment.")

    model = os.getenv("GROQ_MODEL", "llama-3.3-70b-versatile")

    client = OpenAI(
        api_key=api_key,
        base_url="https://api.groq.com/openai/v1"
    )
    response = client.chat.completions.create(
        model=model,
        messages=[{"role": "user", "content": prompt}],
        temperature=0.4,
        max_tokens=800
    )
    return response.choices[0].message.content.strip()


def _rule_based_fallback(analysis: dict) -> str:
    """
    Year 1 fallback: assembles recommendations from templates
    based on detected topics and weak dimensions.
    """
    gap    = analysis.get("gap_analysis", {})
    topics = analysis.get("comment_summary", {}).get("top_topics", [])
    recs   = []

    for topic in topics[:3]:
        if topic in TEMPLATES:
            recs.append(f"- {TEMPLATES[topic]}")

    # Add dimension-based recommendations if no topic match
    if not recs:
        for d in gap.get("weakest_dimensions", [])[:3]:
            dim_name = d["dimension_name"]
            score    = d["score"]
            recs.append(
                f"- Focus on **{dim_name}** (currently {score}%, "
                f"{d['maturity']} level). Develop targeted action plans "
                f"with measurable targets for the next assessment period."
            )

    if not recs:
        recs.append(
            "- Continue current improvement trajectory. Monitor all "
            "dimensions quarterly and update the SIP accordingly."
        )

    return "\n".join(recs)


def generate_recommendations(
    analysis: dict,
    school_name: str,
    sy_label: str,
    backend: str = "rule_based",  # "rule_based" | "ollama" | "openai"
) -> dict:
    """
    Main entry point called by Flask.
    Returns generated text + metadata.
    """
    prompt = _build_prompt(analysis, school_name, sy_label)
    error  = None
    text   = ""

    try:
        if backend == "ollama":
            text = _call_ollama(prompt)
        elif backend == "openai":
            text = _call_openai(prompt)
        # Default/Always fall back to Groq if not specified or explicitly requested
        else:
            text = _call_groq(prompt)
            backend = "groq"
            
    except Exception as e:
        import logging
        import traceback
        tb = traceback.format_exc()
        logging.error(f"Error generating recommendations ({backend}): {e}\n{tb}")
        print(f"[ML ERROR] {backend} failed: {e}\n{tb}", flush=True)
        error = str(e)
        
        # If the failure wasn't Groq, try Groq as a final attempt
        if backend != "groq":
            try:
                text = _call_groq(prompt)
                backend = "groq_fallback"
                error = None # Recovered
            except:
                text = _rule_based_fallback(analysis)
                backend = f"rule_based_error (was: {backend})"
        else:
            text = _rule_based_fallback(analysis)
            backend = f"rule_based_error (was: {backend})"

    return {
        "recommendations": text,
        "backend_used": backend,
        "error": error,
        "prompt_chars": len(prompt),
    }