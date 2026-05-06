<?php

declare(strict_types=1);

use AndroidForensicsLab\Repository\LabRepository;
use AndroidForensicsLab\Service\ForensicsLabService;

$bootstrap = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$repository = new LabRepository($bootstrap['catalog']);
$service = new ForensicsLabService($repository);
$assertions = 0;

function assertThat(bool $condition, string $message): void
{
    global $assertions;
    $assertions++;

    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$summary = $service->summary();
assertThat($summary['metrics']['research_sources'] === 5, 'Research source count must be five.');
assertThat($summary['metrics']['workflow_stages'] === 8, 'Workflow should contain eight stages.');
assertThat($summary['metrics']['advanced_modules'] === 6, 'Advanced module count should be six.');
assertThat($summary['metrics']['evidence_features'] === 9, 'Evidence feature model should contain nine features.');
assertThat($summary['metrics']['acquisition_methods'] === 9, 'Method model should contain nine methods.');
assertThat($summary['metrics']['tool_profiles'] === 10, 'Tool profile catalog should contain ten entries.');
assertThat($summary['metrics']['wiping_artifacts'] === 8, 'Wiping artifact model should contain eight entries.');
assertThat($summary['metrics']['forensic_controls'] === 30, 'Control catalog should contain thirty controls.');
assertThat($summary['metrics']['maximum_score'] === 227, 'Maximum control score should be stable.');

$sourceIds = array_map(static fn (array $source): string => (string) $source['id'], $repository->researchSources());
assertThat(in_array('kumar-narayan-rasid-2026', $sourceIds, true), 'Literature review source must be present.');
assertThat(in_array('sanna-2026-thesis', $sourceIds, true), 'Stealth-attack thesis source must be present.');
assertThat(in_array('gunay-gul-ertam-2026', $sourceIds, true), 'Comparative method study must be present.');
assertThat(in_array('oh-et-al-2026', $sourceIds, true), 'File-wiping study must be present.');
assertThat(in_array('bhardwaj-kaushik-2023', $sourceIds, true), 'Practical digital forensics book must be present.');

$allControlIds = array_map(static fn (array $control): string => (string) $control['id'], $repository->forensicControls());
$allMethodIds = array_map(static fn (array $method): string => (string) $method['id'], $repository->acquisitionMethods());

$strong = $service->assessCase([
    'case_name' => 'Complete Android forensic case',
    'device_model' => 'Android reference device',
    'cloud_relevant' => false,
    'court_report' => false,
    'privacy_sensitive' => false,
    'controls' => $allControlIds,
    'methods' => $allMethodIds,
]);
assertThat($strong['score'] === 100, 'Complete controls and methods should score 100.');
assertThat($strong['readiness'] === 'Court Ready', 'Complete low-risk case should be court ready.');
assertThat($strong['risk_tier'] === 'Low', 'Complete low-risk case should have low residual risk.');

$weak = $service->assessCase([
    'case_name' => 'High-risk Android case',
    'locked_device' => true,
    'deleted_data_needed' => true,
    'cloud_relevant' => true,
    'malware_suspected' => true,
    'memory_needed' => true,
    'wiping_suspected' => true,
    'court_report' => true,
    'privacy_sensitive' => true,
    'controls' => [],
    'methods' => [],
]);
assertThat($weak['score'] === 0, 'No controls under high-risk context should score zero.');
assertThat($weak['readiness'] === 'Not Defensible', 'Weak high-risk case should not be defensible.');
assertThat($weak['risk_tier'] === 'Critical', 'Weak high-risk case should have critical residual risk.');
assertThat(count($weak['recommendations']) > 0, 'Weak case should provide control recommendations.');

$methodPlan = $service->methodCompare([
    'deleted_data_needed' => true,
    'cloud_relevant' => true,
    'malware_suspected' => false,
    'wiping_suspected' => false,
    'court_report' => true,
]);
assertThat($methodPlan['ranked_methods'][0]['id'] === 'physical-acquisition', 'Deleted-data case should prioritize physical acquisition.');
assertThat(count($methodPlan['coverage_by_feature']) === 9, 'Coverage table should include all evidence features.');
assertThat(in_array('Physical Imaging', $methodPlan['recommended_sequence'], true), 'Recommended sequence should include physical imaging.');

$stealthPlan = $service->methodCompare([
    'deleted_data_needed' => false,
    'cloud_relevant' => false,
    'malware_suspected' => true,
    'memory_needed' => true,
    'wiping_suspected' => true,
    'selected_features' => ['malware-indicators', 'browser-app-data', 'deleted-unallocated'],
]);
$stealthTop = array_slice(array_column($stealthPlan['ranked_methods'], 'id'), 0, 4);
assertThat(in_array('memory-acquisition', $stealthTop, true), 'Stealth case should rank memory acquisition highly.');
assertThat(in_array('app-dynamic-analysis', $stealthTop, true), 'Stealth case should rank dynamic analysis highly.');

$wipingMismatch = $service->wipingEvaluation([
    'app_name' => 'Claimed secure wipe utility',
    'declared_claim' => true,
    'overwrite_observed' => false,
    'standards_match' => false,
    'recoverable_content' => true,
    'partial_recovery' => false,
    'execution_artifacts' => true,
    'app_internal_artifacts' => true,
    'os_artifacts' => true,
    'timeline_consistent' => true,
]);
assertThat(str_starts_with($wipingMismatch['classification'], 'Claim mismatch'), 'Recoverable claimed wipe should be a claim mismatch.');
assertThat($wipingMismatch['risk_tier'] === 'Critical', 'Claim mismatch with recovered content should be critical.');

$wipingEffective = $service->wipingEvaluation([
    'app_name' => 'Validated wipe utility',
    'declared_claim' => true,
    'overwrite_observed' => true,
    'standards_match' => true,
    'recoverable_content' => false,
    'partial_recovery' => false,
    'execution_artifacts' => true,
    'app_internal_artifacts' => false,
    'os_artifacts' => true,
    'timeline_consistent' => true,
]);
assertThat($wipingEffective['classification'] === 'Content wiping appears effective while execution traces remain', 'Standards-aligned content wiping should preserve residual trace finding.');
assertThat($wipingEffective['risk_tier'] === 'Low', 'Effective content wiping with traces should be low residual risk.');

$manifest = [
    ['path' => '/extraction/system/build.prop', 'sha256' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
    ['path' => '/extraction/data/com.example/app.db', 'sha256' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'],
    ['path' => '/reports/timeline.csv', 'sha256' => 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc'],
];
$ledgerOne = $service->hashLedger(['case_name' => 'Manifest A', 'manifest' => $manifest]);
$ledgerTwo = $service->hashLedger(['case_name' => 'Manifest A', 'manifest' => array_reverse($manifest)]);
assertThat($ledgerOne['item_count'] === 3, 'Ledger should include three entries.');
assertThat($ledgerOne['merkle_root'] === $ledgerTwo['merkle_root'], 'Ledger root should be deterministic regardless of input order.');

$changedManifest = $manifest;
$changedManifest[1]['sha256'] = 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd';
$ledgerChanged = $service->hashLedger(['case_name' => 'Manifest B', 'manifest' => $changedManifest]);
assertThat($ledgerChanged['merkle_root'] !== $ledgerOne['merkle_root'], 'Ledger root should change when a file hash changes.');

$emptyLedger = $service->hashLedger(['case_name' => 'Empty manifest', 'manifest' => []]);
assertThat($emptyLedger['merkle_root'] === str_repeat('0', 64), 'Empty manifest should have a zero root.');

$workbench = $service->commandWorkbench([
    'scenario_name' => 'Locked stealth and wiping case',
    'locked_device' => true,
    'deleted_data_needed' => true,
    'cloud_relevant' => true,
    'malware_suspected' => true,
    'memory_needed' => true,
    'wiping_suspected' => true,
    'active_network' => true,
    'time_sensitive' => true,
    'native_libraries_present' => true,
    'e2ee_apps_present' => true,
    'court_report' => true,
]);
assertThat($workbench['mission_profile'] === 'Volatile-first stealth investigation', 'Workbench should detect a volatile-first stealth profile.');
assertThat($workbench['urgency_score'] >= 85, 'High-risk workbench scenario should have critical urgency.');
assertThat(count($workbench['priority_stack']) === 5, 'Workbench should return five priority methods.');
assertThat(count($workbench['operational_lanes']) === 6, 'Workbench should return six operational lanes.');
assertThat(count($workbench['evidence_constellation']) === 9, 'Workbench should map all evidence features.');
assertThat($workbench['evidence_constellation'][0]['criticality'] >= 80, 'Constellation should prioritize critical features.');

$timeline = $service->timelineFusion([
    'case_name' => 'Timeline test',
    'events' => [
        ['timestamp' => '2026-05-06T09:00:00+04:00', 'source' => 'Filesystem', 'artifact' => 'Media store', 'description' => 'Artifact remained', 'confidence' => 'High'],
        ['timestamp' => '2026-05-06T08:00:00+04:00', 'source' => 'Device OS', 'artifact' => 'Package event', 'description' => 'Application launched', 'confidence' => 'High'],
        ['timestamp' => '2026-05-06T13:45:00+04:00', 'source' => 'Network', 'artifact' => 'Runtime capture', 'description' => 'Remote endpoint contacted', 'confidence' => 'Medium'],
    ],
]);
assertThat($timeline['event_count'] === 3, 'Timeline fusion should include three events.');
assertThat($timeline['source_count'] === 3, 'Timeline fusion should detect three sources.');
assertThat(count($timeline['clusters']) >= 2, 'Timeline fusion should create activity clusters.');
assertThat(count($timeline['anchors']) >= 2, 'Timeline fusion should return high-confidence anchors.');
assertThat(count($timeline['anomalies']) >= 1, 'Out-of-order or gapped timeline should produce anomaly notes.');
assertThat($timeline['confidence_score'] > 0, 'Timeline confidence should be calculated.');

$migration = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '001_create_core_tables.sql');
$seed = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . '001_seed_research_data.sql');
assertThat(is_string($migration) && str_contains($migration, 'CREATE TABLE IF NOT EXISTS case_assessments'), 'Migration must create case assessments.');
assertThat(is_string($migration) && str_contains($migration, 'CREATE TABLE IF NOT EXISTS wiping_evaluations'), 'Migration must create wiping evaluations.');
assertThat(is_string($migration) && str_contains($migration, 'CREATE TABLE IF NOT EXISTS hash_ledger_runs'), 'Migration must create hash ledger runs.');
assertThat(is_string($migration) && str_contains($migration, 'CREATE TABLE IF NOT EXISTS workbench_runs'), 'Migration must create workbench runs.');
assertThat(is_string($migration) && str_contains($migration, 'CREATE TABLE IF NOT EXISTS timeline_fusions'), 'Migration must create timeline fusions.');
assertThat(is_string($migration) && str_contains($migration, 'CREATE TABLE IF NOT EXISTS chain_of_custody_events'), 'Migration must create custody events.');
assertThat(is_string($seed) && str_contains($seed, '10.35444/IJANA.2025.17407'), 'Seed must include the Android forensics literature review DOI.');
assertThat(is_string($seed) && str_contains($seed, '10.1111/1556-4029.70174'), 'Seed must include the file-wiping study DOI.');
assertThat(is_string($seed) && str_contains($seed, 'wiping-claim-review'), 'Seed must include anti-forensics control data.');

$publicText = collectPublicText(dirname(__DIR__));
$forbidden = '/Codex|ChatGPT|OpenAI|AI-generated|generated by|created by tool|build_profile|Projects that make the profile/i';
assertThat(!preg_match($forbidden, $publicText), 'Public text must not include internal production wording.');

echo 'Tests passed: ' . $assertions . ' assertions.' . PHP_EOL;

function collectPublicText(string $root): string
{
    $extensions = ['md', 'php', 'css', 'json', 'yml', 'yaml', 'sql', 'example', 'txt'];
    $buffer = '';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if (str_contains($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)
            || str_contains($path, DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR)) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        $basename = strtolower($file->getBasename());
        if (!in_array($extension, $extensions, true) && !str_ends_with($basename, '.env.example')) {
            continue;
        }

        $content = file_get_contents($path);
        if (is_string($content)) {
            $buffer .= "\n" . $content;
        }
    }

    return $buffer;
}
