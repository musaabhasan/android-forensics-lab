# Security and Privacy

The platform is designed for forensic lab environments where integrity, privacy, and repeatability matter.

## Application Controls

- PHP sessions use HTTP-only, SameSite cookies.
- Security headers include frame, content-type, referrer, permissions, and content security policy controls.
- Forms use CSRF tokens.
- Database access uses PDO prepared statements.
- JSON APIs validate method type and normalize submitted identifiers.
- Timeline and manifest inputs are normalized before analysis and should be linked to separately preserved evidence files.
- Public routes avoid exposing configuration values or raw environment data.

## Evidence Integrity

- Use SHA-256 manifests for exports, images, parsed artifacts, reports, and appendix files.
- Store Merkle-style roots with chain-of-custody records.
- Retain acquisition logs, tool versions, screenshots, and command history when relevant.
- Use independent verification for high-value artifacts and negative findings.
- Preserve both raw evidence and derived analysis products with clear labels.

## Privacy Handling

- Define scope before acquisition.
- Minimize exports to case-relevant artifacts.
- Separate sensitive personal data from technical indicators when possible.
- Apply role-based access controls in production deployments.
- Encrypt evidence storage and backups.
- Retain audit records and access logs according to policy.

## Production Deployment

- Serve only through HTTPS with modern TLS settings.
- Use a managed secret store for database credentials.
- Restrict database access to the application network.
- Disable verbose errors in production.
- Place uploaded evidence outside the web root.
- Back up the database and evidence manifests with tested restoration procedures.
- Monitor audit events and failed access attempts.

## Forensic Boundary

This platform supports documentation, planning, scoring, and defensibility. It does not replace validated forensic tools, examiner judgment, legal authority, or jurisdiction-specific procedures.
