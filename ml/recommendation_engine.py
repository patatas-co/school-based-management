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
    Includes historical trend data and forces indicator code references.
    """
    gap       = analysis.get("gap_analysis", {})
    weak      = analysis.get("weak_indicators", {})
    comments  = analysis.get("comment_summary", {})
    forecast  = analysis.get("forecast", {})
    by_rating = analysis.get("by_rating", {})
    history   = analysis.get("history", [])

    weakest_dims = gap.get("weakest_dimensions", [])
    top_topics   = comments.get("top_topics", [])
    urgent       = comments.get("has_urgent", False)

    # --- Historical context block ---
    if history and len(history) >= 2:
        scores    = [float(h.get("overall_score", 0)) for h in history]
        change    = round(scores[-1] - scores[-2], 1)
        direction = "improved" if change > 0 else ("declined" if change < 0 else "stable")
        history_line = (
            f"Historical trend: School has {direction} by {abs(change)}% "
            f"from last cycle ({scores[-2]}% → {scores[-1]}%). "
            f"All recorded scores: {', '.join(str(round(s,1)) for s in scores)}"
        )
    elif history and len(history) == 1:
        history_line = (
            f"Historical trend: One prior cycle recorded "
            f"({float(history[0].get('overall_score', 0))}%). "
            f"Current cycle is the second assessment."
        )
    else:
        history_line = "Historical trend: First assessment cycle — no prior data available."

    # --- Forecast line ---
    trend_line = (
        f"Score forecast for next cycle: {forecast.get('forecast', 'N/A')}% "
        f"({forecast.get('trend', 'unknown')} trajectory)"
        if forecast.get("forecast") else ""
    )

    # --- Dimension summary ---
    dim_lines = "\n".join(
        f"  - {d['dimension_name']} ({d['score']}%, {d['maturity']})"
        for d in weakest_dims[:3]
    )

    # --- Weak indicator list (by_rating is most accurate source) ---
    weak_ind_list = []
    if by_rating:
        rating1 = by_rating.get(1, by_rating.get("1", []))
        rating2 = by_rating.get(2, by_rating.get("2", []))
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

    # Fallback to by_dimension if by_rating is empty
    if not weak_ind_list:
        by_dim = weak.get("by_dimension", {})
        for dim_no, inds in by_dim.items():
            for ind in inds[:3]:
                weak_ind_list.append(
                    f"  - [{ind['code']}] {ind['text']} "
                    f"(Rating: {ind['rating']:.1f})"
                )

    weak_ind_lines = "\n".join(weak_ind_list[:15])
    topic_line     = ", ".join(top_topics[:5]) if top_topics else "none identified"

    prompt = textwrap.dedent(f"""
    You are an SBM improvement specialist for Philippine public schools, writing
    directly to a School Head in a helpful, conversational style.

    CRITICAL STYLE RULES — follow these exactly:
    - Write in natural prose like a knowledgeable colleague giving advice.
    - Use **bold inline headers** to introduce each topic area (e.g. "**Strengthen curriculum delivery**").
    - After each bold header, write 1-2 short sentences of context, then use bullet points for specific actions.
    - Keep paragraphs SHORT — 2-3 sentences max before a new topic.
    - Do NOT use rigid section headers like "[Assessment Overview]" or "[Priority Recommendations]".
    - Do NOT number your recommendations (1. 2. 3.). Use bold topic headers and bullets instead.
    - Weave indicator codes naturally into sentences (e.g. "For indicator 2.1, consider...").
    - End with a brief closing statement summarizing the single biggest priority. Do NOT end with a question.
    - The tone should be warm, professional, and direct — not bureaucratic or robotic.

    Here is an example of the EXACT style to follow:

    Here are some targeted suggestions based on your school's current SBM data:

    **Focus on curriculum and teaching quality** Your scores in Dimension 1 suggest room to grow in instructional delivery. This is common in early assessment cycles.

    - Consider scheduling monthly LAC sessions focused on remediation strategies for reading and numeracy gaps.
    - Pair experienced teachers with newer ones through a peer-mentoring arrangement — even informal check-ins help.

    **Build your child protection systems** Indicators around safe learning environments (indicator 2.1, 2.7) are flagged. These are foundational and worth addressing early.

    - Convene or reactivate the Child Protection Committee within the next 6 weeks.
    - Ensure the committee has clear terms of reference and a reporting pathway for incidents.

    The single biggest factor in early SBM improvement is **consistent focus on a few priorities** — everything else becomes easier from there.

    END OF EXAMPLE. Now generate advice for this school:

    School: {school_name}
    School Year: {sy_label}
    Overall SBM Score: {gap.get('average_score', 'N/A')}%
    Maturity Level: {gap.get('overall_maturity', 'N/A')}
    {history_line}
    {trend_line}

    Base your recommendations PRIMARILY on the weak indicators below.

    Weakest Dimensions:
    {dim_lines if dim_lines else "  (none identified)"}

    Weak Indicators (Rated 1 or 2):
    {weak_ind_lines if weak_ind_lines else "  (none identified)"}

    Stakeholder Remarks: {topic_line}
    Urgent issues: {"YES" if urgent else "None"}

    Remember: natural conversational prose, bold topic headers, bullet points for actions,
    short paragraphs, closing statement (not a question). Reference DepEd Order No. 007, s. 2024 where relevant.
    Do NOT use numbered lists. Do NOT use section headers in brackets.
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


