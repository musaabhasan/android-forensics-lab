<?php

declare(strict_types=1);

use AndroidForensicsLab\Repository\LabRepository;
use AndroidForensicsLab\Security\Csrf;
use AndroidForensicsLab\Security\SecurityHeaders;
use AndroidForensicsLab\Service\ForensicsLabService;
use AndroidForensicsLab\Support\Database;
use AndroidForensicsLab\Support\Json;
use AndroidForensicsLab\Support\View;

$bootstrap = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';
$repository = new LabRepository($bootstrap['catalog'], Database::connection());
$service = new ForensicsLabService($repository);
SecurityHeaders::apply();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($path === '/health') {
    Json::respond([
        'status' => 'ok',
        'service' => 'android-forensics-lab',
        'sources' => count($repository->researchSources()),
    ]);
}

if ($path === '/api/summary') {
    Json::respond($service->summary());
}

if ($path === '/api/expert-audit') {
    Json::respond($service->expertAudit(requestPayload()));
}

if ($path === '/api/assess') {
    requirePost($method);
    Json::respond($service->assessCase(requestPayload()));
}

if ($path === '/api/acquisition-readiness') {
    requirePost($method);
    Json::respond($service->acquisitionReadiness(requestPayload()));
}

if ($path === '/api/artifact-triage') {
    requirePost($method);
    Json::respond($service->artifactTriage(requestPayload()));
}

if ($path === '/api/tool-validation') {
    requirePost($method);
    Json::respond($service->toolValidation(requestPayload()));
}

if ($path === '/api/report-readiness') {
    requirePost($method);
    Json::respond($service->reportReadiness(requestPayload()));
}

if ($path === '/api/method-compare') {
    requirePost($method);
    Json::respond($service->methodCompare(requestPayload()));
}

if ($path === '/api/wiping-evaluation') {
    requirePost($method);
    Json::respond($service->wipingEvaluation(requestPayload()));
}

if ($path === '/api/hash-ledger') {
    requirePost($method);
    Json::respond($service->hashLedger(requestPayload()));
}

if ($path === '/api/command-workbench') {
    requirePost($method);
    Json::respond($service->commandWorkbench(requestPayload()));
}

if ($path === '/api/timeline-fusion') {
    requirePost($method);
    Json::respond($service->timelineFusion(requestPayload()));
}

if ($path === '/') {
    echo View::page('Android Digital Forensics Lab', renderDashboard($repository, $service));
    exit;
}

if ($path === '/audit') {
    echo View::page('Expert Audit | Android Digital Forensics Lab', renderExpertAudit($service));
    exit;
}

if ($path === '/casework') {
    echo View::page('Casework | Android Digital Forensics Lab', renderCasework($repository, $service, $method));
    exit;
}

if ($path === '/acquisition') {
    echo View::page('Acquisition Readiness | Android Digital Forensics Lab', renderAcquisition($service, $method));
    exit;
}

if ($path === '/artifacts') {
    echo View::page('Artifact Triage | Android Digital Forensics Lab', renderArtifacts($service, $method));
    exit;
}

if ($path === '/workbench') {
    echo View::page('Workbench | Android Digital Forensics Lab', renderWorkbench($service, $method));
    exit;
}

if ($path === '/validation') {
    echo View::page('Tool Validation | Android Digital Forensics Lab', renderValidation($service, $method));
    exit;
}

if ($path === '/report-readiness') {
    echo View::page('Report Readiness | Android Digital Forensics Lab', renderReportReadiness($service, $method));
    exit;
}

if ($path === '/methods') {
    echo View::page('Methods | Android Digital Forensics Lab', renderMethods($repository, $service, $method));
    exit;
}

if ($path === '/wiping') {
    echo View::page('Wiping | Android Digital Forensics Lab', renderWiping($service, $method));
    exit;
}

if ($path === '/timeline') {
    echo View::page('Timeline | Android Digital Forensics Lab', renderTimeline($service, $method));
    exit;
}

if ($path === '/ledger') {
    echo View::page('Ledger | Android Digital Forensics Lab', renderLedger($service, $method));
    exit;
}

if ($path === '/research') {
    echo View::page('Research | Android Digital Forensics Lab', renderResearch($repository));
    exit;
}

http_response_code(404);
echo View::page('Not Found | Android Digital Forensics Lab', '<section class="panel hero"><h1>Page not found</h1><p>The requested page is not available.</p></section>');

function requirePost(string $method): void
{
    if ($method !== 'POST') {
        Json::respond(['error' => 'POST required'], 405);
    }
}

/**
 * @return array<string, mixed>
 */
function requestPayload(): array
{
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true);
    if (!is_array($payload) || $payload === []) {
        $payload = $_POST;
    }

    return is_array($payload) ? $payload : [];
}

