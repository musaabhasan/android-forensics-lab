# Testing

The repository includes a compact validation suite focused on behavior that affects trust in the lab output.

## Commands

```bash
php bin/lint.php
php bin/test.php
```

## Coverage

The tests verify:

- PHP syntax across source, public, config, and test scripts.
- Catalog counts and expected research source identifiers.
- Expert audit coverage for Android forensic pain points, including lock-state encryption and parser disagreement.
- Acquisition feasibility ranking, first-hour blockers, and locked before-first-unlock constraints.
- Artifact triage priority behavior for E2EE applications, wiping residuals, cloud context, malware context, and WAL/SHM preservation.
- Tool discrepancy validation, consensus scoring, per-tool matrix generation, and report release gates.
- Report readiness scoring for authority, scope, custody, hashes, tool versions, validation, timeline anchors, limitations, privacy, peer review, and reproducible appendices.
- Command workbench mission profiling, urgency scoring, method stack, operational lanes, and evidence constellation.
- Timeline fusion event normalization, source mapping, clustering, anchors, anomaly detection, and confidence scoring.
- Control and method scoring behavior.
- Strong case readiness with complete controls.
- High-risk case behavior when controls are missing.
- Method ranking when deleted data, cloud records, malware, memory, and file wiping are in scope.
- File-wiping classifications for claim mismatch and standards-aligned cases.
- Deterministic Merkle-style root generation.
- Root changes when a manifest entry changes.
- Migration and seed data contain expected tables, DOI values, and controls.
- Public-facing text does not contain internal tool-production wording.

## Manual Review Checklist

- Visit `/audit`, `/acquisition`, `/artifacts`, `/validation`, `/report-readiness`, `/casework`, `/methods`, `/wiping`, `/timeline`, `/ledger`, and `/research`.
- Submit each form once.
- Confirm API routes return JSON.
- Confirm no stack traces appear when the database is not configured.
- Confirm production `.env` values are not committed.
