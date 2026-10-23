<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sbm_indicators.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('sbm_coordinator');
$db = getDB();
$schoolId = SCHOOL_ID;

$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
$cycle = null;
if ($schoolId && $syId) {
  $cycleQuery = $db->prepare("
    SELECT c.*, sy.label AS sy_label
    FROM sbm_cycles c
    JOIN school_years sy ON c.sy_id = sy.sy_id
    WHERE c.school_id = ? AND c.sy_id = ?
    LIMIT 1
  ");
  $cycleQuery->execute([$schoolId, $syId]);
  $cycle = $cycleQuery->fetch(PDO::FETCH_ASSOC);
}

$schoolHeadPreview = [];
$evaluatorPreview = [];
$externalEvidenceByEvaluatorAndIndicator = [];
$previewRatingLabels = [
  1 => 'Not yet manifested',
  2 => 'Rarely manifested',
  3 => 'Frequently manifested',
  4 => 'Always manifested',
];
function renderReviewDimensionTable(array $responses, array $ratingLabels): void
{
  ?>
  <div class="tbl-wrap review-table-scroll">
    <table class="ind-table">
      <thead>
        <tr><th>Code</th><th>Indicator</th><th>MOV Guide</th><th>Rating</th><th>Remarks</th><th>Evidence</th><th>Last saved by</th></tr>
      </thead>
      <tbody>
        <?php foreach ($responses as $response): ?>
          <?php
          $rating = $response['rating_value'];
          $isLowRating = in_array($rating, [1, 2], true);
          $remarks = trim((string) ($response['evaluator_remarks'] ?? ''));
          $evidenceText = trim((string) ($response['evidence_text'] ?? ''));
          $attachments = $response['attachments'] ?? [];
          $hasEvidence = $evidenceText !== '' || !empty($attachments);
          $needsAttention = $isLowRating || $remarks !== '';
          $lastSavedBy = trim((string) ($response['rated_by_name'] ?? ''));
          ?>
          <tr class="<?= $isLowRating ? 'review-low-row' : '' ?>"
            data-preview-match="<?= $remarks !== '' || $hasEvidence ? '1' : '0' ?>"
            data-needs-attention="<?= $needsAttention ? '1' : '0' ?>"
            data-low-rating="<?= $isLowRating ? '1' : '0' ?>">
            <td><span style="font-family:monospace;font-size:11px;font-weight:700;"><?= e($response['indicator_code']) ?></span></td>
            <td style="font-size:12px;line-height:1.45;"><?= e($response['indicator_text']) ?></td>
            <td style="font-size:11px;color:var(--n-500,#64748B);"><?= trim((string) ($response['mov_guide'] ?? '')) !== '' ? e($response['mov_guide']) : '—' ?></td>
            <td style="white-space:nowrap;font-size:12px;font-weight:700;">
              <?= $rating === null ? '—' : (int) $rating . ' — ' . e($ratingLabels[$rating] ?? 'Unknown rating') ?>
              <?php if ($isLowRating): ?><span class="review-low-badge">Low</span><?php endif; ?>
            </td>
            <td style="font-size:12px;"><?= $remarks !== '' ? e($remarks) : '—' ?></td>
            <td style="font-size:12px;">
              <?php if (!$hasEvidence): ?>
                —
              <?php else: ?>
                <?php if ($evidenceText !== ''): ?><?= e($evidenceText) ?><?php endif; ?>
                <?php $isFirstAttachment = true; ?>
                <?php foreach ($attachments as $attachment): ?>
                  <?php if ($evidenceText !== '' || !$isFirstAttachment): ?><br><?php endif; ?>
                  <a href="../includes/serve_attachment.php?id=<?= (int) $attachment['attachment_id'] ?>"><?= e($attachment['original_name']) ?></a>
                  <?php $isFirstAttachment = false; ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;"><?= $lastSavedBy !== '' ? e($lastSavedBy) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php
}

$ratedCount = 0;
if ($cycle) {
  $ratedCountQuery = $db->prepare("
    SELECT COUNT(DISTINCT indicator_id)
    FROM (
      SELECT indicator_id FROM sbm_responses WHERE cycle_id = ?
      UNION
      SELECT indicator_id FROM teacher_responses
      WHERE cycle_id = ? AND school_id = ? AND status = 'submitted'
    ) AS all_responses
  ");
  $ratedCountQuery->execute([$cycle['cycle_id'], $cycle['cycle_id'], $schoolId]);
  $ratedCount = (int) $ratedCountQuery->fetchColumn();
}
if ($cycle && $cycle['status'] === 'submitted') {
  $previewResponses = $db->prepare("
    SELECT r.indicator_id, r.rating, r.evidence_text, i.indicator_code, i.indicator_text, i.mov_guide,
           i.sort_order, d.dimension_no, d.dimension_name, d.color_hex,
           u.full_name AS rated_by_name
    FROM sbm_responses r
    JOIN sbm_indicators i ON i.indicator_id = r.indicator_id
    JOIN sbm_dimensions d ON d.dimension_id = i.dimension_id
    LEFT JOIN users u ON u.user_id = r.rated_by
    WHERE r.cycle_id = ? AND r.school_id = ?
    ORDER BY d.dimension_no, i.sort_order
  ");
  $previewResponses->execute([$cycle['cycle_id'], $schoolId]);
  $schoolHeadAttachments = $db->prepare("
    SELECT attachment_id, indicator_id, original_name
    FROM response_attachments
    WHERE cycle_id = ? AND school_id = ?
      AND deleted_at IS NULL AND is_current_version = 1
    ORDER BY indicator_id, uploaded_at, attachment_id
  ");
  $schoolHeadAttachments->execute([$cycle['cycle_id'], $schoolId]);
  $schoolHeadAttachmentsByIndicator = [];
  foreach ($schoolHeadAttachments->fetchAll(PDO::FETCH_ASSOC) as $attachment) {
    $schoolHeadAttachmentsByIndicator[(int) $attachment['indicator_id']][] = $attachment;
  }
  foreach ($previewResponses->fetchAll(PDO::FETCH_ASSOC) as $response) {
    $response['rating_value'] = is_numeric($response['rating'] ?? null) ? (int) $response['rating'] : null;
    $response['evaluator_remarks'] = '';
    $response['rated_by_name'] = $response['rated_by_name'] ?? '';
    $response['attachments'] = $schoolHeadAttachmentsByIndicator[(int) $response['indicator_id']] ?? [];
    $schoolHeadPreview[(int) $response['dimension_no']][] = $response;
  }

  $teacherPreview = $db->prepare("
    SELECT tr.teacher_id AS evaluator_id, u.full_name AS evaluator_name,
           'Teacher' AS evaluator_role, tr.rating, tr.remarks AS evaluator_remarks,
           i.indicator_id, i.indicator_code, i.indicator_text, i.mov_guide, i.sort_order,
           d.dimension_no, d.dimension_name, d.color_hex
    FROM teacher_responses tr
    JOIN (
      SELECT ts.cycle_id, ts.teacher_id
      FROM teacher_submissions ts
      WHERE ts.cycle_id = ? AND ts.school_id = ? AND ts.status = 'submitted'
        AND ts.submission_id = (
          SELECT MAX(latest.submission_id)
          FROM teacher_submissions latest
          WHERE latest.cycle_id = ts.cycle_id AND latest.school_id = ts.school_id
            AND latest.teacher_id = ts.teacher_id
        )
    ) submitted ON submitted.cycle_id = tr.cycle_id AND submitted.teacher_id = tr.teacher_id
    JOIN users u ON u.user_id = tr.teacher_id AND u.school_id = tr.school_id AND u.role = 'teacher'
    JOIN sbm_indicators i ON i.indicator_id = tr.indicator_id
    JOIN sbm_dimensions d ON d.dimension_id = i.dimension_id
    WHERE tr.cycle_id = ? AND tr.school_id = ? AND tr.status = 'submitted'
    ORDER BY u.full_name, d.dimension_no, i.sort_order
  ");
  $teacherPreview->execute([$cycle['cycle_id'], $schoolId, $cycle['cycle_id'], $schoolId]);
  $teacherResponses = $teacherPreview->fetchAll(PDO::FETCH_ASSOC);
  $teacherAttachments = $db->prepare("
    SELECT attachment_id, indicator_id, uploaded_by, original_name
    FROM response_attachments
    WHERE cycle_id = ? AND school_id = ? AND uploader_role = 'teacher'
      AND deleted_at IS NULL AND is_current_version = 1
    ORDER BY uploaded_by, indicator_id, uploaded_at, attachment_id
  ");
  $teacherAttachments->execute([$cycle['cycle_id'], $schoolId]);
  $teacherAttachmentsByTeacherAndIndicator = [];
  foreach ($teacherAttachments->fetchAll(PDO::FETCH_ASSOC) as $attachment) {
    $teacherAttachmentsByTeacherAndIndicator[(int) $attachment['uploaded_by']][(int) $attachment['indicator_id']][] = $attachment;
  }
  foreach ($teacherResponses as &$response) {
    $response['rating_value'] = is_numeric($response['rating'] ?? null) ? (int) $response['rating'] : null;
    $response['evidence_text'] = '';
    $response['rated_by_name'] = $response['evaluator_name'];
    $response['attachments'] =
      $teacherAttachmentsByTeacherAndIndicator[(int) $response['evaluator_id']][(int) $response['indicator_id']] ?? [];
  }
  unset($response);

  $stakeholderPreview = $db->prepare("
    SELECT sr.stakeholder_id AS evaluator_id, u.full_name AS evaluator_name,
           'Stakeholder' AS evaluator_role, sr.rating, sr.remarks AS evaluator_remarks,
           i.indicator_id, i.indicator_code, i.indicator_text, i.mov_guide, i.sort_order,
           d.dimension_no, d.dimension_name, d.color_hex
    FROM stakeholder_responses sr
    JOIN (
      SELECT ss.cycle_id, ss.stakeholder_id
      FROM stakeholder_submissions ss
      WHERE ss.cycle_id = ? AND ss.school_id = ? AND ss.status = 'submitted'
        AND ss.submission_id = (
          SELECT MAX(latest.submission_id)
          FROM stakeholder_submissions latest
          WHERE latest.cycle_id = ss.cycle_id AND latest.stakeholder_id = ss.stakeholder_id
        )
    ) submitted ON submitted.cycle_id = sr.cycle_id AND submitted.stakeholder_id = sr.stakeholder_id
    JOIN users u ON u.user_id = sr.stakeholder_id AND u.school_id = sr.school_id AND u.role = 'external_stakeholder'
    JOIN sbm_indicators i ON i.indicator_id = sr.indicator_id
    JOIN sbm_dimensions d ON d.dimension_id = i.dimension_id
    WHERE sr.cycle_id = ? AND sr.school_id = ? AND sr.status = 'submitted'
    ORDER BY u.full_name, d.dimension_no, i.sort_order
  ");
  $stakeholderPreview->execute([$cycle['cycle_id'], $schoolId, $cycle['cycle_id'], $schoolId]);
  $stakeholderAttachments = $db->prepare("
    SELECT attachment_id, uploaded_by, indicator_id, original_name
    FROM response_attachments
    WHERE cycle_id = ? AND school_id = ? AND uploader_role = 'external_stakeholder'
      AND deleted_at IS NULL AND is_current_version = 1
  ");
  $stakeholderAttachments->execute([$cycle['cycle_id'], $schoolId]);
  foreach ($stakeholderAttachments->fetchAll(PDO::FETCH_ASSOC) as $attachment) {
    $externalEvidenceByEvaluatorAndIndicator[(int) $attachment['uploaded_by']][(int) $attachment['indicator_id']][] = $attachment;
  }

  $stakeholderResponses = $stakeholderPreview->fetchAll(PDO::FETCH_ASSOC);
  foreach ($stakeholderResponses as &$response) {
    $response['rating_value'] = is_numeric($response['rating'] ?? null) ? (int) $response['rating'] : null;
    $response['evidence_text'] = '';
    $response['rated_by_name'] = $response['evaluator_name'];
    $response['attachments'] =
      $externalEvidenceByEvaluatorAndIndicator[(int) $response['evaluator_id']][(int) $response['indicator_id']] ?? [];
  }
  unset($response);

  foreach (array_merge($teacherResponses, $stakeholderResponses) as $response) {
    $evaluatorKey = $response['evaluator_role'] . ':' . (int) $response['evaluator_id'];
    if (!isset($evaluatorPreview[$evaluatorKey])) {
      $evaluatorPreview[$evaluatorKey] = [
        'id' => (int) $response['evaluator_id'],
        'name' => $response['evaluator_name'],
        'role' => $response['evaluator_role'],
        'dimensions' => [],
        'indicator_count' => 0,
        'remark_count' => 0,
        'evidence_count' => 0,
        'match_count' => 0,
        'rating_sum' => 0,
        'rated_count' => 0,
        'low_count' => 0,
      ];
    }
    $evaluatorPreview[$evaluatorKey]['dimensions'][(int) $response['dimension_no']][] = $response;
    $evaluatorPreview[$evaluatorKey]['indicator_count']++;
    if ($response['rating_value'] !== null && $response['rating_value'] >= 1 && $response['rating_value'] <= 4) {
      $evaluatorPreview[$evaluatorKey]['rating_sum'] += $response['rating_value'];
      $evaluatorPreview[$evaluatorKey]['rated_count']++;
    }
    if (in_array($response['rating_value'], [1, 2], true)) {
      $evaluatorPreview[$evaluatorKey]['low_count']++;
    }
    $hasRemark = trim((string) ($response['evaluator_remarks'] ?? '')) !== '';
    $hasEvidence = !empty($response['attachments']);
    if ($hasRemark) {
      $evaluatorPreview[$evaluatorKey]['remark_count']++;
    }
    if ($hasEvidence) {
      $evaluatorPreview[$evaluatorKey]['evidence_count']++;
    }
    if ($hasRemark || $hasEvidence) {
      $evaluatorPreview[$evaluatorKey]['match_count']++;
    }
  }
}

$teacherEvaluatorPreview = array_filter(
  $evaluatorPreview,
  static fn(array $evaluator): bool => $evaluator['role'] === 'Teacher'
);
$externalEvaluatorPreview = array_filter(
  $evaluatorPreview,
  static fn(array $evaluator): bool => $evaluator['role'] === 'Stakeholder'
);
$previewSubmissionCounts = [
  'school-head' => $schoolHeadPreview ? 1 : 0,
  'teachers' => count($teacherEvaluatorPreview),
  'external' => count($externalEvaluatorPreview),
];
$activePreviewTab = $previewSubmissionCounts['school-head'] > 0
  ? 'school-head'
  : ($previewSubmissionCounts['teachers'] > 0
    ? 'teachers'
    : ($previewSubmissionCounts['external'] > 0 ? 'external' : null));
$summaryCounts = ['low' => 0, 'remarks' => 0, 'evidence' => 0];
foreach ($schoolHeadPreview as $dimensionResponses) {
  foreach ($dimensionResponses as $response) {
    if (in_array($response['rating_value'], [1, 2], true)) {
      $summaryCounts['low']++;
    }
    if (trim((string) ($response['evidence_text'] ?? '')) !== '' || !empty($response['attachments'])) {
      $summaryCounts['evidence']++;
    }
  }
}
foreach ($evaluatorPreview as $evaluator) {
  foreach ($evaluator['dimensions'] as $dimensionResponses) {
    foreach ($dimensionResponses as $response) {
      if (in_array($response['rating_value'], [1, 2], true)) {
        $summaryCounts['low']++;
      }
      if (trim((string) ($response['evaluator_remarks'] ?? '')) !== '') {
        $summaryCounts['remarks']++;
      }
      $hasEvaluatorEvidence = !empty($response['attachments'])
        || (
          $evaluator['role'] === 'Stakeholder'
          && !empty($externalEvidenceByEvaluatorAndIndicator[$evaluator['id']][(int) $response['indicator_id']])
        );
      if ($hasEvaluatorEvidence) {
        $summaryCounts['evidence']++;
      }
    }
  }
}

$pageTitle = 'Assessment Review';
$activePage = 'review.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
  .review-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin: 0 0 16px;
  }
  .review-summary-item,
  .review-card,
  .review-mini-nav {
    border: 1px solid var(--n-200, #E2E8F0);
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .05);
  }
  .review-summary-item {
    padding: 14px 16px;
  }
  .review-summary-value {
    color: var(--n-900, #0F172A);
    font-size: 22px;
    font-weight: 800;
  }
  .review-summary-label {
    margin-top: 2px;
    color: var(--n-500, #64748B);
    font-size: 12px;
  }
  .review-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    padding-bottom: 90px;
  }
  .review-mini-nav {
    position: sticky;
    top: calc(var(--topbar-h, 64px) + 8px);
    z-index: 30;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 12px;
    margin-bottom: 16px;
    overflow-x: auto;
    white-space: nowrap;
  }
  .review-mini-nav-title {
    flex: 0 0 auto;
    margin: 0 4px 0 0;
    color: var(--n-700, #334155);
    font-size: 12px;
    font-weight: 700;
  }
  .review-mini-nav a {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    border-radius: 6px;
    padding: 7px 8px;
    color: var(--n-600, #475569);
    font-size: 12px;
    line-height: 1.3;
    text-decoration: none;
    white-space: nowrap;
  }
  .review-mini-nav a:hover {
    background: var(--n-50, #F8FAFC);
    color: var(--brand-700, #1D4ED8);
  }
  .review-mini-nav a[aria-current="location"] {
    background: var(--brand-50, #EFF6FF);
    color: var(--brand-700, #1D4ED8);
    font-weight: 700;
  }
  .review-filter-row {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin: 0 0 12px;
  }
  .review-filter-row label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    color: var(--n-700, #334155);
  }
  .review-low-row td {
    background: #FFF8E7;
  }
  .review-low-badge {
    display: inline-block;
    margin-left: 5px;
    border: 1px solid #F6D58B;
    border-radius: 10px;
    padding: 2px 6px;
    background: #FEF3C7;
    color: #92400E;
    font-size: 10px;
    font-weight: 700;
  }
  .teacher-preview-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    border: 1px solid var(--n-200, #E2E8F0);
    border-radius: 7px;
    padding: 8px;
    background: #fff;
  }
  .teacher-preview-toggle {
    flex: 1;
    min-width: 0;
    border: 0;
    padding: 2px;
    background: transparent;
    color: var(--n-700, #334155);
    font: inherit;
    font-size: 12px;
    font-weight: 700;
    text-align: left;
    cursor: pointer;
  }
  .teacher-preview-status {
    display: inline-block;
    margin-left: 6px;
    border-radius: 10px;
    padding: 2px 7px;
    background: #DCFCE7;
    color: #166534;
    font-size: 10px;
    white-space: nowrap;
  }
  .teacher-preview-content {
    margin-bottom: 12px;
    border: 1px solid var(--n-200, #E2E8F0);
    border-top: 0;
    border-radius: 0 0 7px 7px;
    padding: 0 10px 10px;
    background: #fff;
  }
  .review-validation-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 16px;
  }
  .review-validation-summary > div {
    display: grid;
    gap: 4px;
    border: 1px solid var(--n-200, #E2E8F0);
    border-radius: 8px;
    padding: 10px;
  }
  .review-validation-summary span {
    color: var(--n-500, #64748B);
    font-size: 11px;
  }
  .review-validation-summary strong {
    color: var(--n-900, #0F172A);
    font-size: 16px;
  }
  .review-card {
    min-width: 0;
    padding: 16px;
  }
  .review-dimension {
    scroll-margin-top: 82px;
    margin-bottom: 12px;
    min-width: 0;
    border: 1px solid var(--n-200, #E2E8F0);
    border-radius: 7px;
    overflow: hidden;
    background: #fff;
  }
  .review-dimension-title {
    background: var(--n-50, #F8FAFC);
    border-bottom: 1px solid var(--n-200, #E2E8F0);
    color: var(--n-700, #334155);
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 700;
  }
  .review-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin: 0 0 12px;
    border-bottom: 1px solid var(--n-200, #E2E8F0);
  }
  .review-table-scroll {
    max-width: 100%;
    overflow-x: auto;
  }
  .review-table-scroll table {
    min-width: 720px;
  }
  .review-table-scroll th:nth-child(2),
  .review-table-scroll td:nth-child(2) {
    min-width: 240px;
  }
  .review-tab {
    padding: 8px 10px;
    border: 0;
    border-bottom: 2px solid transparent;
    background: transparent;
    color: var(--n-600, #475569);
    font: inherit;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
  }
  .review-tab[aria-selected="true"] {
    border-bottom-color: var(--brand-600, #2563EB);
    color: var(--brand-700, #1D4ED8);
  }
  .review-footer {
    position: sticky;
    bottom: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 0 -24px;
    border-top: 1px solid var(--n-200, #E2E8F0);
    padding: 12px 24px;
    background: rgba(255, 255, 255, .97);
    box-shadow: 0 -4px 14px rgba(15, 23, 42, .06);
  }
  @media (max-width: 800px) {
    .review-mini-nav {
      padding: 10px;
    }
    .review-footer {
      margin: 0 -16px;
      padding-right: 16px;
      padding-left: 16px;
    }
  }
  @media (max-width: 560px) {
    .review-summary {
      grid-template-columns: 1fr;
    }
    .review-footer {
      align-items: stretch;
      flex-direction: column;
    }
    .review-footer-actions {
      display: flex;
      flex-wrap: wrap;
    }
  }
</style>

<?php if (!$cycle || $cycle['status'] !== 'submitted'): ?>
  <div class="review-card" style="margin-bottom:16px;">
    <p style="margin:0;color:var(--n-600,#475569);font-size:13px;">
      There is no submitted assessment ready for review.
    </p>
  </div>
<?php else: ?>
  <section class="review-summary" aria-label="Assessment review summary">
    <div class="review-summary-item">
      <div class="review-summary-value"><?= (int) $summaryCounts['low'] ?></div>
      <div class="review-summary-label">Low ratings (1–2)</div>
    </div>
    <div class="review-summary-item">
      <div class="review-summary-value"><?= (int) $summaryCounts['remarks'] ?></div>
      <div class="review-summary-label">Indicator responses with remarks</div>
    </div>
    <div class="review-summary-item">
      <div class="review-summary-value"><?= (int) $summaryCounts['evidence'] ?></div>
      <div class="review-summary-label">Indicator responses with evidence</div>
    </div>
  </section>

  <nav class="review-mini-nav" aria-label="Assessment dimensions">
    <div class="review-mini-nav-title">Jump to dimension</div>
    <?php foreach (SBM_DIMENSIONS as $dimensionNo => $dimension): ?>
      <a href="#" data-dimension-nav="<?= (int) $dimensionNo ?>">
        <?= (int) $dimensionNo ?> · <?= e($dimension['name']) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="review-layout">
    <div class="review-card">
      <div class="review-filter-row">
        <label>
          <input type="checkbox" id="previewRemarksOrEvidenceOnly">
          Show only indicators with remarks or evidence
        </label>
        <label>
          <input type="checkbox" id="previewNeedsAttentionOnly">
          Needs attention
        </label>
      </div>

      <div class="review-tabs" role="tablist" aria-label="Review response source">
        <?php
        $previewTabs = [
          'school-head' => 'School Head',
          'teachers' => 'Teachers',
          'external' => 'External evaluators',
        ];
        foreach ($previewTabs as $tabKey => $tabLabel):
          if ($previewSubmissionCounts[$tabKey] === 0) continue;
        ?>
          <button type="button" role="tab" class="review-tab"
            id="previewTab-<?= e($tabKey) ?>"
            aria-controls="previewPane-<?= e($tabKey) ?>"
            aria-selected="<?= $activePreviewTab === $tabKey ? 'true' : 'false' ?>"
            tabindex="<?= $activePreviewTab === $tabKey ? '0' : '-1' ?>"
            data-preview-tab="<?= e($tabKey) ?>">
            <?= e($tabLabel) ?> (<?= (int) $previewSubmissionCounts[$tabKey] ?>)
          </button>
        <?php endforeach; ?>
      </div>

      <?php if (array_sum($previewSubmissionCounts) === 0): ?>
        <p style="font-size:12px;color:var(--n-500,#64748B);">No submitted responses were found for this cycle.</p>
      <?php endif; ?>

      <section id="previewPane-school-head" role="tabpanel" aria-labelledby="previewTab-school-head"
        data-preview-pane="school-head" <?= $activePreviewTab !== 'school-head' ? 'hidden' : '' ?>>
        <?php if ($schoolHeadPreview): ?>
          <?php foreach ($schoolHeadPreview as $dimensionResponses): $firstResponse = $dimensionResponses[0]; ?>
            <div class="review-dimension" data-preview-dimension data-dimension-section data-dimension-no="<?= (int) $firstResponse['dimension_no'] ?>">
              <div class="review-dimension-title">
                Dimension <?= (int) $firstResponse['dimension_no'] ?>: <?= e($firstResponse['dimension_name']) ?>
              </div>
              <?php renderReviewDimensionTable($dimensionResponses, $previewRatingLabels); ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="font-size:12px;color:var(--n-500,#64748B);">No consolidated SBM indicator responses were found for this cycle.</p>
        <?php endif; ?>
        <p data-preview-empty-state hidden style="font-size:12px;color:var(--n-500,#64748B);margin:0 0 10px;" aria-live="polite">
          No indicators with remarks or evidence match this filter.
        </p>
      </section>

      <section id="previewPane-teachers" role="tabpanel" aria-labelledby="previewTab-teachers"
        data-preview-pane="teachers" <?= $activePreviewTab !== 'teachers' ? 'hidden' : '' ?>>
        <?php foreach ($teacherEvaluatorPreview as $evaluator): ?>
          <div data-preview-evaluator data-preview-teacher-row data-match-count="<?= (int) $evaluator['match_count'] ?>"
            style="margin-bottom:8px;" data-teacher-id="<?= (int) $evaluator['id'] ?>">
            <?php
            $averageRating = $evaluator['rated_count'] > 0
              ? number_format($evaluator['rating_sum'] / $evaluator['rated_count'], 2)
              : '—';
            ?>
            <div class="teacher-preview-row">
              <button type="button" class="teacher-preview-toggle" data-teacher-toggle
                aria-expanded="false" aria-controls="teacher-preview-<?= (int) $evaluator['id'] ?>">
                <?= e($evaluator['name']) ?> · Avg <?= e($averageRating) ?> · <?= (int) $evaluator['low_count'] ?> low rating(s)
                <span class="teacher-preview-status">Submitted</span>
              </button>
              <button type="button" class="btn btn-secondary btn-sm" data-open-teacher-return
                data-teacher-id="<?= (int) $evaluator['id'] ?>" data-teacher-name="<?= e($evaluator['name']) ?>">
                Return to this teacher
              </button>
            </div>
            <div class="teacher-preview-content" id="teacher-preview-<?= (int) $evaluator['id'] ?>" hidden>
              <?php foreach ($evaluator['dimensions'] as $dimensionResponses): $firstResponse = $dimensionResponses[0]; ?>
                <?php $dimensionNo = (int) $firstResponse['dimension_no']; ?>
                <div class="review-dimension" data-preview-dimension data-dimension-section data-dimension-no="<?= $dimensionNo ?>" style="margin-top:8px;">
                  <div class="review-dimension-title" style="border-bottom:0;">
                    Dimension <?= $dimensionNo ?>: <?= e($firstResponse['dimension_name']) ?>
                  </div>
                  <?php renderReviewDimensionTable($dimensionResponses, $previewRatingLabels); ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <p data-preview-empty-state hidden style="font-size:12px;color:var(--n-500,#64748B);margin:0 0 10px;" aria-live="polite">
          No teacher indicators match the selected filters.
        </p>
      </section>

      <section id="previewPane-external" role="tabpanel" aria-labelledby="previewTab-external"
        data-preview-pane="external" <?= $activePreviewTab !== 'external' ? 'hidden' : '' ?>>
        <?php foreach ($externalEvaluatorPreview as $evaluator): ?>
          <details data-preview-evaluator data-match-count="<?= (int) $evaluator['match_count'] ?>"
            style="margin-bottom:8px;border:1px solid var(--n-200,#E2E8F0);border-radius:7px;background:#fff;">
            <summary style="padding:9px 10px;cursor:pointer;font-size:12px;font-weight:700;color:var(--n-700,#334155);">
              <?= e($evaluator['name']) ?> · <?= (int) $evaluator['indicator_count'] ?> indicators · <?= (int) $evaluator['remark_count'] ?> with remarks
            </summary>
            <div style="padding:0 10px 10px;">
              <?php foreach ($evaluator['dimensions'] as $dimensionResponses): $firstResponse = $dimensionResponses[0]; ?>
                <?php $dimensionNo = (int) $firstResponse['dimension_no']; ?>
                <div class="review-dimension" data-preview-dimension data-dimension-section data-dimension-no="<?= $dimensionNo ?>" style="margin-top:8px;">
                  <div class="review-dimension-title" style="border-bottom:0;">
                    Dimension <?= $dimensionNo ?>: <?= e($firstResponse['dimension_name']) ?>
                  </div>
                  <?php renderReviewDimensionTable($dimensionResponses, $previewRatingLabels); ?>
                </div>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endforeach; ?>
        <p data-preview-empty-state hidden style="font-size:12px;color:var(--n-500,#64748B);margin:0 0 10px;" aria-live="polite">
          No external evaluator indicators with remarks match this filter.
        </p>
      </section>

      <p style="font-size:11px;color:var(--n-400,#94A3B8);margin:12px 0 0;">
        Numeric dimension scores remain subject to the existing score-visibility rules.
      </p>
    </div>
  </div>

  <div class="review-footer">
    <div style="font-size:12px;color:var(--n-600,#475569);">
      <strong style="color:var(--n-800,#1E293B);">Assessment ready for review</strong><br>
      Review the submitted responses before completing the cycle.
    </div>
    <div class="review-footer-actions" style="display:flex;gap:8px;">
      <button type="button" class="btn btn-secondary btn-sm" onclick="openReturnCycleModal(<?= (int) $cycle['cycle_id'] ?>)">Return for Revision</button>
      <button type="button" class="btn btn-primary btn-sm" onclick="validateAndCompleteCycle(<?= (int) $cycle['cycle_id'] ?>)">Validate &amp; Complete Cycle</button>
    </div>
  </div>

  <div id="validateCycleModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;max-width:480px;width:90%;box-shadow:0 20px 50px rgba(0,0,0,.2);">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--n-200,#E2E8F0);">
        <span style="font-size:16px;font-weight:700;color:var(--n-900,#0F172A);">Validate &amp; Complete Cycle</span>
        <button type="button" onclick="closeValidateCycleModal()" aria-label="Close" style="background:none;border:none;cursor:pointer;color:var(--n-500,#64748B);">&times;</button>
      </div>
      <div style="padding:22px;">
        <div class="review-validation-summary">
          <div><span>Overall score</span><strong><?= $cycle['overall_score'] !== null ? number_format((float) $cycle['overall_score'], 1) : '—' ?></strong></div>
          <div><span>Rated indicators</span><strong><?= (int) $ratedCount ?></strong></div>
          <div><span>Low ratings (1–2)</span><strong><?= (int) $summaryCounts['low'] ?></strong></div>
        </div>
        <p style="font-size:14px;color:var(--n-700,#334155);line-height:1.5;margin-bottom:16px;">
          Confirming will validate this assessment and lock the cycle. It cannot be edited after validation.
        </p>
        <label for="validateRemarksInput" style="font-size:12.5px;font-weight:600;color:var(--n-600,#475569);display:block;margin-bottom:6px;">
          Optional validation remarks for the School Head
        </label>
        <textarea id="validateRemarksInput" rows="3" placeholder="Add any remarks…" style="width:100%;border:1px solid var(--n-200,#E2E8F0);border-radius:8px;padding:10px 12px;font-size:13.5px;font-family:inherit;resize:vertical;"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;padding:16px 22px;border-top:1px solid var(--n-200,#E2E8F0);">
        <button type="button" class="btn btn-secondary" onclick="closeValidateCycleModal()">Cancel</button>
        <button type="button" class="btn btn-success" onclick="confirmValidateCycle()">Confirm</button>
      </div>
    </div>
  </div>

  <div id="teacherReturnModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:1000;align-items:center;justify-content:center;">
    <form data-teacher-return-form data-teacher-id="" style="background:#fff;border-radius:14px;max-width:480px;width:90%;box-shadow:0 20px 50px rgba(0,0,0,.2);">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--n-200,#E2E8F0);">
        <span id="teacherReturnTitle" style="font-size:16px;font-weight:700;color:var(--n-900,#0F172A);">Return to this teacher</span>
        <button type="button" aria-label="Close" onclick="closeTeacherReturnModal()" style="background:none;border:none;cursor:pointer;color:var(--n-500,#64748B);font-size:20px;line-height:1;">&times;</button>
      </div>
      <div style="padding:22px;">
        <label for="teacherReturnRemarksInput" style="font-size:12.5px;font-weight:600;color:var(--n-600,#475569);display:block;margin-bottom:6px;">
          Required remarks for this teacher <span style="color:#DC2626;">*</span>
        </label>
        <textarea id="teacherReturnRemarksInput" name="remarks" required rows="3" maxlength="5000" placeholder="Explain what this teacher should review or revise." style="width:100%;box-sizing:border-box;border:1px solid var(--n-200,#E2E8F0);border-radius:8px;padding:10px 12px;font-size:13.5px;font-family:inherit;resize:vertical;"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;padding:16px 22px;border-top:1px solid var(--n-200,#E2E8F0);">
        <button type="button" class="btn btn-secondary" onclick="closeTeacherReturnModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Return to this teacher</button>
      </div>
    </form>
  </div>

  <div id="returnCycleModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;max-width:480px;width:90%;box-shadow:0 20px 50px rgba(0,0,0,.2);">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--n-200,#E2E8F0);">
        <span style="font-size:16px;font-weight:700;color:var(--n-900,#0F172A);">Return for Revision</span>
        <button type="button" aria-label="Close" onclick="closeReturnCycleModal()" style="background:none;border:none;cursor:pointer;color:var(--n-500,#64748B);font-size:20px;line-height:1;">&times;</button>
      </div>
      <div style="padding:22px;">
        <p style="font-size:14px;color:var(--n-700,#334155);line-height:1.5;margin-bottom:16px;">
          Explain what needs to be corrected before the assessment is resubmitted.
        </p>
        <label for="returnRemarksInput" style="font-size:12.5px;font-weight:600;color:var(--n-600,#475569);display:block;margin-bottom:6px;">
          Revision remarks <span style="color:#DC2626;">*</span>
        </label>
        <textarea id="returnRemarksInput" rows="3" required placeholder="Add the corrections needed…" style="width:100%;border:1px solid var(--n-200,#E2E8F0);border-radius:8px;padding:10px 12px;font-size:13.5px;font-family:inherit;resize:vertical;"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;padding:16px 22px;border-top:1px solid var(--n-200,#E2E8F0);">
        <button type="button" class="btn btn-secondary" onclick="closeReturnCycleModal()">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="confirmReturnCycle()">Return for Revision</button>
      </div>
    </div>
  </div>

  <script>
    (function initReviewTabsAndFilter() {
      const toggle = document.getElementById('previewRemarksOrEvidenceOnly');
      const needsAttentionToggle = document.getElementById('previewNeedsAttentionOnly');
      const tabs = Array.from(document.querySelectorAll('[data-preview-tab]'));
      const panes = Array.from(document.querySelectorAll('[data-preview-pane]'));
      const dimensionLinks = Array.from(document.querySelectorAll('[data-dimension-nav]'));
      if (!toggle || !needsAttentionToggle || !tabs.length || !panes.length) return;

      let activeTab = tabs.find(tab => tab.getAttribute('aria-selected') === 'true');
      if (!activeTab) return;
      let activeTeacher = null;

      function activePane() {
        return panes.find(item => item.dataset.previewPane === activeTab.dataset.previewTab);
      }

      function activeDimensionScope() {
        const pane = activePane();
        if (!pane) return null;
        if (pane.dataset.previewPane !== 'teachers') return pane;
        return activeTeacher && !activeTeacher.hidden && activeTeacher.dataset.expanded === 'true'
          ? activeTeacher
          : pane.querySelector('[data-preview-evaluator][data-expanded="true"]:not([hidden])')
          || pane.querySelector('[data-preview-evaluator]:not([hidden])');
      }

      function updateActiveDimension() {
        const scope = activeDimensionScope();
        if (!scope) return;
        const dimensions = Array.from(scope.querySelectorAll('[data-dimension-section]:not([hidden])'));
        const nav = document.querySelector('.review-mini-nav');
        if (!nav) return;
        const stickyBottom = nav.getBoundingClientRect().bottom + 12;
        const current = dimensions.filter(dimension => dimension.getBoundingClientRect().top <= stickyBottom).pop()
          || dimensions[0];
        dimensionLinks.forEach(link => {
          if (current && link.dataset.dimensionNav === current.dataset.dimensionNo) {
            link.setAttribute('aria-current', 'location');
          } else {
            link.removeAttribute('aria-current');
          }
        });
      }

      function applyFilters() {
        const pane = panes.find(item => item.dataset.previewPane === activeTab.dataset.previewTab);
        if (!pane) return;
        const remarksOrEvidenceOnly = toggle.checked;
        const needsAttentionOnly = needsAttentionToggle.checked;
        let visibleRows = 0;
        pane.querySelectorAll('[data-preview-match]').forEach(row => {
          row.hidden = (remarksOrEvidenceOnly && row.dataset.previewMatch !== '1')
            || (needsAttentionOnly && row.dataset.needsAttention !== '1');
          if (!row.hidden) visibleRows++;
        });
        pane.querySelectorAll('[data-preview-dimension]').forEach(dimension => {
          dimension.hidden = !dimension.querySelector('[data-preview-match]:not([hidden])');
        });
        pane.querySelectorAll('[data-preview-evaluator]').forEach(evaluator => {
          evaluator.hidden = !evaluator.querySelector('[data-preview-match]:not([hidden])');
          if (pane.dataset.previewPane !== 'teachers') return;
          const toggleButton = evaluator.querySelector('[data-teacher-toggle]');
          const content = evaluator.querySelector('.teacher-preview-content');
          if (needsAttentionOnly && !evaluator.hidden && evaluator.dataset.expanded !== 'true') {
            evaluator.dataset.expanded = 'true';
            evaluator.dataset.filterExpanded = 'true';
            toggleButton.setAttribute('aria-expanded', 'true');
            content.hidden = false;
            if (!activeTeacher) activeTeacher = evaluator;
          } else if (!needsAttentionOnly && evaluator.dataset.filterExpanded === 'true') {
            evaluator.dataset.expanded = 'false';
            delete evaluator.dataset.filterExpanded;
            toggleButton.setAttribute('aria-expanded', 'false');
            content.hidden = true;
            if (activeTeacher === evaluator) activeTeacher = null;
          }
        });
        const emptyState = pane.querySelector('[data-preview-empty-state]');
        if (emptyState) emptyState.hidden = (!remarksOrEvidenceOnly && !needsAttentionOnly) || visibleRows > 0;
        updateActiveDimension();
      }

      function selectTab(nextTab) {
        activeTab = nextTab;
        tabs.forEach(tab => {
          const selected = tab === activeTab;
          tab.setAttribute('aria-selected', selected ? 'true' : 'false');
          tab.tabIndex = selected ? 0 : -1;
        });
        panes.forEach(pane => {
          pane.hidden = pane.dataset.previewPane !== activeTab.dataset.previewTab;
        });
        applyFilters();
      }

      function toggleTeacherRow(button) {
        const evaluator = button.closest('[data-preview-evaluator]');
        const expanded = button.getAttribute('aria-expanded') !== 'true';
        const content = evaluator.querySelector('.teacher-preview-content');
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        evaluator.dataset.expanded = expanded ? 'true' : 'false';
        delete evaluator.dataset.filterExpanded;
        content.hidden = !expanded;
        activeTeacher = expanded ? evaluator : null;
        updateActiveDimension();
      }

      tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectTab(tab));
        tab.addEventListener('keydown', event => {
          if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
          event.preventDefault();
          const direction = event.key === 'ArrowRight' ? 1 : -1;
          const next = tabs[(index + direction + tabs.length) % tabs.length];
          next.focus();
          selectTab(next);
        });
      });
      toggle.addEventListener('change', applyFilters);
      needsAttentionToggle.addEventListener('change', applyFilters);
      document.querySelectorAll('[data-teacher-toggle]').forEach(button => {
        button.addEventListener('click', () => toggleTeacherRow(button));
      });
      document.querySelectorAll('[data-open-teacher-return]').forEach(button => {
        button.addEventListener('click', () => openTeacherReturnModal(button.dataset.teacherId, button.dataset.teacherName));
      });
      window.addEventListener('scroll', updateActiveDimension, { passive: true });
      window.addEventListener('resize', updateActiveDimension);
      selectTab(activeTab);

      document.querySelectorAll('[data-dimension-nav]').forEach(link => {
        link.addEventListener('click', event => {
          event.preventDefault();
          const pane = panes.find(item => item.dataset.previewPane === activeTab.dataset.previewTab);
          if (!pane) return;
          let scope = pane;
          if (pane.dataset.previewPane === 'teachers') {
            let expandedTeacher = activeTeacher && !activeTeacher.hidden
              ? activeTeacher
              : pane.querySelector('[data-preview-evaluator][data-expanded="true"]:not([hidden])');
            if (!expandedTeacher) {
              expandedTeacher = pane.querySelector('[data-preview-evaluator]:not([hidden])');
              if (expandedTeacher) {
                const expandButton = expandedTeacher.querySelector('[data-teacher-toggle]');
                if (expandButton) toggleTeacherRow(expandButton);
              }
            }
            if (!expandedTeacher) return;
            scope = expandedTeacher;
          }
          const target = Array.from(scope.querySelectorAll('[data-dimension-section]'))
            .find(section => section.dataset.dimensionNo === link.dataset.dimensionNav);
          if (!target) return;
          if (toggle.checked || needsAttentionToggle.checked) {
            toggle.checked = false;
            needsAttentionToggle.checked = false;
            applyFilters();
          }
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      });
      dimensionLinks.forEach(link => {
        link.addEventListener('click', () => {
          dimensionLinks.forEach(item => item.removeAttribute('aria-current'));
          link.setAttribute('aria-current', 'location');
        });
      });
    })();

    document.querySelectorAll('[data-teacher-return-form]').forEach(form => {
      form.addEventListener('submit', async event => {
        event.preventDefault();
        const remarksInput = form.elements.namedItem('remarks');
        const remarks = remarksInput.value.trim();
        if (!remarks) {
          remarksInput.setCustomValidity('Return remarks are required.');
          remarksInput.reportValidity();
          return;
        }
        remarksInput.setCustomValidity('');
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        try {
          const modal = document.getElementById('teacherReturnModal');
          const response = await apiPost('dashboard.php', {
            action: 'return_teacher_submission',
            cycle_id: <?= (int) $cycle['cycle_id'] ?>,
            teacher_id: modal.dataset.teacherId,
            remarks
          });
          toast(response.msg, response.ok ? 'ok' : 'err');
          if (response.ok) {
            setTimeout(() => location.reload(), 800);
          } else {
            submitButton.disabled = false;
          }
        } catch (error) {
          toast(error.message || 'Unable to return this teacher submission.', 'err');
          submitButton.disabled = false;
        }
      });
    });

    function openTeacherReturnModal(teacherId, teacherName) {
      const modal = document.getElementById('teacherReturnModal');
      modal.dataset.teacherId = teacherId;
      document.getElementById('teacherReturnTitle').textContent = `Return ${teacherName} for Revision`;
      document.getElementById('teacherReturnRemarksInput').value = '';
      modal.style.display = 'flex';
      document.getElementById('teacherReturnRemarksInput').focus();
    }
    function closeTeacherReturnModal() {
      document.getElementById('teacherReturnModal').style.display = 'none';
    }

    function validateAndCompleteCycle(cycleId) {
      const modal = document.getElementById('validateCycleModal');
      modal.dataset.cycleId = cycleId;
      document.getElementById('validateRemarksInput').value = '';
      modal.style.display = 'flex';
    }
    function openReturnCycleModal(cycleId) {
      const modal = document.getElementById('returnCycleModal');
      modal.dataset.cycleId = cycleId;
      document.getElementById('returnRemarksInput').value = '';
      modal.style.display = 'flex';
      document.getElementById('returnRemarksInput').focus();
    }
    function closeReturnCycleModal() {
      document.getElementById('returnCycleModal').style.display = 'none';
    }
    function confirmReturnCycle() {
      const modal = document.getElementById('returnCycleModal');
      const remarksInput = document.getElementById('returnRemarksInput');
      const remarks = remarksInput.value.trim();
      if (!remarks) {
        remarksInput.focus();
        toast('Return remarks are required.', 'warning');
        return;
      }
      closeReturnCycleModal();
      returnCycleForRevision(modal.dataset.cycleId, remarks);
    }
    async function returnCycleForRevision(cycleId, remarks) {
      const response = await apiPost('dashboard.php', {
        action: 'return_cycle',
        cycle_id: cycleId,
        to_stage: 'in_progress',
        remarks
      });
      toast(response.msg, response.ok ? 'ok' : 'err');
      if (response.ok) setTimeout(() => location.reload(), 800);
    }
    function closeValidateCycleModal() {
      document.getElementById('validateCycleModal').style.display = 'none';
    }
    async function confirmValidateCycle() {
      const modal = document.getElementById('validateCycleModal');
      const cycleId = modal.dataset.cycleId;
      const remarks = document.getElementById('validateRemarksInput').value.trim();
      closeValidateCycleModal();
      const response = await apiPost('../school_head/workflow.php', {
        action: 'validate_cycle',
        cycle_id: cycleId,
        remarks
      });
      toast(response.msg, response.ok ? 'ok' : 'err');
      if (response.ok) setTimeout(() => location.reload(), 800);
    }
  </script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