function renderDashboard(LabRepository $repository, ForensicsLabService $service): string
{
    $summary = $service->summary();
    $metrics = $summary['metrics'];
    $stages = '';
    foreach ($repository->workflowStages() as $index => $stage) {
        $number = $index + 1;
        $stages .= '<article class="stage-card"><span>Stage ' . $number . '</span><h3>' . View::e($stage['name']) . '</h3><p>' . View::e($stage['purpose']) . '</p></article>';
    }

    $modules = '';
    foreach ($repository->advancedModules() as $module) {
        $modules .= '<article class="module-card"><span>' . View::e($module['mode']) . '</span><h3>' . View::e($module['name']) . '</h3><p>' . View::e($module['description']) . '</p></article>';
    }

    $tools = '';
    foreach (array_slice($repository->toolProfiles(), 0, 6) as $tool) {
        $tools .= '<article class="card"><span>' . View::e($tool['category']) . '</span><h3>' . View::e($tool['name']) . '</h3><p>' . View::e($tool['use_case']) . '</p></article>';
    }

    $preview = $service->methodCompare([
        'deleted_data_needed' => true,
        'cloud_relevant' => true,
        'malware_suspected' => true,
        'memory_needed' => true,
        'wiping_suspected' => true,
        'court_report' => true,
    ]);
    $methodStack = '';
    foreach (array_slice($preview['ranked_methods'], 0, 4) as $item) {
        $methodStack .= '<div><strong>' . View::e($item['name']) . '</strong><span>' . View::e($item['role']) . ' - ' . View::e($item['score']) . '/100</span></div>';
    }

    $topMethod = (string) $preview['ranked_methods'][0]['name'];
    $topMethodScore = (string) $preview['ranked_methods'][0]['score'];
    $coverageAnchor = $preview['coverage_by_feature'][count($preview['coverage_by_feature']) - 1];
    $anchorFeature = (string) $coverageAnchor['feature'];
    $anchorMethod = (string) $coverageAnchor['best_method'];
    $featureMap = '';
    foreach (array_slice($preview['coverage_by_feature'], -5) as $feature) {
        $featureMap .= '<div class="constellation-node"><span>' . View::e($feature['best_score']) . '</span><strong>' . View::e($feature['feature']) . '</strong><small>' . View::e($feature['best_method']) . '</small></div>';
    }

    $recent = $summary['recent_assessments'];
    $recentHtml = '<p class="muted">Case assessments are stored when a database connection is configured.</p>';
    if ($recent !== []) {
        $recentHtml = '';
        foreach ($recent as $item) {
            $recentHtml .= '<div><strong>' . View::e($item['case_name']) . '</strong><span>' . View::e($item['score']) . '/100 - ' . View::e($item['risk_tier']) . '</span></div>';
        }
    }

    return <<<HTML
<section class="hero">
  <div>
    <p class="eyebrow">Android Digital Forensics Command Lab</p>
    <h1>A smarter forensic workbench for evidence decisions that need speed, depth, and defensibility.</h1>
    <p class="lead">Plan acquisitions, model stealth and wiping scenarios, fuse timelines, rank methods, preserve integrity roots, and turn Android evidence into a professional case narrative.</p>
    <div class="hero-actions">
      <a class="button-link" href="/audit">Open Expert Audit</a>
      <a class="button-link" href="/workbench">Open Workbench</a>
      <a class="secondary-link" href="/acquisition">Plan Acquisition</a>
      <a class="secondary-link" href="/validation">Validate Tools</a>
    </div>
  </div>
  <aside class="hero-aside">
    <span>Scenario preview</span>
    <strong>{$metrics['advanced_modules']} engines</strong>
    <p>Expert audit, acquisition feasibility, artifact triage, command workbench, timeline fusion, tool validation, report readiness, and integrity ledger.</p>
  </aside>
</section>

<section class="command-strip">
  <article><span>Lead method</span><strong>{$topMethod}</strong><p>{$topMethodScore}/100 for a high-risk stealth and wiping case.</p></article>
  <article><span>Coverage anchor</span><strong>{$anchorFeature}</strong><p>{$anchorMethod} provides the strongest signal.</p></article>
  <article><span>Decision model</span><strong>Expert-gated</strong><p>Plan feasibility, validate parser disagreement, and hold release until evidence is defensible.</p></article>
</section>

<section class="metric-grid">
  <article><span>Methods</span><strong>{$metrics['acquisition_methods']}</strong><p>Acquisition and analysis paths ranked by case context.</p></article>
  <article><span>Evidence Features</span><strong>{$metrics['evidence_features']}</strong><p>Recovered data families mapped to coverage and gaps.</p></article>
  <article><span>Controls</span><strong>{$metrics['forensic_controls']}</strong><p>Governance, integrity, anti-forensics, and reporting controls.</p></article>
  <article><span>Advanced Engines</span><strong>{$metrics['advanced_modules']}</strong><p>Scenario intelligence, timeline fusion, and evidence assurance.</p></article>
</section>

<section class="section-head"><h2>Command Intelligence</h2><a href="/workbench">Build a scenario</a></section>
<section class="workbench-preview">
  <div class="panel stack-panel">
    <h2>Method Stack</h2>
    <div class="rank-list">{$methodStack}</div>
  </div>
  <div class="panel constellation-panel">
    <h2>Evidence Constellation</h2>
    <div class="constellation-grid">{$featureMap}</div>
  </div>
</section>

<section class="section-head"><h2>Advanced Modules</h2><a href="/timeline">Timeline fusion</a></section>
<div class="module-grid">{$modules}</div>

<section class="section-head"><h2>Lab Workflow</h2><a href="/casework">Assess readiness</a></section>
<div class="stage-grid">{$stages}</div>

<section class="section-head"><h2>Tool Coverage Surface</h2><a href="/research">Research alignment</a></section>
<div class="card-grid">{$tools}</div>

<section class="panel recent-panel">
  <h2>Recent Case Assessments</h2>
  <div class="recent-list">{$recentHtml}</div>
</section>
HTML;
}

function renderExpertAudit(ForensicsLabService $service): string
{
    $result = $service->expertAudit();
    $score = View::e($result['audit_score']);
    $tier = View::e($result['audit_tier']);
    $covered = View::e($result['covered_count']);
    $painPointCount = View::e($result['pain_point_count']);

    $rows = '';
    foreach ($result['field_pain_points'] as $item) {
        $rows .= '<tr><td>' . View::e($item['area']) . '</td><td>' . View::e($item['severity']) . '</td><td>' . View::e($item['pain_point']) . '</td><td>' . View::e($item['platform_response']) . '</td><td>' . View::e($item['status']) . '</td></tr>';
    }

    $cards = '';
    foreach (array_slice($result['field_pain_points'], 0, 6) as $item) {
        $cards .= '<article class="card expert-card"><span>' . View::e($item['severity']) . '</span><h3>' . View::e($item['area']) . '</h3><p>' . View::e($item['field_impact']) . '</p><small>' . View::e($item['expert_upgrade']) . '</small></article>';
    }

    $recommendations = '';
    foreach ($result['expert_recommendations'] as $recommendation) {
        $recommendations .= '<li>' . View::e($recommendation) . '</li>';
    }

    return <<<HTML
<section class="panel paper-detail">
  <p class="eyebrow">Expert Audit Console</p>
  <h1>Field pain points mapped to operational Android forensic capabilities.</h1>
  <p class="lead">This audit translates examiner pain points into specific lab engines for device-state planning, parser coverage, anti-forensics review, validation gates, and report defensibility.</p>
</section>

<section class="result-panel panel">
  <div class="result-score">
    <span>Audit Score</span>
    <strong>{$score}</strong>
    <p>{$tier}</p>
  </div>
  <div>
    <h2>{$covered} of {$painPointCount} Areas Covered</h2>
    <p>The lab now addresses the most common Android evidence risks across acquisition feasibility, artifact parsing, cloud/E2EE context, volatile evidence, tool disagreement, and release readiness.</p>
  </div>
  <div>
    <h2>Expert Recommendations</h2>
    <ol class="recommendation-list">{$recommendations}</ol>
  </div>
</section>

<section class="section-head"><h2>Priority Field Risks</h2><span>Expert review highlights</span></section>
<div class="card-grid">{$cards}</div>

<section class="panel">
  <h2>Full Audit Matrix</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Area</th><th>Severity</th><th>Pain point</th><th>Platform response</th><th>Status</th></tr></thead>
      <tbody>{$rows}</tbody>
    </table>
  </div>
</section>
HTML;
}

