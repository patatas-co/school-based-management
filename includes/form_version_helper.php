<?php
// ============================================================
// includes/form_version_helper.php
// Centralized, DB-driven indicator retrieval for the active
// form version. Replaces hardcoded TEACHER_INDICATOR_CODES and
// related PHP constants in assignment-related flows.
// ============================================================

/**
 * Resolves the applicable form version ID.
 * Priority:
 * 1. If a cycle exists, resolve its version via sbm_dimension_scores and sbm_dimensions.
 * 2. Otherwise, use the globally active form version.
 * If no active version exists and no cycle version exists, throws RuntimeException.
 */
function getApplicableFormVersionId(PDO $db, ?int $cycleId = null): int
{
    if ($cycleId !== null && $cycleId > 0) {
        $stmt = $db->prepare("
            SELECT d.form_version_id 
            FROM sbm_dimension_scores ds 
            JOIN sbm_dimensions d ON ds.dimension_id = d.dimension_id 
            WHERE ds.cycle_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$cycleId]);
        $versionId = $stmt->fetchColumn();
        if ($versionId !== false) {
            return (int) $versionId;
        }
    }

    // Otherwise, check current school year's cycle
    try {
        $syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
        if ($syId !== false) {
            $schoolId = SCHOOL_ID;
            $stmt = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id = ? AND sy_id = ? LIMIT 1");
            $stmt->execute([$schoolId, $syId]);
            $cycleId = $stmt->fetchColumn();
            if ($cycleId !== false) {
                $stmt = $db->prepare("
                    SELECT d.form_version_id 
                    FROM sbm_dimension_scores ds 
                    JOIN sbm_dimensions d ON ds.dimension_id = d.dimension_id 
                    WHERE ds.cycle_id = ? 
                    LIMIT 1
                ");
                $stmt->execute([$cycleId]);
                $versionId = $stmt->fetchColumn();
                if ($versionId !== false) {
                    return (int) $versionId;
                }
            }
        }
    } catch (\Throwable $e) {
        // Fallback
    }

    // Globally active version
    $row = $db->query(
        "SELECT version_id FROM form_versions WHERE is_active = 1 LIMIT 1"
    )->fetchColumn();

    if ($row === false || (int) $row === 0) {
        throw new RuntimeException(
            'No active form version found. Please publish a form version before managing indicator assignments.'
        );
    }

    return (int) $row;
}


/**
 * Maps a user-role string to the rater_role values in sbm_indicators
 * that that role is allowed to answer.
 *
 * teacher              → TEACHER, SH_TEACHER, SH_TCH_EXT, TCH_EXT
 * school_head          → SH_ONLY, SH_TEACHER, SH_EXT, SH_TCH_EXT
 * external_stakeholder → SH_EXT, SH_TCH_EXT, TCH_EXT
 */
function getRaterRolesForUserRole(string $userRole): array
{
    static $map = [
        'teacher' => ['TEACHER', 'SH_TEACHER', 'SH_TCH_EXT', 'TCH_EXT'],
        'school_head' => ['SH_ONLY', 'SH_TEACHER', 'SH_EXT', 'SH_TCH_EXT'],
        'external_stakeholder' => ['SH_EXT', 'SH_TCH_EXT', 'TCH_EXT'],
    ];

    if (!isset($map[$userRole])) {
        throw new InvalidArgumentException("Unknown user role: '$userRole'");
    }

    return $map[$userRole];
}

/**
 * Returns full indicator rows for a given form version and user role,
 * ordered by dimension_no then sort_order (preserving the published form order).
 *
 * Each row contains:
 *   indicator_code, indicator_text, mov_guide, rater_role,
 *   dimension_id, dimension_no, dimension_name, color_hex, sort_order
 */
function getRoleIndicators(PDO $db, int $formVersionId, string $userRole): array
{
    $raterRoles   = getRaterRolesForUserRole($userRole);
    $placeholders = implode(',', array_fill(0, count($raterRoles), '?'));

    $stmt = $db->prepare("
        SELECT
            i.indicator_id,
            i.indicator_code,
            i.indicator_text,
            i.mov_guide,
            i.rater_role,
            i.sort_order,
            d.dimension_id,
            d.dimension_no,
            d.dimension_name,
            d.color_hex
        FROM sbm_indicators i
        JOIN sbm_dimensions d
          ON i.dimension_id = d.dimension_id
        WHERE i.form_version_id = ?
          AND i.is_active       = 1
          AND i.rater_role IN ($placeholders)
        ORDER BY d.dimension_no ASC, i.sort_order ASC
    ");

    $stmt->execute(array_merge([$formVersionId], $raterRoles));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Returns only the indicator_code strings — useful for validation
 * and quick membership checks (in_array, array_diff, etc.).
 */
function getRoleIndicatorCodes(PDO $db, int $formVersionId, string $userRole): array
{
    $indicators = getRoleIndicators($db, $formVersionId, $userRole);
    return array_column($indicators, 'indicator_code');
}

/**
 * Returns indicators grouped by dimension, ready for rendering
 * checkbox lists in the assignment UI.
 *
 * Structure:
 * [
 *   dim_no => [
 *     'name'       => 'Curriculum and Teaching',
 *     'color_hex'  => '#2563EB',
 *     'indicators' => [ [...], [...] ]
 *   ],
 *   ...
 * ]
 */
function getRoleIndicatorsGrouped(PDO $db, int $formVersionId, string $userRole): array
{
    $indicators = getRoleIndicators($db, $formVersionId, $userRole);
    $grouped    = [];

    foreach ($indicators as $ind) {
        $dimNo = (int) $ind['dimension_no'];
        if (!isset($grouped[$dimNo])) {
            $grouped[$dimNo] = [
                'name'       => $ind['dimension_name'],
                'color_hex'  => $ind['color_hex'] ?? '#6B7280',
                'indicators' => [],
            ];
        }
        $grouped[$dimNo]['indicators'][] = $ind;
    }

    ksort($grouped);
    return $grouped;
}
