CREATE TABLE IF NOT EXISTS research_sources (
    id VARCHAR(80) PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    authors VARCHAR(500) NOT NULL,
    publication_year INT NOT NULL,
    venue VARCHAR(500) NOT NULL,
    doi VARCHAR(120) NULL,
    source_url VARCHAR(500) NOT NULL,
    contribution TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acquisition_methods (
    id VARCHAR(80) PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    access_level VARCHAR(255) NOT NULL,
    coverage_hint DECIMAL(5,2) NOT NULL,
    strengths JSON NOT NULL,
    limitations JSON NOT NULL,
    feature_scores JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evidence_features (
    id VARCHAR(80) PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    category VARCHAR(80) NOT NULL,
    feature_weight INT NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tool_profiles (
    id VARCHAR(80) PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    category VARCHAR(120) NOT NULL,
    use_case TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forensic_controls (
    id VARCHAR(80) PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    category VARCHAR(120) NOT NULL,
    control_weight INT NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS case_assessments (
    id CHAR(36) PRIMARY KEY,
    case_name VARCHAR(180) NOT NULL,
    device_model VARCHAR(180) NOT NULL,
    score INT NOT NULL,
    readiness VARCHAR(80) NOT NULL,
    risk_tier VARCHAR(40) NOT NULL,
    selected_controls JSON NOT NULL,
    selected_methods JSON NOT NULL,
    result_payload JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_case_assessments_created_at (created_at),
    INDEX idx_case_assessments_score (score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wiping_evaluations (
    id CHAR(36) PRIMARY KEY,
    app_name VARCHAR(180) NOT NULL,
    classification VARCHAR(255) NOT NULL,
    risk_score INT NOT NULL,
    standards_status VARCHAR(120) NOT NULL,
    recoverability_status VARCHAR(160) NOT NULL,
    result_payload JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wiping_evaluations_created_at (created_at),
    INDEX idx_wiping_evaluations_risk_score (risk_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hash_ledger_runs (
    id CHAR(36) PRIMARY KEY,
    case_name VARCHAR(180) NOT NULL,
    item_count INT NOT NULL,
    merkle_root CHAR(64) NOT NULL,
    result_payload JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hash_ledger_merkle_root (merkle_root),
    INDEX idx_hash_ledger_runs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chain_of_custody_events (
    id CHAR(36) PRIMARY KEY,
    evidence_id VARCHAR(120) NOT NULL,
    handler_name VARCHAR(180) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    event_time DATETIME NOT NULL,
    location VARCHAR(180) NOT NULL,
    notes TEXT NULL,
    previous_hash CHAR(64) NULL,
    event_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_custody_evidence_time (evidence_id, event_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_events (
    id CHAR(36) PRIMARY KEY,
    event_name VARCHAR(160) NOT NULL,
    payload JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_events_created_at (created_at),
    INDEX idx_audit_events_event_name (event_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