function renderAcquisition(ForensicsLabService $service, string $method): string
{
    $notice = '';
    if ($method === 'POST' && !Csrf::validate($_POST['_csrf'] ?? null)) {
        $notice = '<div class="notice">The acquisition review could not be submitted because the session token expired.</div>';
        $result = $service->acquisitionReadiness([]);
    } else {
        $result = $service->acquisitionReadiness($method === 'POST' ? $_POST : []);
    }

    $paths = '';
    foreach ($result['ranked_paths'] as $path) {
        $paths .= '<article class="card method-card"><span>' . View::e($path['feasibility']) . ' - ' . View::e($path['score']) . '/100</span><h3>' . View::e($path['name']) . '</h3><p>' . View::e($path['rationale']) . '</p></article>';
    }

    $blockers = '';
    foreach ($result['blockers'] as $item) {
        $blockers .= '<li>' . View::e($item) . '</li>';
    }

    $plan = '';
    foreach ($result['first_hour_plan'] as $item) {
        $plan .= '<li>' . View::e($item) . '</li>';
    }

    $notes = '';
    foreach ($result['preservation_notes'] as $item) {
        $notes .= '<li>' . View::e($item) . '</li>';
    }

    $cautions = '';
    foreach ($result['expert_cautions'] as $item) {
        $cautions .= '<li>' . View::e($item) . '</li>';
    }

    $state = $result['state'];
    $caseName = View::e($state['case_name']);
    $androidVersion = View::e($state['android_version']);
    $lockState = View::e(str_replace('-', ' ', (string) $state['lock_state']));
    $topPath = View::e($result['ranked_paths'][0]['name'] ?? 'Acquisition plan');
    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel">
  <p class="eyebrow">Acquisition Feasibility Planner</p>
  <h1>Rank acquisition paths from Android version, encryption state, lock state, cloud authority, and examiner constraints.</h1>
  {$notice}
  <form method="post" action="/acquisition">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <div class="form-grid">
      <label>Case name<input name="case_name" value="Locked Android anti-forensics review"></label>
      <label>Android major version<input name="android_version" value="14"></label>
      <label>Lock state<select name="lock_state">
        <option value="locked-after-first-unlock" selected>Locked after first unlock</option>
        <option value="locked-before-first-unlock">Locked before first unlock</option>
        <option value="unlocked">Unlocked</option>
      </select></label>
    </div>
    <div class="toggle-row">
      <label><input type="checkbox" name="usb_debugging" value="1"> USB debugging available</label>
      <label><input type="checkbox" name="bootloader_unlocked" value="1"> Bootloader unlocked</label>
      <label><input type="checkbox" name="root_possible" value="1"> Root feasible</label>
      <label><input type="checkbox" name="cloud_authority" value="1" checked> Cloud authority available</label>
      <label><input type="checkbox" name="work_profile" value="1"> Work profile present</label>
      <label><input type="checkbox" name="fbe_enabled" value="1" checked> File-based encryption</label>
      <label><input type="checkbox" name="external_storage" value="1"> External storage present</label>
      <label><input type="checkbox" name="apk_available" value="1" checked> APK available</label>
      <label><input type="checkbox" name="malware_suspected" value="1" checked> Malware suspected</label>
      <label><input type="checkbox" name="wiping_suspected" value="1" checked> Wiping suspected</label>
    </div>
    <button type="submit">Rank Acquisition Paths</button>
  </form>
</section>

<section class="command-strip">
  <article><span>Case</span><strong>{$caseName}</strong><p>Android {$androidVersion}, {$lockState}.</p></article>
  <article><span>Lead Path</span><strong>{$topPath}</strong><p>Selected from device-state feasibility and expected evidence yield.</p></article>
  <article><span>Decision Style</span><strong>State-aware</strong><p>Feasibility is separated from evidence value and report defensibility.</p></article>
</section>

<section class="section-head"><h2>Ranked Acquisition Paths</h2><span>Feasibility and rationale</span></section>
<div class="card-grid">{$paths}</div>

<section class="result-panel panel">
  <div>
    <h2>Critical Blockers</h2>
    <ol class="recommendation-list">{$blockers}</ol>
  </div>
  <div>
    <h2>First-Hour Plan</h2>
    <ol class="recommendation-list">{$plan}</ol>
  </div>
  <div>
    <h2>Preservation Notes</h2>
    <ol class="recommendation-list">{$notes}</ol>
  </div>
</section>

<section class="panel">
  <h2>Expert Cautions</h2>
  <ol class="recommendation-list">{$cautions}</ol>
</section>
HTML;
}

function renderArtifacts(ForensicsLabService $service, string $method): string
{
    $notice = '';
    if ($method === 'POST' && !Csrf::validate($_POST['_csrf'] ?? null)) {
        $notice = '<div class="notice">The artifact triage could not be submitted because the session token expired.</div>';
        $result = $service->artifactTriage([]);
    } else {
        $result = $service->artifactTriage($method === 'POST' ? $_POST : []);
    }

    $cards = '';
    foreach ($result['top_artifacts'] as $artifact) {
        $cards .= '<article class="card artifact-card"><span>' . View::e($artifact['category']) . ' - ' . View::e($artifact['priority']) . '/100</span><h3>' . View::e($artifact['name']) . '</h3><p>' . View::e($artifact['why_it_matters']) . '</p><small>' . View::e($artifact['collection_notes']) . '</small></article>';
    }

    $rows = '';
    foreach ($result['all_artifacts'] as $artifact) {
        $rows .= '<tr><td>' . View::e($artifact['name']) . '</td><td>' . View::e($artifact['category']) . '</td><td>' . View::e($artifact['priority']) . '</td><td>' . View::e($artifact['parser_risks']) . '</td></tr>';
    }

    $wins = '';
    foreach ($result['quick_wins'] as $item) {
        $wins .= '<li>' . View::e($item) . '</li>';
    }

    $parserNotes = '';
    foreach ($result['parser_notes'] as $item) {
        $parserNotes .= '<li>' . View::e($item) . '</li>';
    }

    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel">
  <p class="eyebrow">Artifact Triage Matrix</p>
  <h1>Prioritize Android artifact families that examiners often miss when relying on single-tool exports.</h1>
  {$notice}
  <form method="post" action="/artifacts">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <div class="toggle-row">
      <label><input type="checkbox" name="e2ee_apps_present" value="1" checked> E2EE apps present</label>
      <label><input type="checkbox" name="cloud_relevant" value="1" checked> Cloud relevant</label>
      <label><input type="checkbox" name="wiping_suspected" value="1" checked> Wiping suspected</label>
      <label><input type="checkbox" name="malware_suspected" value="1" checked> Malware suspected</label>
      <label><input type="checkbox" name="browser_relevant" value="1" checked> Browser/WebView relevant</label>
      <label><input type="checkbox" name="media_relevant" value="1" checked> Media relevant</label>
      <label><input type="checkbox" name="location_relevant" value="1"> Location relevant</label>
      <label><input type="checkbox" name="work_profile" value="1"> Work profile present</label>
      <label><input type="checkbox" name="external_storage" value="1"> External storage present</label>
      <label><input type="checkbox" name="locked_device" value="1"> Locked device</label>
    </div>
    <button type="submit">Prioritize Artifacts</button>
  </form>
</section>

<section class="result-panel panel">
  <div>
    <h2>Quick Wins</h2>
    <ol class="recommendation-list">{$wins}</ol>
  </div>
  <div>
    <h2>Parser Risk Notes</h2>
    <ol class="recommendation-list">{$parserNotes}</ol>
  </div>
  <div>
    <h2>Triage Focus</h2>
    <p>Artifact priority combines baseline forensic value with case signals for encrypted messaging, cloud, wiping, malware, WebView, media, work profiles, and removable storage.</p>
  </div>
</section>

<section class="section-head"><h2>Top Artifact Families</h2><span>Priority collection set</span></section>
<div class="card-grid">{$cards}</div>

<section class="panel">
  <h2>Complete Artifact Matrix</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Artifact family</th><th>Category</th><th>Priority</th><th>Parser risk</th></tr></thead>
      <tbody>{$rows}</tbody>
    </table>
  </div>
</section>
HTML;
}

