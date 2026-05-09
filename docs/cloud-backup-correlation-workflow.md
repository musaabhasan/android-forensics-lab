# Android Cloud Backup Correlation Workflow

This workflow helps examiners correlate Android device artifacts with cloud backup, account, and provider records without overstating provenance or mixing local-device evidence with remote account evidence. Use it when Google account data, app cloud sync, OEM backup, enterprise MDM records, or third-party service exports are relevant to an Android investigation.

## Purpose

- Separate local artifacts, cloud backups, app-provider exports, and administrative records into distinct source classes.
- Preserve legal authority, consent, and scope boundaries for cloud records.
- Normalize timestamps across device, account, provider, and export metadata.
- Assign confidence when cloud records corroborate, contradict, or extend local evidence.
- Prevent reports from implying that a cloud record was necessarily created on the seized device.

## Source Classification

| Source Class | Examples | Forensic Meaning | Key Limitation |
| --- | --- | --- | --- |
| Local device artifact | SQLite databases, WAL/SHM, app cache, notifications, media EXIF, package state | Evidence present on the examined device or image | May be incomplete because of encryption, app purge, or anti-forensics |
| Cloud backup artifact | Google Takeout, Android backup metadata, OEM backup package, app restore record | Evidence associated with an account or backup service | May originate from another device linked to the account |
| Provider account record | Login history, sync events, server-side messages, storage metadata | Provider-side account activity | Time zone, retention, and export semantics depend on provider |
| Enterprise record | MDM logs, work-profile policy, identity provider logs, device compliance state | Institutional management and access evidence | May reflect policy state rather than user action |
| Manual support evidence | Screenshots, emails, helpdesk records, user statements | Context for interpretation | Must be corroborated before factual conclusions |

## Authority and Scope Checklist

| Check | Required Evidence | Status |
| --- | --- | --- |
| Cloud account authority is documented | Warrant, consent, corporate authority, or policy authorization | Ready / gap |
| Account identifiers are in scope | Email, phone, account ID, device ID, or enterprise identity reference | Ready / gap |
| Provider export method is recorded | API export, portal download, legal return, MDM report, or user-provided archive | Ready / gap |
| Collection time is documented | UTC timestamp, collector, tool/version, network location where relevant | Ready / gap |
| Privacy minimization boundaries are set | Excluded accounts, unrelated contacts, unrelated files, privileged data | Ready / gap |
| Retention and sharing rules are set | Case storage location, redaction plan, report disclosure boundary | Ready / gap |

Do not expand from a device investigation into broad account review unless the authority and case scope explicitly allow it.

## Correlation Model

| Correlation Element | Local Evidence | Cloud Evidence | Interpretation Rule |
| --- | --- | --- | --- |
| Account binding | Account database, app settings, credential references | Provider account profile, login/session records | Confirms account association, not sole device attribution |
| Device binding | Android ID, hardware serial, app instance ID, backup transport ID | Provider device list, MDM inventory, backup source metadata | Stronger when identifiers match and collection timestamps align |
| Event timing | Local database timestamps, file mtime/ctime, notification logs | Server event timestamp, sync time, export generation time | Normalize all times to UTC and keep original provider time |
| Content presence | Local messages, media, app rows, cache files | Cloud messages, media, app-provider records | Cloud content can corroborate local content but may include other devices |
| Deletion or wiping | Missing local data, tombstones, WAL remnants, wiping app traces | Cloud retention, deleted-item folder, backup version history | Differences may reflect sync delay, retention policy, or user action |
| User action | App UI traces, input logs, foreground activity where available | Provider audit action, login, upload, delete, share event | Prefer explicit action logs over inferred synchronization state |

## Time Normalization Procedure

1. Record the device time, time zone, network time setting, and drift at acquisition.
2. Preserve every original timestamp value and field name from local and cloud sources.
3. Convert timestamps to UTC in the working timeline while keeping original values in the evidence table.
4. Label timestamp semantics: created, modified, accessed, sent, received, synced, backed up, exported, deleted, restored, or observed.
5. Treat export generation time as collection metadata, not the event time.
6. Use timeline anchors from known events, such as login, SMS, file creation, and backup completion, to evaluate consistency.
7. Flag records when provider documentation does not define timestamp semantics.

