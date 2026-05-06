# Testing

The repository includes a compact validation suite focused on behavior that affects trust in the lab output.

## Commands

```bash
php bin/lint.php
php bin/test.php
```

## Coverage

The tests verify:

- PHP syntax across source, public, config, and test scripts.
- Catalog counts and expected research source identifiers.
- Control and method scoring behavior.
- Strong case readiness with complete controls.
- High-risk case behavior when controls are missing.
- Method ranking when deleted data, cloud records, malware, memory, and file wiping are in scope.
- File-wiping classifications for claim mismatch and standards-aligned cases.
- Deterministic Merkle-style root generation.
- Root changes when a manifest entry changes.
- Migration and seed data contain expected tables, DOI values, and controls.
- Public-facing text does not contain internal tool-production wording.

## Manual Review Checklist

- Visit `/casework`, `/methods`, `/wiping`, `/ledger`, and `/research`.
- Submit each form once.
- Confirm API routes return JSON.
- Confirm no stack traces appear when the database is not configured.
- Confirm production `.env` values are not committed.

