<?php
$annexPages = [[1], [2], [3, 4], [4, 5], [6]];
$annexPageNumbers = ['i', 'ii', 'iii', 'iv', 'v'];
$annexIndicatorNumber = 0;
$annexRowClasses = [
    1 => 'tall', 2 => 'x-tall', 3 => 'short', 4 => 'medium',
    5 => 'tall', 6 => 'short', 7 => 'short', 8 => 'medium',
    9 => 'short', 10 => 'short', 11 => 'short', 12 => 'short',
    13 => 'tall', 14 => 'short', 15 => 'short', 16 => 'short',
    17 => 'medium', 18 => 'medium', 19 => 'short', 20 => 'medium',
    21 => 'medium', 22 => 'medium', 23 => 'medium', 24 => 'medium',
    25 => 'medium', 26 => 'medium', 27 => 'medium', 28 => 'medium',
    29 => 'x-tall', 30 => 'tall', 31 => 'tall', 32 => 'tall',
    33 => 'medium', 34 => 'tall', 35 => 'short', 36 => 'short',
    37 => 'medium', 38 => 'short', 39 => 'medium', 40 => 'short',
    41 => 'tall', 42 => 'short',
];
?>
<div class="annex-a-report">
<?php foreach ($annexPages as $pageIndex => $dimensionNumbers): ?>
  <?php if ($pageIndex > 0): ?><pagebreak /><?php endif; ?>
  <section class="annex-page<?= $pageIndex === 0 ? ' annex-page-one' : '' ?>">
    <h1 class="annex-page-title">Annex A: School-Based Management (SBM) Self-Assessment Checklist</h1>
    <?php if ($pageIndex === 0): ?>
      <p class="annex-intro">
        The <em>SBM Self-Assessment Checklist</em> provides a comprehensive understanding of the status of
        continuous improvement in the various areas of school operation. The school assesses the six (6)
        SBM Dimensions and determines the degree of manifestation for each SBM Indicator. These indicators
        are listed as observable school practices and attainable learning outcomes. The extent by which the
        indicators are manifested is described as follows: <em>not yet manifested, rarely manifested,
        frequently manifested,</em> and <em>always manifested.</em>
      </p>
    <?php endif; ?>

    <?php foreach ($dimensionNumbers as $dimNo):
        $inds = $annexGrouped[$dimNo] ?? [];
        if (!$inds) continue;
        $isContinuation = $pageIndex === 3 && $dimNo === 4;
        $dimensionRows = $isContinuation ? array_slice($inds, 4) : $inds;
        if (!$isContinuation && $dimNo === 4 && $pageIndex === 2) {
            $dimensionRows = array_slice($inds, 0, 4);
        }
        if (!$dimensionRows) continue;
        $dimensionName = $inds[0]['dimension_name'];
    ?>
      <div class="annex-section">
        <?php if (!$isContinuation): ?>
          <div class="annex-section-description">
            <strong><?= htmlspecialchars($dimensionName) ?></strong><br />
            <?= htmlspecialchars($annexDimensionDescriptions[$dimNo] ?? '') ?>
          </div>
        <?php endif; ?>
        <table class="annex-checklist-table<?= $isContinuation ? ' annex-continuation' : '' ?>">
          <colgroup>
            <col width="34%" />
            <col width="10%" />
            <col width="10%" />
            <col width="10%" />
            <col width="10%" />
            <col width="26%" />
          </colgroup>
          <?php if (!$isContinuation): ?>
            <thead>
              <tr>
                <th rowspan="2">SBM Indicator</th>
                <th colspan="4" class="annex-manifestation-title">
                  Degree of Manifestation<br />
                  <span style="font-size:8pt;font-weight:normal;">(Please tick the box that best describes your school)</span>
                </th>
                <th rowspan="2">Remarks</th>
              </tr>
              <tr>
                <th class="annex-scale-heading">Not yet<br />manifested</th>
                <th class="annex-scale-heading">Rarely<br />manifested</th>
                <th class="annex-scale-heading">Frequently<br />manifested</th>
                <th class="annex-scale-heading">Always<br />manifested</th>
              </tr>
            </thead>
          <?php endif; ?>
          <tbody>
            <?php foreach ($dimensionRows as $ind):
                $annexIndicatorNumber++;
                $selectedRating = (int)($ind['rating'] ?? 0);
                $rowClass = 'annex-row-' . ($annexRowClasses[$annexIndicatorNumber] ?? 'medium');
            ?>
              <tr class="<?= $rowClass ?>">
                <td class="indicator" width="34%">
                  <?= $annexIndicatorNumber ?>. &nbsp; <?= htmlspecialchars($ind['indicator_text']) ?>
                </td>
                <?php for ($ratingValue = 1; $ratingValue <= 4; $ratingValue++): ?>
                  <td class="choice" width="10%">
                    <svg xmlns="http://www.w3.org/2000/svg" width="3.2mm" height="3.2mm" viewBox="0 0 12 12" style="display:block;margin-left:auto">
                      <rect x="0.5" y="0.5" width="11" height="11" fill="none" stroke="#000" stroke-width="1" />
                      <?php if ($selectedRating === $ratingValue): ?>
                        <path d="M2.5 6L5 8.5L9.5 3.5" fill="none" stroke="#000" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                      <?php endif; ?>
                    </svg>
                  </td>
                <?php endfor; ?>
                <td class="remarks" width="26%"><?= nl2br(htmlspecialchars((string)($ind['evidence_text'] ?? ''))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
    <div class="annex-page-number"><?= $annexPageNumbers[$pageIndex] ?></div>
  </section>
<?php endforeach; ?>
</div>
