<?php

declare(strict_types=1);

namespace AndroidForensicsLab\Repository;

use AndroidForensicsLab\Support\Uuid;
use PDO;
use Throwable;

final class LabRepository
{
    /**
     * @param array<string, mixed> $catalog
     */
    public function __construct(
        private readonly array $catalog,
        private readonly ?PDO $pdo = null
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function platform(): array
    {
        return $this->catalog['platform'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function researchSources(): array
    {
        return $this->catalog['research_sources'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function workflowStages(): array
    {
        return $this->catalog['workflow_stages'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function advancedModules(): array
    {
        return $this->catalog['advanced_modules'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function evidenceFeatures(): array
    {
        return $this->catalog['evidence_features'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function acquisitionMethods(): array
    {
        return $this->catalog['acquisition_methods'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toolProfiles(): array
    {
        return $this->catalog['tool_profiles'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function wipingArtifacts(): array
    {
        return $this->catalog['wiping_artifacts'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forensicControls(): array
    {
        return $this->catalog['forensic_controls'];
    }

    /**
     * @return array<int, string>
     */
    public function reportDimensions(): array
    {
        return $this->catalog['report_dimensions'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function controlsById(): array
    {
        $indexed = [];
        foreach ($this->forensicControls() as $control) {
            $indexed[(string) $control['id']] = $control;
        }

        return $indexed;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function methodsById(): array
    {
        $indexed = [];
        foreach ($this->acquisitionMethods() as $method) {
            $indexed[(string) $method['id']] = $method;
        }

        return $indexed;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function featuresById(): array
    {
        $indexed = [];
        foreach ($this->evidenceFeatures() as $feature) {
            $indexed[(string) $feature['id']] = $feature;
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $assessment
     */
    public function saveCaseAssessment(array $assessment): ?string
    {
        if ($this->pdo === null) {
            return null;
        }

        $id = Uuid::v4();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO case_assessments (
                    id, case_name, device_model, score, readiness, risk_tier,
                    selected_controls, selected_methods, result_payload, created_at
                ) VALUES (
                    :id, :case_name, :device_model, :score, :readiness, :risk_tier,
                    :selected_controls, :selected_methods, :result_payload, NOW()
                )'
            );
            $context = $assessment['context'];
            $statement->execute([
                'id' => $id,
                'case_name' => (string) ($context['case_name'] ?? 'Android forensic case'),
                'device_model' => (string) ($context['device_model'] ?? 'Android device'),
                'score' => (int) $assessment['score'],
                'readiness' => (string) $assessment['readiness'],
                'risk_tier' => (string) $assessment['risk_tier'],
                'selected_controls' => json_encode($assessment['selected_controls'], JSON_THROW_ON_ERROR),
                'selected_methods' => json_encode($assessment['selected_methods'], JSON_THROW_ON_ERROR),
                'result_payload' => json_encode($assessment, JSON_THROW_ON_ERROR),
            ]);

            $this->audit('case_assessment.created', ['assessment_id' => $id, 'score' => $assessment['score']]);

            return $id;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $evaluation
     */
    public function saveWipingEvaluation(array $evaluation): ?string
    {
        if ($this->pdo === null) {
            return null;
        }

        $id = Uuid::v4();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO wiping_evaluations (
                    id, app_name, classification, risk_score, standards_status,
                    recoverability_status, result_payload, created_at
                ) VALUES (
                    :id, :app_name, :classification, :risk_score, :standards_status,
                    :recoverability_status, :result_payload, NOW()
                )'
            );
            $statement->execute([
                'id' => $id,
                'app_name' => (string) $evaluation['app_name'],
                'classification' => (string) $evaluation['classification'],
                'risk_score' => (int) $evaluation['risk_score'],
                'standards_status' => (string) $evaluation['standards_status'],
                'recoverability_status' => (string) $evaluation['recoverability_status'],
                'result_payload' => json_encode($evaluation, JSON_THROW_ON_ERROR),
            ]);

            $this->audit('wiping_evaluation.created', ['evaluation_id' => $id, 'risk_score' => $evaluation['risk_score']]);

            return $id;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $ledger
     */
    public function saveHashLedger(array $ledger): ?string
    {
        if ($this->pdo === null) {
            return null;
        }

        $id = Uuid::v4();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO hash_ledger_runs (
                    id, case_name, item_count, merkle_root, result_payload, created_at
                ) VALUES (
                    :id, :case_name, :item_count, :merkle_root, :result_payload, NOW()
                )'
            );
            $statement->execute([
                'id' => $id,
                'case_name' => (string) $ledger['case_name'],
                'item_count' => (int) $ledger['item_count'],
                'merkle_root' => (string) $ledger['merkle_root'],
                'result_payload' => json_encode($ledger, JSON_THROW_ON_ERROR),
            ]);

            $this->audit('hash_ledger.created', ['ledger_id' => $id, 'item_count' => $ledger['item_count']]);

            return $id;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $workbench
     */
    public function saveWorkbenchRun(array $workbench): ?string
    {
        if ($this->pdo === null) {
            return null;
        }

        $id = Uuid::v4();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO workbench_runs (
                    id, scenario_name, mission_profile, urgency_score,
                    lead_method, result_payload, created_at
                ) VALUES (
                    :id, :scenario_name, :mission_profile, :urgency_score,
                    :lead_method, :result_payload, NOW()
                )'
            );
            $statement->execute([
                'id' => $id,
                'scenario_name' => (string) $workbench['scenario_name'],
                'mission_profile' => (string) $workbench['mission_profile'],
                'urgency_score' => (int) $workbench['urgency_score'],
                'lead_method' => (string) ($workbench['priority_stack'][0]['name'] ?? 'Not ranked'),
                'result_payload' => json_encode($workbench, JSON_THROW_ON_ERROR),
            ]);

            $this->audit('workbench_run.created', ['workbench_id' => $id, 'urgency_score' => $workbench['urgency_score']]);

            return $id;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $timeline
     */
    public function saveTimelineFusion(array $timeline): ?string
    {
        if ($this->pdo === null) {
            return null;
        }

        $id = Uuid::v4();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO timeline_fusions (
                    id, case_name, event_count, source_count, anomaly_count,
                    confidence_score, result_payload, created_at
                ) VALUES (
                    :id, :case_name, :event_count, :source_count, :anomaly_count,
                    :confidence_score, :result_payload, NOW()
                )'
            );
            $statement->execute([
                'id' => $id,
                'case_name' => (string) $timeline['case_name'],
                'event_count' => (int) $timeline['event_count'],
                'source_count' => (int) $timeline['source_count'],
                'anomaly_count' => count($timeline['anomalies']),
                'confidence_score' => (int) $timeline['confidence_score'],
                'result_payload' => json_encode($timeline, JSON_THROW_ON_ERROR),
            ]);

            $this->audit('timeline_fusion.created', ['timeline_id' => $id, 'event_count' => $timeline['event_count']]);

            return $id;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentCaseAssessments(int $limit = 5): array
    {
        if ($this->pdo === null) {
            return [];
        }

        try {
            $statement = $this->pdo->prepare(
                'SELECT id, case_name, device_model, score, readiness, risk_tier, created_at
                 FROM case_assessments
                 ORDER BY created_at DESC
                 LIMIT :limit'
            );
            $statement->bindValue('limit', max(1, min(25, $limit)), PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function audit(string $event, array $payload): void
    {
        if ($this->pdo === null) {
            return;
        }

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO audit_events (id, event_name, payload, created_at)
                 VALUES (:id, :event_name, :payload, NOW())'
            );
            $statement->execute([
                'id' => Uuid::v4(),
                'event_name' => $event,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            ]);
        } catch (Throwable) {
        }
    }
}
