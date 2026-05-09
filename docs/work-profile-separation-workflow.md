# Android Work Profile Separation Workflow

Android enterprise investigations often involve a managed work profile alongside a personal profile on the same device. This workflow helps examiners preserve profile boundaries, separate personal and corporate evidence, document MDM authority, and report conclusions without over-collecting unrelated private data.

## Objectives

- Identify personal, work, and device-owner profile boundaries.
- Confirm legal or institutional authority for managed-profile evidence.
- Separate app containers, accounts, notifications, files, and cloud records by profile.
- Preserve privacy for personal data that is outside the investigation scope.
- Record limitations caused by encryption, MDM policy, remote wipe, or partial acquisition.

## Profile Inventory

| Profile Area | Evidence Source | Questions |
| --- | --- | --- |
| Device owner or profile owner | MDM records, Android settings, package state, policy controller app | Is the device fully managed or personally owned with a work profile? |
| Work profile apps | Package list, launcher labels, app data paths, MDM inventory | Which apps are managed and in scope? |
| Personal profile apps | Package list, app containers, personal account data | Which data must be minimized or excluded? |
| Accounts | Android account database, MDM identity, provider account records | Which account belongs to the managed profile? |
| Storage | Profile-specific files, media, downloads, document providers | Which storage areas are work-managed? |
| Notifications | Notification logs, app records, companion cloud records | Which notifications originate from work apps? |
| Network and VPN | Per-app VPN, work Wi-Fi, proxy, DNS, MDM logs | Which traffic is attributable to the managed profile? |

## Authority and Scope

| Check | Required Evidence | Status |
| --- | --- | --- |
| Device ownership model is documented | BYOD, corporate-owned, fully managed, shared device, kiosk | Ready / gap |
| Work-profile authority is documented | Corporate policy, consent, legal authorization, incident response authority | Ready / gap |
| Personal-profile handling is approved | Minimization plan, exclusion rules, privacy reviewer | Ready / gap |
| MDM administrator actions are recorded | Remote lock, wipe, policy push, compliance check, app deployment | Ready / gap |
| Cloud or identity provider records are in scope | Account ID, tenant, authority, collection method | Ready / gap |

Do not assume that possession of the physical device authorizes collection of personal-profile content. Treat profile boundaries as both technical and legal scoping boundaries.

## Acquisition and Triage Steps

1. Photograph or record visible profile indicators before changing device state.
2. Record lock state, Android version, encryption state, owner/profile owner status, and MDM app details.
3. Capture MDM inventory, compliance, app deployment, remote action, and policy evidence where authorized.
4. Identify profile-specific package names, app IDs, account IDs, and storage paths.
5. Acquire work-profile evidence using the least intrusive method that preserves integrity.
6. Hash acquired files, exports, and reports separately for each profile.
7. Flag personal-profile artifacts encountered during broad acquisition for minimization review.
8. Correlate work-profile device evidence with MDM, identity provider, and cloud records.
9. Document any remote wipe, policy change, or selective wipe event that may affect completeness.

## Evidence Separation Table

| Evidence Item | Profile | Source | Authority | Hash or Evidence ID | Include in Report | Privacy Notes |
| --- | --- | --- | --- | --- | --- | --- |
|  | Work / personal / shared / unknown |  |  |  | Yes / no / restricted |  |

Profile labels:

- `work`: managed profile or corporate-owned profile evidence clearly in scope.
- `personal`: personal profile evidence outside the managed scope.
- `shared`: device-level evidence that may affect both profiles, such as system logs, network state, or hardware identifiers.
- `unknown`: evidence requiring additional review before use.

## Common Artifacts and Boundaries

| Artifact | Boundary Risk | Handling |
| --- | --- | --- |
| Android accounts | Same email provider may appear in personal and work contexts | Map account IDs and profile IDs before attribution |
| Notifications | Work and personal notifications may share logs | Attribute by package, profile, timestamp, and app label |
| Downloads and document providers | Files may be copied between profiles | Preserve path, profile owner, and transfer evidence |
| Browser data | Managed browser may enforce policy, personal browser may not | Separate package names, storage paths, and account state |
| Messaging apps | Same app may exist in both profiles | Confirm profile-specific package data and account ID |
| VPN or proxy logs | Per-app VPN may cover only managed apps | Link traffic to managed app list and MDM policy |
| Remote wipe records | Selective wipe may delete work data only | Preserve MDM event and acquisition timing |

## Correlation Confidence

| Confidence | Criteria | Reporting Language |
| --- | --- | --- |
| High | App package, profile ID, account ID, MDM policy, and timestamp align | The evidence strongly supports work-profile attribution |
| Medium | Two or more signals align, but one profile boundary is incomplete | The evidence supports work-profile attribution with limitations |
| Low | Profile label inferred from weak context or partial acquisition | The evidence suggests, but does not establish attribution |
| Inconclusive | Profile boundary cannot be determined | Profile attribution cannot be concluded |

## Privacy Minimization

- Redact personal contacts, messages, photos, account identifiers, and browser history unless explicitly in scope.
- Use derived tables for personal-profile exclusions rather than copying raw personal content into reports.
- Restrict access to mixed-profile acquisitions until privacy review is complete.
- Document when personal data was technically encountered but excluded from analysis.
- Keep MDM tenant identifiers and employee identifiers in restricted evidence when not needed for reporting.

## Report-Ready Checks

| Check | Pass Criteria |
| --- | --- |
| Work profile identified | Device owner/profile owner evidence is documented |
| Authority recorded | Collection scope supports managed-profile evidence |
| Personal data minimized | Exclusion and redaction decisions are documented |
| Profile-specific hashes retained | Work, personal, shared, and unknown evidence are separated |
| MDM events correlated | Remote actions and policy changes are included when relevant |
| Attribution language calibrated | Conclusions avoid unsupported personal/work profile assumptions |

## Closure Record

| Field | Response |
| --- | --- |
| Device ownership model |  |
| Managed profile authority |  |
| MDM evidence reference |  |
| Work-profile evidence package |  |
| Personal-profile minimization decision |  |
| Mixed-profile evidence restrictions |  |
| Remote wipe or policy-change impact |  |
| Reviewer |  |
