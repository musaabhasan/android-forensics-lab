# Database Model

The MySQL schema is intentionally explicit so the platform can be extended without reverse engineering hidden assumptions.

## Reference Tables

- `research_sources`: formal research references and platform contribution notes.
- `acquisition_methods`: method metadata, strengths, limitations, coverage hints, and feature scores.
- `evidence_features`: nine evidence families used by the comparison engine.
- `tool_profiles`: commercial, open-source, reverse engineering, runtime, memory, and integrity tools.
- `forensic_controls`: weighted controls used by the case readiness engine.

## Operational Tables

- `case_assessments`: submitted case context, selected controls, selected methods, score, readiness, and full result payload.
- `workbench_runs`: scenario name, mission profile, urgency score, lead method, and full command workbench payload.
- `timeline_fusions`: case name, event count, source count, anomaly count, confidence score, and full timeline reconstruction payload.
- `wiping_evaluations`: application name, classification, risk score, standards status, recoverability status, and full result payload.
- `hash_ledger_runs`: case name, manifest count, Merkle-style root, and full result payload.
- `chain_of_custody_events`: custody timeline with chained event-hash fields.
- `audit_events`: application events such as assessment creation, wiping evaluation creation, and ledger creation.

## Extension Patterns

- Add new evidence families in `config/catalog.php` and `evidence_features`.
- Add method scores for every evidence family when a new acquisition method is introduced.
- Keep JSON result payloads for reproducibility while maintaining indexed summary columns for dashboards and reporting.
- Store uploaded evidence outside the web root and link it by evidence identifier rather than raw file path.