## Confidence Scoring

| Score | Criteria | Reporting Language |
| --- | --- | --- |
| High | Local artifact, cloud record, and device/account identifiers align; timestamps are consistent; source semantics are documented | The evidence strongly supports |
| Medium | Two independent sources align, but device attribution, timestamp semantics, or export completeness has limits | The evidence supports, with limitations |
| Low | Single-source cloud record, weak device binding, undocumented timestamp meaning, or partial export | The evidence indicates but does not establish |
| Inconclusive | Sources conflict, identifiers do not match, or collection scope is uncertain | The evidence is insufficient to conclude |

Confidence should be assigned per fact, not per case. A cloud login may be high confidence for account access but low confidence for local-device action.

## Workflow Steps

1. Define the investigation question and the exact fact to be tested.
2. Confirm authority for cloud, provider, or enterprise records.
3. Acquire and hash local Android evidence using the lab evidence-ledger workflow.
4. Collect cloud/provider exports and record collection method, tool version, account ID, and export hash.
5. Classify every source into local, cloud backup, provider account, enterprise, or support evidence.
6. Normalize identifiers and timestamps while preserving original values.
7. Correlate records by account, device, app instance, content hash, message ID, media metadata, and event time.
8. Classify agreement, partial agreement, contradiction, or scope mismatch.
9. Assign fact-level confidence and document limitations.
10. Apply privacy minimization before sharing extracts in reports, support reviews, or peer review.

## Contradiction Handling

| Conflict | Possible Causes | Required Examiner Action |
| --- | --- | --- |
| Cloud record exists but local artifact is absent | App purge, device reset, backup from another device, server retention, encrypted local store | Check device binding and report as account/cloud evidence unless local attribution is corroborated |
| Local record exists but cloud record is absent | Offline use, sync disabled, provider retention limit, export filter, deleted remote data | Review app settings, network state, and provider export limitations |
| Timestamp mismatch | Time zone conversion, provider server time, device clock drift, sync delay | Normalize to UTC and cite timestamp semantics |
| Duplicate content across devices | Shared account, family/shared device, app restore, multi-device sync | Require device-specific identifiers before attributing action to seized device |
| Deleted locally but present in cloud | Backup versioning, trash retention, partial wipe, failed sync | Correlate with wiping traces and provider deletion metadata |

## Privacy and Minimization Rules

- Extract only cloud records tied to the approved account, device, app, event window, or investigation question.
- Redact unrelated contacts, messages, file names, and account identifiers from working summaries unless needed as evidence.
- Keep raw provider exports in controlled evidence storage and share only scoped extracts.
- Separate privileged, medical, student, or unrelated third-party data into restricted review queues.
- Record every reviewer who accessed raw cloud exports.
- Avoid bulk publication of account timelines when a fact-level extract answers the question.

## Report-Ready Evidence Table

| Fact ID | Question Tested | Local Source | Cloud or Provider Source | Device Binding | Time Confidence | Content Confidence | Privacy Notes | Conclusion |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| F-001 |  |  |  | High / medium / low | High / medium / low | High / medium / low |  |  |

## Release Checklist

| Check | Pass Criteria |
| --- | --- |
| Legal or institutional authority is documented | Scope supports cloud/provider record use |
| Local and cloud sources are clearly separated | Report does not imply unsupported device attribution |
| Timestamps are normalized and original values retained | UTC conversion and original provider fields are documented |
| Device binding is explained | Identifiers supporting or limiting attribution are listed |
| Confidence is assigned per fact | Conclusions use calibrated language |
| Privacy minimization is complete | Unrelated cloud data is redacted or excluded |
| Contradictions are addressed | Alternative explanations are documented |
| Evidence hashes are recorded | Local acquisition and cloud export hashes are preserved |

## Reviewer Notes

Document open limitations, provider documentation gaps, inaccessible backup sources, unavailable authority, parsing uncertainty, and facts that should not be used for dispositive conclusions.
