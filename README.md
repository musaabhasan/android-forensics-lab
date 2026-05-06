# Android Digital Forensics Lab

Advanced PHP 8 and MySQL platform for Android forensic casework, acquisition planning, anti-forensics evaluation, volatile evidence triage, evidence integrity, and research-aligned reporting.

The lab is designed for examiners, researchers, and cybersecurity teams who need a structured way to compare Android forensic methods, document defensibility controls, evaluate file-wiping behavior, and preserve evidence integrity with repeatable outputs.

## Core Capabilities

- Case readiness scoring across governance, acquisition, evidence integrity, malware, memory, anti-forensics, privacy, and reporting controls.
- Context-aware method comparison for manual inspection, logical acquisition, file-system acquisition, physical imaging, cloud acquisition, memory acquisition, emulator dynamic analysis, static APK review, and dynamic application review.
- File-wiping evaluation workflow based on declared claims, implementation evidence, standards alignment, recoverability, execution traces, app artifacts, OS artifacts, and timeline consistency.
- Merkle-style SHA-256 evidence ledger for deterministic manifest integrity checkpoints.
- Research-source catalog with explicit alignment from Android forensic literature, stealth-attack detection work, method comparison research, file-wiping evaluation, and practical lab operations.
- MySQL schema for research sources, methods, evidence features, tool profiles, controls, case assessments, wiping evaluations, custody events, ledger runs, and audit events.

## Research Foundation

This project is informed by the following works:

1. Prince Kumar, Ritushree Narayan, and Ekbal Rasid, "Android Forensics: A Literature Review of Methodologies and Tool Efficacy," *International Journal of Advanced Networking and Applications*, 17(4), 7045-7054, 2026. DOI: [10.35444/IJANA.2025.17407](https://www.ijana.in/papers/V17I4-7.pdf)
2. Silvialucia Sanna, "Artificial Intelligence for Android Stealth-Attack Detection: A Digital Forensics Approach," doctoral thesis, Sapienza University of Rome, 2026. [Repository record](https://hdl.handle.net/20.500.14242/357556)
3. Ozge Gunay, Batuhan Gul, and Fatih Ertam, "Comparative Analysis of Digital Forensics Methods on Android Devices," *Firat University Journal of Experimental and Computational Engineering*, 5(1), 1-25, 2026. DOI: [10.62520/fujece.1600312](https://dergipark.org.tr/en/pub/fujece/article/1600312)
4. Dong Bin Oh, Somi Lim, Suji Lee, Yesong Jo, Gahyun Choi, Bumyun Kim, and Huy Kang Kim, "Forensic Analysis and Evaluation of File-Wiping Applications in Android OS," *Journal of Forensic Sciences*, 71(1), 338-352, 2026. DOI: [10.1111/1556-4029.70174](https://doi.org/10.1111/1556-4029.70174)
5. Akashdeep Bhardwaj and Keshav Kaushik, *Practical Digital Forensics: Forensic Lab Setup, Evidence Analysis, and Structured Investigation Across Windows, Mobile, Browser, HDD, and Memory*, BPB Publications, 2023. [Book record](https://reference-global.com/book/9789355511454)

## Application Routes

- `/` - dashboard and workflow overview
- `/casework` - forensic readiness assessment
- `/methods` - acquisition and analysis method comparison
- `/wiping` - Android file-wiping evidence evaluation
- `/ledger` - evidence manifest ledger root
- `/research` - research alignment and evidence feature model
- `/health` - service health check

## JSON APIs

```http
GET /api/summary
POST /api/assess
POST /api/method-compare
POST /api/wiping-evaluation
POST /api/hash-ledger
```

Example method comparison request:

```json
{
  "deleted_data_needed": true,
  "cloud_relevant": true,
  "malware_suspected": true,
  "wiping_suspected": true,
  "selected_features": ["deleted-unallocated", "malware-indicators", "browser-app-data"]
}
```

Example evidence ledger request:

```json
{
  "case_name": "Android evidence manifest",
  "manifest": [
    {
      "path": "/extraction/data/com.example/app.db",
      "sha256": "bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb"
    }
  ]
}
```

## Local Run

With PHP installed:

```bash
php -S 127.0.0.1:8098 -t public
```

With Docker:

```bash
docker compose up --build
```

Then open `http://127.0.0.1:8098`.

## Configuration

Copy `.env.example` to `.env` and update database values if persistence is required. The platform works without a database for assessment and API calculations; database connectivity enables persistence and audit records.

## Validation

```bash
php bin/lint.php
php bin/test.php
```

The test suite validates catalog integrity, scoring behavior, method ranking, file-wiping classification, deterministic hash-ledger behavior, database migrations, seed data, and public-facing text hygiene.

## Documentation

- [Architecture](docs/architecture.md)
- [Research Alignment](docs/research-alignment.md)
- [Security and Privacy](docs/security-and-privacy.md)
- [Database Model](docs/database.md)
- [Testing](docs/testing.md)
- [Extension Guide](docs/extension-guide.md)

