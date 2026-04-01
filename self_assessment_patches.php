<?php
// ════════════════════════════════════════════════════════════════════════════
// SELF ASSESSMENT — LOGIC PATCHES
// Apply these changes to school_head/self_assessment.php
// ════════════════════════════════════════════════════════════════════════════

// ── PATCH 1: save_response action ────────────────────────────────────────────
// REPLACE the old ambiguous teacher-block check with this clean version.
// The SH portal may only save ratings for indicators in SH_RATEABLE_CODES.
// TEACHER_ONLY_CODES and TCH_EXT_CODES are off-limits for SH direct rating.

// OLD (buggy — double-negation logic was wrong):
//   if (in_array($indicatorCode, TEACHER_INDICATOR_CODES) && ...) { ... }

// NEW:
if (!in_array($indicatorCode, SH_RATEABLE_CODES)) {
    echo json_encode(['ok' => false, 'msg' => 'This indicator is not rated by the School Head.']);
    exit;
}


// ── PATCH 2: clear_response action ───────────────────────────────────────────
// REPLACE the old check with a simple SH_RATEABLE_CODES guard.

// OLD:
//   if (!in_array($indicatorCode, SH_ONLY_INDICATOR_CODES) && in_array($indicatorCode, TEACHER_INDICATOR_CODES)) {

// NEW:
if (!in_array($indicatorCode, SH_RATEABLE_CODES)) {
    echo json_encode(['ok' => false, 'msg' => 'Cannot clear a non-SH indicator.']);
    exit;
}


// ── PATCH 3: submit action — SH completion check ─────────────────────────────
// Count only SH_ONLY_INDICATOR_CODES for the "must complete before submit" check.
// SH_TEACHER / SH_EXT / SH_TCH_EXT are all welcome but not strictly required
// (teacher or stakeholder data may fill them in). Adjust to your business rule.
// If you want to require ALL SH_RATEABLE_CODES, swap the constant below.

$shAnswerableCodes = SH_ONLY_INDICATOR_CODES; // strict minimum for submission


// ── PATCH 4: recomputeDimScoreWithOverrides ───────────────────────────────────
// REPLACE the $isTeacher determination with the new constant names.

// OLD:
//   $isTeacher = in_array($ind['indicator_code'], TEACHER_INDICATOR_CODES)
//     && !in_array($ind['indicator_code'], SH_ONLY_INDICATOR_CODES);

// NEW — an indicator is "teacher-handled" (averaged from teacher_responses)
// only when teachers are the EXCLUSIVE raters (TEACHER_ONLY) or the primary
// raters for that indicator (TCH_EXT). For SH_TEACHER / SH_TCH_EXT the SH
// also enters a direct rating so both pools contribute.
function isTeacherHandled(string $code): bool
{
    // Pure teacher-only: no SH input at all
    if (in_array($code, TEACHER_ONLY_CODES)) {
        return true;
    }
    // Teacher + External, no SH direct rating
    if (in_array($code, TCH_EXT_CODES)) {
        return true;
    }
    return false;
}