function renderValidation(ForensicsLabService $service, string $method): string
{
    $notice = '';
    if ($method === 'POST' && !Csrf::validate($_POST['_csrf'] ?? null)) {
        $notice = '<div class="notice">The validation matrix could not be submitted because the session token expired.</div>';
        $result = $service->toolValidation([]);
    } else {
        $result = $service->toolValidation($method === 'POST' ? ['results' => $_POST['results'] ?? ''] : []);
    }

    $sample = View::e(json_encode([
        ['tool' => 'Magnet AXIOM', 'artifact' => 'Messages database', 'count' => 128, 'hash' => str_repeat('a', 64), 'confidence' => 'High'],
        ['tool' => 'Belkasoft X', 'artifact' => 'Messages database', 'count' => 126, 'hash' => str_repeat('a', 64), 'confidence' => 'Medium'],
        ['tool' => 'Autopsy', 'artifact' => 'Media thumbnails', 'count' => 42, 'hash' => str_repeat('b', 64), 'confidence' => 'High'],
        ['tool' => 'Manual SQLite Review', 'artifact' => 'Media thumbnails', 'count' => 42, 'hash' => str_repeat('b', 64), 'confidence' => 'High'],
        ['tool' => 'JADX Review', 'artifact' => 'Wiping APK behavior', 'count' => 7, 'hash' => '', 'confidence' => 'Medium'],
        ['tool' => 'Dynamic Trace', 'artifact' => 'Wiping APK behavior', 'count' => 9, 'hash' => '', 'confidence' => 'Medium'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $matrixRows = '';
    foreach ($result['matrix'] as $artifact => $toolRows) {
        foreach ($toolRows as $tool => $row) {
            $hash = $row['hash'] !== '' ? $row['hash'] : 'N/A';
            $matrixRows .= '<tr><td>' . View::e($artifact) . '</td><td>' . View::e($tool) . '</td><td>' . View::e($row['count']) . '</td><td class="hash-value">' . View::e($hash) . '</td><td>' . View::e($row['confidence']) . '</td></tr>';
        }
    }

    $discrepancies = '';
    if ($result['discrepancies'] === []) {
        $discrepancies = '<article class="card"><span>Clear</span><h3>No discrepancies detected</h3><p>The submitted parser outputs are aligned on counts, hashes, and confidence.</p></article>';
    } else {
        foreach ($result['discrepancies'] as $item) {
            $discrepancies .= '<article class="card discrepancy-card"><span>' . View::e($item['issue']) . ' - ' . View::e($item['average_confidence']) . '/100</span><h3>' . View::e($item['artifact']) . '</h3><p>' . View::e($item['validation_step']) . '</p><small>Tools: ' . View::e(implode(', ', $item['tools'])) . '</small></article>';
        }
    }

    $steps = '';
    foreach ($result['validation_steps'] as $item) {
        $steps .= '<li>' . View::e($item) . '</li>';
    }

    $score = View::e($result['consensus_score']);
    $tier = View::e($result['consensus_tier']);
    $gate = View::e($result['release_gate']);
    $artifactCount = View::e($result['artifact_count']);
    $resultCount = View::e($result['result_count']);
    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel">
  <p class="eyebrow">Tool Discrepancy Validator</p>
  <h1>Compare parser outputs before findings become formal report language.</h1>
  {$notice}
  <form method="post" action="/validation">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <label>Tool result JSON<textarea name="results" rows="13">{$sample}</textarea></label>
    <button type="submit">Validate Tool Consensus</button>
  </form>
</section>

<section class="result-panel panel">
  <div class="result-score">
    <span>Consensus</span>
    <strong>{$score}</strong>
    <p>{$tier}</p>
  </div>
  <div>
    <h2>{$gate}</h2>
    <p>{$resultCount} parser results mapped across {$artifactCount} artifact families.</p>
  </div>
  <div>
    <h2>Validation Steps</h2>
    <ol class="recommendation-list">{$steps}</ol>
  </div>
</section>

<section class="section-head"><h2>Discrepancies</h2><span>Release gate evidence</span></section>
<div class="card-grid">{$discrepancies}</div>

<section class="panel">
  <h2>Validation Matrix</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Artifact</th><th>Tool</th><th>Count</th><th>Hash</th><th>Confidence</th></tr></thead>
      <tbody>{$matrixRows}</tbody>
    </table>
  </div>
</section>
HTML;
}

function renderReportReadiness(ForensicsLabService $service, string $method): string
{
    $notice = '';
    if ($method === 'POST' && !Csrf::validate($_POST['_csrf'] ?? null)) {
        $notice = '<div class="notice">The report readiness pack could not be submitted because the session token expired.</div>';
        $result = $service->reportReadiness([]);
    } else {
        $result = $service->reportReadiness($method === 'POST' ? $_POST : []);
    }

    $missing = '';
    if ($result['missing_items'] === []) {
        $missing = '<li>No missing release criteria detected.</li>';
    } else {
        foreach ($result['missing_items'] as $item) {
            $missing .= '<li>' . View::e($item) . '</li>';
        }
    }

    $sections = '';
    foreach ($result['report_sections'] as $section) {
        $sections .= '<li>' . View::e($section) . '</li>';
    }

    $checks = '';
    foreach ($result['checks'] as $check => $present) {
        $checks .= '<article class="decision-card"><span>' . View::e($present ? 'Complete' : 'Missing') . '</span><h3>' . View::e(ucwords(str_replace('_', ' ', $check))) . '</h3><p>' . View::e($present ? 'Evidence is ready for the report file.' : 'Complete this item before release.') . '</p></article>';
    }

    $score = View::e($result['score']);
    $tier = View::e($result['tier']);
    $decision = View::e($result['release_decision']);
    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel">
  <p class="eyebrow">Report Readiness Pack</p>
  <h1>Score whether an Android forensic case file is ready for defensible release.</h1>
  {$notice}
  <form method="post" action="/report-readiness">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <div class="toggle-row">
      <label><input type="checkbox" name="authority" value="1" checked> Authority documented</label>
      <label><input type="checkbox" name="scope" value="1" checked> Scope defined</label>
      <label><input type="checkbox" name="chain_of_custody" value="1" checked> Chain of custody</label>
      <label><input type="checkbox" name="hashes" value="1" checked> Hashes recorded</label>
      <label><input type="checkbox" name="tool_versions" value="1" checked> Tool versions recorded</label>
      <label><input type="checkbox" name="validation_matrix" value="1"> Validation matrix complete</label>
      <label><input type="checkbox" name="timeline_anchors" value="1"> Timeline anchors complete</label>
      <label><input type="checkbox" name="limitations" value="1" checked> Limitations written</label>
      <label><input type="checkbox" name="privacy_minimization" value="1" checked> Privacy minimization</label>
      <label><input type="checkbox" name="peer_review" value="1"> Peer review complete</label>
      <label><input type="checkbox" name="reproducible_appendix" value="1"> Reproducible appendix</label>
    </div>
    <button type="submit">Score Report Readiness</button>
  </form>
</section>

<section class="result-panel panel">
  <div class="result-score">
    <span>Readiness</span>
    <strong>{$score}</strong>
    <p>{$tier}</p>
  </div>
  <div>
    <h2>{$decision}</h2>
    <p>Release requires authority, scope, custody, hashes, tool versions, validation, timeline anchors, limitations, privacy handling, peer review, and reproducible appendices.</p>
  </div>
  <div>
    <h2>Missing Items</h2>
    <ol class="recommendation-list">{$missing}</ol>
  </div>
</section>

<section class="section-head"><h2>Release Criteria</h2><span>Checklist status</span></section>
<div class="decision-grid">{$checks}</div>

<section class="panel">
  <h2>Recommended Report Sections</h2>
  <ol class="recommendation-list">{$sections}</ol>
</section>
HTML;
}

function renderCasework(LabRepository $repository, ForensicsLabService $service, string $method): string
{
    $result = null;
    $notice = '';
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $notice = '<div class="notice">The case assessment could not be submitted because the session token expired.</div>';
        } else {
            $result = $service->assessCase($_POST);
        }
    }

    $controlGroups = [];
    foreach ($repository->forensicControls() as $control) {
        $controlGroups[(string) $control['category']][] = $control;
    }

    $controls = '';
    foreach ($controlGroups as $category => $items) {
        $controls .= '<fieldset class="control-set"><legend>' . View::e($category) . '</legend><div class="control-grid">';
        foreach ($items as $control) {
            $checked = $result && in_array($control['id'], $result['selected_controls'], true) ? ' checked' : '';
            $controls .= '<label class="control-item"><input type="checkbox" name="controls[]" value="' . View::e($control['id']) . '"' . $checked . '><span><strong>' . View::e($control['name']) . '</strong><small>Weight ' . View::e($control['weight']) . '</small></span></label>';
        }
        $controls .= '</div></fieldset>';
    }

    $methods = '';
    foreach ($repository->acquisitionMethods() as $acquisitionMethod) {
        $checked = $result && in_array($acquisitionMethod['id'], $result['selected_methods'], true) ? ' checked' : '';
        $methods .= '<label class="control-item"><input type="checkbox" name="methods[]" value="' . View::e($acquisitionMethod['id']) . '"' . $checked . '><span><strong>' . View::e($acquisitionMethod['name']) . '</strong><small>' . View::e($acquisitionMethod['access_level']) . '</small></span></label>';
    }

    $resultHtml = '';
    if ($result !== null) {
        $riskRows = '';
        foreach ($result['risk_profile'] as $risk) {
            $riskRows .= '<tr><td>' . View::e($risk['name']) . '</td><td>' . View::e($risk['residual_score']) . '</td><td>' . View::e($risk['residual_tier']) . '</td><td>' . View::e($risk['covered_controls']) . '/' . View::e($risk['required_controls']) . '</td></tr>';
        }

        $recommendations = '';
        foreach ($result['recommendations'] as $recommendation) {
            $recommendations .= '<li><strong>' . View::e($recommendation['name']) . '</strong><span>' . View::e($recommendation['category']) . '</span></li>';
        }

        $sequence = '';
        foreach ($result['method_plan']['recommended_sequence'] as $step) {
            $sequence .= '<li>' . View::e($step) . '</li>';
        }

        $resultHtml = <<<HTML
<section class="panel result-panel">
  <div class="result-score">
    <span>Readiness score</span>
    <strong>{$result['score']}</strong>
    <p>{$result['readiness']} - {$result['risk_tier']} residual risk</p>
  </div>
  <div>
    <h2>Priority Controls</h2>
    <ol class="recommendation-list">{$recommendations}</ol>
  </div>
  <div>
    <h2>Recommended Sequence</h2>
    <ol class="recommendation-list">{$sequence}</ol>
  </div>
</section>
<section class="panel">
  <h2>Risk Profile</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Area</th><th>Residual</th><th>Tier</th><th>Controls</th></tr></thead>
      <tbody>{$riskRows}</tbody>
    </table>
  </div>
</section>
HTML;
    }

    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel">
  <p class="eyebrow">Casework Assessment</p>
  <h1>Score Android forensic readiness against acquisition, anti-forensics, memory, privacy, and reporting controls.</h1>
  {$notice}
  <form method="post" action="/casework">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <div class="form-grid">
      <label>Case name<input name="case_name" placeholder="Android malware and wiping review"></label>
      <label>Device model<input name="device_model" placeholder="Pixel, Samsung Galaxy, OnePlus"></label>
      <label>Android version<input name="android_version" placeholder="Android 14"></label>
    </div>
    <div class="toggle-row">
      <label><input type="checkbox" name="locked_device" value="1"> Locked device</label>
      <label><input type="checkbox" name="deleted_data_needed" value="1" checked> Deleted data needed</label>
      <label><input type="checkbox" name="cloud_relevant" value="1" checked> Cloud artifacts relevant</label>
      <label><input type="checkbox" name="malware_suspected" value="1" checked> Malware suspected</label>
      <label><input type="checkbox" name="memory_needed" value="1"> Memory evidence needed</label>
      <label><input type="checkbox" name="wiping_suspected" value="1" checked> Wiping suspected</label>
      <label><input type="checkbox" name="court_report" value="1" checked> Formal report</label>
      <label><input type="checkbox" name="privacy_sensitive" value="1" checked> Sensitive data present</label>
    </div>
    <fieldset class="control-set">
      <legend>Acquisition and analysis methods already completed</legend>
      <div class="control-grid">{$methods}</div>
    </fieldset>
    {$controls}
    <button type="submit">Calculate Readiness</button>
  </form>
</section>
{$resultHtml}
HTML;
}

function renderWorkbench(ForensicsLabService $service, string $method): string
{
    $result = null;
    $notice = '';
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $notice = '<div class="notice">The workbench scenario could not be submitted because the session token expired.</div>';
        } else {
            $result = $service->commandWorkbench($_POST);
        }
    }

    $resultHtml = '<section class="panel workbench-empty"><h2>Scenario Intelligence Model</h2><p>Submit a scenario to generate a mission profile, acquisition stack, operational lanes, evidence constellation, decision cards, and validation backlog.</p></section>';
    if ($result !== null) {
        $stack = '';
        foreach ($result['priority_stack'] as $item) {
            $stack .= '<article class="card method-card"><span>#' . View::e($item['rank']) . ' - ' . View::e($item['role']) . ' - ' . View::e($item['score']) . '/100</span><h3>' . View::e($item['name']) . '</h3><p>' . View::e($item['why']) . '</p></article>';
        }

        $lanes = '';
        foreach ($result['operational_lanes'] as $lane) {
            $lanes .= '<article class="lane-card"><span>' . View::e($lane['tempo']) . '</span><h3>' . View::e($lane['lane']) . '</h3><p>' . View::e($lane['objective']) . '</p></article>';
        }

        $constellation = '';
        foreach ($result['evidence_constellation'] as $feature) {
            $constellation .= '<article class="constellation-node detail-node"><span>' . View::e($feature['criticality']) . '</span><strong>' . View::e($feature['name']) . '</strong><small>' . View::e($feature['role']) . '</small><p>' . View::e($feature['validation']) . '</p></article>';
        }

        $cards = '';
        foreach ($result['decision_cards'] as $card) {
            $cards .= '<article class="decision-card"><span>' . View::e($card['title']) . '</span><p>' . View::e($card['body']) . '</p></article>';
        }

        $backlog = '';
        foreach ($result['validation_backlog'] as $item) {
            $backlog .= '<li>' . View::e($item) . '</li>';
        }

        $mission = View::e($result['mission_profile']);
        $urgency = View::e($result['urgency_score']);
        $tier = View::e($result['urgency_tier']);
        $brief = View::e($result['analyst_brief']);

        $resultHtml = <<<HTML
<section class="panel result-panel">
  <div class="result-score">
    <span>Urgency</span>
    <strong>{$urgency}</strong>
    <p>{$tier} operational tempo</p>
  </div>
  <div>
    <h2>{$mission}</h2>
    <p>{$brief}</p>
  </div>
  <div>
    <h2>Validation Backlog</h2>
    <ol class="recommendation-list">{$backlog}</ol>
  </div>
</section>
<section class="section-head"><h2>Priority Stack</h2><span>Ranked methods</span></section>
<div class="card-grid">{$stack}</div>
<section class="section-head"><h2>Operational Lanes</h2><span>Case execution model</span></section>
<div class="lane-grid">{$lanes}</div>
<section class="section-head"><h2>Evidence Constellation</h2><span>Feature roles and validations</span></section>
<div class="constellation-detail">{$constellation}</div>
<section class="section-head"><h2>Decision Cards</h2><span>Examiner brief</span></section>
<div class="decision-grid">{$cards}</div>
HTML;
    }

    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel command-form">
  <p class="eyebrow">Command Workbench</p>
  <h1>Turn case signals into an operational forensic mission plan.</h1>
  {$notice}
  <form method="post" action="/workbench">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <div class="form-grid">
      <label>Scenario name<input name="scenario_name" placeholder="Locked Android wiping and stealth review"></label>
      <label>Device model<input name="device_model" placeholder="Pixel, Samsung Galaxy, OnePlus"></label>
      <label>Android version<input name="android_version" placeholder="Android 15"></label>
    </div>
    <div class="toggle-row">
      <label><input type="checkbox" name="locked_device" value="1" checked> Locked device</label>
      <label><input type="checkbox" name="unlock_available" value="1"> Unlock available</label>
      <label><input type="checkbox" name="deleted_data_needed" value="1" checked> Deleted data needed</label>
      <label><input type="checkbox" name="cloud_relevant" value="1" checked> Cloud relevant</label>
      <label><input type="checkbox" name="malware_suspected" value="1" checked> Malware suspected</label>
      <label><input type="checkbox" name="memory_needed" value="1" checked> Memory needed</label>
      <label><input type="checkbox" name="wiping_suspected" value="1" checked> Wiping suspected</label>
      <label><input type="checkbox" name="active_network" value="1" checked> Active network risk</label>
      <label><input type="checkbox" name="time_sensitive" value="1" checked> Time-sensitive evidence</label>
      <label><input type="checkbox" name="native_libraries_present" value="1" checked> Native libraries present</label>
      <label><input type="checkbox" name="anti_emulator_indicators" value="1"> Anti-emulator indicators</label>
      <label><input type="checkbox" name="root_possible" value="1"> Root possible</label>
      <label><input type="checkbox" name="cloud_tokens_present" value="1" checked> Cloud tokens present</label>
      <label><input type="checkbox" name="e2ee_apps_present" value="1" checked> E2EE apps present</label>
      <label><input type="checkbox" name="external_storage_present" value="1"> External storage present</label>
      <label><input type="checkbox" name="recent_user_activity" value="1" checked> Recent user activity</label>
      <label><input type="checkbox" name="court_report" value="1" checked> Formal report</label>
      <label><input type="checkbox" name="privacy_sensitive" value="1" checked> Sensitive data present</label>
    </div>
    <button type="submit">Generate Mission Plan</button>
  </form>
</section>
{$resultHtml}
HTML;
}

