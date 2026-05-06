# Expert Audit

This audit documents the main Android digital forensics pain points addressed by the lab and the platform capabilities that support examiner decision-making.

## Field Pain Points Addressed

- Modern encryption and lock state: before-first-unlock and file-based encryption can block credential-protected storage and app-private evidence.
- Android fragmentation: OEM builds, patch levels, scoped storage, and managed profiles change extraction visibility.
- App database complexity: SQLite companions, Room, WAL, SHM, LevelDB, protobuf, WebView, and cache artifacts require raw-source preservation.
- Tool disagreement: parser counts, timestamps, attachment handling, deleted-state interpretation, and hashes can differ across tools.
- Cloud and E2EE evidence: notifications, backups, linked devices, cloud exports, and token-bearing records may be more important than local extraction alone.
- Anti-forensics and file wiping: content recovery, metadata recovery, execution traces, and overwrite claims must be assessed separately.
- Volatile evidence: runtime behavior, network state, process evidence, malware behavior, and secrets can disappear quickly.
- Timeline confidence: Android evidence timelines combine multiple timestamp sources with different reliability.
- Privacy and scope control: mobile data frequently includes personal information outside the authorized question.
- Defensible reporting: authority, hashes, tool versions, limitations, validation, peer review, and reproducible appendices determine report strength.
- Managed profiles: work-profile containers may require separate authority and collection strategy.
- External storage: removable media and document-provider records can hold evidence outside the main extraction.

## Added Platform Responses

- Expert Audit Console: maps field pain points to covered platform capabilities.
- Acquisition Feasibility Planner: ranks acquisition paths by device state and evidence feasibility.
- Artifact Triage Matrix: prioritizes high-value artifact families and parser risks.
- Tool Discrepancy Validator: compares parser outputs before report language is finalized.
- Report Readiness Pack: scores release criteria and identifies missing items.

## Examiner Workflow

1. Start with `/audit` to understand the field-risk model.
2. Use `/acquisition` before choosing a tool workflow.
3. Use `/artifacts` to define raw evidence families that must be preserved.
4. Use `/workbench` and `/methods` to build the operational plan.
5. Use `/wiping`, `/timeline`, and `/ledger` for anti-forensics review, event reconstruction, and integrity proof.
6. Use `/validation` to resolve parser disagreement.
7. Use `/report-readiness` before formal report release.

## Quality Principles

- Evidence completeness must be stated in relation to acquisition scope.
- Parser output must be validated against raw files for material findings.
- Report limitations are a strength when they are clear, specific, and evidence-based.
- Device-state decisions must be documented before invasive or state-changing actions.
- Privacy minimization should be visible in both workflow and report structure.