// Updated recomputeDimScoreWithOverrides — drop-in replacement:
function recomputeDimScoreWithOverrides(PDO $db, int $cycleId, int $indicatorId, int $schoolId): void
{
    $dimId = $db->prepare("SELECT dimension_id FROM sbm_indicators WHERE indicator_id=?");
    $dimId->execute([$indicatorId]);
    $dimId = $dimId->fetchColumn();

    $inds = $db->prepare("SELECT indicator_id, indicator_code FROM sbm_indicators WHERE dimension_id=? AND is_active=1");
    $inds->execute([$dimId]);
    $inds = $inds->fetchAll();

    $rawTotal = 0;
    $maxTotal = 0;

    foreach ($inds as $ind) {
        $code = $ind['indicator_code'];

        if (isTeacherHandled($code)) {
            // Score comes from teacher_responses average (with optional SH override)
            $ov = $db->prepare("SELECT override_rating FROM sh_indicator_overrides WHERE cycle_id=? AND indicator_id=?");
            $ov->execute([$cycleId, $ind['indicator_id']]);
            $override = $ov->fetchColumn();

            if ($override !== false) {
                $rawTotal += (int) $override;
                $maxTotal += 4;
            } else {
                $avg = $db->prepare("SELECT AVG(rating) FROM teacher_responses WHERE cycle_id=? AND indicator_id=?");
                $avg->execute([$cycleId, $ind['indicator_id']]);
                $avgVal = $avg->fetchColumn();
                if ($avgVal !== null) {
                    $rawTotal += floatval($avgVal);
                    $maxTotal += 4;
                }
            }
        } else {
            // Score comes from sbm_responses (SH direct rating)
            $shResp = $db->prepare("SELECT rating FROM sbm_responses WHERE cycle_id=? AND indicator_id=?");
            $shResp->execute([$cycleId, $ind['indicator_id']]);
            $rating = $shResp->fetchColumn();
            if ($rating !== false) {
                $rawTotal += (int) $rating;
                $maxTotal += 4;
            }
        }
    }

    $rawTotal = round($rawTotal, 2);
    $pct = $maxTotal > 0 ? round(($rawTotal / $maxTotal) * 100, 2) : 0;

    $db->prepare("
        INSERT INTO sbm_dimension_scores (cycle_id, school_id, dimension_id, raw_score, max_score, percentage)
        VALUES (?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            raw_score=VALUES(raw_score),
            max_score=VALUES(max_score),
            percentage=VALUES(percentage),
            computed_at=NOW()
    ")->execute([$cycleId, $schoolId, $dimId, $rawTotal, $maxTotal, $pct]);
}


// ── PATCH 5: Frontend PHP — indicator card role detection ─────────────────────
// In the indicator loop that renders cards, replace the old $isTeacher check:

// OLD:
//   $isTeacher = in_array($ind['indicator_code'], TEACHER_INDICATOR_CODES);

// NEW — determines whether to show a teacher info box or an SH rating input:
$isTeacherCard = isTeacherHandled($ind['indicator_code'] ?? '');

// Also update $role for the data-role attribute:
$role = $isTeacherCard ? 'teacher' : 'sh';

// NOTE: SH_TEACHER_CODES and SH_TCH_EXT_CODES show the SH rating UI
// (SH rates directly) but ALSO show the teacher info box below it so the
// SH can see the teacher average alongside their own input.
// You may want to add a second flag for this:
$showTeacherInfoAlso = in_array($ind['indicator_code'] ?? '', SH_SEES_TEACHER_CODES);


// ── PATCH 6: JS TEACHER_CODES constant ───────────────────────────────────────
// Update the PHP-to-JS export so the frontend filter chip counts are correct.
// Replace:
//   const TEACHER_CODES = new Set(<?= json_encode(TEACHER_INDICATOR_CODES) ?>);
// With:
//   const TEACHER_ONLY_CODES_JS  = new Set(<?= json_encode(TEACHER_ONLY_CODES) ?>);
//   const TCH_EXT_CODES_JS       = new Set(<?= json_encode(TCH_EXT_CODES) ?>);
//   const TEACHER_HANDLED_CODES  = new Set([...TEACHER_ONLY_CODES_JS, ...TCH_EXT_CODES_JS]);
// Then use TEACHER_HANDLED_CODES wherever TEACHER_CODES was used before.


// ── PATCH 7: clear_dimension action ──────────────────────────────────────────
// Replace the $teacherCodes exclusion list with the more precise set.
// Only exclude indicators where TEACHER is the sole rater (no SH direct input).

// OLD:
//   $teacherCodes = TEACHER_INDICATOR_CODES;

// NEW:
$teacherOnlyCodes = array_merge(TEACHER_ONLY_CODES, TCH_EXT_CODES);
// Use $teacherOnlyCodes in the DELETE ... NOT IN (...) query.


// ── PATCH 8: sbm_dimensions table — update indicator_count for Dim 3 & 4 ─────
// Run this SQL once to fix the dimension counts in the database:
/*
UPDATE sbm_dimensions SET indicator_count = 4 WHERE dimension_no = 3;
UPDATE sbm_dimensions SET indicator_count = 6 WHERE dimension_no = 4;
*/

// Also re-seed indicator codes 3.4, 4.1–4.6 if the DB still has the old mapping.
// The old code had:
//   4.1 = "The school innovates..."  → now correct code is 3.4 (Dim 3)
//   4.2 = "The school's strategic plan is operationalized..." → now 4.1 (Dim 4)
// Run seed_sbm.php (with TRUNCATE + re-insert) to apply the corrected codes.
