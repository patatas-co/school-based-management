"""
comment_analyzer.py
Analyzes free-text remarks from teachers and stakeholders.
Handles Filipino/English code-mixed text.
Phase 1 (Year 1): Rule-based. Phase 2: upgrade to fine-tuned model.
"""
import re
from vaderSentiment.vaderSentiment import SentimentIntensityAnalyzer
from rapidfuzz import fuzz

# --- Filipino negation / intensifier map (code-mix aware) ---
TAGALOG_NEGATIONS = {"hindi", "wala", "walang", "huwag", "di"}
TAGALOG_INTENSIFIERS = {"napaka", "sobra", "masyado", "talagang", "talaga"}

# --- Topic keyword taxonomy (DepEd SBM-aligned) ---
TOPIC_TAXONOMY = {
    "bullying":         ["bully", "bullying", "harassment", "nanggugulo",
                         "nang-aaway", "intimidation"],
    "ict_resources":    ["computer", "internet", "laptop", "tablet", "ict",
                         "projector", "gadget", "device", "wi-fi", "wifi"],
    "facilities":       ["room", "classroom", "toilet", "cr", "canteen",
                         "library", "laboratory", "lab", "building",
                         "palikuran", "silid"],
    "teacher_quality":  ["teacher", "teaching", "guro", "instructor",
                         "training", "seminars", "professional"],
    "curriculum":       ["curriculum", "lesson", "module", "lm", "materials",
                         "syllabus", "learning"],
    "safety":           ["safe", "unsafe", "hazard", "risk", "emergency",
                         "evacuation", "drrm", "disaster"],
    "participation":    ["involve", "participation", "engagement", "ssg",
                         "spg", "stakeholder", "community", "pta"],
    "mental_health":    ["mental", "stress", "wellness", "counseling",
                         "emotional", "kalusugan"],
    "finance":          ["budget", "funds", "mooe", "money", "pondo",
                         "financial", "spending"],
    "leadership":       ["principal", "head", "management", "leadership",
                         "admin", "governance"],
}

# Urgency signals: phrases that flag immediate action needed
URGENCY_PATTERNS = [
    r"\burgent\b", r"\bimmediate\b", r"\bnow\b", r"\bkailanman\b",
    r"\basap\b", r"\bcritical\b", r"\bdangerous\b", r"\bdeadly\b",
    r"\bhazardous\b", r"\bbroken\b", r"\bdamaged\b", r"\bleaking\b",
    r"\bgrave\b", r"\bserious(ly)?\b", r"\bmalala\b",  # Tagalog: severe
    r"\bnapanganib\b",                                   # endangered
    r"\bwalang.{0,15}(tubig|kuryente|ilaw)\b",           # no water/electricity
]

vader = SentimentIntensityAnalyzer()

# Augment VADER lexicon with Filipino-flavored words
FILIPINO_LEXICON = {
    # Positive Filipino words
    "maganda":  2.5,  "mabuti": 2.0,  "mahusay": 2.8,  "magaling": 2.5,
    "maayos":   2.0,  "masaya": 2.2,  "maliwanag": 1.8, "epektibo": 2.0,
    "kapaki-pakinabang": 2.3, "nakakatulong": 2.0, "mahalaga": 1.5,
    "nag-improve": 2.0, "napabuti": 2.2, "nagtagumpay": 2.5,
    "maganda ang": 2.3, "okay naman": 1.2, "sapat": 1.0,
    "nandoon": 0.5, "meron": 0.5, "mayroon": 0.5,
    "napapanahon": 1.5, "organisado": 1.8, "aktibo": 1.5,

    # Negative Filipino words
    "masama":  -2.0, "malala": -2.5, "mahirap": -1.5, "kulang":  -1.8,
    "sirang":  -2.5, "wala":   -1.0, "hindi":   -0.5, "problema": -1.5,
    "nakakainis": -2.2, "mapanganib": -2.5, "walang": -1.2,
    "bigo": -2.0, "hindi epektibo": -2.0, "hindi maayos": -1.8,
    "hindi sapat": -1.6, "kulang-kulang": -2.0, "pabaya": -2.0,
    "hindi organisado": -1.8, "di maayos": -1.8, "wala pang": -1.5,
    "marami pa ring": -1.5, "hindi pa": -1.0, "di pa": -1.0,
    "hindi naipapatupad": -2.2, "hindi naisasagawa": -2.2,

    # Strong negative intensifiers
    "sobrang masama": -3.0, "napaka-pangit": -2.8,
    "napaka-dami": -1.5, "lubhang kulang": -2.5,
}
vader.lexicon.update(FILIPINO_LEXICON)


