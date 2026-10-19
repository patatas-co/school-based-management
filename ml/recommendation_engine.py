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
    dimensions = gap.get("all_dimensions") or analysis.get("dimension_rows", [])
    weak      = analysis.get("weak_indicators", {})
    comments  = analysis.get("comment_summary", {})
    by_rating = analysis.get("by_rating", {})
    teacher_summaries = analysis.get("teacher_summaries", []) or []
    dimension_outlook = analysis.get("dimension_outlook", []) or []
    previous_suggestions = analysis.get("previous_suggestions", []) or []
    score_comparison = analysis.get("score_comparison", {}) or {}
    dimension_lines = "\n".join(
        f"  - dimension_id={int(d.get('dimension_id', 0))}, "
        f"dimension_no={int(d.get('dimension_no', 0))}, "
        f"dimension_name={d.get('dimension_name', '')!r}, "
        f"current_score={d.get('score', d.get('dimension_score', 'N/A'))}%, "
        f"precomputed_comparison={score_comparison.get('dimensions', {}).get(str(d.get('dimension_id', 0)), {})}, "
        f"weakest_indicators={analysis.get('dimension_weakest_indicators', {}).get(str(d.get('dimension_id', 0)), [])}"
        for d in dimensions
    )

    weakest_dims = gap.get("weakest_dimensions", [])
    weakest_indicators_by_dimension = analysis.get("dimension_weakest_indicators", {}) or {}
    dimension_indicator_lines = "\n".join(
        f"  - D{int(d.get('dimension_no', 0))} {d.get('dimension_name', '')}: "
        + (
            "; ".join(
                f"{item.get('code')} ({float(item.get('rating', 0)):.1f} average)"
                for item in weakest_indicators_by_dimension.get(str(d.get("dimension_id", 0)), [])
            )
            or "no rated indicators"
        )
        for d in dimensions
    )
    top_topics   = comments.get("top_topics", [])
    urgent       = comments.get("has_urgent", False)
    flagged_teachers = [
        teacher for teacher in teacher_summaries
        if teacher.get("low_rater") is True
    ]
    teacher_summary_lines = (
        json.dumps(teacher_summaries, ensure_ascii=False)
        if teacher_summaries else "  (none available)"
    )
    teacher_guidance = ""
    if flagged_teachers:
        flagged_labels = ", ".join(
            str(teacher.get("label", "a flagged teacher"))
            for teacher in flagged_teachers
        )
        teacher_guidance = (
            f"\nAt least one assigned teacher rated every assigned indicator exactly 1: {flagged_labels}. "
            "Include a supportive one-on-one conversation with each flagged teacher as an exploratory next step. "
            "Explain that an all-low pattern may reflect disengagement, a misunderstanding of the rating scale, "
            "or real concerns that deserve attention; do not assume which explanation applies. "
            "Keep the conversation non-punitive, curious, and focused on listening and support."
        )
    outlook_lines = "\n".join(
        f"  - {item.get('dimension_name') or 'Dimension ' + str(item.get('dimension_no'))}: "
        f"priority={item.get('priority', 'medium')}, "
        f"decline risk={item.get('decline_risk', 'medium')}, "
        f"confidence={item.get('confidence', 0):.2f}, "
        f"source={item.get('model_source', 'rule_fallback')}, "
        f"top factors={', '.join(item.get('top_factors', [])) or 'none'}"
        for item in dimension_outlook
    )
    outlook_source = (
        dimension_outlook[0].get("model_source", "rule_fallback")
        if dimension_outlook else "rule_fallback"
    )

    previous_by_dimension = {
        int(item.get("dimension_no", 0)): item
        for item in previous_suggestions
        if int(item.get("dimension_no", 0)) > 0
    }
    previous_lines = []
    for dimension in dimensions:
        dimension_no = int(dimension.get("dimension_no", 0))
        previous = previous_by_dimension.get(dimension_no)
        if previous and previous.get("suggestion_title"):
            plan_status = (
                "plan created" if previous.get("plan_created")
                else "no plan recorded"
            )
            previous_lines.append(
                f"  - D{dimension_no}: prior suggestion "
                f"{str(previous['suggestion_title']).strip()!r}; {plan_status}."
            )
        else:
            previous_lines.append(f"  - D{dimension_no}: no saved prior suggestion.")
    previous_suggestion_lines = "\n".join(previous_lines)
    improvement_cycle_guidance = (
        "Prior suggestion context (for avoiding repetition only; do not reproduce "
        "score values or make score comparisons):\n"
        + (previous_suggestion_lines or "  (none — generate as usual)")
        + "\nRules for this cycle: Avoid repeating prior actions verbatim. Keep every suggestion strictly within its own listed "
        "dimension's topic; never move curriculum advice into leadership, governance, or another dimension. "
        "Use prior suggestions only for the matching dimension_no. Never claim an indicator weakness or result not present in the supplied data. "
        "When no weak indicators are supplied for a dimension, do not invent indicator codes or gaps; propose modest, "
        "measurable actions directly relevant to that dimension."
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

    Return ONLY valid JSON with exactly this shape:
    {{
      "suggestions": [
        {{
          "dimension_id": 123,
          "actions": ["Action one.", "Action two.", "Action three."]
        }}
      ]
    }}

    Include each listed dimension exactly once. Copy its integer dimension_id
    exactly. The application will add the dimension heading, score comparison,
    and trend lines; do not return headings, titles, scores, deltas, trend
    statements, overviews, or closing text.
    Return exactly three distinct, concrete actions per dimension. Each action
    must identify an owner or role and a frequency or deadline. When lowest-rated
    indicators are supplied for a dimension, address them and cite at least one
    supplied indicator code in every action. Do not invent indicator codes. Keep
    every action within its dimension. Use relative deadlines only, such as
    "within 30 days", "by the end of the first quarter", or "every month".
    Never use calendar dates or years in action bullets.
    Never calculate, derive, round, or invent any scores or score changes. Any
    score values shown below are authoritative and must not be altered or repeated
    in the actions.

    Precomputed score comparison (read-only; application-rendered):
    {json.dumps(score_comparison, ensure_ascii=False)}

    Exact dimensions for this assessment:
    {dimension_lines if dimension_lines else "  (none provided)"}

    Lowest-rated indicators by dimension (use these for the three actions):
    {dimension_indicator_lines if dimension_indicator_lines else "  (none available)"}

    School: {school_name}
    School Year: {sy_label}
    Overall SBM Score: {gap.get('average_score', 'N/A')}%
    Maturity Level: {gap.get('overall_maturity', 'N/A')}

    {improvement_cycle_guidance}

    Base your recommendations PRIMARILY on the weak indicators below.

    Weakest Dimensions:
    {dim_lines if dim_lines else "  (none identified)"}

    Dimension outlook:
    {outlook_lines if outlook_lines else "  (none available)"}
    Treat each dimension outlook's priority and decline risk as binding:
    do not contradict them; explain them and use them to focus the advice.
    The outlook source is "{outlook_source}". If it is "rule_fallback", do not
    claim that a trained or learned model produced the outlook.

    Weak Indicators (Rated 1 or 2):
    {weak_ind_lines if weak_ind_lines else "  (none identified)"}

    Stakeholder Remarks: {topic_line}
    Urgent issues: {"YES" if urgent else "None"}

    Teacher rating summaries (neutral labels only; do not infer or invent names):
    {teacher_summary_lines}
    {teacher_guidance}

    Keep actions warm, professional, and concise. Do not include calendar dates,
    years, or references containing a 4-digit year in action bullets. Never
    include numeric score calculations in actions.
    """).strip()

    return prompt


def _build_single_dimension_retry_prompt(
    analysis: dict,
    school_name: str,
    sy_label: str,
    dimension: dict,
) -> str:
    dimension_no = int(dimension.get("dimension_no", 0))
    dimension_id = int(dimension.get("dimension_id", 0))
    retry_analysis = dict(analysis)
    gap = dict(analysis.get("gap_analysis", {}))
    all_dimensions = gap.get("all_dimensions") or analysis.get("dimension_rows", [])
    retry_dimensions = [
        item for item in all_dimensions
        if int(item.get("dimension_id", 0)) == dimension_id
    ]
    gap["all_dimensions"] = retry_dimensions
    gap["weakest_dimensions"] = [
        item for item in gap.get("weakest_dimensions", [])
        if int(item.get("dimension_no", 0)) == dimension_no
    ]
    retry_analysis["gap_analysis"] = gap
    retry_analysis["dimension_rows"] = retry_dimensions
    retry_analysis["dimension_outlook"] = [
        item for item in analysis.get("dimension_outlook", [])
        if int(item.get("dimension_id", 0)) == dimension_id
    ]
    retry_analysis["previous_suggestions"] = [
        item for item in analysis.get("previous_suggestions", [])
        if int(item.get("dimension_no", 0)) == dimension_no
    ]
    weak_indicators = dict(analysis.get("weak_indicators", {}))
    by_dimension = weak_indicators.get("by_dimension", {})
    weak_indicators["by_dimension"] = {
        key: value for key, value in by_dimension.items()
        if str(key) == str(dimension_no)
    }
    retry_analysis["weak_indicators"] = weak_indicators
    retry_analysis["by_rating"] = {}
    retry_analysis["comment_summary"] = {}
    return _build_prompt(retry_analysis, school_name, sy_label) + (
        "\n\nThis is a single-dimension retry. Return exactly one suggestion for "
        f"dimension_id={dimension_id} and dimension_no={dimension_no}. Make at "
        "least three distinct concrete action bullets; each needs an owner or "
        "role and a frequency or deadline."
    )


def _count_suggestion_actions(text: str) -> int:
    normalized = re.sub(r"\s+-\s+(?=[\w“\"'])", "\n- ", text)
    return len(re.findall(r"(?m)^\s*[-*]\s+\S", normalized))


def _privacy_safe_backend(backend: str) -> str:
    configured = backend.strip().lower()
    if configured in {"groq", "ollama", "openai"}:
        return configured
    return "ollama"


def _backend_model_name(backend: str) -> str:
    if backend == "groq":
        return os.getenv("GROQ_MODEL", "openai/gpt-oss-120b")
    if backend == "openai":
        return os.getenv("OPENAI_MODEL", "gpt-4o-mini")
    return os.getenv("OLLAMA_MODEL", "llama3.2:3b")


def _groq_max_tokens() -> int:
    value = os.getenv("GROQ_MAX_TOKENS", "8192")
    try:
        max_tokens = int(value)
    except ValueError as exc:
        raise RuntimeError("GROQ_MAX_TOKENS must be an integer between 1 and 65536.") from exc
    if not 1 <= max_tokens <= 65536:
        raise RuntimeError("GROQ_MAX_TOKENS must be between 1 and 65536.")
    return max_tokens


def _call_backend(prompt: str, backend: str, json_mode: bool = False) -> str:
    if backend == "groq":
        return _call_groq(prompt, json_mode=json_mode)
    if backend == "openai":
        return _call_openai(prompt, model=_backend_model_name(backend))
    return _call_ollama(prompt, model=_backend_model_name(backend))


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


def _call_groq(prompt: str, json_mode: bool = False) -> str:
    """Call Groq API using OpenAI-compatible SDK."""
    from openai import OpenAI

    api_key = os.getenv("GROQ_API_KEY")
    if not api_key:
        raise RuntimeError("GROQ_API_KEY is missing. Set it in the ML service environment.")

    model = os.getenv("GROQ_MODEL", "openai/gpt-oss-120b")

    client = OpenAI(
        api_key=api_key,
        base_url="https://api.groq.com/openai/v1"
    )
    response = client.chat.completions.create(
        model=model,
        messages=[{"role": "user", "content": prompt}],
        temperature=0.4,
        max_tokens=_groq_max_tokens(),
        **({"response_format": {"type": "json_object"}} if json_mode else {}),
    )
    choice = response.choices[0]
    content = choice.message.content
    if not isinstance(content, str) or not content.strip():
        raise RuntimeError(
            "Groq returned an empty message "
            f"(finish_reason={choice.finish_reason or 'unknown'}, model={response.model or model})."
        )
    return content.strip()


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
    backend = _privacy_safe_backend(backend)
    prompt = _build_form_extraction_prompt(raw_text)
    error  = None
    parsed = {"dimensions": []}

    try:
        text = _call_backend(prompt, backend, json_mode=True)

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

    model = os.getenv("GROQ_MODEL", "openai/gpt-oss-120b")

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


import re

CONFIDENCE_RESPONSE_TARGET_PER_INDICATOR = 5


def compute_recommendation_confidence(
    block_text: str, analysis: dict, dimension_id: int | None = None
) -> dict:
    """
    Computes a transparent, data-derived confidence score for one
    recommendation block (NOT the LLM's self-reported certainty).
    Insufficient data is determined by the cited dimension's response coverage.
    """
    codes = sorted(set(re.findall(r'\b\d{1,2}\.\d{1,2}\b', block_text)))
    dimension_coverage = analysis.get("dimension_coverage", {}) or {}
    dimension_evidence = analysis.get("dimension_evidence", {}) or {}
    coverage = dimension_coverage.get(str(dimension_id), {}) if dimension_id is not None else {}
    evidence = dimension_evidence.get(str(dimension_id), {}) if dimension_id is not None else {}

    if not isinstance(coverage, dict) or not coverage.get("sufficient", False):
        total_indicators = int(coverage.get("total_indicators", 0)) if isinstance(coverage, dict) else 0
        answered_indicators = int(coverage.get("answered_indicators", 0)) if isinstance(coverage, dict) else 0
        minimum_answered = (total_indicators + 1) // 2
        reason = (
            f"Response coverage is too low: {answered_indicators} of {total_indicators} active "
            f"indicators have recorded responses; at least {minimum_answered} are required."
            if total_indicators > 0 and answered_indicators < minimum_answered
            else "A dimension score is unavailable, so assessment coverage cannot be confirmed."
        )
        return {
            "insufficient_data": True,
            "reason": reason,
            "indicator_codes": codes,
        }

    rated_indicator_count = int(evidence.get("rated_indicator_count", 0) or 0)
    total_response_count = int(evidence.get("response_count", 0) or 0)
    if rated_indicator_count == 0 or total_response_count == 0:
        return {
            "insufficient_data": False,
            "confidence_level": "General Recommendation",
            "factors": [],
            "indicator_codes": codes,
        }

    total_indicators = int(coverage.get("total_indicators", 0) or 0)
    answered_indicators = int(coverage.get("answered_indicators", 0) or 0)
    dimension_coverage_pct = (
        min(100.0, answered_indicators / total_indicators * 100)
        if total_indicators > 0 else 0.0
    )
    rated_indicator_share_pct = min(
        100.0,
        rated_indicator_count / total_indicators * 100,
    ) if total_indicators > 0 else 0.0
    average_responses_per_indicator = total_response_count / rated_indicator_count
    response_depth_pct = min(
        100.0,
        average_responses_per_indicator
        / CONFIDENCE_RESPONSE_TARGET_PER_INDICATOR * 100,
    )
    final_pct = round(
        (dimension_coverage_pct + rated_indicator_share_pct + response_depth_pct) / 3,
        1,
    )
    factors = [
        f"Dimension response coverage: {dimension_coverage_pct:.1f}% "
        f"({answered_indicators} of {total_indicators} active indicators)",
        f"Dimension indicators with responses: {rated_indicator_count} of {total_indicators} "
        f"({rated_indicator_share_pct:.1f}%)",
        f"Responses behind dimension indicators: {total_response_count} total "
        f"({average_responses_per_indicator:.1f} per rated indicator; "
        f"response-depth score capped at {CONFIDENCE_RESPONSE_TARGET_PER_INDICATOR} per indicator)",
    ]

    if final_pct >= 80:
        level = "High Confidence"
    elif final_pct >= 60:
        level = "Moderate Confidence"
    else:
        level = "Low Confidence"

    return {
        "insufficient_data": False,
        "confidence_pct": final_pct,
        "confidence_level": level,
        "factors": factors,
        "indicator_codes": codes,
    }


def split_into_confidence_blocks(raw_text: str, analysis: dict) -> list:
    """
    Splits the LLM's prose output on '**Bold Header**' lines (same boundary
    the frontend's parseAILogicToHtml() already uses) and computes an
    independent confidence score per block. Does NOT alter raw_text itself.
    """
    if not raw_text:
        return []

    lines = raw_text.split("\n")
    blocks = []
    current_title = None
    current_body_lines = []

    def flush():
        if current_title is not None:
            body_text = "\n".join(current_body_lines)
            full_block_text = current_title + "\n" + body_text
            conf = compute_recommendation_confidence(full_block_text, analysis)
            blocks.append({"title": current_title, **conf})

    for line in lines:
        stripped = line.strip()
        m = re.match(r'^\*\*(.+?)\*\*(.*)$', stripped)
        if m:
            flush()
            current_title = m.group(1).strip()
            current_body_lines = [m.group(2).strip()] if m.group(2).strip() else []
        elif current_title is not None:
            current_body_lines.append(stripped)

    flush()
    return blocks


def _build_ip_field_prompt(field_type: str, indicator_code: str, indicator_text: str,
                            dimension_name: str, snippet: str) -> str:
    """
    Builds a focused prompt that turns already-generated AI recommendation
    snippet into an Objective or Strategy for a School Improvement Plan.
    Uses ONLY the matched snippet as context — never the full article, and
    never content outside the selected indicators.
    """
    if field_type == "objective":
        task = (
            "Write ONE concise, measurable, professional objective statement for "
            "a School Improvement Plan describing WHAT the school aims to achieve. "
            "Return only the objective text (1-2 sentences) in plain text — no bullet "
            "points, headers, markdown, or JSON wrapper."
        )
    else:
        task = (
            "Write a formal implementation strategy describing HOW the objective "
            "will be achieved. Return 1-3 concise sentences of prose in plain text — "
            "no bullet points, headers, markdown, or JSON wrapper — suitable for a "
            "School Improvement Plan."
        )

    prompt = textwrap.dedent(f"""
    You are an SBM improvement specialist for Philippine public schools.

    A School Head selected these indicators for one School Improvement Plan:
    Dimension(s): {dimension_name}
    Indicator code(s): {indicator_code}
    Indicator descriptions:
    {indicator_text}

    Below are the relevant recommendations already shown to the School Head.
    Use ONLY these recommendations as context. Address the selected indicators
    together in one coherent plan. Do not reference any other dimension or
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
    "Generate Strategy" buttons. Turns matched AI-suggestion snippets
    into a ready-to-edit Objective or Strategy.
    """
    backend = _privacy_safe_backend(backend)
    prompt = _build_ip_field_prompt(field_type, indicator_code, indicator_text, dimension_name, snippet)
    error  = None
    text   = ""

    try:
        raw_text = _call_backend(prompt, backend, json_mode=False)
        if isinstance(raw_text, str):
            cleaned = raw_text.strip()
            if cleaned.startswith("{") and cleaned.endswith("}"):
                try:
                    candidate = json.loads(cleaned)
                    if isinstance(candidate, dict):
                        for key in ("text", "objective", "strategy", "content"):
                            value = candidate.get(key)
                            if isinstance(value, str) and value.strip():
                                cleaned = value.strip()
                                break
                except (TypeError, ValueError):
                    pass
            text = cleaned
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
    backend: str = "groq",  # "rule_based" | "ollama" | "openai" | "groq"
) -> dict:
    """
    Main entry point called by Flask.
    Returns generated text + metadata.
    """
    configured_backend = backend
    backend = _privacy_safe_backend(backend)
    dimension_outlook = analysis.get("dimension_outlook", []) or []
    prompt = _build_prompt(analysis, school_name, sy_label)
    error  = None
    warning = None
    text   = ""
    suggestions = []
    gap_analysis = analysis.get("gap_analysis", {})
    dimensions = gap_analysis.get("all_dimensions") or analysis.get("dimension_rows", [])
    generation_succeeded = True

    try:
        text = _call_backend(prompt, backend, json_mode=True)
        suggestions = _parse_dimension_suggestions(
            text,
            dimensions,
            analysis.get("previous_suggestions", []),
            analysis.get("dimension_weakest_indicators", {}),
        )
        model_name = _backend_model_name(backend)
        for suggestion in suggestions:
            suggestion["model_version"] = model_name
    except Exception as e:
        import logging
        import traceback
        tb = traceback.format_exc()
        logging.error(f"Error generating recommendations ({backend}): {e}\n{tb}")
        print(f"[ML ERROR] {backend} failed: {e}\n{tb}", flush=True)
        warning = f"{backend} generation failed; rule-based suggestions need review: {e}"
        generation_succeeded = False
        suggestions = _rule_based_dimension_suggestions(analysis, dimensions)
        for suggestion in suggestions:
            suggestion["action_count"] = _count_suggestion_actions(suggestion["text"])
            suggestion["needs_review"] = True
            suggestion["review_reason"] = f"{backend} was unavailable; verify and expand this suggestion."
            suggestion["model_version"] = "rule_based_local_fallback"
        backend = "rule_based_local_fallback"

    dimensions_by_id = {
        int(dimension["dimension_id"]): dimension
        for dimension in dimensions
        if isinstance(dimension, dict) and str(dimension.get("dimension_id", "")).isdigit()
    }
    for suggestion in suggestions:
        if not generation_succeeded or not suggestion.get("needs_review"):
            continue
        dimension_id = int(suggestion["dimension_id"])
        dimension = dimensions_by_id[dimension_id]
        try:
            retry_prompt = _build_single_dimension_retry_prompt(
                analysis, school_name, sy_label, dimension
            )
            retry_text = _call_backend(
                retry_prompt,
                _privacy_safe_backend(configured_backend),
                json_mode=True,
            )
            retry_result = _parse_dimension_suggestions(
                retry_text,
                [dimension],
                analysis.get("previous_suggestions", []),
                analysis.get("dimension_weakest_indicators", {}),
            )[0]
            retry_count = _count_suggestion_actions(retry_result["text"])
            original_count = int(suggestion.get("action_count", 0))
            if (
                retry_count == 3
                and retry_result.get("actions_supported", True)
                and not retry_result.get("calendar_year_violation", False)
            ):
                retry_result["action_count"] = retry_count
                retry_result["needs_review"] = False
                retry_result["review_reason"] = None
                retry_result["model_version"] = _backend_model_name(
                    _privacy_safe_backend(configured_backend)
                )
                suggestion.update(retry_result)
            else:
                if (
                    abs(retry_count - 3) < abs(original_count - 3)
                    or retry_result.get("actions_supported", True)
                    and not suggestion.get("actions_supported", True)
                    or not retry_result.get("calendar_year_violation", False)
                    and suggestion.get("calendar_year_violation", False)
                ):
                    suggestion.update(retry_result)
                suggestion["action_count"] = int(suggestion.get("action_count", original_count))
                suggestion["needs_review"] = True
                suggestion.setdefault(
                    "model_version",
                    _backend_model_name(_privacy_safe_backend(configured_backend)),
                )
                suggestion["review_reason"] = (
                    "Action bullets must use relative deadlines and must not contain calendar dates or 4-digit years."
                    if suggestion.get("calendar_year_violation")
                    else f"Exactly three action bullets are required; {suggestion['action_count']} remained after one retry."
                )
        except Exception as retry_error:
            import logging
            logging.warning(
                "Single-dimension retry failed for dimension_id %s: %s",
                dimension_id,
                retry_error,
            )
            suggestion["needs_review"] = True
            suggestion.setdefault(
                "model_version",
                _backend_model_name(_privacy_safe_backend(configured_backend)),
            )
            suggestion["review_reason"] = (
                f"Exactly three action bullets are required; the "
                f"{configured_backend} retry failed: {retry_error}"
            )

    for suggestion in suggestions:
        if (
            int(suggestion.get("action_count", 0)) == 3
            and suggestion.get("actions_supported", True)
            and not suggestion.get("calendar_year_violation", False)
        ):
            continue
        dimension = dimensions_by_id[int(suggestion["dimension_id"])]
        fallback = _rule_based_dimension_suggestions(analysis, [dimension])[0]
        had_calendar_year = bool(suggestion.get("calendar_year_violation"))
        suggestion["text"] = fallback["text"]
        suggestion["action_count"] = 3
        suggestion["calendar_year_violation"] = False
        suggestion["needs_review"] = True
        suggestion["review_reason"] = (
            "The model actions contained a calendar date or 4-digit year; relative-deadline standard actions are shown for review."
            if had_calendar_year
            else "The model actions did not meet the three-action or indicator-citation requirement; standard actions are shown for review."
        )

    from suggestion_ranker import rank_suggestions

    suggestions, suggestion_ranking = rank_suggestions(suggestions, dimensions)
    score_comparison = analysis.get("score_comparison", {}) or {}
    comparisons = score_comparison.get("dimensions", {}) or {}
    suggestions.sort(key=lambda item: (
        comparisons.get(str(item["dimension_id"]), {}).get("change") is None,
        comparisons.get(str(item["dimension_id"]), {}).get("change")
        if comparisons.get(str(item["dimension_id"]), {}).get("change") is not None
        else float("inf"),
        int(item["dimension_no"]),
    ))
    text = _format_dimension_suggestions(suggestions, score_comparison)

    blocks = []
    for suggestion in suggestions:
        block_text = f"{suggestion['title']}\n{suggestion['text']}"
        blocks.append({
            "dimension_id": suggestion["dimension_id"],
            "dimension_no": suggestion["dimension_no"],
            "dimension_name": suggestion["dimension_name"],
            "title": suggestion["title"],
            "body_text": suggestion["text"],
            "indicator_codes": suggestion.get("indicator_codes", []),
            "action_count": suggestion.get("action_count", 0),
            "needs_review": bool(suggestion.get("needs_review")),
            "review_reason": suggestion.get("review_reason"),
            "model_version": suggestion.get(
                "model_version",
                "rule_based_local_fallback" if backend == "rule_based_local_fallback"
                else _backend_model_name(_privacy_safe_backend(configured_backend)),
            ),
            **(
                {"approval_score": suggestion["approval_score"]}
                if "approval_score" in suggestion else {}
            ),
            **compute_recommendation_confidence(
                block_text, analysis, int(suggestion["dimension_id"])
            ),
        })

    model_source = (
        dimension_outlook[0].get("model_source", "rule_fallback")
        if dimension_outlook else "rule_fallback"
    )
    confidence = (
        round(min(float(item.get("confidence", 0.55)) for item in dimension_outlook), 3)
        if dimension_outlook else 0.55
    )

    return {
        "recommendations": text,
        "blocks": blocks,
        "suggestions": suggestions,
        "backend_used": _backend_model_name(backend) if backend in {"ollama", "groq", "openai"} else backend,
        "error": error,
        "warning": warning,
        "privacy_notice": (
            f"Assessment-derived content was sent to the external {configured_backend} service."
            if _privacy_safe_backend(configured_backend) in {"groq", "openai"} else None
        ),
        "prompt_chars": len(prompt),
        "model_source": model_source,
        "confidence": confidence,
        "dimension_outlook": dimension_outlook,
        "suggestion_ranking": suggestion_ranking,
    }


def _parse_dimension_suggestions(
    raw_text: str,
    dimensions: list[dict],
    previous_suggestions: list[dict] | None = None,
    weakest_indicators_by_dimension: dict | None = None,
) -> list[dict]:
    cleaned = raw_text.strip()
    if cleaned.startswith("```"):
        cleaned = re.sub(r"^```(?:json)?\s*|\s*```$", "", cleaned, flags=re.IGNORECASE)
    parsed = json.loads(cleaned)
    suggestions = parsed.get("suggestions") if isinstance(parsed, dict) else None
    if not isinstance(suggestions, list):
        raise ValueError("Recommendation JSON must contain a suggestions array.")

    dimensions_by_id = {
        int(dimension["dimension_id"]): dimension
        for dimension in dimensions
        if isinstance(dimension, dict) and str(dimension.get("dimension_id", "")).isdigit()
    }
    previous_by_dimension = {
        int(item.get("dimension_no", 0)): item
        for item in (previous_suggestions or [])
        if isinstance(item, dict) and int(item.get("dimension_no", 0)) > 0
    }
    validated = []
    seen_ids = set()
    for suggestion in suggestions:
        if not isinstance(suggestion, dict):
            raise ValueError("Every recommendation must be a JSON object.")
        dimension_id = suggestion.get("dimension_id")
        if isinstance(dimension_id, bool) or not isinstance(dimension_id, int):
            raise ValueError("Every recommendation must include an integer dimension_id.")
        dimension = dimensions_by_id.get(dimension_id)
        if dimension is None:
            raise ValueError(f"Recommendation returned unknown dimension_id {dimension_id}.")
        if dimension_id in seen_ids:
            raise ValueError(f"Recommendation returned duplicate dimension_id {dimension_id}.")
        supplied_title = str(suggestion.get("title", "")).strip()
        title = (
            f"D{int(dimension.get('dimension_no', 0))} "
            f"{str(dimension.get('dimension_name', '')).strip()}"
        )
        actions = suggestion.get("actions")
        if isinstance(actions, list):
            body = "\n".join(
                "- " + re.sub(r"^\s*[-*]\s*", "", str(action)).strip()
                for action in actions
                if str(action).strip()
            )
        else:
            body = str(suggestion.get("text", "")).strip()
            if not supplied_title:
                raise ValueError(f"Recommendation for dimension_id {dimension_id} is missing actions.")
        body_lines = body.splitlines()
        first_content_line = next(
            (index for index, line in enumerate(body_lines) if line.strip()),
            None,
        )
        if first_content_line is not None:
            repeated_title = re.match(
                r"^\s*\*\*(.+?)\*\*(.*)$",
                body_lines[first_content_line],
            )
            if (
                repeated_title
                and re.sub(r"\s+", " ", repeated_title.group(1)).strip().casefold()
                == re.sub(r"\s+", " ", supplied_title).strip().casefold()
            ):
                body_lines[first_content_line] = repeated_title.group(2)
                body = "\n".join(body_lines).strip()
                if not body:
                    raise ValueError(
                        f"Recommendation for dimension_id {dimension_id} contains only a repeated title."
                    )
        action_count = _count_suggestion_actions(body)
        weakest_indicators = (weakest_indicators_by_dimension or {}).get(
            str(dimension_id), []
        )
        required_codes = [
            str(indicator.get("code", "")).strip()
            for indicator in weakest_indicators
            if str(indicator.get("code", "")).strip()
        ]
        actions = [
            line.strip()[2:].strip()
            for line in body.splitlines()
            if re.match(r"^\s*[-*]\s+\S", line)
        ]
        calendar_year_violation = any(
            re.search(r"\b\d{4}\b", action)
            for action in actions
        )
        actions_supported = not required_codes or (
            action_count == 3
            and len(actions) == 3
            and all(
                any(
                    re.search(
                        rf"(?<![\w.]){re.escape(code)}(?![\w.])",
                        action,
                    )
                    for code in required_codes
                )
                for action in actions
            )
        )
        last_cycle_note = suggestion.get("last_cycle_note")
        dimension_no = int(dimension.get("dimension_no", 0))
        previous = previous_by_dimension.get(dimension_no)
        has_previous = previous is not None
        if has_previous:
            last_cycle_note = _format_last_cycle_note(dimension_no, previous)
        if not has_previous:
            last_cycle_note = None
        seen_ids.add(dimension_id)
        validated.append({
            "dimension_id": dimension_id,
            "dimension_no": int(dimension.get("dimension_no", 0)),
            "dimension_name": str(dimension.get("dimension_name", "")),
            "title": title,
            "text": body,
            "last_cycle_note": last_cycle_note.strip() if isinstance(last_cycle_note, str) else None,
            "action_count": action_count,
            "calendar_year_violation": calendar_year_violation,
            "needs_review": (
                action_count != 3
                or not actions_supported
                or calendar_year_violation
            ),
            "review_reason": (
                "Action bullets must use relative deadlines and must not contain calendar dates or 4-digit years."
                if calendar_year_violation
                else f"Exactly three concrete action bullets are required; {action_count} were returned."
                if action_count != 3
                else "Each action must cite a supplied lowest-rated indicator."
                if not actions_supported else None
            ),
            "actions_supported": actions_supported,
            "model_version": "llama3.2:3b",
        })
    missing = set(dimensions_by_id) - seen_ids
    if missing:
        raise ValueError(f"Recommendation omitted dimension IDs: {', '.join(map(str, sorted(missing)))}.")
    return validated


def _format_dimension_suggestions(
    suggestions: list[dict], score_comparison: dict | None = None
) -> str:
    score_comparison = score_comparison or {}
    sections = [str(score_comparison.get("overview_text") or "No prior-year data available.")]
    comparisons = score_comparison.get("dimensions", {}) or {}
    weakest_by_dimension = score_comparison.get("weakest_indicators", {}) or {}
    for suggestion in suggestions:
        comparison = comparisons.get(str(suggestion["dimension_id"]), {}) or {}
        current_score = comparison.get("current_score")
        current_label = f"{float(current_score):.1f}%" if current_score is not None else "N/A"
        change = comparison.get("change")
        change_label = (
            f"{'+' if float(change) > 0 else ('−' if float(change) < 0 else '')}"
            f"{abs(float(change)):.1f} pts vs last year"
            if change is not None else "No prior-year data available"
        )
        weakest = weakest_by_dimension.get(str(suggestion["dimension_id"]), [])
        weakest_label = ", ".join(
            f"{indicator['code']} (avg {float(indicator['rating']):.1f})"
            for indicator in weakest
        ) or "No rated indicators available"
        sections.append(
            f"**{suggestion['title']}**\n"
            f"{current_label} · {change_label}\n"
            f"Weakest indicators: {weakest_label}\n"
            f"{suggestion['text']}"
        )
    return "\n\n".join(sections)


def _format_last_cycle_note(dimension_no: int, previous: dict) -> str:
    has_previous_suggestion = bool(
        previous.get("has_previous_suggestion", bool(previous.get("suggestion_title")))
    )
    title = re.sub(
        r"\s+",
        " ",
        str(previous.get("suggestion_title", "")).replace("**", "").replace('"', ""),
    ).strip()
    summary = (
        f'"{title}"'
        if has_previous_suggestion and title
        else f"no saved AI suggestion was recorded for D{dimension_no}"
    )
    previous_score = previous.get("score_at_time")
    current_score = previous.get("current_score")
    change = previous.get("score_change")
    if change is None:
        result = "score change unavailable"
    else:
        change = float(change)
        if change > 0:
            result = f"score improved by {abs(change):.1f}"
        elif change < 0:
            result = f"score declined by {abs(change):.1f}"
        else:
            result = "score was unchanged"
    if previous_score is not None and current_score is not None:
        result += f" from {float(previous_score):.1f}% to {float(current_score):.1f}%"

    if not has_previous_suggestion:
        plan_outcome = "there was no prior suggestion to adopt into an improvement plan"
    else:
        plan_outcome = (
            "an improvement plan was created"
            if previous.get("plan_created")
            else "no improvement plan was created"
        )
    return f"Last cycle: {summary}; Result: {result}, and {plan_outcome}."


def _rule_based_dimension_suggestions(analysis: dict, dimensions: list[dict]) -> list[dict]:
    weakest_by_dimension = analysis.get("dimension_weakest_indicators", {}) or {}
    previous_by_dimension = {
        int(item["dimension_no"]): item
        for item in analysis.get("previous_suggestions", [])
        if isinstance(item, dict) and str(item.get("dimension_no", "")).isdigit()
    }
    suggestions = []
    for dimension in dimensions:
        dimension_id = int(dimension.get("dimension_id", 0))
        dimension_no = int(dimension.get("dimension_no", 0))
        name = str(dimension.get("dimension_name", f"Dimension {dimension_id}"))
        prior = previous_by_dimension.get(dimension_no)
        indicators = weakest_by_dimension.get(str(dimension_id), [])
        codes = [
            str(indicator.get("code", "")).strip()
            for indicator in indicators
            if str(indicator.get("code", "")).strip()
        ]
        reference = lambda index: f" (indicator {codes[index % len(codes)]})" if codes else ""
        text = "\n".join([
            f"- The Dimension Coordinator will review {name} evidence{reference(0)} and set one priority action by the end of this month.",
            f"- The School Head will assign an owner and measurable target{reference(1)} before the next monthly meeting.",
            f"- The assigned team will record progress{reference(2)} and report to the School Head at the end of each quarter.",
        ])
        suggestions.append({
            "dimension_id": dimension_id,
            "dimension_no": dimension_no,
            "dimension_name": name,
            "title": f"D{dimension_no} {name}",
            "text": text,
            "last_cycle_note": (
                "Last cycle: the prior suggestion was "
                + ("adopted" if prior.get("plan_created") else "not adopted")
                + "; Result: "
                + (
                    f"score {'improved' if float(prior['score_change']) > 0 else 'declined' if float(prior['score_change']) < 0 else 'was unchanged'} "
                    f"by {abs(float(prior['score_change'])):.1f} points"
                    if prior.get("score_change") is not None
                    else "score change unavailable"
                )
                + f", and an improvement plan was {'created' if prior.get('plan_created') else 'not created'}."
                if prior else None
            ),
        })
    return suggestions