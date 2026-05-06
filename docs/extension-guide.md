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

- `assessCase()` for case readiness and report outline
- `methodCompare()` for acquisition rationale
- `wipingEvaluation()` for anti-forensics findings
- `hashLedger()` for evidence manifest integrity

