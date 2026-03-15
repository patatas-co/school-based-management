<?php
/**
 * ml_service.php
 * PHP ↔ Python ML microservice bridge.
 * Called after assessment finalization or from the analytics dashboard.
 */

define('ML_SERVICE_URL', getenv('ML_SERVICE_URL') ?: 'http://127.0.0.1:5000');
define('ML_SECRET',      getenv('ML_SECRET')      ?: 'sbm-ml-secret-change-in-production');

function ml_post(string $endpoint, array $payload): ?array
{
    $ch = curl_init(ML_SERVICE_URL . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-ML-Secret: ' . ML_SECRET,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $body  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$body) {
        error_log("ML service error: $error");
        return null;
    }
    return json_decode($body, true);
}

/**
 * Triggered after a cycle is finalized/submitted.
 * Stores results in MySQL via ml_recommendations + ml_predictions tables.
 */
function runMLPipeline(PDO $db, int $cycleId): bool
{
    // 1. Gather dim scores
    $dimQ = $db->prepare("
        SELECT d.dimension_no, d.dimension_name, ds.percentage
        FROM sbm_dimension_scores ds
        JOIN sbm_dimensions d ON ds.dimension_id = d.dimension_id
        WHERE ds.cycle_id = ?
    ");
    $dimQ->execute([$cycleId]);
    $dimScores = [];
    foreach ($dimQ->fetchAll() as $row) {
        $dimScores[(int)$row['dimension_no']] = [
            'percentage'     => (float)$row['percentage'],
            'dimension_name' => $row['dimension_name'],
        ];
    }

    // 2. Gather ALL indicator responses with ratings (1-4), not just weak ones
    $indQ = $db->prepare("
        SELECT 
            i.indicator_id,
            i.indicator_code,
            i.indicator_text,
            d.dimension_no,
            d.dimension_name,
            r.rating,
            r.evidence_text
        FROM sbm_responses r
        JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
        JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
        WHERE r.cycle_id = ?
        ORDER BY d.dimension_no, r.rating ASC
    ");
    $indQ->execute([$cycleId]);
    $allIndicators = $indQ->fetchAll();

    // Also gather teacher responses for teacher indicators
    $teacherIndQ = $db->prepare("
        SELECT 
            i.indicator_id,
            i.indicator_code,
            i.indicator_text,
            d.dimension_no,
            d.dimension_name,
            ROUND(AVG(tr.rating), 2) AS rating,
            NULL AS evidence_text
        FROM teacher_responses tr
        JOIN sbm_indicators i ON tr.indicator_id = i.indicator_id
        JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
        WHERE tr.cycle_id = ?
        GROUP BY i.indicator_id
        ORDER BY d.dimension_no, AVG(tr.rating) ASC
    ");
    $teacherIndQ->execute([$cycleId]);
    $teacherIndicators = $teacherIndQ->fetchAll();

    // Merge all indicators, teacher overrides take precedence
    $mergedIndicators = $allIndicators;
    $shIndicatorIds   = array_column($allIndicators, 'indicator_id');
    foreach ($teacherIndicators as $ti) {
        if (!in_array($ti['indicator_id'], $shIndicatorIds)) {
            $mergedIndicators[] = $ti;
        }
    }

    // 3. Group indicators by rating level for structured analysis
    $byRating = [1 => [], 2 => [], 3 => [], 4 => []];
    foreach ($mergedIndicators as $ind) {
        $rating = (int)round((float)$ind['rating']);
        if ($rating >= 1 && $rating <= 4) {
            $byRating[$rating][] = [
                'code'           => $ind['indicator_code'],
                'text'           => $ind['indicator_text'],
                'dimension_no'   => $ind['dimension_no'],
                'dimension_name' => $ind['dimension_name'],
                'rating'         => (float)$ind['rating'],
                'evidence'       => $ind['evidence_text'] ?? '',
            ];
        }
    }

    // 4. Gather ALL text remarks (teacher + stakeholder + SH evidence)
    $commentsRaw = [];

    // Teacher remarks
    $commQ = $db->prepare("
        SELECT 
            tr.remarks AS text,
            'teacher' AS source,
            i.indicator_code,
            i.indicator_text,
            d.dimension_name,
            tr.rating
        FROM teacher_responses tr
        JOIN sbm_indicators i ON tr.indicator_id = i.indicator_id
        JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
        WHERE tr.cycle_id = ? 
          AND tr.remarks IS NOT NULL 
          AND tr.remarks != ''
    ");
    $commQ->execute([$cycleId]);
    foreach ($commQ->fetchAll() as $row) {
        $commentsRaw[] = $row;
    }

    // Stakeholder remarks
    $stakeQ = $db->prepare("
        SELECT 
            sr.remarks AS text,
            'stakeholder' AS source,
            i.indicator_code,
            i.indicator_text,
            d.dimension_name,
            sr.rating
        FROM stakeholder_responses sr
        JOIN sbm_indicators i ON sr.indicator_id = i.indicator_id
        JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
        WHERE sr.cycle_id = ?
          AND sr.remarks IS NOT NULL
          AND sr.remarks != ''
    ");
    $stakeQ->execute([$cycleId]);
    foreach ($stakeQ->fetchAll() as $row) {
        $commentsRaw[] = $row;
    }

    // School Head evidence notes from SBM responses
    $evidenceQ = $db->prepare("
        SELECT 
            r.evidence_text AS text,
            'school_head' AS source,
            i.indicator_code,
            i.indicator_text,
            d.dimension_name,
            r.rating
        FROM sbm_responses r
        JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
        JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
        WHERE r.cycle_id = ?
          AND r.evidence_text IS NOT NULL
          AND r.evidence_text != ''
    ");
    $evidenceQ->execute([$cycleId]);
    foreach ($evidenceQ->fetchAll() as $row) {
        $commentsRaw[] = $row;
    }

    // 5. Gather historical scores for trend
    $histQ = $db->prepare("
        SELECT YEAR(c.submitted_at) cycle_year, c.overall_score
        FROM sbm_cycles c
        WHERE c.school_id = (SELECT school_id FROM sbm_cycles WHERE cycle_id = ?)
          AND c.status = 'validated'
          AND c.cycle_id != ?
        ORDER BY c.submitted_at ASC
        LIMIT 5
    ");
    $histQ->execute([$cycleId, $cycleId]);
    $history = $histQ->fetchAll();

    // 6. Get school info
    $metaQ = $db->prepare("
        SELECT s.school_name, s.classification, sy.label sy_label,
               c.overall_score, c.maturity_level
        FROM sbm_cycles c
        JOIN schools s ON c.school_id = s.school_id
        JOIN school_years sy ON c.sy_id = sy.sy_id
        WHERE c.cycle_id = ?
    ");
    $metaQ->execute([$cycleId]);
    $meta = $metaQ->fetch();

    // 7. Build the full payload
    $payload = [
        'cycle_id'       => $cycleId,
        'school_name'    => $meta['school_name']    ?? 'School',
        'classification' => $meta['classification'] ?? '',
        'sy_label'       => $meta['sy_label']       ?? '',
        'overall_score'  => $meta['overall_score']  ?? 0,
        'maturity_level' => $meta['maturity_level'] ?? 'Beginning',
        'dim_scores'     => array_map(fn($d) => $d['percentage'], $dimScores),
        'dim_details'    => $dimScores,
        'indicators'     => $mergedIndicators,
        'by_rating'      => $byRating,
        'comments'       => $commentsRaw,
        'history'        => $history,
        // Summary counts
        'rating_summary' => [
            'not_yet_manifested' => count($byRating[1]),
            'emerging'           => count($byRating[2]),
            'developing'         => count($byRating[3]),
            'always_manifested'  => count($byRating[4]),
            'total_rated'        => count($mergedIndicators),
        ],
    ];

    // 8. Try ML service first, fall back to rule-based
    $result = null;
    if (defined('ML_SERVICE_URL') && ML_SERVICE_URL !== 'http://127.0.0.1:5000') {
        $result = ml_post('/api/full_pipeline', $payload);
    }

    // If ML service unavailable or not configured, use built-in rule-based engine
    if (!$result) {
        $result = runRuleBasedPipeline($payload);
    }

    if (!$result) return false;

    // 9. Store results
    saveMLResults($db, $cycleId, $result);
    return true;
}

/**
 * Built-in rule-based pipeline — runs entirely in PHP.
 * No Python or external service needed.
 */
function runRuleBasedPipeline(array $payload): array
{
    $byRating    = $payload['by_rating'];
    $dimScores   = $payload['dim_scores'];
    $comments    = $payload['comments'];
    $schoolName  = $payload['school_name'];
    $syLabel     = $payload['sy_label'];
    $overall     = (float)($payload['overall_score'] ?? 0);
    $maturity    = $payload['maturity_level'] ?? 'Beginning';
    $ratingSummary = $payload['rating_summary'];

    $ratingLabels = [
        1 => 'Not Yet Manifested',
        2 => 'Emerging',
        3 => 'Developing',
        4 => 'Always Manifested',
    ];

    // ── Step 1: Analyze scores ─────────────────────────────────
    $avgScore = count($dimScores) > 0 
        ? round(array_sum($dimScores) / count($dimScores), 1) 
        : 0;

    $weakestDims = [];
    arsort($dimScores); // sort desc
    $sortedDims  = $dimScores;
    asort($sortedDims); // sort asc = weakest first

    $dimNames = [
        1 => 'Curriculum and Teaching',
        2 => 'Learning Environment',
        3 => 'Leadership and Governance',
        4 => 'Accountability and Continuous Improvement',
        5 => 'Human Resource Development',
        6 => 'Finance and Resource Management',
    ];

    foreach ($sortedDims as $dimNo => $pct) {
        $gap = $avgScore - $pct;
        $weakestDims[] = [
            'dimension_no'   => $dimNo,
            'dimension_name' => $dimNames[$dimNo] ?? "Dimension $dimNo",
            'score'          => $pct,
            'gap_from_avg'   => round($gap, 1),
            'maturity'       => getMaturityLabel($pct),
            'priority'       => $gap > 15 ? 'high' : ($gap > 5 ? 'medium' : 'low'),
        ];
    }

    // ── Step 2: Analyze all remarks ────────────────────────────
    $commentAnalysis = analyzeAllRemarks($comments);

    // ── Step 3: Build structured recommendations ───────────────
    $recommendations = buildStructuredRecommendations(
        $byRating,
        $weakestDims,
        $commentAnalysis,
        $schoolName,
        $syLabel,
        $overall,
        $maturity,
        $ratingSummary,
        $dimNames
    );

    return [
        'score_analysis' => [
            'gap_analysis' => [
                'average_score'      => $avgScore,
                'overall_maturity'   => $maturity,
                'weakest_dimensions' => $weakestDims,
                'all_dimensions'     => $weakestDims,
            ],
            'weak_indicators' => [
                'total_weak'  => count($byRating[1]) + count($byRating[2]),
                'by_rating'   => $byRating,
                'critical'    => $byRating[1],
            ],
        ],
        'comment_analysis' => $commentAnalysis,
        'recommendations'  => [
            'recommendations' => $recommendations,
            'backend_used'    => 'rule_based',
            'error'           => null,
        ],
    ];
}

/**
 * Analyze all collected remarks from all sources.
 */
function analyzeAllRemarks(array $comments): array
{
    if (empty($comments)) {
        return [
            'total'             => 0,
            'sentiment_counts'  => ['positive' => 0, 'negative' => 0, 'neutral' => 0],
            'top_topics'        => [],
            'has_urgent'        => false,
            'by_source'         => [],
            'summary'           => 'No remarks were submitted.',
            'key_concerns'      => [],
            'positive_notes'    => [],
            'individual'        => [],
        ];
    }

    // Keyword maps
    $positiveWords = ['good', 'excellent', 'great', 'outstanding', 'improve', 'mabuti', 'maganda', 'mahusay', 'nag-improve', 'maayos', 'epektibo'];
    $negativeWords = ['lack', 'lacking', 'no', 'none', 'wala', 'kulang', 'hindi', 'poor', 'needs', 'absent', 'missing', 'broken', 'damaged', 'insufficient'];
    $urgentWords   = ['urgent', 'immediate', 'critical', 'asap', 'dangerous', 'hazard', 'broken', 'damaged', 'leaking', 'malala', 'kailangan agad'];

    $topicKeywords = [
        'bullying'        => ['bully', 'harassment', 'nanggugulo', 'nang-aaway'],
        'ict_resources'   => ['computer', 'internet', 'laptop', 'projector', 'gadget', 'wifi', 'device'],
        'facilities'      => ['room', 'classroom', 'toilet', 'cr', 'canteen', 'library', 'lab', 'building', 'silid'],
        'teacher_quality' => ['teacher', 'guro', 'training', 'seminar', 'professional development'],
        'curriculum'      => ['curriculum', 'lesson', 'module', 'materials', 'learning'],
        'safety'          => ['safe', 'unsafe', 'hazard', 'emergency', 'evacuation', 'drrm'],
        'participation'   => ['involve', 'participation', 'pta', 'ssg', 'community', 'stakeholder'],
        'mental_health'   => ['mental', 'wellness', 'stress', 'counseling', 'emotional'],
        'finance'         => ['budget', 'funds', 'mooe', 'financial', 'money'],
        'leadership'      => ['principal', 'head', 'management', 'leadership', 'governance'],
    ];

    $sentimentCounts = ['positive' => 0, 'negative' => 0, 'neutral' => 0];
    $topicFreq       = [];
    $hasUrgent       = false;
    $bySource        = ['teacher' => [], 'stakeholder' => [], 'school_head' => []];
    $keyConcerns     = [];
    $positiveNotes   = [];
    $individual      = [];

    foreach ($comments as $c) {
        $text   = strtolower($c['text'] ?? '');
        $source = $c['source'] ?? 'unknown';
        $rating = (int)($c['rating'] ?? 0);

        if (empty(trim($text))) continue;

        // Sentiment
        $posCount = 0;
        $negCount = 0;
        foreach ($positiveWords as $w) {
            if (strpos($text, $w) !== false) $posCount++;
        }
        foreach ($negativeWords as $w) {
            if (strpos($text, $w) !== false) $negCount++;
        }

        if ($posCount > $negCount) {
            $sentiment = 'positive';
            $positiveNotes[] = [
                'text'       => $c['text'],
                'source'     => $source,
                'indicator'  => $c['indicator_code'] ?? '',
                'dimension'  => $c['dimension_name'] ?? '',
            ];
        } elseif ($negCount > $posCount) {
            $sentiment  = 'negative';
            $keyConcerns[] = [
                'text'       => $c['text'],
                'source'     => $source,
                'indicator'  => $c['indicator_code'] ?? '',
                'dimension'  => $c['dimension_name'] ?? '',
                'rating'     => $rating,
            ];
        } else {
            $sentiment = 'neutral';
        }
        $sentimentCounts[$sentiment]++;

        // Urgency
        foreach ($urgentWords as $w) {
            if (strpos($text, $w) !== false) {
                $hasUrgent = true;
                break;
            }
        }

        // Topics
        foreach ($topicKeywords as $topic => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($text, $kw) !== false) {
                    $topicFreq[$topic] = ($topicFreq[$topic] ?? 0) + 1;
                    break;
                }
            }
        }

        // Group by source
        if (isset($bySource[$source])) {
            $bySource[$source][] = $c['text'];
        }

        $individual[] = [
            'text'      => $c['text'],
            'source'    => $source,
            'sentiment' => $sentiment,
            'indicator' => $c['indicator_code'] ?? '',
            'dimension' => $c['dimension_name'] ?? '',
            'rating'    => $rating,
        ];
    }

    arsort($topicFreq);
    $topTopics = array_keys(array_slice($topicFreq, 0, 5));

    // Build structured summary
    $totalComments = count($individual);
    $summaryJson   = buildRemarksSummary($individual, $bySource, $sentimentCounts, $topTopics, $totalComments);
    $summaryData   = json_decode($summaryJson, true) ?? ['intro' => $summaryJson];

    return [
        'total'            => $totalComments,
        'sentiment_counts' => $sentimentCounts,
        'top_topics'       => $topTopics,
        'has_urgent'       => $hasUrgent,
        'by_source'        => $bySource,
        'summary'          => $summaryData,   // now a structured array
        'key_concerns'     => array_slice($keyConcerns, 0, 5),
        'positive_notes'   => array_slice($positiveNotes, 0, 3),
        'individual'       => $individual,
    ];
}

/**
 * Build a human-readable summary of all remarks.
 */
function buildRemarksSummary(array $individual, array $bySource, array $sentimentCounts, array $topTopics, int $total): string
{
    if ($total === 0) {
        return json_encode([
            'intro'     => 'No remarks were submitted for this assessment cycle.',
            'sentiment' => '',
            'topics'    => '',
            'concerns'  => [],
            'positives' => [],
        ]);
    }

    $teacherCount = count($bySource['teacher']     ?? []);
    $stakeCount   = count($bySource['stakeholder'] ?? []);
    $shCount      = count($bySource['school_head'] ?? []);

    // ── Paragraph 1: Who submitted and how many ────────────────
    $sourceParts = [];
    if ($teacherCount > 0) $sourceParts[] = $teacherCount === 1
        ? "1 teacher"
        : "$teacherCount teachers";
    if ($stakeCount > 0)   $sourceParts[] = $stakeCount === 1
        ? "1 external stakeholder"
        : "$stakeCount external stakeholders";
    if ($shCount > 0)      $sourceParts[] = $shCount === 1
        ? "1 entry from the school head"
        : "$shCount entries from the school head";

    $sourceStr = '';
    if (count($sourceParts) === 1) {
        $sourceStr = $sourceParts[0];
    } elseif (count($sourceParts) === 2) {
        $sourceStr = $sourceParts[0] . ' and ' . $sourceParts[1];
    } else {
        $last = array_pop($sourceParts);
        $sourceStr = implode(', ', $sourceParts) . ', and ' . $last;
    }

    $intro = "A total of $total remark" . ($total > 1 ? 's' : '') .
             " were collected for this assessment cycle" .
             ($sourceStr ? ", coming from $sourceStr" : "") . ".";

    // ── Paragraph 2: Sentiment tone ───────────────────────────
    $pos  = $sentimentCounts['positive'] ?? 0;
    $neg  = $sentimentCounts['negative'] ?? 0;
    $neut = $sentimentCounts['neutral']  ?? 0;

    $dominant = 'neutral';
    if ($pos > $neg && $pos > $neut)        $dominant = 'positive';
    elseif ($neg > $pos && $neg > $neut)    $dominant = 'negative';
    elseif ($neut >= $pos && $neut >= $neg) $dominant = 'neutral';

    $toneMap = [
        'positive' => 'generally positive, indicating satisfaction with the school\'s programs and practices',
        'negative' => 'largely critical, highlighting areas that require immediate attention and improvement',
        'neutral'  => 'mostly neutral or observational in nature',
    ];
    $sentiment = "The overall tone of the feedback was {$toneMap[$dominant]}. " .
                 "Of the $total remarks, $pos were positive, $neg raised concerns, and $neut were neutral or descriptive.";

    // ── Paragraph 3: Key topics ───────────────────────────────
    $topics = '';
    if (!empty($topTopics)) {
        $topicLabels = array_map(fn($t) => ucwords(str_replace('_', ' ', $t)), $topTopics);
        if (count($topicLabels) === 1) {
            $topics = "The most frequently mentioned topic in the feedback was " . $topicLabels[0] . ".";
        } else {
            $last        = array_pop($topicLabels);
            $topicsJoined = implode(', ', $topicLabels);
            $topics = "The key themes that emerged from the feedback include $topicsJoined, and $last. " .
                      "These areas should be prioritized in the School Improvement Plan.";
        }
    }

    // ── Concerns: clean quoted list ───────────────────────────
    $negRemarks = array_values(array_filter(
        $individual, fn($i) => $i['sentiment'] === 'negative'
    ));
    $concernsList = [];
    foreach (array_slice($negRemarks, 0, 4) as $r) {
        $src = ucfirst($r['source'] ?? 'Respondent');
        $src = str_replace('_', ' ', $src);
        $dim = !empty($r['dimension']) ? $r['dimension'] : '';
        $concernsList[] = [
            'source'    => $src,
            'dimension' => $dim,
            'text'      => trim($r['text']),
            'indicator' => $r['indicator'] ?? '',
        ];
    }

    // ── Positives: clean quoted list ──────────────────────────
    $posRemarks = array_values(array_filter(
        $individual, fn($i) => $i['sentiment'] === 'positive'
    ));
    $positivesList = [];
    foreach (array_slice($posRemarks, 0, 3) as $r) {
        $src = ucfirst($r['source'] ?? 'Respondent');
        $src = str_replace('_', ' ', $src);
        $positivesList[] = [
            'source' => $src,
            'text'   => trim($r['text']),
        ];
    }

    // Return structured JSON so the display layer can render it cleanly
    return json_encode([
        'intro'     => $intro,
        'sentiment' => $sentiment,
        'topics'    => $topics,
        'concerns'  => $concernsList,
        'positives' => $positivesList,
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Build structured recommendations covering ALL rating levels (1-4).
 */
function buildStructuredRecommendations(
    array  $byRating,
    array  $weakestDims,
    array  $commentAnalysis,
    string $schoolName,
    string $syLabel,
    float  $overall,
    string $maturity,
    array  $ratingSummary,
    array  $dimNames
): string {

    $ratingLabels = [
        1 => 'Not Yet Manifested',
        2 => 'Emerging',
        3 => 'Developing',
        4 => 'Always Manifested',
    ];

    $lines = [];

    // ── Header ────────────────────────────────────────────────
    $lines[] = "SCHOOL IMPROVEMENT PLAN RECOMMENDATIONS";
    $lines[] = "School: $schoolName | SY: $syLabel";
    $lines[] = "Overall SBM Score: {$overall}% | Maturity Level: $maturity";
    $lines[] = str_repeat("─", 60);

    // ── Rating Overview ───────────────────────────────────────
    $lines[] = "\n📊 ASSESSMENT OVERVIEW";
    $lines[] = "Total Indicators Rated: {$ratingSummary['total_rated']}";
    $lines[] = "  ▪ Not Yet Manifested (1): {$ratingSummary['not_yet_manifested']} indicator(s) — Requires immediate action";
    $lines[] = "  ▪ Emerging (2):           {$ratingSummary['emerging']} indicator(s) — Needs focused intervention";
    $lines[] = "  ▪ Developing (3):         {$ratingSummary['developing']} indicator(s) — Continue and strengthen";
    $lines[] = "  ▪ Always Manifested (4):  {$ratingSummary['always_manifested']} indicator(s) — Sustain and document";

    // ── Remarks Summary ───────────────────────────────────────
    $summary = $commentAnalysis['summary'] ?? [];
    if (!empty($summary)) {
        $lines[] = "\n📝 STAKEHOLDER REMARKS SUMMARY";
        // Handle both old string format and new structured array format
        if (is_array($summary)) {
            if (!empty($summary['intro']))     $lines[] = $summary['intro'];
            if (!empty($summary['sentiment'])) $lines[] = $summary['sentiment'];
            if (!empty($summary['topics']))    $lines[] = $summary['topics'];
            if (!empty($summary['concerns'])) {
                $lines[] = "\nConcerns raised:";
                foreach ($summary['concerns'] as $c) {
                    $dim = !empty($c['dimension']) ? " [{$c['dimension']}]" : "";
                    $lines[] = "  • ({$c['source']}{$dim}): \"{$c['text']}\"";
                }
            }
            if (!empty($summary['positives'])) {
                $lines[] = "\nPositive feedback noted:";
                foreach ($summary['positives'] as $p) {
                    $lines[] = "  • ({$p['source']}): \"{$p['text']}\"";
                }
            }
        } else {
            $lines[] = $summary;
        }
    }

    // ── Urgent Issues ─────────────────────────────────────────
    if ($commentAnalysis['has_urgent'] ?? false) {
        $lines[] = "\n🚨 URGENT ISSUES FLAGGED IN REMARKS";
        $lines[] = "One or more remarks contain urgent concerns that require immediate attention from the School Head and SDO.";
        foreach (($commentAnalysis['key_concerns'] ?? []) as $concern) {
            if (!empty($concern['text'])) {
                $lines[] = "  • [" . ucfirst($concern['source'] ?? '') . "] " . trim($concern['text']);
            }
        }
    }

    // ── Rating Level 1: Not Yet Manifested ────────────────────
    if (!empty($byRating[1])) {
        $lines[] = "\n🔴 PRIORITY 1 — NOT YET MANIFESTED (Immediate Action Required)";
        $lines[] = "These " . count($byRating[1]) . " indicator(s) have not been demonstrated and need urgent attention:";

        $groupedByDim = [];
        foreach ($byRating[1] as $ind) {
            $groupedByDim[$ind['dimension_name']][] = $ind;
        }

        foreach ($groupedByDim as $dimName => $indicators) {
            $lines[] = "\n  📌 $dimName:";
            foreach ($indicators as $ind) {
                $lines[] = "     [{$ind['code']}] {$ind['text']}";
                $evidence = trim($ind['evidence'] ?? '');
                if ($evidence) {
                    $lines[] = "     → Evidence noted: \"$evidence\"";
                }
                $lines[] = "     → RECOMMENDED ACTION: Establish a baseline program immediately. Assign a point person,";
                $lines[] = "       set a 30-day implementation target, and document all initial steps taken.";
            }
        }
    }

    // ── Rating Level 2: Emerging ──────────────────────────────
    if (!empty($byRating[2])) {
        $lines[] = "\n🟡 PRIORITY 2 — EMERGING (Focused Intervention Needed)";
        $lines[] = "These " . count($byRating[2]) . " indicator(s) show early signs but need structured support:";

        $groupedByDim = [];
        foreach ($byRating[2] as $ind) {
            $groupedByDim[$ind['dimension_name']][] = $ind;
        }

        foreach ($groupedByDim as $dimName => $indicators) {
            $lines[] = "\n  📌 $dimName:";
            foreach ($indicators as $ind) {
                $lines[] = "     [{$ind['code']}] {$ind['text']}";
                $evidence = trim($ind['evidence'] ?? '');
                if ($evidence) {
                    $lines[] = "     → Evidence noted: \"$evidence\"";
                }
                $lines[] = "     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.";
                $lines[] = "       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.";
            }
        }
    }

    // ── Rating Level 3: Developing ────────────────────────────
    if (!empty($byRating[3])) {
        $lines[] = "\n🔵 PRIORITY 3 — DEVELOPING (Continue & Strengthen)";
        $lines[] = "These " . count($byRating[3]) . " indicator(s) show good progress and should be maintained:";

        $groupedByDim = [];
        foreach ($byRating[3] as $ind) {
            $groupedByDim[$ind['dimension_name']][] = $ind;
        }

        foreach ($groupedByDim as $dimName => $indicators) {
            $lines[] = "\n  📌 $dimName:";
            foreach ($indicators as $ind) {
                $lines[] = "     [{$ind['code']}] {$ind['text']}";
                $evidence = trim($ind['evidence'] ?? '');
                if ($evidence) {
                    $lines[] = "     → Evidence noted: \"$evidence\"";
                }
                $lines[] = "     → RECOMMENDED ACTION: Scale current practices. Document best practices,";
                $lines[] = "       share with other schools, and target transition to 'Always Manifested' next cycle.";
            }
        }
    }

    // ── Rating Level 4: Always Manifested ─────────────────────
    if (!empty($byRating[4])) {
        $lines[] = "\n🟢 SUSTAINED PRACTICES — ALWAYS MANIFESTED";
        $lines[] = "These " . count($byRating[4]) . " indicator(s) are consistently implemented — keep it up:";

        $groupedByDim = [];
        foreach ($byRating[4] as $ind) {
            $groupedByDim[$ind['dimension_name']][] = $ind;
        }

        foreach ($groupedByDim as $dimName => $indicators) {
            $lines[] = "\n  📌 $dimName:";
            foreach ($indicators as $ind) {
                $lines[] = "     [{$ind['code']}] {$ind['text']}";
                $lines[] = "     → Continue current practices. Document these as best practices in the SIP.";
                $lines[] = "       Consider sharing these with neighboring schools as models.";
            }
        }
    }

    // ── Dimension-Level Recommendations ───────────────────────
    $lines[] = "\n📐 DIMENSION-LEVEL PRIORITY ACTIONS";
    $weakOnly = array_filter($weakestDims, fn($d) => $d['score'] < 76);
    foreach (array_slice($weakOnly, 0, 4) as $dim) {
        $pct     = $dim['score'];
        $dimMat  = $dim['maturity'];
        $dimName = $dim['dimension_name'];
        $gap     = $dim['gap_from_avg'];
        $lines[] = "\n  $dimName ($pct% — $dimMat):";
        if ($pct < 26) {
            $lines[] = "  → CRITICAL: This dimension is in the Beginning level. Immediate SDO technical assistance";
            $lines[] = "    is recommended. Prioritize this in the SIP as a High Priority action.";
        } elseif ($pct < 51) {
            $lines[] = "  → This dimension needs significant improvement. Create a dedicated action plan,";
            $lines[] = "    allocate resources, and schedule monthly monitoring with the SDO.";
        } elseif ($pct < 76) {
            $lines[] = "  → Good progress noted. Focus on the remaining weak indicators to reach the";
            $lines[] = "    Advanced level. Current gap from average: {$gap}%.";
        }
    }

    // ── Topics from Remarks ───────────────────────────────────
    $topics = $commentAnalysis['top_topics'] ?? [];
    if (!empty($topics)) {
        $topicRecs = [
            'bullying'        => 'Strengthen the anti-bullying program. Ensure the Child Protection Committee (CPC) is active, conducts quarterly sessions, and all incidents are documented and resolved.',
            'ict_resources'   => 'Address ICT resource gaps through LGU partnership, alumni donations, or MOOE allocation. Prepare an ICT acquisition plan aligned with the SIP.',
            'facilities'      => 'Prioritize infrastructure needs in the Annual Procurement Plan. Coordinate with barangay and LGU for supplemental funding.',
            'teacher_quality' => 'Schedule focused LAC sessions addressing identified instructional gaps. Nominate qualified teachers for SDO-led professional development trainings.',
            'safety'          => 'Update and re-practice the DRRM plan immediately. Conduct a school safety audit and resolve all flagged hazards before the next grading period.',
            'mental_health'   => 'Implement a structured mental wellness program with a guidance counselor. Establish a clear referral pathway for learners in crisis.',
            'participation'   => 'Strengthen stakeholder engagement through regular SGC and PTA meetings. Involve community partners in SIP planning and implementation.',
            'finance'         => 'Review MOOE utilization and ensure 100% liquidation with complete documentation. Prepare a detailed Annual Budget Plan aligned with SIP priorities.',
            'leadership'      => 'Ensure regular SGC meetings are documented. School Head should continue innovations in frontline service delivery.',
        ];

        $lines[] = "\n💬 RECOMMENDATIONS FROM STAKEHOLDER REMARKS";
        foreach ($topics as $topic) {
            if (isset($topicRecs[$topic])) {
                $topicLabel = ucwords(str_replace('_', ' ', $topic));
                $lines[] = "\n  [$topicLabel]";
                $lines[] = "  → " . $topicRecs[$topic];
            }
        }
    }

    // ── Closing Note ──────────────────────────────────────────
    $lines[] = "\n" . str_repeat("─", 60);
    $lines[] = "NOTE: These recommendations are generated based on the SBM self-assessment data";
    $lines[] = "submitted by $schoolName for SY $syLabel. All action plans should be";
    $lines[] = "integrated into the School Improvement Plan (SIP) and monitored quarterly by the SDO.";
    $lines[] = "For dimensions rated 'Beginning' or 'Developing', SDO technical assistance is strongly advised.";

    return implode("\n", $lines);
}

/**
 * Get maturity label from percentage.
 */
function getMaturityLabel(float $pct): string
{
    if ($pct >= 76) return 'Advanced';
    if ($pct >= 51) return 'Maturing';
    if ($pct >= 26) return 'Developing';
    return 'Beginning';
}

/**
 * Save ML results to database.
 */
function saveMLResults(PDO $db, int $cycleId, array $result): void
{
    $gap     = $result['score_analysis']['gap_analysis']     ?? [];
    $comms   = $result['comment_analysis']                   ?? [];
    $recs    = $result['recommendations']['recommendations'] ?? '';

    // Save to ml_predictions for weak/critical dimensions
    if (!empty($gap['weakest_dimensions'])) {
        foreach ($gap['weakest_dimensions'] as $wd) {
            if (($wd['priority'] ?? 'low') === 'low') continue;
            try {
                $db->prepare("
                    INSERT INTO ml_predictions
                        (school_id, cycle_id, prediction_type,
                         predicted_value, risk_level, recommendation, confidence_score)
                    SELECT school_id, ?, 'risk_flag', ?, ?, ?, 0.75
                    FROM sbm_cycles WHERE cycle_id = ?
                    ON DUPLICATE KEY UPDATE
                        risk_level     = VALUES(risk_level),
                        recommendation = VALUES(recommendation)
                ")->execute([
                    $cycleId,
                    $wd['score'],
                    $wd['priority'] === 'high' ? 'high' : 'medium',
                    "Dimension {$wd['dimension_no']} ({$wd['dimension_name']}) " .
                    "is at {$wd['score']}% ({$wd['maturity']} level). " .
                    "Gap from average: {$wd['gap_from_avg']}%.",
                    $cycleId,
                ]);
            } catch (Exception $e) {
                error_log("ML prediction save error: " . $e->getMessage());
            }
        }
    }

    // Save main recommendations
    if ($recs) {
        try {
            $db->prepare("
                INSERT INTO ml_recommendations
                    (cycle_id, recommendation_text, generated_by,
                     top_topics, has_urgent, sentiment_summary)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    recommendation_text = VALUES(recommendation_text),
                    generated_by        = VALUES(generated_by),
                    top_topics          = VALUES(top_topics),
                    has_urgent          = VALUES(has_urgent),
                    sentiment_summary   = VALUES(sentiment_summary),
                    generated_at        = NOW()
            ")->execute([
                $cycleId,
                $recs,
                $result['recommendations']['backend_used'] ?? 'rule_based',
                json_encode($comms['top_topics'] ?? []),
                ($comms['has_urgent'] ?? false) ? 1 : 0,
                json_encode($comms['sentiment_counts'] ?? []),
            ]);
        } catch (Exception $e) {
            error_log("ML recommendations save error: " . $e->getMessage());
        }
    }
}