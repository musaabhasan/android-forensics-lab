# Android Evidence Integrity And Chain Of Custody Workflow

Use this workflow when planning, acquiring, validating, transferring, analyzing, or reporting Android evidence. It connects acquisition feasibility, hash-ledger checkpoints, tool validation, privacy minimization, and report readiness into a defensible custody record.

## Workflow Header

| Field | Value |
| --- | --- |
| Case ID |  |
| Device identifier |  |
| Android version |  |
| Lock and encryption state |  |
| Acquisition method | Logical / filesystem / physical / cloud / memory / hybrid |
| Examiner |  |
| Evidence owner |  |
| Date opened |  |

## 1. Authority And Scope

| Check | Evidence | Status |
| --- | --- | --- |
| Legal authority, consent, or institutional approval is documented | Authority reference |  |
| Device, account, cloud, removable-media, and companion-app scope is defined | Scope note |  |
| Privacy-sensitive content boundaries are recorded | Minimization plan |  |
| Work profile, multiple users, or managed device status is considered | Device profile note |  |
| Remote wipe, malware, or file-wiping suspicion is recorded | Risk note |  |
| First-hour preservation actions are documented | Preservation log |  |

## 2. Acquisition Integrity

| Control | Required evidence |
| --- | --- |
| Device state record | Photographs or notes for power, lock, network, SIM, storage, and visible notifications |
| Time reference | Examiner workstation time, device time, timezone, and known drift |
| Tool versions | Acquisition tool name, version, settings, plugins, and license state |
| Method limitation | Why logical, filesystem, physical, cloud, memory, or hybrid acquisition was selected |
| Hashing | SHA-256 hashes for exports, images, databases, archives, and generated reports |
| Write protection | How original evidence was protected from modification |
| Network handling | Airplane mode, Faraday handling, live network capture, or cloud preservation decision |

## 3. Chain Of Custody Events

| Event time | Actor | Action | Evidence item | Hash or seal | Location | Notes |
| --- | --- | --- | --- | --- | --- | --- |
|  |  | Acquired |  |  |  |  |
|  |  | Transferred |  |  |  |  |
|  |  | Analyzed |  |  |  |  |
|  |  | Exported |  |  |  |  |
|  |  | Archived |  |  |  |  |

Minimum rule: every evidence movement or transformation must identify the actor, time, source, destination, and integrity value or seal.

## 4. Validation And Cross-Tool Review

| Review area | Question | Evidence |
| --- | --- | --- |
| Parser count | Do tool counts match for key artifacts such as messages, browser records, notifications, or media? | Tool discrepancy validator |
| Hash consistency | Do repeated exports preserve expected hashes? | Hash ledger |
| Timeline anchors | Are device events aligned with external anchors such as network, account, or witness records? | Timeline fusion |
| Deleted data | Are claims about deleted or wiped records supported by method limits and recoverability evidence? | Wiping evaluation |
| Cloud correlation | Are cloud records separated from device-local records in the report? | Source catalog |
| Manual review | Are critical findings checked against raw databases, WAL/SHM files, or source artifacts where possible? | Manual validation notes |

## 5. Privacy And Minimization

| Control | Required decision |
| --- | --- |
| Relevance filtering | Define which apps, accounts, dates, and artifact classes are in scope. |
| Sensitive third-party data | Flag contacts, household members, minors, health, financial, or unrelated communications. |
| Report redaction | Remove or mask irrelevant names, phone numbers, media, and private content. |
| Evidence access | Restrict raw exports to authorized examiners and reviewers. |
| Retention | Define retention, archive, and disposal rules for raw and derived evidence. |

## 6. Report Readiness

Before release, confirm:

- Authority, scope, and acquisition method are clear.
- Tool versions, settings, and limitations are documented.
- Hashes are recorded for original exports and report attachments.
- Chain-of-custody events are complete.
- Conflicting tool results are explained or escalated.
- Timeline claims are supported by source confidence and anchors.
- Deleted-data or anti-forensics claims avoid overstating recoverability.
- Privacy minimization is applied to exhibits and appendices.

## Closure Record

| Item | Complete? | Evidence reference |
| --- | --- | --- |
| Authority and scope |  |  |
| Acquisition integrity |  |  |
| Hash ledger |  |  |
| Chain of custody |  |  |
| Tool validation |  |  |
| Timeline support |  |  |
| Privacy minimization |  |  |
| Peer review |  |  |
| Final report archive |  |  |

## Escalation Triggers

Escalate when evidence hashes change unexpectedly, acquisition modifies the source, parser disagreement affects a key finding, device time cannot be reconciled, malware or wiping behavior may alter evidence, privacy exposure exceeds scope, or the case requires formal expert testimony.