function renderMethods(LabRepository $repository, ForensicsLabService $service, string $method): string
{
    $result = null;
    $notice = '';
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $notice = '<div class="notice">The method comparison could not be submitted because the session token expired.</div>';
        } else {
            $result = $service->methodCompare($_POST);
        }
    } else {
        $result = $service->methodCompare([
            'deleted_data_needed' => true,
            'cloud_relevant' => true,
            'malware_suspected' => true,
            'wiping_suspected' => true,
            'court_report' => true,
        ]);
    }

    $featureOptions = '';
    foreach ($repository->evidenceFeatures() as $feature) {
        $featureOptions .= '<label class="control-item"><input type="checkbox" name="selected_features[]" value="' . View::e($feature['id']) . '" checked><span><strong>' . View::e($feature['name']) . '</strong><small>' . View::e($feature['category']) . '</small></span></label>';
    }

    $methodCards = '';
    foreach ($result['ranked_methods'] as $rankedMethod) {
        $methodCards .= '<article class="card method-card"><span>' . View::e($rankedMethod['role']) . ' - ' . View::e($rankedMethod['score']) . '/100</span><h3>' . View::e($rankedMethod['name']) . '</h3><p>' . View::e($rankedMethod['access_level']) . '</p><small>Reference coverage hint: ' . View::e($rankedMethod['coverage_hint']) . '%</small></article>';
    }

    $coverageRows = '';
    foreach ($result['coverage_by_feature'] as $coverage) {
        $coverageRows .= '<tr><td>' . View::e($coverage['feature']) . '</td><td>' . View::e($coverage['best_method']) . '</td><td>' . View::e($coverage['best_score']) . '</td></tr>';
    }

    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel">
  <p class="eyebrow">Method Comparison Engine</p>
  <h1>Rank Android forensic methods by evidence goals and case constraints.</h1>
  {$notice}
  <form method="post" action="/methods">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <div class="toggle-row">
      <label><input type="checkbox" name="locked_device" value="1"> Locked device</label>
      <label><input type="checkbox" name="deleted_data_needed" value="1" checked> Deleted data needed</label>
      <label><input type="checkbox" name="cloud_relevant" value="1" checked> Cloud relevant</label>
      <label><input type="checkbox" name="malware_suspected" value="1" checked> Malware suspected</label>
      <label><input type="checkbox" name="memory_needed" value="1"> Memory needed</label>
      <label><input type="checkbox" name="wiping_suspected" value="1" checked> Wiping suspected</label>
      <label><input type="checkbox" name="court_report" value="1" checked> Formal report</label>
    </div>
    <fieldset class="control-set">
      <legend>Evidence features</legend>
      <div class="control-grid">{$featureOptions}</div>
    </fieldset>
    <button type="submit">Rank Methods</button>
  </form>