def preprocess(text: str) -> str:
    """Lowercase, strip excess whitespace, normalize punctuation."""
    text = text.lower().strip()
    text = re.sub(r"\s+", " ", text)
    text = re.sub(r"[^\w\s.,!?'-]", " ", text)
    return text


def analyze_sentiment(text: str) -> dict:
    """
    Returns compound score and label.
    Applies simple Tagalog negation flip:
      if Filipino negation precedes a positive word, invert sign.
    """
    cleaned = preprocess(text)
    tokens = cleaned.split()

    # Detect Tagalog negation: flip compound if negation found near top
    has_negation = any(t in TAGALOG_NEGATIONS for t in tokens[:5])
    scores = vader.polarity_scores(cleaned)
    compound = scores["compound"]

    if has_negation and compound > 0.1:
        compound = -abs(compound) * 0.8

    if compound >= 0.05:
        label = "positive"
    elif compound <= -0.05:
        label = "negative"
    else:
        label = "neutral"

    return {
        "compound": round(compound, 4),
        "label": label,
        "pos": round(scores["pos"], 3),
        "neg": round(scores["neg"], 3),
        "neu": round(scores["neu"], 3),
    }


def extract_topics(text: str) -> list:
    """
    Returns list of matched topic keys.
    Uses exact regex match first, then fuzzy match for misspellings.
    Handles Filipino/English code-mixed text.
    """
    cleaned = preprocess(text)
    words   = cleaned.split()
    matched = []

    for topic, keywords in TOPIC_TAXONOMY.items():
        # Step 1: Fast exact match via regex
        pattern = r"\b(" + "|".join(re.escape(k) for k in keywords) + r")\b"
        if re.search(pattern, cleaned):
            matched.append(topic)
            continue

        # Step 2: Fuzzy match for misspellings (e.g. "bulying", "harasment")
        # Only apply fuzzy on words longer than 4 chars to avoid false positives
        found_fuzzy = False
        for word in words:
            if len(word) < 4:
                continue
            for kw in keywords:
                if len(kw) < 4:
                    continue
                score = fuzz.ratio(word, kw)
                if score > 82:
                    matched.append(topic)
                    found_fuzzy = True
                    break
            if found_fuzzy:
                break

    return list(set(matched))


def detect_urgency(text: str) -> dict:
    """
    Returns urgency level: 'high', 'medium', or 'low'.
    High = multiple urgency signals or safety/infrastructure issue.
    """
    cleaned = preprocess(text)
    hits = sum(
        1 for pat in URGENCY_PATTERNS if re.search(pat, cleaned)
    )
    topics = extract_topics(text)
    safety_topics = {"bullying", "safety", "facilities"}
    has_safety = bool(safety_topics & set(topics))

    if hits >= 2 or (hits >= 1 and has_safety):
        level = "high"
    elif hits == 1:
        level = "medium"
    else:
        level = "low"

    return {"level": level, "signal_count": hits}


def analyze_comment(comment: str, indicator_code: str = None) -> dict:
    """
    Full pipeline for a single comment.
    Returns a dict ready for INSERT into ml_comment_analysis.
    """
    if not comment or not comment.strip():
        return {
            "sentiment_label": "neutral",
            "sentiment_score": 0.0,
            "topics": [],
            "urgency_level": "low",
            "urgency_signals": 0,
        }

    sentiment = analyze_sentiment(comment)
    topics = extract_topics(comment)
    urgency = detect_urgency(comment)

    return {
        "sentiment_label": sentiment["label"],
        "sentiment_score": sentiment["compound"],
        "topics": topics,
        "urgency_level": urgency["level"],
        "urgency_signals": urgency["signal_count"],
    }


def batch_analyze(comments: list) -> dict:
    """
    Aggregate multiple comment analyses for one cycle.
    Used by the PHP bridge to get dimension-level summaries.
    """
    if not comments:
        return {"total": 0, "sentiment_counts": {}, "top_topics": [], "has_urgent": False}

    results = [analyze_comment(c.get("text", "")) for c in comments]

    sentiment_counts = {"positive": 0, "negative": 0, "neutral": 0}
    topic_freq: dict = {}
    has_urgent = False

    for r in results:
        sentiment_counts[r["sentiment_label"]] += 1
        if r["urgency_level"] == "high":
            has_urgent = True
        for t in r["topics"]:
            topic_freq[t] = topic_freq.get(t, 0) + 1

    top_topics = sorted(topic_freq.items(), key=lambda x: -x[1])[:5]

    return {
        "total": len(results),
        "sentiment_counts": sentiment_counts,
        "top_topics": [t[0] for t in top_topics],
        "has_urgent": has_urgent,
        "individual": results,
    }