<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/suggestion_feedback.php';
requireRole('school_head', 'sbm_coordinator');

$db = getDB();
$schoolId = (int) ($_SESSION['school_id'] ?? SCHOOL_ID);
$historyTableStmt = $db->prepare("
  SELECT COUNT(*)
  FROM information_schema.tables
  WHERE table_schema = DATABASE() AND table_name = 'ai_suggestion_history'
");
$historyTableStmt->execute();
$historyTableAvailable = (int) $historyTableStmt->fetchColumn() > 0;
$years = [];
$currentSchoolYear = (string) ($db->query("SELECT label FROM school_years WHERE is_current = 1 LIMIT 1")->fetchColumn() ?: '');
$currentYearStart = (int) substr($currentSchoolYear, 0, 4);
if ($historyTableAvailable) {
    $yearsStmt = $db->prepare("
      SELECT DISTINCT h.school_year
      FROM ai_suggestion_history h
      JOIN school_years sy ON sy.label = h.school_year
      WHERE h.school_id = ? AND CAST(LEFT(h.school_year, 4) AS UNSIGNED) >= 2001
      ORDER BY CAST(LEFT(h.school_year, 4) AS UNSIGNED) DESC, h.school_year DESC
    ");
    $yearsStmt->execute([$schoolId]);
    $years = array_values(array_map('strval', $yearsStmt->fetchAll(PDO::FETCH_COLUMN)));
}
$previousSchoolYear = $currentYearStart > 0 ? ($currentYearStart - 1) . '-' . $currentYearStart : '';
$yearA = trim((string) ($_GET['year_a'] ?? ($currentSchoolYear ?: ($years[0] ?? ''))));
$yearB = trim((string) ($_GET['year_b'] ?? ($previousSchoolYear ?: ($years[1] ?? ''))));
if (!in_array($yearA, $years, true)) $yearA = $years[0] ?? '';
if (!in_array($yearB, $years, true)) $yearB = $years[1] ?? '';

function renderSuggestionPreview(array $item): string
{
    $title = trim(str_replace('**', '', (string) ($item['suggestion_title'] ?? '')));
    $body = trim((string) ($item['suggestion_text'] ?? ''));
    if ($title !== '') {
        $body = preg_replace(
            '/^\s*\*{0,2}' . preg_quote($title, '/') . '\*{0,2}(?![\p{L}\p{N}])\s*/iu',
            '',
            $body,
            1
        ) ?? $body;
    }
    $body = preg_replace(
        '/\s+-\s+(?=[\p{L}\d“"\'])/u',
        "\n- ",
        $body
    ) ?? $body;
    $lines = preg_split('/\R/', $body) ?: [];
    $lines = array_values(array_filter(array_map('trim', $lines), static fn(string $line): bool => $line !== ''));
    $html = '';
    $inList = false;
    $formatInline = static function (string $text): string {
        $parts = preg_split('/(\*\*.+?\*\*)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        return implode('', array_map(static function (string $part): string {
            if (preg_match('/^\*\*(.+)\*\*$/u', $part, $matches)) {
                return '<strong>' . e($matches[1]) . '</strong>';
            }
            return e($part);
        }, $parts));
    };
    foreach ($lines as $line) {
        if (strpos($line, '- ') === 0) {
            if (!$inList) { $html .= '<ul>'; $inList = true; }
            $html .= '<li>' . $formatInline(trim(substr($line, 2))) . '</li>';
            continue;
        }
        if ($inList) { $html .= '</ul>'; $inList = false; }
        $html .= '<p>' . $formatInline($line) . '</p>';
    }
    if ($inList) $html .= '</ul>';
    return $html ?: '<p>Details unavailable.</p>';
}

