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
    forecast  = analysis.get("forecast", {})
    by_rating = analysis.get("by_rating", {})
    history   = analysis.get("history", [])
    teacher_summaries = analysis.get("teacher_summaries", []) or []
    dimension_outlook = analysis.get("dimension_outlook", []) or []
    previous_suggestions = analysis.get("previous_suggestions", []) or []
    dimension_lines = "\n".join(
        f"  - dimension_id={int(d.get('dimension_id', 0))}, "
        f"dimension_no={int(d.get('dimension_no', 0))}, "
        f"dimension_name={d.get('dimension_name', '')!r}, "
        f"score={d.get('score', d.get('dimension_score', 'N/A'))}%"
        for d in dimensions
    )

    weakest_dims = gap.get("weakest_dimensions", [])
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

    previous_by_dimension = {
        int(item.get("dimension_no", 0)): item
        for item in previous_suggestions
        if int(item.get("dimension_no", 0)) > 0
    }
    previous_lines = []
    for dimension in dimensions:
        dimension_no = int(dimension.get("dimension_no", 0))
        previous = previous_by_dimension.get(dimension_no)
        prefix = (
            f"  - dimension_no={dimension_no}, "
            f"current dimension_id={int(dimension.get('dimension_id', 0))}: "
        )
        if previous is None:
            previous_lines.append(prefix + "No previous-year suggestion or outcome is available.")
            continue

        if not previous.get("has_previous_suggestion", True):
            score_change = previous.get("score_change")
            if score_change is None:
                score_result = "score change is unavailable"
            else:
                score_change = float(score_change)
                direction = "improved" if score_change > 0 else ("declined" if score_change < 0 else "was unchanged")
                score_result = f"score {direction} by {abs(score_change):.1f} points"
            previous_score = previous.get("score_at_time")
            current_score = previous.get("current_score")
            previous_score_label = f"{float(previous_score):.1f}%" if previous_score is not None else "unknown"
            current_score_label = f"{float(current_score):.1f}%" if current_score is not None else "unknown"
            previous_lines.append(
                prefix
                + f"No saved AI suggestion was recorded last cycle. Result: score at the time "
                + f"{previous_score_label}, current score {current_score_label}; {score_result}; "
                + "there was no prior suggestion to adopt into an improvement plan."
            )
            continue

        suggestion_text = str(previous.get("suggestion_text", "")).strip().rstrip(".!?")
        score_change = previous.get("score_change")
        if score_change is None:
            score_result = "score change is unavailable"
        else:
            score_change = float(score_change)
            direction = "improved" if score_change > 0 else ("declined" if score_change < 0 else "was unchanged")
            score_result = f"score {direction} by {abs(score_change):.1f} points"
        previous_score = previous.get("score_at_time")
        current_score = previous.get("current_score")
        previous_score_label = f"{float(previous_score):.1f}%" if previous_score is not None else "unknown"
        current_score_label = f"{float(current_score):.1f}%" if current_score is not None else "unknown"
        plan_result = "an improvement plan was created" if previous.get("plan_created") else "no improvement plan was created"
        previous_lines.append(
            prefix
            + f"Last cycle you suggested: {suggestion_text}. "
            + f"Result: score at the time {previous_score_label}, "
            + f"current score {current_score_label}; {score_result}; {plan_result}."
        )
    previous_suggestion_lines = "\n".join(previous_lines)
    improvement_cycle_guidance = (
        "Previous-year suggestions and outcomes:\n"
        + (previous_suggestion_lines or "  (none — generate as usual)")
        + "\nRules for this cycle: Don't repeat suggestions that were adopted and worked; "
        "replace suggestions that were ignored or followed by a score decline; build on what improved; "
        "reference last year's result where relevant. Keep every suggestion strictly within its own listed "
        "dimension's topic; never move curriculum advice into leadership, governance, or another dimension. "
        "Use prior suggestions only for the matching dimension_no. "
        "Do not put claims about last cycle, prior suggestions, score changes, or plan adoption in the new suggestion text; "
        "put historical facts only in last_cycle_note. Never claim an indicator weakness or result not present in the supplied data. "
        "If you continue any action from last cycle, explicitly state in last_cycle_note why it is being continued, "
        "grounding that reason in the supplied score change and plan linkage without claiming causation. "
        "If the prior suggestion was not adopted, replace its actions rather than repeating them, even when the score improved. "
        "When no weak indicators are supplied for this dimension, do not invent indicator codes or gaps; propose a modest, "
        "measurable action directly relevant to this dimension instead. "
        "When a prior score declined, acknowledge the actual numeric dip using the supplied scores (for example, "
        "'Score dipped from 78.8% to 78.1%') and adjust the advice to address evidence-backed gaps; do not merely "
        "rephrase last year's suggestion. When a score improved, acknowledge the gain and build on actions supported "
        "by the evidence. Do not claim the prior suggestion caused improvement unless an improvement plan was created. "
        "For each dimension with any previous-cycle result, return exactly one concise sentence in last_cycle_note using this "
        "format: 'Last cycle: [brief prior-suggestion takeaway]; Result: [score change and whether a plan was created].' "
        "If the new suggestion deliberately continues an action, explain briefly in the prior-suggestion takeaway why it is "
        "being continued (for example, the score improved and a plan was linked); do not imply the plan caused the change. "
        "When a score exists but no prior suggestion was saved, clearly say no suggestion was recorded and report the "
        "real score outcome; do not invent a suggestion or plan. Return null only when no previous-cycle suggestion or score exists."
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

    For each assessment dimension below, write one concise suggestion. Keep the
    title separate from the body. Body text should use natural prose and may use
    lines beginning with "- " for actions. Do not include the title again in body.

    Exact dimensions for this assessment:
    {dimension_lines if dimension_lines else "  (none provided)"}

    Return ONLY valid JSON with exactly this shape:
    {{
      "suggestions": [
        {{
          "dimension_id": 123,
          "title": "Short action-oriented title",
          "text": "Context followed by specific actions.",
          "last_cycle_note": "One sentence describing last year's result, or null when no previous data exists."
        }}
      ]
    }}

    Include each listed dimension exactly once. Copy its integer dimension_id
    exactly; do not infer IDs, use array positions, or assign a suggestion to a
    different dimension. The dimension_name is the authoritative subject for
    that entry. Do not include dimensions that are not listed.
    Every title and action must fit that exact dimension_name; do not borrow
    another dimension's subject matter or indicator codes. If continuing an action
    from the matching prior suggestion, explain in last_cycle_note why it is being
    continued using the supplied result.

    School: {school_name}
    School Year: {sy_label}
    Overall SBM Score: {gap.get('average_score', 'N/A')}%
    Maturity Level: {gap.get('overall_maturity', 'N/A')}
    {history_line}
    {trend_line}

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

    Keep each text field warm, professional, actionable, and concise. Reference
    DepEd Order No. 007, s. 2024 where relevant. Return one suggestion per exact
    listed dimension, using the JSON structure above and no surrounding prose.
    Each suggestion MUST contain at least three distinct, concrete action bullets.
    Every action must identify a responsible owner or role and a frequency or
    deadline. Include a related indicator code when one is available in the supplied
    data, and never invent a code. Do not treat a single document review as a full
    suggestion.     unless the dimension score is already at least 87.5% (Advanced), the new
    suggestion must not be shorter or less specific than an existing prior-year
    suggestion; if an Advanced score justifies a shorter suggestion, state that
    reason explicitly in last_cycle_note.
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
    # Assessment-derived content must stay on the local Ollama service.
    return "ollama"


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

    model = os.getenv("GROQ_MODEL", "openai/gpt-oss-120b")

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
    backend = _privacy_safe_backend(backend)
    prompt = _build_form_extraction_prompt(raw_text)
    error  = None
    parsed = {"dimensions": []}

    try:
        text = _call_ollama(prompt)

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

CONFIDENCE_WEIGHTS = {
    "indicator_evidence": 0.40,
    "respondent_consistency": 0.25,
    "historical_evidence": 0.20,
    "data_completeness": 0.15,
}


def _indicator_evidence_score(codes: list, by_rating: dict) -> tuple:
    """
    40% factor. Uses the actual rating value (1 = Not Yet Manifested,
    2 = Emerging) for each indicator code mentioned in this recommendation
    block. Lower ratings = stronger evidence of a real gap = higher score.
    Returns (score_0_100, list_of_factor_strings, matched_count).
    """
    ratings = []
    rating1 = by_rating.get("1", by_rating.get(1, []))
    rating2 = by_rating.get("2", by_rating.get(2, []))
    lookup = {ind.get("code"): ind.get("rating") for ind in (rating1 + rating2) if ind.get("code")}

    for code in codes:
        if code in lookup and lookup[code] is not None:
            ratings.append(float(lookup[code]))

    if not ratings:
        return None, [], 0

    # rating 1.0 -> 100 evidence strength, rating 2.49 (weak cutoff) -> ~0
    scores = [max(0.0, min(100.0, (2.5 - r) / 1.5 * 100)) for r in ratings]
    avg_score = sum(scores) / len(scores)
    factor_note = f"{len(ratings)} indicator{'s' if len(ratings) != 1 else ''} identified as below target"
    return avg_score, [factor_note], len(ratings)


def _respondent_consistency_score(codes: list, respondent_consistency: dict) -> tuple:
    """
    25% factor. Compares School-Head-pool vs Teacher-pool average ratings
    for the same indicator. Smaller gap = higher agreement = higher score.
    Rating scale is 1-4, so max possible gap is 3.
    """
    agreements = []
    for code in codes:
        entry = respondent_consistency.get(code)
        if not entry:
            continue
        sh = entry.get("sh_avg")
        tc = entry.get("teacher_avg")
        if sh is None or tc is None:
            continue
        gap = abs(float(sh) - float(tc))
        agreement_pct = max(0.0, min(100.0, (1 - gap / 3.0) * 100))
        agreements.append(agreement_pct)

    if not agreements:
        return None, []

    avg_agreement = sum(agreements) / len(agreements)
    factor_note = f"{round(avg_agreement)}% respondent agreement between School Head and Teacher ratings"
    return avg_agreement, [factor_note]


def _historical_evidence_score(codes: list, indicator_history: dict) -> tuple:
    """
    20% factor. Checks whether the same indicator(s) were also rated weak
    (<2.5) in prior cycles. Consistent weakness across cycles = higher score.
    Gracefully returns None (not 0) when no prior-cycle data exists at all,
    since "no history" is not the same as "inconsistent history".
    """
    any_history = False
    persistent_hits = 0
    total_checked = 0

    for code in codes:
        hist = indicator_history.get(code, [])
        if not hist:
            continue
        any_history = True
        total_checked += 1
        weak_count = sum(1 for r in hist if r < 2.5)
        if weak_count / len(hist) >= 0.5:
            persistent_hits += 1

    if not any_history:
        return None, []

    consistency_pct = (persistent_hits / total_checked * 100) if total_checked else 0
    if consistency_pct >= 50:
        factor_note = "Consistent performance issue across prior assessment cycles"
    else:
        factor_note = "This issue appears newly emerging (not consistently flagged in prior cycles)"
    return consistency_pct, [factor_note]


def _data_completeness_score(data_completeness) -> tuple:
    """15% factor. Straight passthrough of the rated-vs-total percentage."""
    if data_completeness is None:
        return None, []
    pct = float(data_completeness)
    factor_note = f"Assessment data is {round(pct)}% complete"
    return pct, [factor_note]


def compute_recommendation_confidence(block_text: str, analysis: dict) -> dict:
    """
    Computes a transparent, data-derived confidence score for one
    recommendation block (NOT the LLM's self-reported certainty).
    Returns None fields / 'insufficient_data' when too little data exists
    to calculate something meaningful, rather than inventing a number.
    """
    codes = sorted(set(re.findall(r'\b\d{1,2}\.\d{1,2}\b', block_text)))
    by_rating = analysis.get("by_rating", {}) or {}
    respondent_consistency = analysis.get("respondent_consistency", {}) or {}
    indicator_history = analysis.get("indicator_history", {}) or {}
    data_completeness = analysis.get("data_completeness")

    ev_score, ev_factors, matched_count = _indicator_evidence_score(codes, by_rating)
    rc_score, rc_factors = _respondent_consistency_score(codes, respondent_consistency)
    he_score, he_factors = _historical_evidence_score(codes, indicator_history)
    dc_score, dc_factors = _data_completeness_score(data_completeness)

    # Not enough grounding to say anything meaningful — most commonly when
    # the recommendation block didn't reference specific indicator codes at all.
    if ev_score is None and matched_count == 0:
        return {
            "insufficient_data": True,
            "reason": "Not enough completed assessment data is available to calculate a reliable recommendation confidence score.",
            "indicator_codes": codes,
        }

    weighted_total = 0.0
    weight_used = 0.0
    factors = []

    for score, factor_list, weight_key in [
        (ev_score, ev_factors, "indicator_evidence"),
        (rc_score, rc_factors, "respondent_consistency"),
        (he_score, he_factors, "historical_evidence"),
        (dc_score, dc_factors, "data_completeness"),
    ]:
        if score is not None:
            w = CONFIDENCE_WEIGHTS[weight_key]
            weighted_total += score * w
            weight_used += w
            factors.extend(factor_list)

    if weight_used == 0:
        return {
            "insufficient_data": True,
            "reason": "Not enough completed assessment data is available to calculate a reliable recommendation confidence score.",
            "indicator_codes": codes,
        }

    # Re-normalize against only the factors we actually had data for, so a
    # missing factor doesn't silently drag the score down — it's excluded,
    # not scored as zero.
    final_pct = round(weighted_total / weight_used, 1)
    final_pct = max(0.0, min(100.0, final_pct))

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
        text = _call_ollama(prompt)
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

    try:
        text = _call_ollama(prompt)
        suggestions = _parse_dimension_suggestions(
            text, dimensions, analysis.get("previous_suggestions", [])
        )
    except Exception as e:
        import logging
        import traceback
        tb = traceback.format_exc()
        logging.error(f"Error generating recommendations ({backend}): {e}\n{tb}")
        print(f"[ML ERROR] {backend} failed: {e}\n{tb}", flush=True)
        warning = f"Local Ollama generation failed; rule-based suggestions need review: {e}"
        suggestions = _rule_based_dimension_suggestions(analysis, dimensions)
        for suggestion in suggestions:
            suggestion["action_count"] = _count_suggestion_actions(suggestion["text"])
            suggestion["needs_review"] = True
            suggestion["review_reason"] = "The local model was unavailable; verify and expand this suggestion."
            suggestion["model_version"] = "rule_based_local_fallback"
        backend = "rule_based_local_fallback"

    dimensions_by_id = {
        int(dimension["dimension_id"]): dimension
        for dimension in dimensions
        if isinstance(dimension, dict) and str(dimension.get("dimension_id", "")).isdigit()
    }
    for suggestion in suggestions:
        if not suggestion.get("needs_review"):
            continue
        dimension_id = int(suggestion["dimension_id"])
        dimension = dimensions_by_id[dimension_id]
        try:
            retry_prompt = _build_single_dimension_retry_prompt(
                analysis, school_name, sy_label, dimension
            )
            retry_text = _call_ollama(retry_prompt, model="llama3.2:3b")
            retry_result = _parse_dimension_suggestions(
                retry_text,
                [dimension],
                analysis.get("previous_suggestions", []),
            )[0]
            retry_count = _count_suggestion_actions(retry_result["text"])
            original_count = int(suggestion.get("action_count", 0))
            if retry_count >= 3:
                retry_result["action_count"] = retry_count
                retry_result["needs_review"] = False
                retry_result["review_reason"] = None
                retry_result["model_version"] = "llama3.2:3b"
                suggestion.update(retry_result)
            else:
                if retry_count > original_count:
                    suggestion.update(retry_result)
                suggestion["action_count"] = max(original_count, retry_count)
                suggestion["needs_review"] = True
                suggestion.setdefault("model_version", "llama3.2:3b")
                suggestion["review_reason"] = (
                    f"Only {suggestion['action_count']} concrete action bullets were returned after one retry."
                )
        except Exception as retry_error:
            import logging
            logging.warning(
                "Single-dimension retry failed for dimension_id %s: %s",
                dimension_id,
                retry_error,
            )
            suggestion["needs_review"] = True
            suggestion.setdefault("model_version", "llama3.2:3b")
            suggestion["review_reason"] = (
                f"Fewer than three action bullets were returned; the one local retry failed: {retry_error}"
            )

    text = _format_dimension_suggestions(suggestions)

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
            "model_version": suggestion.get("model_version", "llama3.2:3b"),
            **compute_recommendation_confidence(block_text, analysis),
        })

    model_source = (
        dimension_outlook[0].get("model_source", "rule_fallback")
        if dimension_outlook else "rule_fallback"
    )
    confidence = (
        round(min(float(item.get("confidence", 0.55)) for item in dimension_outlook), 3)
        if dimension_outlook else 0.55
    )
    if model_source == "rule_fallback":
        for block in blocks:
            block["confidence_pct"] = 55
            block["confidence_level"] = "Rule-based fallback"

    return {
        "recommendations": text,
        "blocks": blocks,
        "suggestions": suggestions,
        "backend_used": "llama3.2:3b" if backend == "ollama" else backend,
        "error": error,
        "warning": warning,
        "privacy_notice": (
            "Remote model backends are disabled; generation uses local Ollama."
            if configured_backend in {"groq", "openai"} else None
        ),
        "prompt_chars": len(prompt),
        "model_source": model_source,
        "confidence": confidence,
        "dimension_outlook": dimension_outlook,
    }