</section>

<section class="section-head"><h2>Ranked Methods</h2><span>Context-aware scores</span></section>
<div class="card-grid">{$methodCards}</div>

<section class="panel">
  <h2>Best Coverage by Evidence Feature</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Feature</th><th>Best method</th><th>Score</th></tr></thead>
      <tbody>{$coverageRows}</tbody>
    </table>
  </div>
</section>
HTML;
}

function renderWiping(ForensicsLabService $service, string $method): string
{
    $result = null;
    $notice = '';
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $notice = '<div class="notice">The wiping evaluation could not be submitted because the session token expired.</div>';
        } else {
            $result = $service->wipingEvaluation($_POST);
        }
    }

    $resultHtml = '';
    if ($result !== null) {
        $tests = '';
        foreach ($result['recommended_tests'] as $test) {
            $tests .= '<li>' . View::e($test) . '</li>';
        }

        $resultHtml = <<<HTML
<section class="panel result-panel">
  <div class="result-score">
    <span>Residual risk</span>
    <strong>{$result['risk_score']}</strong>
    <p>{$result['risk_tier']} - {$result['classification']}</p>
  </div>
  <div>
    <h2>Recommended Tests</h2>
    <ol class="recommendation-list">{$tests}</ol>
  </div>
</section>
<section class="panel">
  <h2>Report Language</h2>
  <p>{$result['report_language']}</p>
</section>
HTML;
    }

    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel">
  <p class="eyebrow">File-Wiping Evaluation</p>
  <h1>Evaluate Android deletion and wiping behavior against claims, implementation evidence, recoverability, and residual artifacts.</h1>
  {$notice}
  <form method="post" action="/wiping">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <label>Application name<input name="app_name" placeholder="Secure delete utility"></label>
    <div class="toggle-row">
      <label><input type="checkbox" name="declared_claim" value="1" checked> Declares wiping claim</label>
      <label><input type="checkbox" name="overwrite_observed" value="1"> Overwrite observed</label>
      <label><input type="checkbox" name="standards_match" value="1"> Standards alignment demonstrated</label>
      <label><input type="checkbox" name="recoverable_content" value="1" checked> Content recoverable</label>
      <label><input type="checkbox" name="partial_recovery" value="1"> Partial recovery</label>
      <label><input type="checkbox" name="execution_artifacts" value="1" checked> Execution artifacts remain</label>
      <label><input type="checkbox" name="app_internal_artifacts" value="1" checked> App artifacts remain</label>
      <label><input type="checkbox" name="os_artifacts" value="1" checked> OS artifacts remain</label>
      <label><input type="checkbox" name="timeline_consistent" value="1" checked> Timeline consistent</label>
    </div>
    <button type="submit">Evaluate Wiping Evidence</button>
  </form>
