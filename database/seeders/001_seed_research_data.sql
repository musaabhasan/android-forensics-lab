INSERT INTO research_sources (id, title, authors, publication_year, venue, doi, source_url, contribution) VALUES
('kumar-narayan-rasid-2026', 'Android Forensics: A Literature Review of Methodologies and Tool Efficacy', 'Prince Kumar, Ritushree Narayan, Ekbal Rasid', 2026, 'International Journal of Advanced Networking and Applications, 17(4), 7045-7054', '10.35444/IJANA.2025.17407', 'https://www.ijana.in/papers/V17I4-7.pdf', 'Android acquisition categories, tool coverage limits, anti-forensics challenges, admissibility concerns, and evidence integrity patterns.'),
('sanna-2026-thesis', 'Artificial Intelligence for Android Stealth-Attack Detection: A Digital Forensics Approach', 'Silvialucia Sanna', 2026, 'Doctoral thesis, Sapienza University of Rome', NULL, 'https://hdl.handle.net/20.500.14242/357556', 'Native-code exploitability triage, memory acquisition planning, stealth malware indicators, and anti-analysis resilience.'),
('gunay-gul-ertam-2026', 'Comparative Analysis of Digital Forensics Methods on Android Devices', 'Ozge Gunay, Batuhan Gul, Fatih Ertam', 2026, 'Firat University Journal of Experimental and Computational Engineering, 5(1), 1-25', '10.62520/fujece.1600312', 'https://dergipark.org.tr/en/pub/fujece/article/1600312', 'Method comparison for manual inspection, logical imaging, and physical imaging across nine evidence features.'),
('oh-et-al-2026', 'Forensic Analysis and Evaluation of File-Wiping Applications in Android OS', 'Dong Bin Oh, Somi Lim, Suji Lee, Yesong Jo, Gahyun Choi, Bumyun Kim, Huy Kang Kim', 2026, 'Journal of Forensic Sciences, 71(1), 338-352', '10.1111/1556-4029.70174', 'https://doi.org/10.1111/1556-4029.70174', 'File-wiping claim evaluation, implementation evidence, standards alignment, recoverability, and residual artifacts.'),
('bhardwaj-kaushik-2023', 'Practical Digital Forensics', 'Akashdeep Bhardwaj, Keshav Kaushik', 2023, 'BPB Publications', NULL, 'https://reference-global.com/book/9789355511454', 'Lab operating model, evidence handling discipline, structured investigation workflow, and forensic lab readiness.')
ON DUPLICATE KEY UPDATE title = VALUES(title), contribution = VALUES(contribution);

INSERT INTO evidence_features (id, name, category, feature_weight, description) VALUES
('device-identity', 'Device Identity', 'System', 7, 'Device model, Android build, serials, accounts, carrier, SIM, and lock state.'),
('installed-apps', 'Installed Applications', 'Application', 8, 'Package inventory, install sources, permissions, signing certificates, app versions, and suspicious packages.'),
('contacts-accounts', 'Contacts and Accounts', 'User Data', 6, 'Contacts, account bindings, profile artifacts, synced identities, and ownership indicators.'),
('messages-notifications', 'Messages and Notifications', 'User Data', 9, 'SMS, messaging databases, notification artifacts, chat exports, and encryption limitations.'),
('browser-app-data', 'Browser and App Data', 'Application', 8, 'SQLite databases, WebView storage, cache records, app-specific files, tokens, and local configuration.'),
('media-metadata', 'Media and Metadata', 'User Data', 7, 'Images, videos, thumbnails, EXIF metadata, media-store records, and residual preview artifacts.'),
('deleted-unallocated', 'Deleted and Unallocated Data', 'Recovery', 10, 'Recoverable deleted data, file slack, unallocated space, remnants, and overwritten-content limits.'),
('malware-indicators', 'Malware Indicators', 'Malware', 9, 'Static indicators, dynamic behavior, package anomalies, network callbacks, evasion behavior, and persistence traces.'),
('cloud-sync-artifacts', 'Cloud and Sync Artifacts', 'Cloud', 8, 'Backups, cloud service records, account synchronization, remote deletion risk, and legal-access requirements.')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO tool_profiles (id, name, category, use_case) VALUES
('magnet-axiom', 'Magnet AXIOM', 'Commercial suite', 'Logical, file-system, artifact parsing, timeline analysis, and reporting validation.'),
('belkasoft-x', 'Belkasoft X', 'Commercial suite', 'Mobile artifact parsing, media review, chat artifacts, and cross-source case analysis.'),
('cellebrite-ufed', 'Cellebrite UFED', 'Commercial extraction', 'Logical, file-system, and supported physical acquisition workflows.'),
('andriller', 'Andriller', 'Focused extraction', 'Android logical acquisition and selected artifact parsing.'),
('aflogical', 'AFLogical', 'Open-source extraction', 'Logical acquisition for selected Android datasets.'),
('autopsy-sleuth-kit', 'Autopsy and The Sleuth Kit', 'Open-source analysis', 'Filesystem review, timeline correlation, hash sets, keyword search, and reporting.'),
('jadx-dex2jar', 'JADX and dex2jar', 'Reverse engineering', 'APK static analysis, manifest review, bytecode inspection, and file-wiping claim validation.'),
('frida-strace', 'Frida and strace', 'Runtime instrumentation', 'Dynamic behavior observation, API tracing, file access review, and anti-analysis experiments.'),
('memory-acquisition-toolkit', 'Android Memory Acquisition Toolkit', 'Volatile evidence', 'Process or full-memory capture planning, memory artifact review, and stealth behavior triage.'),
('hash-ledger', 'Hash Ledger and Merkle Manifest', 'Integrity control', 'Evidence inventory integrity, manifest comparison, peer review, and custody checkpoints.')
ON DUPLICATE KEY UPDATE name = VALUES(name), use_case = VALUES(use_case);

INSERT INTO forensic_controls (id, name, category, control_weight, description) VALUES
('legal-authority', 'Document Legal Authority', 'Governance', 10, 'Record authority, scope, consent, and limits before acquisition.'),
('case-scope', 'Define Case Scope', 'Governance', 7, 'Document devices, accounts, apps, dates, questions, and exclusions.'),
('privacy-minimization', 'Privacy Minimization', 'Governance', 7, 'Limit review and exports to relevant artifacts.'),
('chain-of-custody', 'Chain of Custody', 'Evidence Handling', 10, 'Record every transfer, handler, storage location, and evidence seal.'),
('hash-manifest', 'Hash Manifest', 'Evidence Integrity', 10, 'Create SHA-256 manifests for extracted files, images, exports, and reports.'),
('merkle-root', 'Merkle Manifest Root', 'Evidence Integrity', 8, 'Create a compact integrity root over normalized evidence manifests.'),
('method-selection-rationale', 'Method Selection Rationale', 'Acquisition', 9, 'Explain why selected acquisition methods fit the device state and case questions.'),
('memory-capture-plan', 'Memory Capture Plan', 'Memory Forensics', 8, 'Plan process or full-memory capture, root constraints, timing, tooling, and validation.'),
('wiping-claim-review', 'Wiping Claim Review', 'Anti-Forensics', 7, 'Compare app claims with static code, runtime writes, recoverability, and residual artifacts.'),
('peer-review', 'Peer Review', 'Reporting', 8, 'Require independent technical review before release of formal results.')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

