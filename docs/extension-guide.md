# Extension Guide

The platform is built to be extended by adding structured catalog entries and service rules.

## Add an Acquisition Method

1. Add the method to `config/catalog.php` under `acquisition_methods`.
2. Include `feature_scores` for every evidence feature.
3. Add context adjustments in `ForensicsLabService::methodContextAdjustment()` if the method should react to a special case condition.
4. Add seed data if the method should be available in MySQL reference tables.
5. Add or update tests for ranking behavior.

## Add a Forensic Control

1. Add the control to `config/catalog.php` under `forensic_controls`.
2. Assign a category and weight.
3. Reference it in the risk profile if it mitigates a specific residual-risk area.
4. Add seed data and tests if the control affects scoring expectations.

## Add a Wiping Evaluation Rule

1. Extend `wipingEvaluation()` input normalization.
2. Update `wipingClassification()` for the new decision branch.
3. Add recommended tests in `wipingRecommendedTests()`.
4. Add an assertion in `bin/test.php`.

## Add Reporting Outputs

The result arrays are already suitable for PDF or document generation. A reporting module can consume:

- `expertAudit()` for field pain points and upgrade coverage
- `acquisitionReadiness()` for device-state method feasibility and first-hour planning
- `artifactTriage()` for artifact family priorities and parser risks
- `toolValidation()` for parser discrepancy gates
- `reportReadiness()` for release criteria scoring
- `assessCase()` for case readiness and report outline
- `commandWorkbench()` for scenario mission plans, operational lanes, evidence constellation, and validation backlog
- `methodCompare()` for acquisition rationale
- `timelineFusion()` for event reconstruction, anchors, anomalies, and confidence scoring
- `wipingEvaluation()` for anti-forensics findings
- `hashLedger()` for evidence manifest integrity

## Add a Timeline Source

1. Normalize the source into events containing `timestamp`, `source`, `artifact`, `description`, `confidence`, and optional `hash`.
2. Keep parser-specific fields outside the core event object unless they are needed for reporting.
3. Add source-specific anomaly checks only when the source has reliable timestamp or artifact semantics.
4. Add tests that confirm ordering, confidence, source count, and anomaly behavior.

## Add an Artifact Family

1. Extend `ForensicsLabService::artifactFamilies()` with the family name, category, base score, signal boosts, collection notes, and parser risks.
2. Add a matching signal to `artifactTriage()` when the family should react to case context.
3. Add a test that proves the family is elevated when the relevant signal is enabled.
4. Update documentation if the new family changes the recommended examiner workflow.

## Add an Acquisition Feasibility Rule

1. Update `acquisitionReadiness()` if a new input signal is required.
2. Adjust `acquisitionFeasibilityRows()` when the signal changes method ranking.
3. Add a blocker, preservation note, or caution if the signal affects defensibility.
4. Add an assertion that covers the method-ranking effect and the examiner-facing note.

## Add a Tool Validation Rule

1. Extend `normalizeToolResults()` only when the result payload needs a new defensible field.
2. Update `discrepancyIssue()` or `discrepancyValidationStep()` for the new disagreement type.
3. Add a test with at least two tools and one known discrepancy.
4. Preserve raw tool outputs with the case record before any normalization or reporting transformation.
