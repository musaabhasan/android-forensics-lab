# Architecture

The platform follows a compact PHP 8 architecture that can run as a simple built-in-server application, an Apache container, or a traditional PHP deployment.

## Layers

- `public/index.php` handles routing, forms, API entry points, and page rendering.
- `src/Service/ForensicsLabService.php` contains expert audit, acquisition readiness, artifact triage, tool validation, report readiness, scoring, command workbench, timeline fusion, method ranking, wiping evaluation, and evidence-ledger logic.
- `src/Repository/LabRepository.php` exposes catalog data and optional MySQL persistence.
- `config/catalog.php` defines research sources, evidence features, acquisition methods, tool profiles, wiping artifacts, workflow stages, and forensic controls.
- `database/migrations` and `database/seeders` provide the MySQL 8 schema and starter reference data.

## Casework Flow

```mermaid
flowchart LR
  A["Intake and Authority"] --> B["Isolation and Preservation"]
  B --> C["Acquisition Selection"]
  C --> D["Tool Validation"]
  D --> E["Anti-Forensics Review"]
  E --> F["Memory and Stealth Triage"]
  F --> G["Correlation and Reporting"]
  G --> H["Review and Retention"]
```

## Scoring Model

Case readiness combines three inputs:

- Control coverage: weighted controls across governance, evidence handling, integrity, acquisition, memory, malware, anti-forensics, analysis, reporting, and operations.
- Method coverage: completed methods such as logical acquisition, physical imaging, memory acquisition, cloud acquisition, and static or dynamic review.
- Case context: locked device, deleted-data need, cloud relevance, suspected malware, volatile evidence need, suspected wiping, court-facing reporting, and privacy sensitivity.

The output includes a readiness score, residual risk tier, priority controls, method sequence, report outline, and next actions.

## Method Comparison

Each acquisition or analysis method is scored against nine evidence features. Context adjustments raise or lower the score when deleted data, cloud evidence, malware behavior, volatile memory, file wiping, lock state, or formal reporting changes the evidence need.

This produces:

- Ranked method list
- Primary, supporting, targeted, or limited role labels
- Best method by evidence feature
- Evidence gap recommendations

## Command Workbench

The command workbench converts scenario signals into an examiner-ready mission plan. It evaluates lock state, unlock availability, deleted-data need, cloud relevance, suspected malware, volatile evidence, wiping suspicion, active network risk, native libraries, encrypted messaging, external storage, recent user activity, and report posture.

Outputs include:

- Mission profile
- Urgency score and tier
- Ranked method stack with rationale
- Operational lanes for preservation, acquisition, decoding, reverse review, recovery, and reporting
- Evidence constellation with feature roles and validation actions
- Decision cards and validation backlog

## Expert Audit and Acquisition Readiness

The expert audit console maps field pain points to lab capabilities. It focuses on modern encryption, Android fragmentation, app database complexity, tool disagreement, cloud and E2EE evidence, anti-forensics, volatile evidence, timeline confidence, privacy, reporting, managed profiles, and external storage.

The acquisition planner treats method selection as a device-state decision. It normalizes Android version, lock state, USB debugging, bootloader state, root feasibility, cloud authority, work profile, file-based encryption, external storage, APK availability, malware suspicion, and wiping suspicion.

Outputs include:

- Ranked acquisition paths with feasibility scores
- Critical blockers for encrypted or locked-device scenarios
- First-hour preservation plan
- Preservation notes and expert cautions

## Artifact Triage

The artifact triage matrix prioritizes Android evidence families that are frequently missed during parser-only review:

- SQLite, Room, WAL, SHM, journal files, and app-private databases
- Shared preferences, protobuf, JSON, configuration files, and app state
- LevelDB, WebView, Chromium storage, cache, cookies, and IndexedDB
- Notifications, usage state, media store, thumbnails, EXIF, and document providers
- Accounts, tokens, keystore context, cloud sync, backups, and E2EE app evidence
- Package records, permissions, APKs, native libraries, runtime traces, wiping residuals, and managed-profile artifacts

The triage output separates collection notes from parser risks so examiners can preserve raw material before transforming it into report tables.

## Tool Validation and Report Readiness

The tool discrepancy validator compares parser results across artifact counts, hashes, confidence scores, and tool names. Disagreement creates a release gate and a validation step, such as raw SQLite/WAL review, media metadata comparison, or static and dynamic APK behavior testing.

The report readiness pack scores whether the case file has:

- Authority and scope
- Chain of custody
- Evidence hashes
- Tool versions
- Validation matrix
- Timeline anchors
- Limitations statement
- Privacy minimization
- Peer review
- Reproducible appendix

The release decision is intentionally conservative: unresolved gaps hold the report for examiner completion.

## Timeline Fusion

Timeline fusion accepts mixed-source Android events as JSON. Events are normalized, sorted, confidence-scored, clustered by time window, and reviewed for anomalies such as low source diversity, invalid timestamps, low confidence, duplicate hashes, chronological normalization, and long gaps.

The result gives examiners a set of high-confidence anchors and reconstruction steps suitable for reporting.

## Evidence Ledger

The ledger accepts a manifest of `path` and `sha256` values. Entries are normalized, sorted, converted to leaf hashes, and folded into a deterministic Merkle-style root. The root is suitable for chain-of-custody checkpoints, report appendices, and peer review.
