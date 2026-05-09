# Android WebView And Browser Artifact Workflow

This workflow helps examiners triage Android browser and WebView artifacts for case reconstruction, account activity, phishing, drive-by download, credential exposure, web malware, policy violations, and app-embedded browsing. It covers Chrome-like browsers, OEM browsers, embedded WebView stores, custom tabs, app-specific browser caches, downloads, cookies, autofill, permissions, profiles, and privacy-sensitive content.

## Objectives

- Separate full browser evidence from app-embedded WebView evidence.
- Preserve browsing, download, cookie, cache, form, autofill, permission, and account context with source paths and timestamps.
- Identify phishing, malicious redirects, suspicious downloads, tracking, and credential exposure indicators.
- Avoid overclaiming user intent from cached or preloaded web artifacts.
- Minimize unrelated personal, work-profile, and third-party web content in reports.

## Evidence Sources

| Source | Examples | Review Notes |
| --- | --- | --- |
| Browser history | URLs, titles, visit counts, typed count, transitions | May include sync or prefetch events |
| Downloads | File name, URL, MIME, hash, destination, completion state | Correlate with file system and notification records |
| Cookies and local storage | Session cookies, tokens, site state | Treat as sensitive and avoid exposing secrets |
| Cache | HTML, scripts, images, redirects, service worker assets | Cached content is not proof of user viewing |
| Autofill and form data | Emails, names, addresses, search strings | High privacy sensitivity |
| Permissions | Camera, microphone, location, notifications for sites | Correlate with WebView/browser settings |
| WebView databases | App-specific WebView cookies, cache, local storage | Attribute to the host app and profile |
| Custom tabs | App-launched browser sessions | Review referrer and app handoff context |
| Sync artifacts | Account profile, bookmarks, remote tabs | Device presence may be inferred, not guaranteed |

## Triage Questions

| Question | Evidence |
| --- | --- |
| Which app or browser created the artifact? | Package name, profile ID, source path |
| Was it browser, WebView, or custom tab activity? | Storage location, package container, intent/referrer |
| Was the page typed, clicked, redirected, preloaded, or synced? | Transition type, referrer, visit source |
| Was a file downloaded and opened? | Download DB, file hash, media/document access, app launch |
| Were credentials or tokens exposed? | Cookies, local storage, autofill, reset links, notifications |
| Did site permissions enable sensitive capture? | Site settings, permission prompts, AppOps, runtime permissions |
| Does work-profile browsing differ from personal browsing? | User/profile ID, managed profile path, MDM policy |

## Timeline Anchors

| Anchor | Why It Matters |
| --- | --- |
| First visit | Establishes earliest known contact with URL or domain |
| Last visit | Supports incident window reconstruction |
| Download start and completion | Connects web activity to file evidence |
| Cookie creation and expiry | Helps identify session window |
| Local storage modification | Shows site or app state change |
| Site permission grant | Supports camera, mic, location, or notification claims |
| App foreground event | Corroborates active use |
| Network or DNS log | Corroborates device-to-domain contact |

## Suspicious Patterns

| Pattern | Review Action |
| --- | --- |
| Short redirect chain to credential page | Preserve redirect URLs, referrers, cookies, and screenshots if available |
| Download followed by install or execution | Correlate download DB, package install, file hash, and permission changes |
| Browser data in unexpected app container | Review embedded WebView and app permissions |
| Cookies or tokens in exported evidence | Redact and treat as credential exposure |
| Work-profile site data in personal profile | Review profile boundary and MDM policy |
| Repeated failed login or reset URLs | Correlate account notifications and provider logs |
| Cache exists without visit record | Consider prefetch, embedded content, or app rendering |

## Confidence Levels

| Level | Criteria |
| --- | --- |
| High | History/download record, source path, timestamp, package, and corroborating activity align |
| Medium | Source path and timestamp are clear but transition, sync, or user action is inferred |
| Low | Only cache, screenshot, or secondary artifact is available |
| Inconclusive | Profile boundary, timestamp, package attribution, or source integrity conflicts materially |

## Reporting Guidance

Use precise language:

- "The browser history records a visit to..." is stronger than "the user visited..." when attribution is uncertain.
- "A cached resource from this domain exists" does not prove active browsing.
- "A download record exists" does not prove the file was opened or executed.
- "A WebView artifact in app X exists" should not be reported as full browser history without app-container context.
- "Synced tab or bookmark evidence exists" should be distinguished from local device browsing.

## Privacy And Minimization

- Redact cookies, bearer tokens, reset links, session IDs, and autofill values.
- Limit browser exports to the relevant package, profile, and time window.
- Separate personal-profile and work-profile browsing artifacts.
- Avoid reporting unrelated search history, medical, financial, legal, or family content unless directly in scope.
- Preserve raw evidence under chain-of-custody controls while minimizing report excerpts.

## Release Checklist

- Browser, WebView, custom tab, and sync artifacts are differentiated.
- Package, profile ID, source path, parser, and timestamp interpretation are documented.
- Downloads are correlated with file hashes and app/package activity.
- Cookies, tokens, autofill, and local storage are redacted where needed.
- Site permissions and app permissions are compared for sensitive access claims.
- Conclusions state confidence and avoid overclaiming user intent.
