# Android Notification Evidence Triage Workflow

This workflow helps examiners triage Android notification artifacts while preserving context, privacy, and evidentiary limits. Notifications can reveal message previews, app events, authentication prompts, missed calls, delivery updates, calendar reminders, security alerts, and deleted or transient communications. They can also expose sensitive third-party content, so they require careful scoping.

## Objectives

- Identify notification evidence sources and their retention limits.
- Reconstruct notification timelines around incidents, account activity, deleted messages, or app behavior.
- Separate notification display evidence from proof that a user read, opened, or acted on a message.
- Review notification listener access and accessibility services that may capture sensitive content.
- Minimize unrelated personal, work-profile, and bystander information in reports.

## Evidence Sources

| Source | Examples | Key Limitation |
| --- | --- | --- |
| System notification records | Posted, removed, channel, package, timestamp, importance | May not retain full body text |
| App databases | Messaging previews, delivery state, badge counts, alert status | App-specific schema and retention |
| Notification listener logs | Third-party capture, automation apps, wearable sync | Listener may alter or duplicate evidence |
| Accessibility service artifacts | Screen events, text capture, automation traces | High privacy and attribution risk |
| Wearable or companion app | Watch notifications, replies, dismissals | Sync delay and separate device scope |
| Cloud or account exports | Notification emails, account alerts, service events | Server time may differ from device time |
| Screenshots and media | Lock-screen image, chat preview, screen recording | Manual capture and editing risk |
| Usage stats and event logs | App foreground after notification, notification tap events | Retention varies by Android version |

## Notification Event Types

| Event Type | Meaning | Reporting Caution |
| --- | --- | --- |
| Posted | Notification was created or displayed by an app | Does not prove user saw it |
| Updated | Existing notification content or state changed | May overwrite earlier preview text |
| Removed | Notification was dismissed, expired, or replaced | Dismissal does not always mean user action |
| Tapped | User or system opened notification target | Verify with app foreground and unlock events |
| Reply action | Inline reply or action button used | Confirm account, device state, and app logs |
| Channel change | Notification category changed | May affect visibility, not message content |
| Listener access | App had permission to read notifications | Does not prove every notification was captured |

## Triage Steps

### 1. Scope The Review

Define:

- incident window;
- relevant app packages;
- personal, work, or secondary user profile;
- notification categories of interest;
- whether lock-screen visibility matters;
- whether notification listener or wearable evidence is in scope.

### 2. Build Timeline Anchors

| Anchor | Why It Matters |
| --- | --- |
| Notification post time | Earliest evidence of alert or preview |
| Notification tap or action | Possible user interaction |
| Device unlock | Helps assess whether notification may have been seen |
| App foreground event | Corroborates user interaction with app |
| Message database event | Confirms underlying message or app event |
| Network or cloud event | Corroborates server-side delivery |
| Deletion or edit event | Explains missing app content or changed preview |

### 3. Classify Sensitivity

| Category | Examples | Handling |
| --- | --- | --- |
| Low | App update, generic reminder, non-sensitive status | Normal evidence handling |
| Medium | Personal messages, names, email subjects, calendar titles | Redact unrelated participants |
| High | OTP, password reset, medical, financial, legal, child-related, work-confidential | Minimize, restrict, and justify inclusion |
| Critical | Credentials, tokens, private keys, recovery codes | Redact immediately and treat as exposed secret |

### 4. Corroborate Claims

| Claim | Minimum Corroboration |
| --- | --- |
| Notification was displayed | System/app notification record plus timestamp |
| User likely opened notification | Tap/action event plus unlock or app foreground event |
| Message was deleted after notification | Notification preview plus app database deletion/edit evidence |
| Notification listener captured content | Listener permission plus listener artifact or companion sync |
| Work notification appeared in personal context | Profile ID, package scope, and work-profile boundary review |
| OTP or recovery notification was exposed | Notification preview plus account/security event and access scope |

## Notification Listener And Accessibility Review

Escalate when:

- an app has notification listener access without clear user or policy justification;
- accessibility service access overlaps with sensitive notification content;
- a wearable, companion app, or automation tool stores notification text;
- notification content appears in unexpected app storage;
- listener access was enabled shortly before account compromise or data leakage;
- work-profile notification content is visible to personal-profile apps.

## Deleted Message And Ephemeral Content Handling

Notifications may preserve previews of messages that were later deleted, edited, or expired. Report this carefully:

- state that the notification artifact reflects displayed or cached preview content;
- do not infer full message content beyond the preserved preview;
- compare app database, cloud sync, backup, and contact/account records;
- preserve timestamp and source path for the notification artifact;
- record whether content was captured by the OS, app, listener, wearable, screenshot, or cloud service.

## Privacy And Minimization

- Redact unrelated message bodies, contact names, OTPs, and private calendar details.
- Keep work-profile notification evidence separate from personal-profile evidence.
- Avoid broad notification exports when a narrow time window answers the question.
- Mark OTPs, reset links, tokens, or recovery codes as exposed credentials if captured.
- Do not treat lock-screen notification visibility as proof that a person read the content.

## Confidence Levels

| Level | Criteria |
| --- | --- |
| High | Notification record, app event, device activity, and source path align |
| Medium | Notification record is clear but user interaction or underlying app event is inferred |
| Low | Only screenshot, preview, or secondary listener evidence is available |
| Inconclusive | Timestamp, source, profile, or package attribution conflicts materially |

## Release Checklist

- Relevant packages, profile IDs, and incident window are documented.
- Notification post, update, removal, tap, and action meanings are separated.
- Listener, accessibility, wearable, and companion-app evidence is reviewed where in scope.
- Sensitive notification content is minimized or redacted.
- Deleted or ephemeral content claims are corroborated by app or cloud evidence.
- Conclusions state confidence and do not overclaim user reading or intent.