$historyByYear = [];
$plansByYear = [];
$completedYears = [];
foreach ([$yearA, $yearB] as $year) {
    if ($year === '') continue;
    $completionStmt = $db->prepare("
      SELECT EXISTS (
        SELECT 1
        FROM sbm_cycles c
        JOIN school_years sy ON sy.sy_id = c.sy_id
        WHERE c.school_id = ? AND sy.label = ? AND c.status = 'finalized'
      )
    ");
    $completionStmt->execute([$schoolId, $year]);
    $completedYears[$year] = (bool) $completionStmt->fetchColumn();

    $q = $db->prepare("
      SELECT h.*, d.dimension_no, d.dimension_name,
             linked_plan.is_sample AS adopted_plan_is_sample,
             COALESCE((
               SELECT ROUND(AVG(ds.percentage), 1)
               FROM sbm_dimension_scores ds
               JOIN sbm_dimensions score_dimension ON score_dimension.dimension_id = ds.dimension_id
               JOIN sbm_cycles c ON c.cycle_id = ds.cycle_id
               JOIN school_years sy ON sy.sy_id = c.sy_id
               WHERE c.school_id = h.school_id AND score_dimension.dimension_no = d.dimension_no
                 AND sy.label = h.school_year
                 AND c.status IN ('validated','finalized','completed')
                 AND c.cycle_id = (
                   SELECT c_latest.cycle_id
                   FROM sbm_cycles c_latest
                   JOIN school_years sy_latest ON sy_latest.sy_id = c_latest.sy_id
                   WHERE c_latest.school_id = h.school_id
                     AND sy_latest.label = h.school_year
                     AND c_latest.status IN ('validated','finalized','completed')
                   ORDER BY COALESCE(c_latest.finalized_at, c_latest.created_at) DESC, c_latest.cycle_id DESC
                   LIMIT 1
                 )
             ), NULLIF(h.score_at_time, 0)) AS display_score
      FROM ai_suggestion_history h
      JOIN sbm_dimensions d ON d.dimension_id = h.dimension_id
      LEFT JOIN improvement_plans linked_plan ON linked_plan.plan_id = h.adopted_plan_id
      WHERE h.school_id = ? AND h.school_year = ? AND h.is_final = 1
      ORDER BY d.dimension_no, h.id
    ");
    $q->execute([$schoolId, $year]);
    $historyRows = $q->fetchAll(PDO::FETCH_ASSOC);
    $historyByDimension = [];
    foreach ($historyRows as $historyRow) {
        $dimensionKey = (int) $historyRow['dimension_no'];
        if (
            !isset($historyByDimension[$dimensionKey])
            || (string) $historyRow['generated_at'] > (string) $historyByDimension[$dimensionKey]['generated_at']
            || (
                (string) $historyRow['generated_at'] === (string) $historyByDimension[$dimensionKey]['generated_at']
                && (int) $historyRow['id'] > (int) $historyByDimension[$dimensionKey]['id']
            )
        ) {
            $historyByDimension[$dimensionKey] = $historyRow;
        }
    }
    $historyByYear[$year] = array_values($historyByDimension);

    $plansStmt = $db->prepare("
      SELECT ip.plan_id, ip.priority_level, ip.objective, ip.strategy, ip.status,
             ip.workflow_status, ip.target_date, ip.is_sample, d.dimension_no, d.dimension_name,
             h.suggestion_title AS linked_suggestion_title
      FROM improvement_plans ip
      JOIN sbm_cycles c ON c.cycle_id = ip.cycle_id
      JOIN school_years sy ON sy.sy_id = c.sy_id
      JOIN sbm_dimensions d ON d.dimension_id = ip.dimension_id
      JOIN ai_suggestion_history h
        ON h.adopted_plan_id = ip.plan_id
       AND h.school_id = ip.school_id
       AND h.school_year = sy.label
      WHERE ip.school_id = ? AND sy.label = ?
      ORDER BY d.dimension_no, ip.priority_level, ip.plan_id
    ");
    $plansStmt->execute([$schoolId, $year]);
    $plansByYear[$year] = $plansStmt->fetchAll(PDO::FETCH_ASSOC);
}

$left = $historyByYear[$yearA] ?? [];
$right = $historyByYear[$yearB] ?? [];
$yearAStart = (int) substr($yearA, 0, 4);
$yearBStart = (int) substr($yearB, 0, 4);
$newerColumnIndex = $yearAStart > $yearBStart ? 0 : 1;
$newerYear = $newerColumnIndex === 0 ? $yearA : $yearB;
$olderYear = $newerColumnIndex === 0 ? $yearB : $yearA;
$newerItemByDimension = [];
$olderItemByDimension = [];
foreach ($newerColumnIndex === 0 ? $left : $right as $row) {
    $newerItemByDimension[(int) $row['dimension_no']] = $row;
}
foreach ($newerColumnIndex === 0 ? $right : $left as $row) {
    $olderItemByDimension[(int) $row['dimension_no']] = $row;
}
$rightByDimension = [];
foreach ($right as $row) $rightByDimension[(int) $row['dimension_no']] = $row;
$olderByDimension = [];
foreach ($newerColumnIndex === 0 ? $right : $left as $row) {
    $olderByDimension[(int) $row['dimension_no']] = $row;
}
$learningSummary = [
    'suggestions' => count($olderByDimension),
    'adopted' => 0,
    'improved' => 0,
    'revised' => 0,
    'repeated' => 0,
];
foreach ($olderByDimension as $dimensionNo => $olderItem) {
    if (!empty($olderItem['adopted_plan_id'])) {
        $learningSummary['adopted']++;
    }
    $newerItem = $newerItemByDimension[$dimensionNo] ?? null;
    if (!$newerItem) {
        continue;
    }
    $newerScore = $newerItem['display_score'] ?? null;
    $olderScore = $olderItem['display_score'] ?? null;
    if ($newerScore !== null && $olderScore !== null
        && round((float) $newerScore, 1) > round((float) $olderScore, 1)) {
        $learningSummary['improved']++;
    }
    $similarity = sfSuggestionActionSimilarity($olderItem, $newerItem);
    if ($similarity >= .88) {
        $learningSummary['repeated']++;
    } else {
        $learningSummary['revised']++;
    }
}
$dimensions = [];
foreach (array_merge($left, $right) as $row) {
    $dimensions[(int) $row['dimension_no']] = $row;
}

$pageTitle = 'Suggestion History';
$activePage = 'suggestion_history.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"></div>
<div class="card" style="margin-bottom:18px;">
  <div class="card-head" style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;">
    <span class="card-title">Suggestion History</span>
    <?php if (count($years) >= 1): ?>
    <form method="get" id="historyCompareForm" style="display:flex;gap:8px;align-items:center;">
      <select class="history-year-select" name="year_a" aria-label="First school year">
        <?php foreach ($years as $year): ?><option value="<?= e($year) ?>" <?= $year === $yearA ? 'selected' : '' ?>><?= e($year) ?></option><?php endforeach; ?>
      </select>
      <span style="color:var(--n-400);">vs</span>
      <select class="history-year-select" name="year_b" aria-label="Second school year" <?= count($years) < 2 ? 'disabled' : '' ?>>
        <?php if (count($years) < 2): ?>
          <option>No other year available</option>
        <?php else: ?>
          <?php foreach ($years as $year): ?><option value="<?= e($year) ?>" <?= $year === $yearB ? 'selected' : '' ?>><?= e($year) ?></option><?php endforeach; ?>
        <?php endif; ?>
      </select>
    </form>
    <?php endif; ?>
  </div>
  <?php if (!$historyTableAvailable): ?>
    <div class="card-body" style="padding:28px;">
      <div style="font-size:16px;font-weight:700;margin-bottom:6px;">Suggestion History is not installed yet</div>
      <p style="color:var(--n-500);font-size:13px;">Run <code>config/migration_ai_suggestion_history.sql</code> against the SBM database, then reload this page.</p>
    </div>
  <?php elseif (count($years) < 2): ?>
    <div class="card-body" style="padding:28px;">
      <div style="font-size:16px;font-weight:700;margin-bottom:6px;">Your repository is just getting started</div>
      <p style="color:var(--n-500);font-size:13px;">Generate AI suggestions for another school year to compare how recommendations and scores improve from cycle to cycle.</p>
    </div>
  <?php else: ?>
    <div class="card-body" style="padding:20px 24px;">
      <div class="learning-summary">
        <strong>Last cycle</strong>
        <span><?= (int) $learningSummary['suggestions'] ?> suggestions</span>
        <span><?= (int) $learningSummary['adopted'] ?> adopted</span>
        <span><?= (int) $learningSummary['improved'] ?> improved</span>
        <span><?= (int) $learningSummary['revised'] ?> revised</span>
        <span><?= (int) $learningSummary['repeated'] ?> repeated</span>
      </div>
      <div class="history-grid history-grid-head">
        <div>Dimension</div><div><?= e($yearA) ?></div><div><?= e($yearB) ?></div>
      </div>
      <?php foreach ($dimensions as $dimensionId => $dimension): ?>
        <?php
          $a = null;
          foreach ($left as $item) if ((int) $item['dimension_no'] === $dimensionId) { $a = $item; break; }
          $b = $rightByDimension[$dimensionId] ?? null;
          $newerScore = $newerItemByDimension[$dimensionId]['display_score'] ?? null;
          $olderScore = $olderItemByDimension[$dimensionId]['display_score'] ?? null;
          $scoreChange = ($newerScore === null || $olderScore === null)
              ? null
              : round(round((float) $newerScore, 1) - round((float) $olderScore, 1), 1);
          $scoreStatus = $scoreChange === null
              ? null
              : ($scoreChange > 0 ? 'improved' : ($scoreChange < 0 ? 'declined' : 'unchanged'));
          $scoreStatusLabel = $scoreStatus === 'improved'
              ? 'Improved +' . number_format($scoreChange, 1)
              : ($scoreStatus === 'declined'
                  ? 'Declined −' . number_format(abs($scoreChange), 1)
                  : 'Unchanged 0.0');
          $newerItem = $newerItemByDimension[$dimensionId] ?? null;
          $olderItem = $olderItemByDimension[$dimensionId] ?? null;
          $similarity = ($newerItem && $olderItem)
              ? sfSuggestionActionSimilarity($olderItem, $newerItem)
              : 0;
          $suggestionStatus = !$olderItem && $newerItem
              ? 'New'
              : (($newerItem && $olderItem) ? ($similarity >= .88 ? 'Repeated' : 'Revised') : null);
          $dataTransition = ($newerItem && $olderItem && $olderItem['data_status'] !== $newerItem['data_status'])
              ? ucfirst($olderItem['data_status']) . ' → ' . ucfirst($newerItem['data_status']) : null;
        ?>
        <div class="history-grid history-row" data-dimension-row="<?= (int) $dimensionId ?>">
          <div class="dimension-label"><strong>D<?= (int) $dimension['dimension_no'] ?></strong><span><?= e($dimension['dimension_name']) ?></span></div>
          <?php foreach ([$a, $b] as $columnIndex => $item): ?>
          <?php $columnYear = $columnIndex === 0 ? $yearA : $yearB; ?>
          <div class="history-column" data-history-cell="<?= $columnIndex ?>">
            <?php if (!$item): ?>
              <div class="history-cell-year"><?= e($columnYear) ?></div>
              <div class="empty-suggestion">No suggestion saved for this year.</div>
            <?php else: ?>
              <article class="suggestion-card">
              <div class="history-cell-year"><?= e($columnYear) ?></div>
              <?php $safeTitle = trim(str_replace('**', '', (string) $item['suggestion_title'])); ?>
              <?php $itemScore = $item['display_score'] !== null && is_numeric($item['display_score']) ? (float) $item['display_score'] : null; ?>
              <?php
                $isSampleAdoption = !empty($item['adopted_plan_id']) && !empty($item['adopted_plan_is_sample']);
                $adoptionLabel = !$completedYears[$columnYear]
                    ? 'Pending'
                    : (!empty($item['adopted_plan_id']) ? 'Adopted' : 'Not adopted');
                $fullCycleNote = trim((string) ($item['last_cycle_note'] ?? ''));
                $compactCycleNote = '';
                $continuedReason = '';
                if ($columnYear === $newerYear && $fullCycleNote !== '') {
                    $previousAdoption = !$olderItem
                        ? 'unavailable'
                        : (!$completedYears[$olderYear]
                            ? 'pending'
                            : (!empty($olderItem['adopted_plan_id']) ? 'adopted' : 'not adopted'));
                    $noteScoreChange = $scoreChange;
                    $scoreText = $noteScoreChange === null
                        ? 'unavailable'
                        : ($noteScoreChange > 0
                            ? '+' . number_format($noteScoreChange, 1)
                            : ($noteScoreChange < 0
                                ? '−' . number_format(abs($noteScoreChange), 1)
                                : number_format(0, 1)));
                    $compactCycleNote = 'Last cycle: ' . $previousAdoption . '; score ' . $scoreText . '.';
                    if (preg_match('/\bcontinu(?:e|ed|ing|ation)\b/i', $fullCycleNote)) {
                        $scoreOutcome = $noteScoreChange === null
                            ? 'the score change is unavailable'
                            : ($noteScoreChange > 0
                                ? 'the score improved'
                                : ($noteScoreChange < 0 ? 'the score declined' : 'the score was unchanged'));
                        $planOutcome = $previousAdoption === 'adopted'
                            ? 'a plan was linked.'
                            : ($previousAdoption === 'not adopted'
                                ? 'no plan was linked.'
                                : 'plan adoption is ' . $previousAdoption . '.');
                        $continuedReason = 'Continued: ' . $scoreOutcome . ' and ' . $planOutcome;
                    }
                }
              ?>
              <?php if ($compactCycleNote !== ''): ?>
                <div class="last-cycle-note">
                  <span><?= e($compactCycleNote) ?></span>
                  <?php if ($continuedReason !== ''): ?><span class="continued-reason"><?= e($continuedReason) ?></span><?php endif; ?>
                  <details class="last-cycle-details">
                    <summary>Details</summary>
                    <p><?= e($fullCycleNote) ?></p>
                  </details>
                </div>
              <?php endif; ?>
              <div class="suggestion-title"><?= e($safeTitle) ?></div>
              <div class="history-meta">
                <span>Score: <?= $itemScore !== null ? e(number_format($itemScore, 1)) . '%' : '—' ?></span>
                <span class="tag tag-<?= e(strtolower($item['decline_risk'])) ?>">Risk <?= e(ucfirst($item['decline_risk'])) ?></span>
                <span class="tag tag-data"><?= e(ucfirst($item['data_status'])) ?> Data</span>
                <span class="tag <?= $adoptionLabel === 'Pending' ? 'tag-pending' : (!empty($item['adopted_plan_id']) ? 'tag-adopted' : 'tag-not-adopted') ?>"><?= e($adoptionLabel) ?></span>
                <?php if ($isSampleAdoption): ?><span class="tag tag-sample">Sample</span><?php endif; ?>
                <?php if (!empty($item['needs_review'])): ?><span class="tag tag-pending">Needs review</span><?php endif; ?>
                <?php if (!empty($item['is_sample'])): ?><span class="tag tag-sample">Sample data</span><?php endif; ?>
                <?php if ($columnYear === $newerYear): ?>
                  <?php if ($scoreStatus === null): ?><span class="tag tag-no-score">No score data</span><?php else: ?><span class="tag tag-score-<?= e($scoreStatus) ?>"><?= e($scoreStatusLabel) ?></span><?php endif; ?>
                  <?php if ($suggestionStatus !== null): ?><span class="tag tag-status"><?= e($suggestionStatus) ?></span><?php endif; ?>
                  <?php if ($dataTransition): ?><span class="tag tag-data"><?= e($dataTransition) ?></span><?php endif; ?>
                <?php endif; ?>
              </div>
              <div class="suggestion-text collapsed"><?= renderSuggestionPreview($item) ?></div>
              <button type="button" class="show-more-btn" aria-expanded="false">Show more</button>
              <?php if ($columnYear === $currentSchoolYear): ?><a class="plan-link" href="improvement_plans.php?prefill_history_id=<?= (int) $item['id'] ?>">Create plan from suggestion</a><?php endif; ?>
              <div class="suggestion-card-footer">
                <span>Model: <?= e(trim((string) ($item['model_version'] ?? '')) ?: 'Not recorded') ?></span>
              </div>
              </article>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <div class="history-plans">
        <div class="history-plans-title">Improvement plans by school year</div>
        <div class="history-plans-grid">
          <?php foreach ([$yearA, $yearB] as $planYear): ?>
            <?php $yearPlans = $plansByYear[$planYear] ?? []; ?>
            <section class="history-plan-column">
              <h3><?= e($planYear) ?></h3>
              <?php if (!$yearPlans): ?>
                <div class="history-plan-empty">No linked improvement plans recorded for this year.</div>
              <?php else: ?>
                <?php foreach ($yearPlans as $plan): ?>
                  <article class="history-plan-card">
                    <div class="history-plan-card-head">
                      <strong>D<?= (int) $plan['dimension_no'] ?> · <?= e($plan['dimension_name']) ?></strong>
                      <div style="display:flex;gap:5px;flex-wrap:wrap;justify-content:flex-end;">
                        <span class="tag tag-<?= e(strtolower((string) $plan['priority_level'])) ?>"><?= e((string) $plan['priority_level']) ?></span>
                        <?php if (!empty($plan['is_sample'])): ?><span class="tag tag-sample">Sample</span><?php endif; ?>
                      </div>
                    </div>
                    <div class="history-plan-label">Linked suggestion</div>
                    <p><?= e((string) $plan['linked_suggestion_title']) ?></p>
                    <div class="history-plan-label">Objective</div>
                    <p><?= e((string) $plan['objective']) ?></p>
                    <div class="history-plan-label">Strategy</div>
                    <p><?= e((string) $plan['strategy']) ?></p>
                    <div class="history-plan-footer">
                      <span>Status: <?= e(ucfirst((string) ($plan['status'] ?: $plan['workflow_status']))) ?></span>
                      <?php if (!empty($plan['target_date'])): ?><span>Target: <?= e((string) $plan['target_date']) ?></span><?php endif; ?>
                    </div>
                  </article>
                <?php endforeach; ?>
              <?php endif; ?>
            </section>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<style>
.last-cycle-note{display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 8px;margin:0 0 12px;padding:9px 11px;border-left:3px solid #86c79a;border-radius:0 6px 6px 0;background:#f3faf5;color:var(--n-600);font-size:11.5px;line-height:1.5}
.continued-reason{color:var(--n-500)}
.last-cycle-details{flex-basis:100%;font-size:11px}
.last-cycle-details summary{width:max-content;color:var(--brand-700);font-weight:600;cursor:pointer}
.last-cycle-details p{margin:5px 0 0;color:var(--n-500);line-height:1.45}
.tag-sample{background:#f1f5f9;color:#475569}
.learning-summary{display:flex;flex-wrap:wrap;align-items:center;gap:7px;margin:0 0 18px;padding:10px 12px;border:1px solid #d9eee0;border-radius:8px;background:#f5fbf6;color:var(--n-600);font-size:12px}
.learning-summary strong{margin-right:3px;color:var(--brand-700)}
.learning-summary span{padding:3px 8px;border-radius:999px;background:#fff;border:1px solid var(--n-150)}
.history-grid{display:grid;grid-template-columns:112px minmax(0,1fr) minmax(0,1fr);gap:12px}
.history-grid-head{align-items:center;padding:0 0 10px;color:var(--n-600);font-size:11px;font-weight:700}
.history-grid-head>div:not(:first-child){padding:9px 12px;border:1px solid var(--n-150);border-radius:7px;background:var(--n-50);color:var(--n-800)}
.history-row{align-items:stretch;padding:14px 0;border-bottom:1px solid var(--n-150)}
.dimension-label{display:flex;flex-direction:column;gap:5px;padding:7px 4px;color:var(--n-600);font-size:11.5px;line-height:1.4}
.dimension-label strong{color:var(--brand-700);font-size:14px}
.history-column{min-width:0}
.suggestion-card{height:100%;box-sizing:border-box;padding:14px;border:1px solid var(--n-150);border-radius:9px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.035)}
.history-cell-year{display:none}
.suggestion-title{margin:0 0 8px;color:var(--n-900);font-size:13px;line-height:1.4}
.suggestion-text{overflow:hidden;color:var(--n-600);font-size:12px;line-height:1.55}
.suggestion-text.collapsed{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical}
.suggestion-text p{margin:0 0 8px}
.suggestion-text p:last-child{margin-bottom:0}
.suggestion-text ul{margin:2px 0 0;padding-left:19px}
.suggestion-text li{margin:0 0 6px;padding-left:2px}
.suggestion-text li:last-child{margin-bottom:0}
.show-more-btn{display:inline-block;margin-top:5px;padding:3px 0;border:0;background:none;color:var(--brand-700);font-size:11.5px;font-weight:600;cursor:pointer}
.history-meta{display:flex;flex-wrap:wrap;gap:5px;margin:0 0 9px;color:var(--n-500);font-size:11px}
.tag{display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;font-size:10px;font-weight:700;line-height:1.25;background:var(--n-100);color:var(--n-600)}
.tag-score-improved{background:#dcfce7;color:#15803d}
.tag-score-declined{background:#fee2e2;color:#b91c1c}
.tag-score-unchanged,.tag-no-score{background:var(--n-100);color:var(--n-600)}
.tag-pending{background:#f1f5f9;color:#64748b}
.tag-status{background:#ede9fe;color:#6d28d9}
.tag-high{background:#fee2e2;color:#b91c1c}
.tag-medium{background:#fef3c7;color:#a16207}
.tag-low{background:#dcfce7;color:#15803d}
.tag-data{background:#e0f2fe;color:#0369a1}
.plan-link{display:inline-block;margin-top:8px;color:var(--brand-700);font-size:11px;font-weight:600;text-decoration:none}
.plan-link:hover{text-decoration:underline}
.suggestion-card-footer{margin-top:10px;padding-top:8px;border-top:1px solid var(--n-100);color:var(--n-400);font-size:10.5px;line-height:1.4}
.empty-suggestion{min-height:92px;display:flex;align-items:center;justify-content:center;padding:10px;border:1px dashed var(--n-300);border-radius:8px;color:var(--n-400);font-size:12px;text-align:center}
.history-year-select{height:32px;padding:0 30px 0 10px;border:1px solid var(--n-300);border-radius:6px;background:#fff;color:var(--n-700);font:inherit;font-size:12px;outline:none}
.history-year-select:focus{border-color:var(--brand-500);box-shadow:0 0 0 3px var(--brand-100)}
.history-plans{margin-top:26px;padding-top:20px;border-top:1px solid var(--n-200)}
.history-plans-title{margin-bottom:14px;color:var(--n-900);font-size:15px;font-weight:700}
.history-plans-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px}
.history-plan-column{min-width:0}
.history-plan-column h3{margin:0 0 10px;padding:9px 11px;border:1px solid var(--n-150);border-radius:7px;background:var(--n-50);color:var(--n-800);font-size:12px}
.history-plan-card{margin-bottom:10px;padding:14px;border:1px solid var(--n-200);border-radius:8px;background:#fff}
.history-plan-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:12px;color:var(--n-800);font-size:12px}
.history-plan-label{margin-top:9px;color:var(--n-500);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.history-plan-card p{margin:3px 0 0;color:var(--n-600);font-size:12px;line-height:1.5;white-space:pre-line}
.history-plan-footer{display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;padding-top:9px;border-top:1px solid var(--n-100);color:var(--n-500);font-size:11px}
@media(max-width:1024px){
  .history-grid{grid-template-columns:minmax(0,1fr);gap:9px}
  .history-grid-head{display:none}
  .history-row{gap:9px;padding:12px 0}
  .dimension-label{padding:0 2px 8px;border-bottom:1px solid var(--n-100)}
  .history-cell-year{display:block;margin-bottom:9px;color:var(--brand-700);font-size:11px;font-weight:700}
  .suggestion-card{height:auto;padding:12px}
  .history-plans-grid{grid-template-columns:minmax(0,1fr)}
}
</style>
<script>
document.querySelectorAll('.show-more-btn').forEach(function (button) {
  const preview = button.previousElementSibling;
  if (preview && preview.scrollHeight <= preview.clientHeight + 1) {
    button.style.display = 'none';
  }
  button.addEventListener('click', function () {
    const row = button.closest('[data-dimension-row]');
    const expanded = button.getAttribute('aria-expanded') === 'true';
    row.querySelectorAll('.suggestion-text').forEach(function (text) {
      text.classList.toggle('collapsed', expanded);
    });
    row.querySelectorAll('.show-more-btn').forEach(function (toggle) {
      toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      toggle.textContent = expanded ? 'Show more' : 'Show less';
    });
  });
});
document.querySelectorAll('.history-year-select').forEach(function (select) {
  select.addEventListener('change', function () {
    document.getElementById('historyCompareForm').submit();
  });
});
</script>