def _build_form_extraction_prompt(raw_text: str) -> str:
    """
    Builds a prompt instructing the LLM to convert raw extracted document
    text (from a PDF/DOCX SBM assessment form) into strict structured JSON
    matching the SBM form editor's shape.
    """
    # Guard against oversized documents blowing the context window.
    max_chars = 24000
    if len(raw_text) > max_chars:
        raw_text = raw_text[:max_chars]

    prompt = textwrap.dedent(f"""
    You are extracting structured data from a Philippine DepEd School-Based
    Management (SBM) self-assessment form document. The document lists
    dimensions (e.g. "Dimension 1: Curriculum and Teaching"), each containing
    numbered indicators (e.g. "1.1", "1.2") with descriptive text, and each
    indicator usually has a "Means of Verification" (MOV) reference — the
    documents/evidence needed to prove the indicator (may be labeled "MOV:",
    "Means of Verification:", "Evidence:", or similar).

    Return ONLY valid JSON (no markdown fences, no commentary, no preamble)
    in EXACTLY this shape:

    {{
      "dimensions": [
        {{
          "dimension_no": 1,
          "dimension_name": "Curriculum and Teaching",
          "indicators": [
            {{
              "indicator_text": "Grade 3 learners achieve the proficiency level for each cluster of early language, literacy, and numeracy skill.",
              "mov_guide": "MPS/proficiency data, class records, early language and literacy assessment results"
            }}
          ]
        }}
      ]
    }}

    Rules:
    - dimension_no must be a sequential integer starting at 1, in the order dimensions appear in the document.
    - Preserve the original wording of indicator_text and mov_guide as closely as possible — do not paraphrase or summarize.
    - If an indicator has no explicit MOV in the document, set mov_guide to an empty string "".
    - Do not invent dimensions or indicators that are not present in the document.
    - Do not include indicator numbering/codes (like "1.1") inside indicator_text — that is derived separately.
    - If the document is not a recognizable SBM assessment form (no dimensions/indicators found), return {{"dimensions": []}}.

    Document text:
    ---
    {raw_text}
    ---

    Return ONLY the JSON object described above.
    """).strip()

    return prompt


