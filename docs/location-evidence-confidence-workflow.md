# Android Location Evidence Confidence Workflow

This workflow helps examiners evaluate Android location evidence without overstating precision, source reliability, or user attribution. It is designed for cases that include GPS fixes, Wi-Fi scans, cell tower data, EXIF metadata, app check-ins, cloud timelines, navigation artifacts, Bluetooth proximity, mobile-device-management records, or third-party provider exports.

## Objectives

- Classify every location artifact by source, acquisition path, timestamp quality, and privacy sensitivity.
- Separate observed device location from inferred user presence.
- Identify spoofing, anti-forensics, clock drift, sync delay, and provider-normalization risks.
- Corroborate important location claims across independent evidence families.
- Present location conclusions with confidence, limitations, and minimization controls.

## Location Source Classes

| Source Class | Examples | Typical Strength | Key Limitations |
| --- | --- | --- | --- |
| GNSS/GPS fix | Android fused location, navigation cache, fitness route | High when recent and precise | Spoofing, indoor weakness, app sampling intervals |
| Wi-Fi positioning | BSSID scans, known network history, provider-derived location | Medium to high | Router movement, stale databases, scan-only artifacts |
| Cell network | Cell ID, LTE/5G serving cell, call data records | Low to medium | Broad radius, carrier processing delay, handover ambiguity |
| EXIF metadata | Photos and videos with GPS tags | Medium | Metadata editing, shared media, timezone ambiguity |
| App check-ins | Maps, rideshare, delivery, messaging, social, browser | Medium | Account sharing, cloud sync, server-side inference |
| Cloud timeline | Account activity, backup records, location history exports | Medium to high | Sync delay, account/device binding, provider transformations |
| BLE and proximity | Beacons, wearable sync, nearby device logs | Low to medium | Proximity is not exact location |
| MDM and work profile | Managed-device location, compliance logs, app inventory | Medium | Policy scope, personal/work boundary, administrator access limits |
| User-entered location | Profile, form, saved address, calendar venue | Low | Declared intent, not observed presence |

## Evidence Intake

| Field | Required Detail |
| --- | --- |
| Case reference | Case ID, examiner, authority, acquisition date |
| Artifact source | App/package, database, log, cloud export, carrier export, MDM platform, media file |
| Acquisition method | Logical, file-system, cloud, manual export, MDM, provider return |
| Device state | Locked/unlocked, BFU/AFU, work profile present, network state |
| Timestamp fields | Event time, server time, modified time, sync time, timezone |
| Coordinate details | Latitude, longitude, altitude, accuracy radius, provider, confidence field |
| Integrity evidence | Hash, extraction tool version, parser version, export chain |
| Privacy scope | Personal, work, third-party, bystander, sensitive place indicator |

## Confidence Scoring

Score each claim from 0 to 3 in each dimension. Use the lowest weak dimension as a review trigger before relying on a location claim.

| Dimension | 0 | 1 | 2 | 3 |
| --- | --- | --- | --- | --- |
| Provenance | Unknown source | Screenshot or unsupported export | Tool-parsed artifact with source path | Hash-preserved source with parser/version evidence |
| Spatial precision | Place name only | City or large cell area | Street/venue or moderate radius | Coordinates with meaningful accuracy radius |
| Timestamp confidence | Missing or inconsistent | One timestamp only | Multiple timestamps aligned after timezone review | Independent time anchors corroborate the event |
| Corroboration | None | Same app only | Different artifacts on same device | Independent source families support claim |
| Anti-forensics exposure | High spoofing or editing indicators | Unresolved anomaly | Checked with minor limitations | No material spoofing/editing indicators |
| Attribution | No device/user link | Account-only link | Device-account link | Device, account, and activity context align |
| Privacy minimization | Excess unrelated data retained | Partial redaction | Redacted evidence package | Purpose-limited extract with bystander minimization |

## Corroboration Matrix

| Claim Type | Minimum Corroboration |
| --- | --- |
| Device was near a venue | GPS or Wi-Fi artifact plus timeline or app activity anchor |
| User likely visited a location | Device location plus unlock/use/app activity plus account binding |
| User took a photo at a location | EXIF GPS plus file system timestamps plus camera app or gallery artifacts |
| Device moved along a route | Multiple ordered location fixes plus navigation, fitness, or transport app context |
| Work-profile app was used at a location | Managed-profile artifact plus MDM/app log plus profile boundary review |
| Location history was modified or deleted | Database/log delta plus cloud sync evidence plus anti-forensics review |

## Timestamp Review

1. Normalize all timestamps to UTC while preserving original timezone fields.
2. Identify whether the timestamp is event time, write time, sync time, server receipt time, or parser-derived time.
3. Compare device clock state with network time, file system timestamps, message events, media capture times, and cloud export metadata.
4. Flag events where location and activity are separated by likely sync delay.
5. Avoid using a provider export timestamp as proof that the device was present at that moment unless provider documentation supports it.

## Spoofing And Anti-Forensics Checks

| Indicator | Review Action |
| --- | --- |
| Mock location setting enabled | Check developer options, app permissions, and installed spoofing tools |
| Implausible movement speed | Compare route geometry, time gaps, transport context, and sensor data |
| Accuracy radius inconsistent with venue claim | Report the uncertainty instead of exact placement |
| EXIF edited or stripped | Compare file hashes, media database, thumbnails, cloud copies, and sharing history |
| Location history gaps | Review deletion traces, account settings, app caches, and backup history |
| Work profile boundary ambiguity | Confirm whether artifact came from personal or managed profile storage |
| Cloud-only location artifact | Validate account binding and device identifier before attributing to a device |

## Privacy And Minimization

- Treat home, workplace, health, worship, political, and child-related locations as sensitive.
- Redact unrelated route segments, bystander identifiers, and third-party addresses where they are not necessary.
- Do not export full cloud timelines when a narrow time window answers the investigative question.
- Preserve raw evidence securely, but report only the minimum location facts needed.
- Separate personal-profile and work-profile artifacts in enterprise cases.
- Record lawful authority and scope before requesting carrier, provider, cloud, or MDM location data.

## Reporting Language

Use careful language that reflects evidentiary confidence:

| Avoid | Prefer |
| --- | --- |
| "The user was at X" | "The device produced location artifacts consistent with being near X" |
| "GPS proves exact location" | "The artifact reports coordinates with an accuracy radius of Y meters" |
| "No location means not present" | "No reviewed artifact placed the device at that location during the examined interval" |
| "Cloud timeline confirms device location" | "The provider export records an account/device location event, subject to sync and attribution limitations" |

## Release Checklist

- Source path, parser version, acquisition method, and hashes are documented.
- Coordinate precision and accuracy radius are reported.
- Timestamps are normalized and categorized by event/write/sync/server meaning.
- At least one independent corroborating source supports high-impact claims.
- Spoofing, editing, deletion, and clock-drift risks were reviewed.
- Personal/work profile boundaries are clear where managed profiles exist.
- Sensitive and bystander locations are minimized in the report package.
- Conclusions state confidence and limitations rather than overstating user presence.
