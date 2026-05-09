# Android Bluetooth And Nearby-Device Artifact Workflow

This workflow helps examiners review Android Bluetooth, BLE, Nearby Share, wearable, vehicle, peripheral, and proximity-related artifacts without overstating what the evidence proves. These artifacts can support association, device exposure, handoff, or proximity hypotheses, but they usually need corroboration from location, network, app, notification, account, or timeline evidence.

## Objectives

- Identify paired, bonded, discovered, connected, and recently exposed devices.
- Separate classic Bluetooth, BLE, Nearby Share, fast-pair, vehicle, wearable, and app-specific proximity artifacts.
- Preserve source paths, package context, profile or user ID, parser version, timestamps, and device identifiers.
- Correlate device association with app foreground events, notifications, location, network, and file-transfer records.
- Minimize unrelated third-party device identifiers and personal-profile data.

## Evidence Sources

| Source | Examples | Review Notes |
| --- | --- | --- |
| Bluetooth stack records | Paired devices, bond state, MAC-like identifiers, device class, aliases | Modern Android may randomize identifiers or restrict access |
| BLE scan artifacts | Beacons, advertisements, app-specific scan logs | A scan does not prove physical contact or user interaction |
| Nearby Share records | Send or receive attempts, device names, account context | Correlate with files, notifications, and user confirmation prompts |
| Fast Pair and wearable data | Earbuds, watches, fitness devices, companion apps | Review companion app databases and account sync separately |
| Vehicle systems | Hands-free, media, contacts sync, head-unit pairing | Consider vehicle-side logs where available |
| App permission state | Bluetooth scan/connect/advertise, nearby devices, location | Permission grants support capability, not necessarily use |
| Notifications and system UI | Pairing prompts, transfer prompts, connection status | Useful for timeline anchors and user visibility |
| File-transfer evidence | Received files, downloads, share intents, media scanner records | Correlate hashes and storage paths |

## Triage Questions

| Question | Evidence |
| --- | --- |
| Was the device paired, merely discovered, or actively connected? | Bond state, connection events, scan logs, notifications |
| Which Android profile or work profile contained the artifact? | User ID, package path, managed-profile boundary |
| Which app had Bluetooth or nearby-device permissions? | Runtime permissions, AppOps, package install history |
| Did a file transfer occur? | Nearby Share records, storage path, media metadata, hashes |
| Could the identifier be randomized or reused? | Android version, BLE address type, app source, parser notes |
| Is there corroborating location or network evidence? | GPS, Wi-Fi, cell, account, vehicle, router, or access-control logs |
| Does the artifact involve a wearable, vehicle, or medical device? | Companion app data, paired-device label, sensitive data category |

## Timeline Anchors

| Anchor | Why It Matters |
| --- | --- |
| First seen | Establishes earliest observed exposure or pairing candidate |
| Bond created | Supports pairing or trusted-device setup |
| Last connected | Supports possible recent proximity or device use |
| Permission granted | Shows when an app could scan or connect |
| Transfer started or completed | Connects proximity artifacts to file evidence |
| Notification shown | Supports user-visible prompt or status |
| Companion app foregrounded | Corroborates wearable or peripheral interaction |
| Vehicle or head-unit sync | Supports car-related association hypothesis |

## Confidence Levels

| Level | Criteria |
| --- | --- |
| High | Paired or connected record, timestamp, package/profile context, and corroborating event align |
| Medium | Source and timestamp are clear, but user action or physical proximity is inferred |
| Low | Only scan, cache, alias, or secondary app record exists |
| Inconclusive | Identifier randomization, parser conflict, profile boundary, or timestamp drift materially affects interpretation |

## Reporting Guidance

Use cautious wording:

- "The device recorded a paired Bluetooth entry for..." is stronger than "the user used..."
- "A BLE advertisement was observed" does not prove the devices were physically together.
- "Nearby Share transfer evidence exists" should be supported by file paths, hashes, and prompt records.
- "A wearable companion app record exists" should not be treated as system-level pairing unless stack records support it.
- "Vehicle pairing evidence exists" should be separated from vehicle-side activity unless head-unit logs are available.

## Privacy And Sensitive Data

- Redact unrelated device identifiers, contact names, vehicle names, and wearable health indicators where not in scope.
- Separate personal and managed-profile Bluetooth evidence.
- Avoid exposing nearby third-party device names unless directly relevant.
- Treat wearable, medical, vehicle, and child-device artifacts as sensitive.
- Preserve raw evidence under chain-of-custody controls while limiting report excerpts.

## Release Checklist

- Classic Bluetooth, BLE, Nearby Share, wearable, vehicle, and app-specific sources are differentiated.
- Package, profile ID, source path, parser, and timestamp basis are documented.
- Identifier randomization and Android permission model limitations are stated.
- File-transfer claims are supported by hashes, paths, and transfer or notification records.
- App permissions are compared with observed behavior.
- Conclusions state confidence and avoid overclaiming user intent or physical proximity.
