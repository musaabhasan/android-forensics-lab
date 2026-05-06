<?php

declare(strict_types=1);

namespace AndroidForensicsLab\Service;

use AndroidForensicsLab\Repository\LabRepository;

final class ForensicsLabService
{
    public function __construct(private readonly LabRepository $repository)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $featureCategories = [];
        $controlCategories = [];

        foreach ($this->repository->evidenceFeatures() as $feature) {
            $category = (string) $feature['category'];
            $featureCategories[$category] = ($featureCategories[$category] ?? 0) + 1;
        }

        foreach ($this->repository->forensicControls() as $control) {
            $category = (string) $control['category'];
            $controlCategories[$category] = ($controlCategories[$category] ?? 0) + 1;
        }

        return [
            'platform' => $this->repository->platform(),
            'metrics' => [
                'research_sources' => count($this->repository->researchSources()),
                'workflow_stages' => count($this->repository->workflowStages()),
                'advanced_modules' => count($this->repository->advancedModules()),
                'evidence_features' => count($this->repository->evidenceFeatures()),
                'acquisition_methods' => count($this->repository->acquisitionMethods()),
                'tool_profiles' => count($this->repository->toolProfiles()),
                'wiping_artifacts' => count($this->repository->wipingArtifacts()),
                'forensic_controls' => count($this->repository->forensicControls()),
                'maximum_score' => $this->maximumScore(),
            ],
            'feature_categories' => $featureCategories,
            'control_categories' => $controlCategories,
            'report_dimensions' => $this->repository->reportDimensions(),
            'recent_assessments' => $this->repository->recentCaseAssessments(),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function assessCase(array $input): array
    {
        $context = $this->context($input);
        $selectedControls = $this->normalizeIds($input['controls'] ?? []);
        $selectedMethods = $this->normalizeIds($input['methods'] ?? []);
        $controlsById = $this->repository->controlsById();
        $methodsById = $this->repository->methodsById();

        $selectedControls = array_values(array_intersect($selectedControls, array_keys($controlsById)));
        $selectedMethods = array_values(array_intersect($selectedMethods, array_keys($methodsById)));

        $earned = 0;
        foreach ($selectedControls as $controlId) {
            $earned += (int) $controlsById[$controlId]['weight'];
        }

        $methodBonus = min(10, count($selectedMethods) * 2);
        $baseScore = (int) round(($earned / $this->maximumScore()) * 100);
        $score = max(0, min(100, $baseScore + $methodBonus - $this->contextPenalty($context)));
        $riskProfile = $this->riskProfile($selectedControls, $selectedMethods, $context, $baseScore);
        $riskTier = $this->riskTier($riskProfile);
        $readiness = $this->readiness($score, $riskTier);
        $methodPlan = $this->methodCompare(array_merge($context, [
            'selected_features' => array_keys($this->repository->featuresById()),
        ]));
        $recommendations = $this->recommendations($selectedControls, $riskProfile);

        $result = [
            'score' => $score,
            'base_score' => $baseScore,
            'available_weight' => $this->maximumScore(),
            'readiness' => $readiness,
            'risk_tier' => $riskTier,
            'selected_controls' => $selectedControls,
            'selected_methods' => $selectedMethods,
            'context' => $context,
            'risk_profile' => $riskProfile,
            'method_plan' => $methodPlan,
            'recommendations' => $recommendations,
            'report_outline' => $this->reportOutline($context, $methodPlan),
            'next_actions' => $this->nextActions($readiness, $recommendations),
        ];

        $assessmentId = $this->repository->saveCaseAssessment($result);
        if ($assessmentId !== null) {
            $result['assessment_id'] = $assessmentId;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function methodCompare(array $input): array
    {
        $context = $this->context($input);
        $features = $this->normalizeIds($input['selected_features'] ?? []);
        $featuresById = $this->repository->featuresById();
        if ($features === []) {
            $features = array_keys($featuresById);
        }
        $features = array_values(array_intersect($features, array_keys($featuresById)));

        $ranked = [];
        foreach ($this->repository->acquisitionMethods() as $method) {
            $scores = $method['feature_scores'];
            $weighted = 0;
            $weightTotal = 0;

            foreach ($features as $featureId) {
                $featureWeight = (int) $featuresById[$featureId]['weight'];
                $weighted += (($scores[$featureId] ?? 0) * $featureWeight);
                $weightTotal += $featureWeight;
            }

            $score = $weightTotal > 0 ? (int) round($weighted / $weightTotal) : 0;
            $score += $this->methodContextAdjustment((string) $method['id'], $context);
            $score = max(0, min(100, $score));

            $ranked[] = [
                'id' => (string) $method['id'],
                'name' => (string) $method['name'],
                'score' => $score,
                'coverage_hint' => (float) $method['coverage_hint'],
                'access_level' => (string) $method['access_level'],
                'strengths' => $method['strengths'],
                'limitations' => $method['limitations'],
                'role' => $this->methodRole($score),
            ];
        }

        usort($ranked, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return [
            'context' => $context,
            'selected_features' => $features,
            'ranked_methods' => $ranked,
            'recommended_sequence' => array_values(array_map(static fn (array $method): string => $method['name'], array_slice($ranked, 0, 4))),
            'coverage_by_feature' => $this->coverageByFeature($ranked, $features),
            'evidence_gaps' => $this->evidenceGaps($ranked, $features),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function wipingEvaluation(array $input): array
    {
        $appName = $this->cleanText($input['app_name'] ?? 'Android wiping application');
        $declaredClaim = $this->bool($input['declared_claim'] ?? true);
        $overwriteObserved = $this->bool($input['overwrite_observed'] ?? false);
        $standardsMatch = $this->bool($input['standards_match'] ?? false);
        $recoverableContent = $this->bool($input['recoverable_content'] ?? false);
        $partialRecovery = $this->bool($input['partial_recovery'] ?? false);
        $executionArtifacts = $this->bool($input['execution_artifacts'] ?? true);
        $appInternalArtifacts = $this->bool($input['app_internal_artifacts'] ?? true);
        $osArtifacts = $this->bool($input['os_artifacts'] ?? true);
        $timelineConsistent = $this->bool($input['timeline_consistent'] ?? true);

        $risk = 20;
        $risk += $declaredClaim && !$overwriteObserved ? 25 : 0;
        $risk += !$standardsMatch ? 15 : 0;
        $risk += $recoverableContent ? 30 : 0;
        $risk += $partialRecovery ? 18 : 0;
        $risk += !$timelineConsistent ? 12 : 0;
        $risk += ($executionArtifacts || $appInternalArtifacts || $osArtifacts) ? 10 : 0;
        $risk = max(0, min(100, $risk));

        $classification = $this->wipingClassification(
            $declaredClaim,
            $overwriteObserved,
            $standardsMatch,
            $recoverableContent,
            $partialRecovery,
            $executionArtifacts || $appInternalArtifacts || $osArtifacts
        );

        $result = [
            'app_name' => $appName,
            'classification' => $classification,
            'risk_score' => $risk,
            'risk_tier' => $this->tierFromScore($risk),
            'standards_status' => $standardsMatch ? 'Aligned or documented' : 'Not demonstrated',
            'recoverability_status' => $recoverableContent
                ? 'Content recoverable'
                : ($partialRecovery ? 'Partial content or metadata recoverable' : 'Content not recovered in submitted test'),
            'residual_artifacts' => [
                'execution_artifacts' => $executionArtifacts,
                'app_internal_artifacts' => $appInternalArtifacts,
                'os_artifacts' => $osArtifacts,
                'timeline_consistent' => $timelineConsistent,
            ],
            'recommended_tests' => $this->wipingRecommendedTests($recoverableContent, $partialRecovery, $executionArtifacts, $appInternalArtifacts, $osArtifacts),
            'report_language' => $this->wipingReportLanguage($classification, $risk),
        ];

        $evaluationId = $this->repository->saveWipingEvaluation($result);
        if ($evaluationId !== null) {
            $result['evaluation_id'] = $evaluationId;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function hashLedger(array $input): array
    {
        $caseName = $this->cleanText($input['case_name'] ?? 'Android evidence manifest');
        $manifest = $this->normalizeManifest($input['manifest'] ?? []);
        $leaves = [];

        foreach ($manifest as $path => $hash) {
            $leaves[] = [
                'path' => $path,
                'sha256' => $hash,
                'leaf_hash' => hash('sha256', $path . "\0" . $hash),
            ];
        }

        $root = $this->merkleRoot(array_column($leaves, 'leaf_hash'));
        $result = [
            'case_name' => $caseName,
            'item_count' => count($leaves),
            'merkle_root' => $root,
            'leaves' => $leaves,
            'integrity_notes' => $this->ledgerNotes(count($leaves), $root),
        ];

        $ledgerId = $this->repository->saveHashLedger($result);
        if ($ledgerId !== null) {
            $result['ledger_id'] = $ledgerId;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function commandWorkbench(array $input): array
    {
        $context = $this->context($input);
        $signals = $this->workbenchSignals($input, $context);
        $methodPlan = $this->methodCompare(array_merge($context, [
            'deleted_data_needed' => $signals['deleted_data_needed'],
            'cloud_relevant' => $signals['cloud_relevant'],
            'malware_suspected' => $signals['malware_suspected'],
            'memory_needed' => $signals['memory_needed'],
            'wiping_suspected' => $signals['wiping_suspected'],
            'selected_features' => array_keys($this->repository->featuresById()),
        ]));

        $priorityStack = [];
        foreach (array_slice($methodPlan['ranked_methods'], 0, 5) as $rank => $method) {
            $priorityStack[] = [
                'rank' => $rank + 1,
                'id' => $method['id'],
                'name' => $method['name'],
                'role' => $method['role'],
                'score' => $method['score'],
                'why' => $this->methodWhy((string) $method['id'], $signals),
            ];
        }

        $urgencyScore = $this->urgencyScore($signals);
        $missionProfile = $this->missionProfile($signals);
        $result = [
            'scenario_name' => $this->cleanText($input['scenario_name'] ?? 'Advanced Android investigation'),
            'mission_profile' => $missionProfile,
            'urgency_score' => $urgencyScore,
            'urgency_tier' => $this->tierFromScore($urgencyScore),
            'signals' => $signals,
            'priority_stack' => $priorityStack,
            'operational_lanes' => $this->operationalLanes($signals),
            'evidence_constellation' => $this->evidenceConstellation($signals),
            'decision_cards' => $this->decisionCards($signals, $priorityStack),
            'validation_backlog' => $this->validationBacklog($signals),
            'analyst_brief' => $this->analystBrief($missionProfile, $urgencyScore, $priorityStack),
        ];

        $workbenchId = $this->repository->saveWorkbenchRun($result);
        if ($workbenchId !== null) {
            $result['workbench_id'] = $workbenchId;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function timelineFusion(array $input): array
    {
        $caseName = $this->cleanText($input['case_name'] ?? 'Android timeline fusion');
        $events = $this->normalizeTimelineEvents($input['events'] ?? $this->sampleTimelineEvents());
        $anomalies = $this->timelineAnomalies($events);
        $sourceMap = $this->sourceMap($events);
        $confidenceScore = $this->timelineConfidence($events, $anomalies);

        $result = [
            'case_name' => $caseName,
            'event_count' => count($events),
            'source_count' => count($sourceMap),
            'confidence_score' => $confidenceScore,
            'confidence_tier' => $this->confidenceTier($confidenceScore),
            'events' => $events,
            'source_map' => $sourceMap,
            'clusters' => $this->timelineClusters($events),
            'anchors' => $this->timelineAnchors($events),
            'anomalies' => $anomalies,
            'reconstruction_steps' => $this->reconstructionSteps($events, $anomalies),
        ];

        $timelineId = $this->repository->saveTimelineFusion($result);
        if ($timelineId !== null) {
            $result['timeline_id'] = $timelineId;
        }

        return $result;
    }

    private function maximumScore(): int
    {
        return array_sum(array_map(
            static fn (array $control): int => (int) $control['weight'],
            $this->repository->forensicControls()
        ));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function context(array $input): array
    {
        return [
            'case_name' => $this->cleanText($input['case_name'] ?? 'Android forensic case'),
            'device_model' => $this->cleanText($input['device_model'] ?? 'Android device'),
            'android_version' => $this->cleanText($input['android_version'] ?? 'Unknown'),
            'locked_device' => $this->bool($input['locked_device'] ?? false),
            'deleted_data_needed' => $this->bool($input['deleted_data_needed'] ?? false),
            'cloud_relevant' => $this->bool($input['cloud_relevant'] ?? true),
            'malware_suspected' => $this->bool($input['malware_suspected'] ?? false),
            'memory_needed' => $this->bool($input['memory_needed'] ?? false),
            'wiping_suspected' => $this->bool($input['wiping_suspected'] ?? false),
            'court_report' => $this->bool($input['court_report'] ?? true),
            'privacy_sensitive' => $this->bool($input['privacy_sensitive'] ?? true),
        ];
    }

    private function cleanText(mixed $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return substr($value, 0, 180);
    }

    private function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, bool>
     */
    private function workbenchSignals(array $input, array $context): array
    {
        return [
            'locked_device' => $context['locked_device'],
            'unlock_available' => $this->bool($input['unlock_available'] ?? false),
            'deleted_data_needed' => $context['deleted_data_needed'],
            'cloud_relevant' => $context['cloud_relevant'],
            'malware_suspected' => $context['malware_suspected'],
            'memory_needed' => $context['memory_needed'],
            'wiping_suspected' => $context['wiping_suspected'],
            'privacy_sensitive' => $context['privacy_sensitive'],
            'court_report' => $context['court_report'],
            'active_network' => $this->bool($input['active_network'] ?? true),
            'time_sensitive' => $this->bool($input['time_sensitive'] ?? true),
            'native_libraries_present' => $this->bool($input['native_libraries_present'] ?? true),
            'anti_emulator_indicators' => $this->bool($input['anti_emulator_indicators'] ?? false),
            'root_possible' => $this->bool($input['root_possible'] ?? false),
            'cloud_tokens_present' => $this->bool($input['cloud_tokens_present'] ?? true),
            'e2ee_apps_present' => $this->bool($input['e2ee_apps_present'] ?? true),
            'external_storage_present' => $this->bool($input['external_storage_present'] ?? false),
            'recent_user_activity' => $this->bool($input['recent_user_activity'] ?? true),
        ];
    }

    /**
     * @param array<string, bool> $signals
     */
    private function urgencyScore(array $signals): int
    {
        $score = 18;
        $score += $signals['time_sensitive'] ? 16 : 0;
        $score += $signals['active_network'] ? 12 : 0;
        $score += $signals['wiping_suspected'] ? 16 : 0;
        $score += $signals['malware_suspected'] ? 13 : 0;
        $score += $signals['memory_needed'] ? 12 : 0;
        $score += $signals['locked_device'] && !$signals['unlock_available'] ? 9 : 0;
        $score += $signals['recent_user_activity'] ? 6 : 0;
        $score += $signals['court_report'] ? 5 : 0;

        return max(0, min(100, $score));
    }

    /**
     * @param array<string, bool> $signals
     */
    private function missionProfile(array $signals): string
    {
        if ($signals['memory_needed'] && $signals['malware_suspected']) {
            return 'Volatile-first stealth investigation';
        }
        if ($signals['wiping_suspected'] && $signals['deleted_data_needed']) {
            return 'Anti-forensics recovery investigation';
        }
        if ($signals['locked_device'] && $signals['cloud_relevant']) {
            return 'Cloud-assisted locked-device investigation';
        }
        if ($signals['e2ee_apps_present'] && $signals['recent_user_activity']) {
            return 'Application correlation and live-state preservation';
        }

        return 'Layered Android evidence reconstruction';
    }

    /**
     * @param array<string, bool> $signals
     * @return array<int, array<string, mixed>>
     */
    private function operationalLanes(array $signals): array
    {
        return [
            [
                'lane' => 'Preserve',
                'tempo' => $signals['time_sensitive'] || $signals['active_network'] ? 'Immediate' : 'Standard',
                'objective' => 'Stabilize power, radios, lock state, cloud sync, screen state, and external media before evidence drift.',
                'controls' => ['legal-authority', 'device-isolation', 'photographic-record', 'chain-of-custody'],
            ],
            [
                'lane' => 'Acquire',
                'tempo' => $signals['deleted_data_needed'] || $signals['locked_device'] ? 'High' : 'Standard',
                'objective' => 'Select logical, file-system, physical, cloud, and memory routes based on feasibility and expected evidence yield.',
                'controls' => ['method-selection-rationale', 'tool-version-record', 'tool-coverage-matrix'],
            ],
            [
                'lane' => 'Decode',
                'tempo' => $signals['e2ee_apps_present'] || $signals['cloud_tokens_present'] ? 'High' : 'Standard',
                'objective' => 'Parse databases, application storage, synchronized records, media stores, and token-bearing artifacts with validation.',
                'controls' => ['independent-verification', 'privacy-minimization', 'confidence-labels'],
            ],
            [
                'lane' => 'Reverse',
                'tempo' => $signals['malware_suspected'] || $signals['native_libraries_present'] ? 'High' : 'Targeted',
                'objective' => 'Review APK structure, native libraries, anti-analysis behavior, permissions, runtime calls, and file-write behavior.',
                'controls' => ['static-malware-review', 'dynamic-malware-review', 'native-code-risk', 'anti-analysis-checks'],
            ],
            [
                'lane' => 'Recover',
                'tempo' => $signals['wiping_suspected'] || $signals['deleted_data_needed'] ? 'High' : 'Targeted',
                'objective' => 'Separate deleted content recovery, metadata recovery, overwrite evidence, and residual execution traces.',
                'controls' => ['wiping-claim-review', 'recoverability-test', 'standards-review'],
            ],
            [
                'lane' => 'Report',
                'tempo' => $signals['court_report'] ? 'High' : 'Standard',
                'objective' => 'Produce a limitations-aware report with hash manifests, confidence labels, timeline anchors, and reproducible appendices.',
                'controls' => ['limitations-statement', 'peer-review', 'reproducible-appendix', 'audit-retention'],
            ],
        ];
    }

    /**
     * @param array<string, bool> $signals
     * @return array<int, array<string, mixed>>
     */
    private function evidenceConstellation(array $signals): array
    {
        $roles = [
            'device-identity' => 'Anchor',
            'installed-apps' => $signals['malware_suspected'] ? 'Suspect Surface' : 'Context',
            'contacts-accounts' => 'Identity Link',
            'messages-notifications' => $signals['e2ee_apps_present'] ? 'Volatile Context' : 'Communication',
            'browser-app-data' => 'Application State',
            'media-metadata' => 'Timeline Support',
            'deleted-unallocated' => $signals['deleted_data_needed'] ? 'Recovery Critical' : 'Validation',
            'malware-indicators' => $signals['malware_suspected'] ? 'Primary Threat' : 'Screening',
            'cloud-sync-artifacts' => $signals['cloud_relevant'] ? 'External Corroboration' : 'Optional',
        ];

        $constellation = [];
        foreach ($this->repository->evidenceFeatures() as $feature) {
            $id = (string) $feature['id'];
            $constellation[] = [
                'id' => $id,
                'name' => (string) $feature['name'],
                'role' => $roles[$id] ?? 'Supporting',
                'criticality' => $this->featureCriticality($id, $signals),
                'validation' => $this->featureValidation($id, $signals),
            ];
        }

        usort($constellation, static fn (array $left, array $right): int => $right['criticality'] <=> $left['criticality']);

        return $constellation;
    }

    /**
     * @param array<string, bool> $signals
     */
    private function featureCriticality(string $featureId, array $signals): int
    {
        $score = match ($featureId) {
            'device-identity' => 78,
            'installed-apps' => 66,
            'contacts-accounts' => 58,
            'messages-notifications' => 62,
            'browser-app-data' => 70,
            'media-metadata' => 56,
            'deleted-unallocated' => 50,
            'malware-indicators' => 52,
            'cloud-sync-artifacts' => 55,
            default => 50,
        };

        $score += $featureId === 'deleted-unallocated' && $signals['deleted_data_needed'] ? 30 : 0;
        $score += $featureId === 'malware-indicators' && $signals['malware_suspected'] ? 32 : 0;
        $score += $featureId === 'cloud-sync-artifacts' && $signals['cloud_relevant'] ? 28 : 0;
        $score += $featureId === 'messages-notifications' && $signals['e2ee_apps_present'] ? 18 : 0;
        $score += $featureId === 'browser-app-data' && $signals['wiping_suspected'] ? 18 : 0;

        return max(0, min(100, $score));
    }

    /**
     * @param array<string, bool> $signals
     */
    private function featureValidation(string $featureId, array $signals): string
    {
        return match ($featureId) {
            'device-identity' => 'Correlate settings, build properties, extraction metadata, SIM/account records, and photographs.',
            'installed-apps' => 'Compare package inventory, signing certificates, install times, permissions, APK hashes, and tool output.',
            'contacts-accounts' => 'Validate account links across device records, cloud exports, app databases, and contact providers.',
            'messages-notifications' => $signals['e2ee_apps_present']
                ? 'Preserve notification, database, attachment, backup, and screen-state records before live context is lost.'
                : 'Correlate message databases, notifications, backups, and timestamps across parsers.',
            'browser-app-data' => 'Review SQLite, WebView, cache, preferences, tokens, media-store references, and runtime traces.',
            'media-metadata' => 'Validate EXIF, thumbnails, media-store entries, filesystem timestamps, and recovered previews.',
            'deleted-unallocated' => 'Use physical or filesystem review when feasible, then confirm recoverability with independent tools.',
            'malware-indicators' => 'Pair static APK and native-code review with dynamic execution, network capture, and memory triage.',
            'cloud-sync-artifacts' => 'Confirm authority, export source, backup timeline, account state, and remote-deletion risk.',
            default => 'Apply independent validation and confidence labeling.',
        };
    }

    /**
     * @param array<string, bool> $signals
     * @param array<int, array<string, mixed>> $priorityStack
     * @return array<int, array<string, string>>
     */
    private function decisionCards(array $signals, array $priorityStack): array
    {
        $firstMethod = $priorityStack[0]['name'] ?? 'Method comparison';
        $cards = [
            [
                'title' => 'First Move',
                'body' => $signals['time_sensitive']
                    ? 'Preserve live state, network exposure, lock status, and volatile traces before standard extraction.'
                    : 'Confirm authority, scope, identifiers, and baseline photographs before acquisition.',
            ],
            [
                'title' => 'Lead Method',
                'body' => 'Use ' . $firstMethod . ' as the lead acquisition or analysis lane, then validate critical artifacts independently.',
            ],
            [
                'title' => 'Report Posture',
                'body' => $signals['court_report']
                    ? 'Prepare a limitations-first report with tool versions, hashes, confidence labels, and a reproducible appendix.'
                    : 'Prepare an operational report with evidence gaps, next actions, and validation status.',
            ],
        ];

        if ($signals['wiping_suspected']) {
            $cards[] = [
                'title' => 'Anti-Forensics Lens',
                'body' => 'Separate wiping claims from overwrite evidence, content recovery, metadata recovery, and residual execution traces.',
            ];
        }

        if ($signals['malware_suspected']) {
            $cards[] = [
                'title' => 'Stealth Lens',
                'body' => 'Combine static APK review, runtime monitoring, native-code review, and memory triage before concluding behavior.',
            ];
        }

        return $cards;
    }

    /**
     * @param array<string, bool> $signals
     * @return array<int, string>
     */
    private function validationBacklog(array $signals): array
    {
        $items = [
            'Record legal authority, device identifiers, tool versions, extraction mode, and hash manifests.',
            'Cross-validate high-value artifacts with a second parser or manual SQLite/file review.',
            'Create a confidence-labeled timeline with source, timestamp basis, and validation notes.',
        ];

        if ($signals['wiping_suspected']) {
            $items[] = 'Test wiping claims with static review, runtime writes, recoverability attempts, and residual artifact analysis.';
        }
        if ($signals['memory_needed']) {
            $items[] = 'Decide whether process memory, full memory, or emulator reproduction is feasible and document constraints.';
        }
        if ($signals['cloud_relevant']) {
            $items[] = 'Preserve cloud authority, export records, account state, MFA constraints, and sync timeline.';
        }
        if ($signals['native_libraries_present']) {
            $items[] = 'Review native libraries for exploitability indicators, unsafe calls, packed code, and memory exposure.';
        }

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $priorityStack
     */
    private function analystBrief(string $missionProfile, int $urgencyScore, array $priorityStack): string
    {
        $lead = $priorityStack[0]['name'] ?? 'method comparison';
        return $missionProfile . ' with urgency ' . $urgencyScore . '/100. Lead with '
            . $lead . ', preserve volatile context early, and treat validation gaps as reportable limitations.';
    }

    /**
     * @param array<string, bool> $signals
     */
    private function methodWhy(string $methodId, array $signals): string
    {
        return match ($methodId) {
            'physical-acquisition' => $signals['deleted_data_needed']
                ? 'Highest value for deleted and unallocated evidence when device state and authority allow.'
                : 'Useful for completeness, but feasibility depends on encryption and device support.',
            'cloud-acquisition' => $signals['locked_device']
                ? 'Strong fallback when device access is constrained and authorized cloud records are in scope.'
                : 'Corroborates account state, backups, sync timelines, and remote activity.',
            'memory-acquisition' => $signals['memory_needed'] || $signals['malware_suspected']
                ? 'Prioritizes volatile secrets, stealth behavior, process state, and malware indicators.'
                : 'Targeted option for volatile evidence questions.',
            'app-static-reverse-engineering' => 'Clarifies APK claims, permissions, bytecode, native libraries, and file-wiping implementation.',
            'app-dynamic-analysis' => 'Validates runtime file writes, network behavior, permissions, services, and anti-analysis responses.',
            'file-system-acquisition' => 'Strengthens app database, cache, preferences, and filesystem artifact coverage.',
            'logical-acquisition' => 'Efficient baseline for accessible records and repeatable parser output.',
            'emulator-dynamic-analysis' => 'Supports controlled reproduction while accounting for anti-emulator behavior.',
            default => 'Provides targeted context but should be corroborated by stronger evidence sources.',
        };
    }

    /**
     * @param mixed $ids
     * @return array<int, string>
     */
    private function normalizeIds(mixed $ids): array
    {
        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)));
        }

        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): string => preg_replace('/[^a-z0-9-]/', '', strtolower((string) $id)) ?? '',
            $ids
        ))));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function contextPenalty(array $context): int
    {
        $penalty = 0;
        $penalty += $context['locked_device'] ? 6 : 0;
        $penalty += $context['deleted_data_needed'] ? 6 : 0;
        $penalty += $context['malware_suspected'] ? 6 : 0;
        $penalty += $context['memory_needed'] ? 5 : 0;
        $penalty += $context['wiping_suspected'] ? 6 : 0;
        $penalty += $context['court_report'] ? 4 : 0;
        $penalty += $context['privacy_sensitive'] ? 3 : 0;

        return $penalty;
    }

    /**
     * @param array<int, string> $selectedControls
     * @param array<int, string> $selectedMethods
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function riskProfile(array $selectedControls, array $selectedMethods, array $context, int $baseScore): array
    {
        $areas = [
            [
                'id' => 'method-coverage',
                'name' => 'Acquisition method coverage',
                'severity' => 76,
                'controls' => ['method-selection-rationale', 'tool-coverage-matrix', 'independent-verification'],
                'methods' => ['logical-acquisition', 'file-system-acquisition', 'physical-acquisition', 'cloud-acquisition'],
                'context_bonus' => $context['deleted_data_needed'] ? 16 : 0,
            ],
            [
                'id' => 'anti-forensics',
                'name' => 'Anti-forensics and wiping uncertainty',
                'severity' => 78,
                'controls' => ['wiping-claim-review', 'recoverability-test', 'standards-review', 'timeline-correlation'],
                'methods' => ['app-static-reverse-engineering', 'app-dynamic-analysis', 'physical-acquisition'],
                'context_bonus' => $context['wiping_suspected'] ? 20 : 0,
            ],
            [
                'id' => 'memory-stealth',
                'name' => 'Memory, stealth, and native-code exposure',
                'severity' => 74,
                'controls' => ['memory-capture-plan', 'native-code-risk', 'anti-analysis-checks', 'dynamic-malware-review'],
                'methods' => ['memory-acquisition', 'app-static-reverse-engineering', 'app-dynamic-analysis'],
                'context_bonus' => ($context['memory_needed'] || $context['malware_suspected']) ? 18 : 0,
            ],
            [
                'id' => 'integrity',
                'name' => 'Evidence integrity and repeatability',
                'severity' => 72,
                'controls' => ['hash-manifest', 'merkle-root', 'tool-version-record', 'chain-of-custody'],
                'methods' => [],
                'context_bonus' => $context['court_report'] ? 12 : 0,
            ],
            [
                'id' => 'privacy-admissibility',
                'name' => 'Privacy, scope, and admissibility',
                'severity' => 70,
                'controls' => ['legal-authority', 'case-scope', 'privacy-minimization', 'limitations-statement', 'peer-review'],
                'methods' => [],
                'context_bonus' => $context['privacy_sensitive'] ? 12 : 0,
            ],
            [
                'id' => 'cloud-lock-state',
                'name' => 'Cloud dependency and lock-state uncertainty',
                'severity' => 66,
                'controls' => ['device-isolation', 'cloud-authority', 'method-selection-rationale'],
                'methods' => ['cloud-acquisition', 'logical-acquisition'],
                'context_bonus' => ($context['locked_device'] || $context['cloud_relevant']) ? 12 : 0,
            ],
        ];

        $profile = [];
        foreach ($areas as $area) {
            $coveredControls = count(array_intersect($selectedControls, $area['controls']));
            $coveredMethods = count(array_intersect($selectedMethods, $area['methods']));
            $residual = (int) $area['severity']
                + (int) $area['context_bonus']
                - ($coveredControls * 16)
                - ($coveredMethods * 10)
                - min(12, (int) round($baseScore * 0.1));

            $residual = max(5, min(100, $residual));
            $profile[] = [
                'id' => (string) $area['id'],
                'name' => (string) $area['name'],
                'residual_score' => $residual,
                'residual_tier' => $this->tierFromScore($residual),
                'covered_controls' => $coveredControls,
                'required_controls' => count($area['controls']),
                'covered_methods' => $coveredMethods,
                'required_methods' => count($area['methods']),
                'controls' => $area['controls'],
            ];
        }

        usort($profile, static fn (array $left, array $right): int => $right['residual_score'] <=> $left['residual_score']);

        return $profile;
    }

    /**
     * @param array<int, array<string, mixed>> $profile
     */
    private function riskTier(array $profile): string
    {
        return $this->tierFromScore((int) max(array_column($profile, 'residual_score')));
    }

    private function tierFromScore(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Critical',
            $score >= 70 => 'High',
            $score >= 45 => 'Medium',
            default => 'Low',
        };
    }

    private function readiness(int $score, string $riskTier): string
    {
        if ($score >= 90 && $riskTier === 'Low') {
            return 'Court Ready';
        }
        if ($score >= 74 && in_array($riskTier, ['Low', 'Medium'], true)) {
            return 'Lab Defensible';
        }
        if ($score >= 48) {
            return 'Review Required';
        }

        return 'Not Defensible';
    }

    /**
     * @param array<int, string> $selectedControls
     * @param array<int, array<string, mixed>> $riskProfile
     * @return array<int, array<string, mixed>>
     */
    private function recommendations(array $selectedControls, array $riskProfile): array
    {
        $controlsById = $this->repository->controlsById();
        $ranked = [];

        foreach (array_slice($riskProfile, 0, 4) as $area) {
            foreach ($area['controls'] as $controlId) {
                if (!in_array($controlId, $selectedControls, true) && isset($controlsById[$controlId])) {
                    $ranked[$controlId] = ($ranked[$controlId] ?? 0) + (int) $area['residual_score'];
                }
            }
        }

        foreach ($controlsById as $controlId => $control) {
            if (!in_array($controlId, $selectedControls, true)) {
                $ranked[$controlId] = ($ranked[$controlId] ?? 0) + (int) $control['weight'];
            }
        }

        arsort($ranked);
        $recommendations = [];
        foreach (array_slice(array_keys($ranked), 0, 8) as $controlId) {
            $control = $controlsById[$controlId];
            $recommendations[] = [
                'id' => $controlId,
                'name' => (string) $control['name'],
                'category' => (string) $control['category'],
                'weight' => (int) $control['weight'],
                'description' => (string) $control['description'],
            ];
        }

        return $recommendations;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function methodContextAdjustment(string $methodId, array $context): int
    {
        $adjustment = 0;
        $adjustment += $context['locked_device'] && $methodId === 'cloud-acquisition' ? 16 : 0;
        $adjustment -= $context['locked_device'] && in_array($methodId, ['file-system-acquisition', 'physical-acquisition', 'memory-acquisition'], true) ? 10 : 0;
        $adjustment += $context['deleted_data_needed'] && $methodId === 'physical-acquisition' ? 18 : 0;
        $adjustment += $context['deleted_data_needed'] && $methodId === 'file-system-acquisition' ? 6 : 0;
        $adjustment -= $context['deleted_data_needed'] && $methodId === 'manual-inspection' ? 14 : 0;
        $adjustment += $context['cloud_relevant'] && $methodId === 'cloud-acquisition' ? 14 : 0;
        $adjustment += $context['malware_suspected'] && in_array($methodId, ['app-static-reverse-engineering', 'app-dynamic-analysis', 'memory-acquisition', 'emulator-dynamic-analysis'], true) ? 16 : 0;
        $adjustment += $context['memory_needed'] && $methodId === 'memory-acquisition' ? 22 : 0;
        $adjustment += $context['wiping_suspected'] && in_array($methodId, ['app-static-reverse-engineering', 'app-dynamic-analysis', 'physical-acquisition'], true) ? 16 : 0;
        $adjustment += $context['court_report'] && in_array($methodId, ['logical-acquisition', 'file-system-acquisition', 'physical-acquisition'], true) ? 5 : 0;

        return $adjustment;
    }

    private function methodRole(int $score): string
    {
        if ($score >= 82) {
            return 'Primary';
        }
        if ($score >= 65) {
            return 'Supporting';
        }
        if ($score >= 45) {
            return 'Targeted';
        }

        return 'Limited';
    }

    /**
     * @param array<int, array<string, mixed>> $ranked
     * @param array<int, string> $features
     * @return array<int, array<string, mixed>>
     */
    private function coverageByFeature(array $ranked, array $features): array
    {
        $methodsById = $this->repository->methodsById();
        $featuresById = $this->repository->featuresById();
        $coverage = [];

        foreach ($features as $featureId) {
            $best = null;
            foreach ($ranked as $rankedMethod) {
                $method = $methodsById[$rankedMethod['id']];
                $score = (int) ($method['feature_scores'][$featureId] ?? 0);
                if ($best === null || $score > $best['score']) {
                    $best = [
                        'method' => (string) $method['name'],
                        'score' => $score,
                    ];
                }
            }

            $coverage[] = [
                'feature_id' => $featureId,
                'feature' => (string) $featuresById[$featureId]['name'],
                'best_method' => (string) $best['method'],
                'best_score' => (int) $best['score'],
            ];
        }

        usort($coverage, static fn (array $left, array $right): int => $left['best_score'] <=> $right['best_score']);

        return $coverage;
    }

    /**
     * @param array<int, array<string, mixed>> $ranked
     * @param array<int, string> $features
     * @return array<int, string>
     */
    private function evidenceGaps(array $ranked, array $features): array
    {
        $coverage = $this->coverageByFeature($ranked, $features);
        $gaps = [];
        foreach ($coverage as $feature) {
            if ((int) $feature['best_score'] < 65) {
                $gaps[] = $feature['feature'] . ' remains below strong coverage; add a targeted validation step.';
            }
        }

        return $gaps === [] ? ['No major coverage gap was detected for the selected evidence features.'] : $gaps;
    }

    private function wipingClassification(
        bool $declaredClaim,
        bool $overwriteObserved,
        bool $standardsMatch,
        bool $recoverableContent,
        bool $partialRecovery,
        bool $hasResidualArtifacts
    ): string {
        if ($declaredClaim && !$overwriteObserved && ($recoverableContent || $partialRecovery)) {
            return 'Claim mismatch: deletion behavior with recoverable evidence';
        }
        if ($overwriteObserved && !$standardsMatch) {
            return 'Non-standard wiping behavior requiring validation';
        }
        if ($overwriteObserved && $standardsMatch && !$recoverableContent && $hasResidualArtifacts) {
            return 'Content wiping appears effective while execution traces remain';
        }
        if (!$declaredClaim && ($recoverableContent || $partialRecovery)) {
            return 'Deletion event with recoverable residual evidence';
        }
        if ($overwriteObserved && $standardsMatch && !$recoverableContent) {
            return 'Standards-aligned wiping with no submitted content recovery';
        }

        return 'Inconclusive wiping evaluation';
    }

    /**
     * @return array<int, string>
     */
    private function wipingRecommendedTests(
        bool $recoverableContent,
        bool $partialRecovery,
        bool $executionArtifacts,
        bool $appInternalArtifacts,
        bool $osArtifacts
    ): array {
        $tests = [
            'Preserve pre-test and post-test images or exports with SHA-256 manifests.',
            'Perform static APK review for deletion APIs, overwrite routines, native calls, and filesystem handling.',
            'Run dynamic review in a contained lab and capture file, process, network, and timestamp activity.',
        ];

        if ($recoverableContent || $partialRecovery) {
            $tests[] = 'Run independent recovery with at least two tools and record recovered paths, metadata, and confidence.';
        }
        if ($executionArtifacts || $appInternalArtifacts || $osArtifacts) {
            $tests[] = 'Correlate residual app and OS artifacts with the wiping timeline and original file metadata.';
        }

        $tests[] = 'Document standards alignment as a finding only when observed behavior and implementation evidence support it.';

        return $tests;
    }

    private function wipingReportLanguage(string $classification, int $risk): string
    {
        return 'The submitted evidence supports the classification "' . $classification
            . '" with a residual risk score of ' . $risk
            . '/100. Findings should distinguish content recovery from residual execution traces and metadata recovery.';
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $methodPlan
     * @return array<int, string>
     */
    private function reportOutline(array $context, array $methodPlan): array
    {
        $outline = $this->repository->reportDimensions();
        $topMethod = $methodPlan['ranked_methods'][0]['name'] ?? 'Selected method';
        $outline[] = 'Primary acquisition method: ' . $topMethod;

        if ($context['wiping_suspected']) {
            $outline[] = 'File-wiping claim, recoverability, and residual artifact evaluation';
        }
        if ($context['malware_suspected']) {
            $outline[] = 'Static, dynamic, anti-analysis, and malware indicator review';
        }
        if ($context['memory_needed']) {
            $outline[] = 'Memory acquisition feasibility, volatile artifact scope, and validation';
        }

        return $outline;
    }

    /**
     * @param array<int, array<string, mixed>> $recommendations
     * @return array<int, string>
     */
    private function nextActions(string $readiness, array $recommendations): array
    {
        if ($readiness === 'Court Ready') {
            return [
                'Lock the evidence manifest and final report appendix.',
                'Complete peer review and preserve reviewer notes with the case record.',
                'Prepare concise testimony notes covering methods, limitations, and validation.',
            ];
        }

        $actions = [
            'Close the highest residual risk areas before report release.',
            'Strengthen independent validation for high-value artifacts and negative findings.',
        ];

        foreach (array_slice($recommendations, 0, 3) as $recommendation) {
            $actions[] = 'Implement: ' . $recommendation['name'] . '.';
        }

        return $actions;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleTimelineEvents(): array
    {
        return [
            [
                'timestamp' => '2026-05-06T08:11:00+04:00',
                'source' => 'Device OS',
                'artifact' => 'Package manager',
                'description' => 'File-wiping application launch recorded.',
                'confidence' => 'High',
                'hash' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            ],
            [
                'timestamp' => '2026-05-06T08:13:21+04:00',
                'source' => 'Application Data',
                'artifact' => 'Preferences database',
                'description' => 'Deletion job completed according to local app state.',
                'confidence' => 'Medium',
                'hash' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
            ],
            [
                'timestamp' => '2026-05-06T08:14:02+04:00',
                'source' => 'Filesystem',
                'artifact' => 'Media store',
                'description' => 'Residual thumbnail and metadata entry remained after deletion.',
                'confidence' => 'High',
                'hash' => 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
            ],
            [
                'timestamp' => '2026-05-06T08:21:49+04:00',
                'source' => 'Network',
                'artifact' => 'Runtime capture',
                'description' => 'Application contacted telemetry endpoint after wiping workflow.',
                'confidence' => 'Medium',
                'hash' => 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd',
            ],
        ];
    }

    /**
     * @param mixed $events
     * @return array<int, array<string, mixed>>
     */
    private function normalizeTimelineEvents(mixed $events): array
    {
        if (is_string($events)) {
            $decoded = json_decode($events, true);
            $events = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($events) || $events === []) {
            $events = $this->sampleTimelineEvents();
        }

        $normalized = [];
        $index = 0;
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $parsed = $this->parseEventTime($event['timestamp'] ?? '');
            $confidence = $this->confidenceValue($event['confidence'] ?? 50);
            $hash = strtolower(trim((string) ($event['hash'] ?? '')));

            $normalized[] = [
                'original_index' => $index,
                'timestamp' => $parsed['iso'],
                'timestamp_valid' => $parsed['valid'],
                'source' => $this->cleanText($event['source'] ?? 'Unknown source'),
                'artifact' => $this->cleanText($event['artifact'] ?? 'Unspecified artifact'),
                'description' => $this->cleanText($event['description'] ?? 'Event without description'),
                'confidence' => $confidence,
                'confidence_label' => $this->confidenceTier($confidence),
                'hash' => preg_match('/^[a-f0-9]{64}$/', $hash) ? $hash : '',
            ];
            $index++;
        }

        usort($normalized, static function (array $left, array $right): int {
            return strcmp((string) $left['timestamp'], (string) $right['timestamp']);
        });

        return $normalized;
    }

    /**
     * @return array{iso: string, valid: bool}
     */
    private function parseEventTime(mixed $value): array
    {
        $raw = trim((string) $value);
        try {
            $date = new \DateTimeImmutable($raw);
            return [
                'iso' => $date->format(\DateTimeInterface::ATOM),
                'valid' => true,
            ];
        } catch (\Throwable) {
            return [
                'iso' => '1970-01-01T00:00:00+00:00',
                'valid' => false,
            ];
        }
    }

    private function confidenceValue(mixed $value): int
    {
        if (is_numeric($value)) {
            return max(0, min(100, (int) $value));
        }

        return match (strtolower(trim((string) $value))) {
            'confirmed', 'high' => 90,
            'probable', 'medium' => 68,
            'possible', 'low' => 40,
            'unsupported' => 18,
            default => 50,
        };
    }

    private function confidenceTier(int $score): string
    {
        return match (true) {
            $score >= 82 => 'High',
            $score >= 58 => 'Medium',
            $score >= 35 => 'Low',
            default => 'Fragile',
        };
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array<string, int>
     */
    private function sourceMap(array $events): array
    {
        $map = [];
        foreach ($events as $event) {
            $source = (string) $event['source'];
            $map[$source] = ($map[$source] ?? 0) + 1;
        }
        ksort($map);

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array<int, array<string, mixed>>
     */
    private function timelineClusters(array $events): array
    {
        $clusters = [];
        foreach ($events as $event) {
            $hour = substr((string) $event['timestamp'], 0, 13) . ':00';
            if (!isset($clusters[$hour])) {
                $clusters[$hour] = [
                    'window' => $hour,
                    'events' => 0,
                    'sources' => [],
                    'highest_confidence' => 0,
                ];
            }
            $clusters[$hour]['events']++;
            $clusters[$hour]['sources'][(string) $event['source']] = true;
            $clusters[$hour]['highest_confidence'] = max($clusters[$hour]['highest_confidence'], (int) $event['confidence']);
        }

        return array_values(array_map(static function (array $cluster): array {
            $cluster['source_count'] = count($cluster['sources']);
            $cluster['sources'] = array_keys($cluster['sources']);
            return $cluster;
        }, $clusters));
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array<int, array<string, mixed>>
     */
    private function timelineAnchors(array $events): array
    {
        $anchors = array_values(array_filter($events, static fn (array $event): bool => (int) $event['confidence'] >= 82));
        return array_slice($anchors, 0, 6);
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array<int, string>
     */
    private function timelineAnomalies(array $events): array
    {
        $anomalies = [];
        if ($events === []) {
            return ['No timeline events were available for reconstruction.'];
        }

        $originalIndexes = array_column($events, 'original_index');
        $sortedIndexes = $originalIndexes;
        sort($sortedIndexes);
        if ($originalIndexes !== $sortedIndexes) {
            $anomalies[] = 'Submitted events required chronological normalization.';
        }

        $invalid = count(array_filter($events, static fn (array $event): bool => !$event['timestamp_valid']));
        if ($invalid > 0) {
            $anomalies[] = $invalid . ' event timestamps could not be parsed and require examiner review.';
        }

        $lowConfidence = count(array_filter($events, static fn (array $event): bool => (int) $event['confidence'] < 50));
        if ($lowConfidence > 0) {
            $anomalies[] = $lowConfidence . ' low-confidence events should not be used as primary anchors.';
        }

        $sourceCount = count($this->sourceMap($events));
        if ($sourceCount < 3) {
            $anomalies[] = 'Timeline uses fewer than three independent source families.';
        }

        for ($i = 1; $i < count($events); $i++) {
            $previous = new \DateTimeImmutable((string) $events[$i - 1]['timestamp']);
            $current = new \DateTimeImmutable((string) $events[$i]['timestamp']);
            $gap = $current->getTimestamp() - $previous->getTimestamp();
            if ($gap > 14400) {
                $anomalies[] = 'Gap greater than four hours between ' . $events[$i - 1]['artifact'] . ' and ' . $events[$i]['artifact'] . '.';
            }
        }

        $hashes = array_filter(array_column($events, 'hash'));
        if (count($hashes) !== count(array_unique($hashes))) {
            $anomalies[] = 'Duplicate evidence hashes appear in multiple timeline events; confirm whether they reference the same artifact.';
        }

        return $anomalies;
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @param array<int, string> $anomalies
     */
    private function timelineConfidence(array $events, array $anomalies): int
    {
        if ($events === []) {
            return 0;
        }

        $average = (int) round(array_sum(array_column($events, 'confidence')) / count($events));
        $penalty = min(30, count($anomalies) * 6);

        return max(0, min(100, $average - $penalty));
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @param array<int, string> $anomalies
     * @return array<int, string>
     */
    private function reconstructionSteps(array $events, array $anomalies): array
    {
        $steps = [
            'Use high-confidence events as anchors and keep low-confidence events as supporting context.',
            'Normalize timestamps, timezone assumptions, source clocks, and acquisition times before final reporting.',
            'Link every event to an evidence item, parser output, hash, screenshot, or examiner note.',
        ];

        if ($anomalies !== []) {
            $steps[] = 'Resolve anomaly notes before treating the reconstruction as final.';
        }
        if (count($this->sourceMap($events)) >= 3) {
            $steps[] = 'Prioritize cross-source agreement because the timeline includes multiple source families.';
        }

        return $steps;
    }

    /**
     * @param mixed $manifest
     * @return array<string, string>
     */
    private function normalizeManifest(mixed $manifest): array
    {
        if (is_string($manifest)) {
            $decoded = json_decode($manifest, true);
            $manifest = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($manifest)) {
            return [];
        }

        $normalized = [];
        foreach ($manifest as $key => $value) {
            if (is_array($value)) {
                $path = (string) ($value['path'] ?? $value['file'] ?? '');
                $hash = (string) ($value['sha256'] ?? $value['hash'] ?? '');
            } else {
                $path = (string) $key;
                $hash = (string) $value;
            }

            $path = trim(str_replace('\\', '/', $path));
            $hash = strtolower(trim($hash));

            if ($path !== '' && preg_match('/^[a-f0-9]{64}$/', $hash)) {
                $normalized[$path] = $hash;
            }
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param array<int, string> $hashes
     */
    private function merkleRoot(array $hashes): string
    {
        if ($hashes === []) {
            return str_repeat('0', 64);
        }

        sort($hashes);
        while (count($hashes) > 1) {
            $next = [];
            for ($i = 0; $i < count($hashes); $i += 2) {
                $left = $hashes[$i];
                $right = $hashes[$i + 1] ?? $left;
                $next[] = hash('sha256', $left . $right);
            }
            $hashes = $next;
        }

        return $hashes[0];
    }

    /**
     * @return array<int, string>
     */
    private function ledgerNotes(int $count, string $root): array
    {
        if ($count === 0) {
            return ['No valid SHA-256 entries were submitted.'];
        }

        return [
            'The Merkle root ' . $root . ' summarizes the normalized manifest.',
            'Changing a path or SHA-256 value changes the corresponding leaf and root.',
            'Store the root with chain-of-custody records, tool output, and report appendices.',
        ];
    }
}
