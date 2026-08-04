<?php
ob_start();
// school_head/ai_suggestion_planning.php — AI Suggestion Planning
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head', 'sbm_coordinator');
$db = getDB();

// ── AI ASSISTANT AJAX HANDLER (reused from school_head/dashboard.php) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'get_ai_suggestions') {
  header('Content-Type: application/json');
  require_once __DIR__ . '/../includes/ml_service.php';

  $schoolIdAjax = (int) ($_SESSION['school_id'] ?? 0);
  $syIdAjax = (int) ($_POST['sy_id'] ?? 0);

  $sQ = $db->prepare("SELECT school_name FROM schools WHERE school_id = ?");
  $sQ->execute([$schoolIdAjax]);
  $schoolName = $sQ->fetchColumn() ?: 'School';

  $syQ = $db->prepare("SELECT label FROM school_years WHERE sy_id = ?");
  $syQ->execute([$syIdAjax]);
  $syLabelAjax = $syQ->fetchColumn() ?: 'Unknown';

  $dimQ = $db->prepare("
        SELECT d.dimension_no, d.dimension_name, ROUND(AVG(ds.percentage), 1) as avg_pct
        FROM sbm_dimensions d
        LEFT JOIN sbm_dimension_scores ds ON d.dimension_id = ds.dimension_id
        LEFT JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id AND c.sy_id = ? AND c.school_id = ?
        GROUP BY d.dimension_id ORDER BY d.dimension_no
    ");
  $dimQ->execute([$syIdAjax, $schoolIdAjax]);
  $dimScoresAjax = [];
  foreach ($dimQ->fetchAll() as $row) {
    $dimScoresAjax[] = [
      'dimension_name' => $row['dimension_name'],
      'score' => (float) $row['avg_pct'],
      'maturity' => sbmMaturityLevel(floatval($row['avg_pct']))['label']
    ];
  }

  $weakQ = $db->prepare("
        SELECT i.indicator_code, i.indicator_text, ROUND(AVG(all_r.rating), 2) as rating
        FROM (
            SELECT cycle_id, indicator_id, rating FROM sbm_responses
            UNION ALL
            SELECT cycle_id, indicator_id, rating FROM teacher_responses
        ) AS all_r
        JOIN sbm_indicators i ON all_r.indicator_id = i.indicator_id
        JOIN sbm_cycles c ON all_r.cycle_id = c.cycle_id
        WHERE c.sy_id = ? AND c.school_id = ?
        GROUP BY i.indicator_id
        HAVING rating < 2.5
        ORDER BY rating ASC
    ");
  $weakQ->execute([$syIdAjax, $schoolIdAjax]);
  $byRatingAjax = ['1' => [], '2' => []];
  foreach ($weakQ->fetchAll() as $row) {
    $r = (int) floor($row['rating']);
    if ($r < 1) $r = 1;
    if ($r > 2) $r = 2;
    $byRatingAjax[strval($r)][] = [
      'code' => $row['indicator_code'],
      'text' => $row['indicator_text'],
      'rating' => (float) $row['rating']
    ];
  }

  $histQ = $db->prepare("
        SELECT overall_score FROM sbm_cycles 
        WHERE school_id = ? AND status IN ('validated','finalized','completed') AND sy_id != ? 
        ORDER BY created_at DESC LIMIT 3
    ");
  $histQ->execute([$schoolIdAjax, $syIdAjax]);
  $historyAjax = $histQ->fetchAll();

  $scoreQ = $db->prepare("
        SELECT overall_score, maturity_level FROM sbm_cycles 
        WHERE school_id = ? AND sy_id = ? AND status IN ('validated','finalized','completed')
        ORDER BY created_at DESC LIMIT 1
    ");
  $scoreQ->execute([$schoolIdAjax, $syIdAjax]);
  $scoreDataAjax = $scoreQ->fetch();
  $overallScoreAjax = $scoreDataAjax ? (float) $scoreDataAjax['overall_score'] : 0;
  $overallMaturityAjax = $scoreDataAjax ? $scoreDataAjax['maturity_level'] : 'N/A';

  $payload = [
    'school_name' => $schoolName,
    'sy_label' => $syLabelAjax,
    'analysis' => [
      'gap_analysis' => [
        'average_score' => $overallScoreAjax,
        'overall_maturity' => $overallMaturityAjax,
        'weakest_dimensions' => array_slice($dimScoresAjax, 0, 3)
      ],
      'by_rating' => $byRatingAjax,
      'history' => $historyAjax,
      'comment_summary' => ['top_topics' => [], 'has_urgent' => false]
    ]
  ];

  $response = ml_post('/api/recommend', $payload);
  echo json_encode($response ?: [
    'recommendations' => "I'm sorry, I'm having trouble connecting to my central intelligence. Please check if the ML service is running.",
    'error' => 'Service Unavailable'
  ]);
  exit;
}

$syId     = (int)($_GET['sy'] ?? $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn());
$schoolId = SCHOOL_ID;

$cycle = null;
if ($schoolId) {
    $stmt = $db->prepare("SELECT c.*, sy.label sy_label FROM sbm_cycles c JOIN school_years sy ON c.sy_id=sy.sy_id WHERE c.school_id=? AND c.sy_id=? LIMIT 1");
    $stmt->execute([$schoolId, $syId]);
    $cycle = $stmt->fetch();
}

$overallScore  = $cycle['overall_score'] ?? null;
$maturityLevel = $cycle['maturity_level'] ?? null;
$mat           = $overallScore !== null ? sbmMaturityLevel(floatval($overallScore)) : ['label' => '—', 'color' => 'var(--n-400)'];
$generatedAt   = $cycle['updated_at'] ?? $cycle['created_at'] ?? null;
$syLabel       = $cycle['sy_label'] ?? '—';

$pageTitle = 'AI Suggestion Planning'; $activePage = 'ai_suggestion_planning.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head"></div>

<div class="card" style="margin-bottom:18px;">
  <div class="card-body" style="padding:20px 24px;">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;">

      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:6px;">Overall SBM Score</div>
        <div style="font-size:26px;font-weight:700;color:<?= $mat['color'] ?>;line-height:1.2;">
          <?= $overallScore !== null ? number_format($overallScore, 1) . '%' : '—' ?>
        </div>
        <div style="font-size:13px;font-weight:600;color:<?= $mat['color'] ?>;margin-top:2px;">
          <?= e($mat['label']) ?>
        </div>
      </div>

      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:6px;">Maturity Level</div>
        <div style="font-size:16px;font-weight:700;color:var(--n-900);line-height:1.2;">
          <?= e($maturityLevel ?? $mat['label']) ?>
        </div>
      </div>

      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:6px;">Generated On</div>
        <div style="font-size:14px;font-weight:600;color:var(--n-900);line-height:1.4;" id="generatedOnVal">
          —
        </div>
      </div>

      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:6px;">School Year</div>
        <div style="font-size:14px;font-weight:700;color:var(--n-900);line-height:1.2;">
          SY <?= e($syLabel) ?>
        </div>
      </div>

    </div>
  </div>
</div>

<div class="card" style="margin-bottom:18px;">
  <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;">
    <span class="card-title">AI Suggestions</span>
    <button type="button" class="btn btn-primary" id="genAiSuggestBtn" onclick="loadAISuggestionsPlan()">
      Generate AI Suggestions
    </button>
  </div>
  <div class="card-body ai-suggest-content" id="aiSuggestBody" style="padding:24px 28px;">
    <p style="font-size:13px;color:var(--n-500);">Click "Generate AI Suggestions" to analyze this cycle's data and get recommendations.</p>
  </div>
</div>

<style>
.ai-suggest-content {
  font-family: var(--font-serif, Georgia, 'Times New Roman', serif);
  font-size: 14.5px;
  line-height: 1.85;
  color: var(--n-800);
}
.ai-suggest-content p {
  margin: 0 0 14px 0;
}
.ai-suggest-content p:last-child {
  margin-bottom: 0;
}
.ai-suggest-content strong {
  color: var(--n-900);
  font-weight: 700;
}
.ai-suggest-content ul {
  margin: 0 0 16px 0;
  padding-left: 22px;
}
.ai-suggest-content li {
  margin-bottom: 8px;
  padding-left: 4px;
}
.ai-suggest-content li:last-child {
  margin-bottom: 0;
}
.ai-suggest-content hr {
  border: none;
  border-top: 1px solid var(--n-200);
  margin: 20px 0;
}
</style>

<script>
/** Reused from school_head/dashboard.php */
function parseAILogicToHtml(text) {
  let html = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  const lines = html.split('\n');
  let finalHtml = '';
  let inList = false;

  lines.forEach(line => {
    const trimmed = line.trim();
    if (trimmed.startsWith('- ')) {
      if (!inList) { finalHtml += '<ul>'; inList = true; }
      finalHtml += '<li>' + trimmed.substring(2) + '</li>';
    } else if (trimmed === '---') {
      if (inList) { finalHtml += '</ul>'; inList = false; }
      finalHtml += '<hr>';
    } else {
      if (inList) { finalHtml += '</ul>'; inList = false; }
      if (trimmed) finalHtml += '<p>' + trimmed + '</p>';
    }
  });

  if (inList) finalHtml += '</ul>';
  return finalHtml;
}

async function loadAISuggestionsPlan() {
  const body = document.getElementById('aiSuggestBody');
  const btn = document.getElementById('genAiSuggestBtn');

  btn.disabled = true;
  btn.textContent = 'Generating...';
  body.innerHTML = '<p style="font-size:13px;color:var(--n-500);">Analyzing your SBM data...</p>';

  const formData = new FormData();
  formData.append('action', 'get_ai_suggestions');
  formData.append('sy_id', '<?= $syId ?>');

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: formData });
    const data = await res.json();

    if (data.error) {
      body.innerHTML = '<p style="font-size:13px;color:var(--red);">Couldn\'t reach the analysis service. Please check if the ML service is running.</p>';
    } else {
      const recs = data.recommendations || '';
      body.innerHTML = recs
        ? parseAILogicToHtml(recs)
        : '<p style="font-size:13px;color:var(--n-500);">Your performance is currently optimal with no critical gaps detected.</p>';

      const now = new Date();
      const dateStr = now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
      const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
      document.getElementById('generatedOnVal').innerHTML = dateStr + '<br>' + timeStr;
    }
  } catch (err) {
    console.error(err);
    body.innerHTML = '<p style="font-size:13px;color:var(--red);">Network error. Please try again.</p>';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Generate AI Suggestions';
  }
}
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>