</section>
{$resultHtml}
HTML;
}

function renderTimeline(ForensicsLabService $service, string $method): string
{
    $result = null;
    $notice = '';
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $notice = '<div class="notice">The timeline fusion could not be submitted because the session token expired.</div>';
        } else {
            $result = $service->timelineFusion([
                'case_name' => $_POST['case_name'] ?? '',
                'events' => $_POST['events'] ?? '',
            ]);
        }
    }

    $sample = View::e(json_encode([
        [
            'timestamp' => '2026-05-06T08:11:00+04:00',
            'source' => 'Device OS',
            'artifact' => 'Package manager',
            'description' => 'File-wiping application launch recorded.',
            'confidence' => 'High',
            'hash' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
        [
            'timestamp' => '2026-05-06T08:14:02+04:00',
            'source' => 'Filesystem',
            'artifact' => 'Media store',
            'description' => 'Residual thumbnail and metadata entry remained after deletion.',
            'confidence' => 'High',
            'hash' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
        ],
        [
            'timestamp' => '2026-05-06T08:21:49+04:00',
            'source' => 'Network',
            'artifact' => 'Runtime capture',
            'description' => 'Application contacted telemetry endpoint after wiping workflow.',
            'confidence' => 'Medium',
            'hash' => 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $resultHtml = '<section class="panel workbench-empty"><h2>Timeline Fusion Model</h2><p>Submit mixed-source Android events to normalize timestamps, cluster activity, expose anomalies, and produce reconstruction steps.</p></section>';
    if ($result !== null) {
        $clusters = '';
        foreach ($result['clusters'] as $cluster) {
            $clusters .= '<article class="card"><span>' . View::e($cluster['events']) . ' events</span><h3>' . View::e($cluster['window']) . '</h3><p>' . View::e(implode(', ', $cluster['sources'])) . '</p><small>Highest confidence: ' . View::e($cluster['highest_confidence']) . '</small></article>';
        }

        $anchors = '';
        foreach ($result['anchors'] as $anchor) {
            $anchors .= '<tr><td>' . View::e($anchor['timestamp']) . '</td><td>' . View::e($anchor['source']) . '</td><td>' . View::e($anchor['artifact']) . '</td><td>' . View::e($anchor['description']) . '</td></tr>';
        }

        $anomalies = '';
        foreach ($result['anomalies'] as $anomaly) {
            $anomalies .= '<li>' . View::e($anomaly) . '</li>';
        }
        if ($anomalies === '') {
            $anomalies = '<li>No major timeline anomalies detected.</li>';
        }

        $steps = '';
        foreach ($result['reconstruction_steps'] as $step) {
            $steps .= '<li>' . View::e($step) . '</li>';
        }

        $sourceBars = '';
        foreach ($result['source_map'] as $source => $count) {
            $width = min(100, max(18, $count * 24));
            $sourceBars .= '<div class="source-bar"><span>' . View::e($source) . '</span><strong style="width: ' . $width . '%">' . View::e($count) . '</strong></div>';
        }

        $confidence = View::e($result['confidence_score']);
        $tier = View::e($result['confidence_tier']);
        $eventCount = View::e($result['event_count']);
        $sourceCount = View::e($result['source_count']);

        $resultHtml = <<<HTML
<section class="panel result-panel">
  <div class="result-score">
    <span>Confidence</span>
    <strong>{$confidence}</strong>
    <p>{$tier} reconstruction confidence</p>
  </div>
  <div>
    <h2>{$eventCount} Events</h2>
    <p>Normalized into clustered activity windows and source families.</p>
    <div class="source-bars">{$sourceBars}</div>
  </div>
  <div>
    <h2>{$sourceCount} Sources</h2>
    <ol class="recommendation-list">{$steps}</ol>
  </div>
</section>
<section class="section-head"><h2>Activity Clusters</h2><span>Time windows</span></section>
<div class="card-grid">{$clusters}</div>
<section class="panel">
  <h2>High-Confidence Anchors</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Timestamp</th><th>Source</th><th>Artifact</th><th>Description</th></tr></thead>
      <tbody>{$anchors}</tbody>
    </table>
  </div>
</section>
<section class="panel">
  <h2>Anomaly Notes</h2>
  <ol class="recommendation-list">{$anomalies}</ol>
</section>
HTML;
    }

    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel">
  <p class="eyebrow">Timeline Fusion</p>
  <h1>Normalize Android events into a confidence-scored reconstruction.</h1>
  {$notice}
  <form method="post" action="/timeline">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <label>Case name<input name="case_name" placeholder="Android wiping and activity timeline"></label>
    <label>Events JSON<textarea name="events" rows="14">{$sample}</textarea></label>
    <button type="submit">Fuse Timeline</button>
  </form>
</section>
{$resultHtml}
HTML;
}

function renderLedger(ForensicsLabService $service, string $method): string
{
    $result = null;
    $notice = '';
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $notice = '<div class="notice">The hash ledger could not be submitted because the session token expired.</div>';
        } else {
            $result = $service->hashLedger([
                'case_name' => $_POST['case_name'] ?? '',
                'manifest' => $_POST['manifest'] ?? '',
            ]);
        }
    }

    $sample = '[{"path":"/extraction/system/build.prop","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"},{"path":"/extraction/data/com.example/app.db","sha256":"bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb"},{"path":"/reports/timeline.csv","sha256":"cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"}]';
    $resultHtml = '';
    if ($result !== null) {
        $notes = '';
        foreach ($result['integrity_notes'] as $note) {
            $notes .= '<li>' . View::e($note) . '</li>';
        }

        $root = View::e($result['merkle_root']);
        $count = View::e($result['item_count']);
        $resultHtml = <<<HTML
<section class="panel result-panel">
  <div class="result-score wide-score">
    <span>Manifest entries</span>
    <strong>{$count}</strong>
    <p class="hash-value">{$root}</p>
  </div>
  <div>
    <h2>Integrity Notes</h2>
    <ol class="recommendation-list">{$notes}</ol>
  </div>
</section>
HTML;
    }

    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel">
  <p class="eyebrow">Evidence Integrity Ledger</p>
  <h1>Create a deterministic Merkle-style root for normalized SHA-256 evidence manifests.</h1>
  {$notice}
  <form method="post" action="/ledger">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <label>Case name<input name="case_name" placeholder="Android evidence manifest"></label>
    <label>Manifest JSON<textarea name="manifest" rows="9">{$sample}</textarea></label>
    <button type="submit">Create Ledger Root</button>
  </form>
</section>
{$resultHtml}
HTML;
}

function renderResearch(LabRepository $repository): string
{
    $sources = '';
    foreach ($repository->researchSources() as $source) {
        $doi = $source['doi'] !== '' ? '<span>DOI: ' . View::e($source['doi']) . '</span>' : '<span>Reference source</span>';
        $sources .= '<article class="card research-card"><span>' . View::e($source['year']) . '</span><h3>' . View::e($source['title']) . '</h3><p>' . View::e($source['authors']) . '</p><p>' . View::e($source['venue']) . '</p><p>' . View::e($source['contribution']) . '</p><a href="' . View::e($source['url']) . '" target="_blank" rel="noreferrer">Open source</a>' . $doi . '</article>';
    }

    $features = '';
    foreach ($repository->evidenceFeatures() as $feature) {
        $features .= '<article class="card"><span>' . View::e($feature['category']) . '</span><h3>' . View::e($feature['name']) . '</h3><p>' . View::e($feature['description']) . '</p></article>';
    }

    return <<<HTML
<section class="panel paper-detail">
  <p class="eyebrow">Research Alignment</p>
  <h1>Documented research base for advanced Android forensic casework.</h1>
  <p class="lead">The platform converts peer-reviewed literature, doctoral research, file-wiping evaluation work, and practical lab-operation guidance into structured controls, methods, and evidence review workflows.</p>
</section>
<section class="section-head"><h2>Research Sources</h2><span>Formal references</span></section>
<div class="card-grid research-grid">{$sources}</div>
<section class="section-head"><h2>Evidence Feature Model</h2><span>Coverage dimensions</span></section>
<div class="card-grid">{$features}</div>
HTML;
}