def extract_form_from_document(raw_text: str, backend: str = "groq") -> dict:
    """
    Entry point for the "Import from Document" feature in Manage Form.
    Sends extracted document text to the LLM and parses its JSON response
    into the dimensions/indicators structure used by the form editor.
    """
    prompt = _build_form_extraction_prompt(raw_text)
    error  = None
    parsed = {"dimensions": []}

    try:
        if backend == "ollama":
            text = _call_ollama(prompt)
        elif backend == "openai":
            text = _call_openai(prompt)
        else:
            text = _call_groq_json(prompt)
            backend = "groq"

        # Strip markdown code fences if the model added them anyway.
        cleaned = text.strip()
        if cleaned.startswith("```"):
            cleaned = cleaned.strip("`")
            if cleaned.lower().startswith("json"):
                cleaned = cleaned[4:]
        cleaned = cleaned.strip()

        parsed = json.loads(cleaned)
        if not isinstance(parsed, dict) or "dimensions" not in parsed:
            raise ValueError("Response JSON missing 'dimensions' key.")

    except Exception as e:
        import logging
        import traceback
        tb = traceback.format_exc()
        logging.error(f"Error extracting form from document ({backend}): {e}\n{tb}")
        print(f"[ML ERROR] extract_form_from_document {backend} failed: {e}\n{tb}", flush=True)
        error = str(e)
        parsed = {"dimensions": []}

    return {
        "dimensions": parsed.get("dimensions", []),
        "backend_used": backend,
        "error": error,
    }


def _call_groq_json(prompt: str) -> str:
    """Call Groq with JSON mode enabled and a much larger token budget —
    used for form-extraction, which can require a long structured response."""
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
        temperature=0.2,
        max_tokens=8000,
        response_format={"type": "json_object"},
    )
    return response.choices[0].message.content.strip()


def _build_ip_field_prompt(field_type: str, indicator_code: str, indicator_text: str,
                            dimension_name: str, snippet: str) -> str:
    """
    Builds a focused prompt that turns ONE already-generated AI recommendation
    snippet into an Objective or Strategy for a School Improvement Plan.
    Uses ONLY the matched snippet as context — never the full article, and
    never another indicator's content.
    """
    if field_type == "objective":
        task = (
            "Write ONE concise, measurable, professional objective statement for "
            "a School Improvement Plan describing WHAT the school aims to achieve. "
            "Return only the objective text (1-2 sentences) — no bullet points, "
            "headers, or preamble."
        )
    else:
        task = (
            "Write a formal implementation strategy describing HOW the objective "
            "will be achieved. Return 1-3 concise sentences of prose — no bullet "
            "points, headers, or preamble — suitable for a School Improvement Plan."
        )

    prompt = textwrap.dedent(f"""
    You are an SBM improvement specialist for Philippine public schools.

    A School Head selected this indicator for a School Improvement Plan:
    Dimension: {dimension_name}
    Indicator: [{indicator_code}] {indicator_text}

    Below is the ONLY relevant recommendation already shown to the School Head.
    Use ONLY this content as context. Do not reference any other dimension or
    indicator, and do not introduce issues not mentioned below.

    ---
    {snippet}
    ---

    Task: {task}
    """).strip()

    return prompt


def generate_ip_field(
    field_type: str,
    indicator_code: str,
    indicator_text: str,
    dimension_name: str,
    snippet: str,
    backend: str = "groq",
) -> dict:
    """
    Entry point for the Add Improvement Plan modal's "Generate Objective" /
    "Generate Strategy" buttons. Turns a single matched AI-suggestion snippet
    into a ready-to-edit Objective or Strategy.
    """
    prompt = _build_ip_field_prompt(field_type, indicator_code, indicator_text, dimension_name, snippet)
    error  = None
    text   = ""

    try:
        if backend == "ollama":
            text = _call_ollama(prompt)
        elif backend == "openai":
            text = _call_openai(prompt)
        else:
            text = _call_groq(prompt)
            backend = "groq"
    except Exception as e:
        import logging
        import traceback
        tb = traceback.format_exc()
        logging.error(f"Error generating IP field ({field_type}): {e}\n{tb}")
        print(f"[ML ERROR] generate_ip_field {backend} failed: {e}\n{tb}", flush=True)
        error = str(e)
        text = ""

    return {
        "text": text.strip(),
        "backend_used": backend,
        "error": error,
        "field_type": field_type,
    }


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