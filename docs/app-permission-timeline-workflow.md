# Android App Permission Timeline Workflow

This workflow helps examiners reconstruct Android permission changes around an incident, application installation, upgrade, suspicious activity, or data-access event. It focuses on runtime permission grants, revocations, special app access, background permissions, notification access, accessibility services, device admin, work-profile controls, and permission changes introduced by app updates or policy.

## Objectives

- Reconstruct when an app gained, lost, or used sensitive permissions.
- Separate user-granted permissions from system defaults, MDM policy, app upgrade behavior, and restored backup state.
- Identify suspicious permission escalation around account compromise, malware activity, data exfiltration, stalking, or policy violations.
- Preserve source-path, parser, timestamp, and device-state limitations for defensible reporting.
- Minimize unrelated personal and work-profile data while retaining enough evidence for review.

## Permission Evidence Sources

| Source | Examples | Review Notes |
| --- | --- | --- |
| Package manager state | Package name, install time, update time, granted permissions | Check parser version and Android version behavior |
| Runtime permission records | Camera, microphone, location, contacts, files, SMS, call logs | User prompts may not be retained directly |
| AppOps records | Background location, clipboard, media access, usage operations | Useful for access timing and mode changes |
| Settings and special access | Accessibility, notification listener, VPN, device admin, usage access | High value for abuse investigations |
| MDM or work profile | Managed configuration, policy grants, profile owner, device owner | Separate enterprise policy from user action |
| App manifest | Requested permissions by APK version | Requested permission is not the same as granted permission |
| Event logs and usage stats | Foreground events, permission controller, app launches | Retention and completeness vary by version |
| Cloud backup or restore | Restored package state, settings backup | May explain permission state after device migration |

## Timeline Anchors

| Anchor | Why It Matters |
| --- | --- |
| App install time | Establishes earliest possible grant window |
| App update time | New permissions may appear after upgrade |
| First launch after install or update | Permission prompts often occur near first use |
| Permission grant or mode change | Core event for timeline reconstruction |
| Sensitive operation event | AppOps, file access, camera, microphone, location, contacts, or message access |
| Device unlock or user activity | Helps distinguish active user action from background behavior |
| MDM policy change | Explains enterprise-managed permission state |
| Incident time | Compares permission state to reported harm or data access |

## Sensitive Permission Groups

| Group | Examples | Risk |
| --- | --- | --- |
| Location | Fine location, coarse location, background location | Tracking, stalking, place inference |
| Media and sensors | Camera, microphone, body sensors | Surveillance or sensitive capture |
| Communications | SMS, call logs, contacts, phone state | Social graph and account abuse |
| Files and media | External storage, media collections, document provider access | Data exfiltration |
| Notifications and accessibility | Notification listener, accessibility service | Credential capture, message reading, remote control |
| Admin and VPN | Device admin, VPN service, install unknown apps | Persistence, traffic interception, policy bypass |
| Account and identity | Account access, autofill, credential provider | Account compromise and impersonation |

## Reconstruction Steps

1. Identify the target package name, user/profile ID, app version, signing certificate, and installation source.
2. Collect package manager, AppOps, special-access, event log, usage stats, and MDM records where available.
3. Normalize all timestamps to UTC and preserve original timezone, device clock state, and parser interpretation.
4. Map requested permissions from the APK manifest to actual granted permissions and mode changes.
5. Compare permission changes to app install, update, first launch, incident window, and sensitive operation events.
6. Distinguish personal profile, work profile, and secondary user records.
7. Corroborate high-impact claims with independent evidence such as app activity, network logs, media metadata, cloud records, or user interaction.

## Suspicious Change Patterns

| Pattern | Review Action |
| --- | --- |
| Sensitive permission granted shortly before incident | Check first launch, user activity, prompt context, and app update |
| Permission granted without clear user activity | Review MDM policy, restore state, accessibility, malware, or shared device risk |
| Background location enabled after foreground-only use | Review app settings, AppOps mode, and location artifacts |
| Accessibility or notification access enabled | Treat as high-impact; review data capture and persistence risk |
| Permission revoked immediately after incident | Check anti-forensics, app cleanup, and user complaint timeline |
| App update adds new dangerous permissions | Compare APK versions, release notes, and grant state before/after update |
| Work-profile permission affects personal evidence | Confirm profile boundary before attributing access |

## Confidence Matrix

| Confidence Level | Criteria |
| --- | --- |
| High | Permission state, timestamp anchor, package version, profile ID, and corroborating activity align |
| Medium | Permission state and app version are clear, but exact grant time or user action is inferred |
| Low | Only requested permissions or final state are available without timing evidence |
| Inconclusive | Source is incomplete, profile boundary is unclear, or timestamps conflict materially |

## Reporting Guidance

Use precise wording:

- "The app requested camera permission" is different from "camera permission was granted."
- "The available artifact shows permission was enabled by this time" is safer than claiming the exact grant moment when logs are incomplete.
- "Permission state is consistent with background location access" should be paired with accuracy, AppOps, and activity limitations.
- "User action is inferred" should be stated when no direct prompt acceptance or interaction record exists.

## Privacy And Scope Controls

- Limit extracted app records to the relevant package, profile, and incident window where possible.
- Treat contact, message, notification, location, and accessibility artifacts as sensitive.
- Do not mix work-profile and personal-profile permission records unless scope permits it.
- Redact unrelated app names, contacts, notification contents, or location points from routine reports.
- Preserve full raw evidence under chain-of-custody controls when legal or investigative scope requires it.

## Release Checklist

- Target package, version, signing certificate, profile ID, and install source are documented.
- Requested permissions and granted permissions are clearly separated.
- AppOps and special-access records are reviewed for sensitive groups.
- Timestamps are normalized and limitations are stated.
- Permission changes are compared to install, update, first launch, incident, and sensitive access events.
- Work profile and MDM policy effects are separated from personal-profile artifacts.
- Confidence level and unresolved gaps are recorded before reporting conclusions.
