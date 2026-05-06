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

if ($path === '/api/assess') {
    requirePost($method);
    Json::respond($service->assessCase(requestPayload()));
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

if ($path === '/') {
    echo View::page('Android Digital Forensics Lab', renderDashboard($repository, $service));
    exit;
}

if ($path === '/casework') {
    echo View::page('Casework | Android Digital Forensics Lab', renderCasework($repository, $service, $method));
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

    $tools = '';
    foreach (array_slice($repository->toolProfiles(), 0, 6) as $tool) {
        $tools .= '<article class="card"><span>' . View::e($tool['category']) . '</span><h3>' . View::e($tool['name']) . '</h3><p>' . View::e($tool['use_case']) . '</p></article>';
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
    <p class="eyebrow">Android Digital Forensics Laboratory</p>
    <h1>Research-aligned casework for acquisition planning, anti-forensics, volatile evidence, and evidence integrity.</h1>
    <p class="lead">A PHP 8 and MySQL platform for examiners who need defensible decisions across manual, logical, file-system, physical, cloud, memory, static, dynamic, and emulator-assisted Android workflows.</p>
    <div class="hero-actions">
      <a class="button-link" href="/casework">Run Case Assessment</a>
      <a class="secondary-link" href="/methods">Compare Methods</a>
    </div>
  </div>
  <aside class="hero-aside">
    <span>Research base</span>
    <strong>{$metrics['research_sources']} sources</strong>
    <p>Android acquisition, method comparison, stealth attack detection, wiping applications, and lab operations.</p>
  </aside>
</section>

<section class="metric-grid">
  <article><span>Methods</span><strong>{$metrics['acquisition_methods']}</strong><p>Acquisition and analysis paths ranked by case context.</p></article>
  <article><span>Evidence Features</span><strong>{$metrics['evidence_features']}</strong><p>Recovered data families mapped to coverage and gaps.</p></article>
  <article><span>Controls</span><strong>{$metrics['forensic_controls']}</strong><p>Governance, integrity, anti-forensics, and reporting controls.</p></article>
  <article><span>Tool Profiles</span><strong>{$metrics['tool_profiles']}</strong><p>Commercial, open-source, reverse engineering, and integrity tooling.</p></article>
</section>

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

