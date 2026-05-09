# Android Artifact Source Confidence Matrix

Use this matrix when deciding how strongly an Android artifact can support an investigative conclusion. A parser output, SQLite row, notification cache, cloud export, or media timestamp may be relevant, but relevance is not the same as evidentiary confidence. Confidence should be assigned before drafting findings and should remain visible in limitation notes.

## Case Context

| Field | Value |
| --- | --- |
| Case ID |  |
| Device model and Android version |  |
| Acquisition method | Logical / filesystem / physical / cloud / memory / hybrid |
| Encryption and lock state |  |
| Artifact family | Messages / Browser / Media / App data / Notifications / Location / Package state / Other |
| Examiner |  |
| Review date |  |

## Confidence Dimensions

Score each dimension from `0` to `3`.

| Dimension | 0 - Not Supportable | 1 - Weak | 2 - Moderate | 3 - Strong |
| --- | --- | --- | --- | --- |
| Provenance | Source path, owner, or acquisition route unknown | Derived export without complete path or scope | Known source path and tool route | Known source path, acquisition route, owner, and scope |
| Integrity | No hash or custody evidence | Final export hash only | Hashes and custody for derived artifact | Original export, derived artifact, and report attachment hashed |
| Parser reliability | Unsupported or opaque parser | Single tool with no manual review | Tool output checked against source structure | Multiple tools or manual source review agree |
| Timestamp reliability | Timestamp origin unknown | Timestamp exists but timezone or clock drift unclear | Timestamp origin known with documented timezone | Timestamp reconciled across device, cloud, network, or external anchor |
| Completeness | Unknown coverage | Partial capture or known extraction gaps | Known scope with documented gaps | Full relevant scope or defensible reason for limits |
| Corroboration | Single unverified artifact | Related artifact from same source | Independent artifact from another device, account, or log | Independent source plus timeline or witness anchor |
| Anti-forensics exposure | Wiping, tampering, or malware risk unresolved | Risk noted but not tested | Risk tested with limitations | Risk tested and supported by control artifacts |
| Privacy minimization | Scope not filtered | Scope filtered informally | Scope and redaction documented | Scope, redaction, and access controls documented |

## Confidence Rating

| Total Score | Rating | Reporting Rule |
| --- | --- | --- |
| 20-24 | High | Artifact can support a finding when consistent with case scope and limitations. |
| 14-19 | Moderate | Artifact can support a finding if limitations and corroboration gaps are stated. |
| 8-13 | Low | Artifact should be used as a lead or context, not as the only basis for a conclusion. |
| 0-7 | Insufficient | Artifact should not support a formal conclusion without additional validation. |

## Artifact Review Sheet

| Artifact | Source Path Or Export | Tool Output | Manual Review | Score | Rating | Notes |
| --- | --- | --- | --- | --- | --- | --- |
|  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |

## Common Android Artifact Notes

| Artifact Type | Confidence Risks | Validation Actions |
| --- | --- | --- |
| SQLite records | WAL/SHM files may contain uncheckpointed or deleted records | Review database schema, WAL/SHM, row IDs, and timestamps |
| Notifications | Notification text may be truncated, cached, or app-generated | Correlate with app database, system logs, screenshots, or cloud records |
| Browser/WebView artifacts | App-specific WebView storage may be fragmented across profiles | Compare browser history, cookies, cache, downloads, and app sandbox data |
| Media files | EXIF can be absent, edited, or inconsistent with filesystem times | Compare EXIF, filesystem metadata, thumbnails, cloud sync, and gallery database |
| Messaging apps | End-to-end encryption and cloud backup behavior can limit local evidence | Correlate local database, notifications, media folders, backups, and account records |
| Location artifacts | Location caches may reflect network, app, or provider estimates | Record accuracy, provider, timestamp source, and external anchors |
| Package state | Installed package lists do not prove user execution | Combine with usage stats, logs, app data, network events, or witness timeline |
| Cloud exports | Provider export may reflect account state rather than device-local state | Separate cloud facts from device-local facts and record export scope |
| Memory captures | Volatile, tool-sensitive, and difficult to reproduce | Preserve tool version, device state, and exact capture method |
| Wiping artifacts | Absence of data is not proof of wiping by itself | Combine app behavior, execution traces, deleted records, and timeline gaps |

## Conflict Handling

When two sources disagree:

- Preserve both source records and do not silently choose the cleaner answer.
- Check whether the disagreement is due to timezone, sync delay, parser scope, app version, account state, or acquisition timing.
- Prefer source-of-record data when provenance and integrity are stronger, but document why.
- Use manual source review for report-critical conflicts.
- Escalate to peer review when parser disagreement changes a key conclusion.

## Report Wording Guide

| Confidence | Suggested Wording |
| --- | --- |
| High | "The evidence supports..." |
| Moderate | "The evidence indicates..., subject to the following limitations..." |
| Low | "The artifact is consistent with..., but requires corroboration..." |
| Insufficient | "The artifact was observed but is not relied upon for the conclusion." |

## Closure Checklist

- Artifact source, acquisition method, and scope are documented.
- Hashes and chain-of-custody references are recorded.
- Parser output is tied to source files and tool versions.
- Timestamp origin, timezone, and drift assumptions are explicit.
- Corroboration is documented or the limitation is stated.
- Anti-forensics or wiping risk is addressed where relevant.
- Privacy minimization is applied before reporting.
- Confidence rating appears in the finding notes or appendix.
