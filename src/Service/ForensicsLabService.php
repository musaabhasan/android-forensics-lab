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