def _parse_dimension_suggestions(
    raw_text: str,
    dimensions: list[dict],
    previous_suggestions: list[dict] | None = None,
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
        title = str(suggestion.get("title", "")).strip()
        body = str(suggestion.get("text", "")).strip()
        if not title or not body:
            raise ValueError(f"Recommendation for dimension_id {dimension_id} is missing title or text.")
        action_count = _count_suggestion_actions(body)
        last_cycle_note = suggestion.get("last_cycle_note")
        has_previous = int(dimension.get("dimension_no", 0)) in previous_by_dimension
        if has_previous and (not isinstance(last_cycle_note, str) or not last_cycle_note.strip()):
            raise ValueError(f"Recommendation for dimension_id {dimension_id} is missing last_cycle_note.")
        if has_previous:
            note = last_cycle_note.strip()
            if (
                not note.startswith("Last cycle:")
                or "; Result:" not in note
                or "\n" in note
                or note[-1:] not in ".!?"
                or re.search(r"[.!?]\s+\S", note.rstrip(".!?"))
            ):
                raise ValueError(
                    f"Recommendation for dimension_id {dimension_id} must return one formatted last_cycle_note sentence."
                )
        if not has_previous and last_cycle_note is not None:
            raise ValueError(f"Recommendation for dimension_id {dimension_id} must use null last_cycle_note without prior data.")
        seen_ids.add(dimension_id)
        validated.append({
            "dimension_id": dimension_id,
            "dimension_no": int(dimension.get("dimension_no", 0)),
            "dimension_name": str(dimension.get("dimension_name", "")),
            "title": title,
            "text": body,
            "last_cycle_note": last_cycle_note.strip() if isinstance(last_cycle_note, str) else None,
            "action_count": action_count,
            "needs_review": action_count < 3,
            "review_reason": (
                f"Only {action_count} concrete action bullets were returned."
                if action_count < 3 else None
            ),
            "model_version": "llama3.2:3b",
        })
    missing = set(dimensions_by_id) - seen_ids
    if missing:
        raise ValueError(f"Recommendation omitted dimension IDs: {', '.join(map(str, sorted(missing)))}.")
    return validated


def _format_dimension_suggestions(suggestions: list[dict]) -> str:
    sections = ["Here are some targeted suggestions based on your school's current SBM data:"]
    for suggestion in suggestions:
        sections.append(f"**{suggestion['title']}**\n{suggestion['text']}")
    return "\n\n".join(sections)


def _rule_based_dimension_suggestions(analysis: dict, dimensions: list[dict]) -> list[dict]:
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
        score = dimension.get("score", dimension.get("dimension_score", "N/A"))
        prior = previous_by_dimension.get(dimension_no)
        text = (
            f"Review the current evidence and score for {name}, then select a focused, "
            "measurable action with a responsible person and review date."
        )
        if prior:
            text += " Build on the previous cycle's work without repeating actions that already succeeded."
        suggestions.append({
            "dimension_id": dimension_id,
            "dimension_no": dimension_no,
            "dimension_name": name,
            "title": f"Strengthen {name}",
            "text": f"{text} Current dimension score: {score}%.",
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