-- CapTable — T&Tech Consulting Group (OHADA / Cameroon)
-- MySQL / MariaDB schema

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(190) NOT NULL,
    legal_form VARCHAR(120) NOT NULL DEFAULT 'SA',
    rccm VARCHAR(120) DEFAULT NULL,
    niu VARCHAR(120) DEFAULT NULL,
    head_office VARCHAR(190) DEFAULT NULL,
    currency VARCHAR(8) NOT NULL DEFAULT 'XAF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS shareholders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('individual','corporate') NOT NULL DEFAULT 'individual',
    name VARCHAR(190) NOT NULL,
    id_type VARCHAR(20) NOT NULL DEFAULT 'CNI',
    id_number VARCHAR(120) NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    phone VARCHAR(60) DEFAULT NULL,
    nationality VARCHAR(90) DEFAULT 'Camerounaise',
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS share_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    nominal_value BIGINT UNSIGNED NOT NULL,
    shares_authorized BIGINT UNSIGNED NOT NULL,
    rights TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS share_issuances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    share_class_id INT NOT NULL,
    shareholder_id INT NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    apport_type ENUM('cash','in_kind') NOT NULL DEFAULT 'cash',
    issuance_date DATE NOT NULL,
    reference VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (share_class_id) REFERENCES share_classes(id),
    FOREIGN KEY (shareholder_id) REFERENCES shareholders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS share_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    share_class_id INT NOT NULL,
    seller_id INT NOT NULL,
    buyer_id INT NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    price_per_share BIGINT UNSIGNED DEFAULT NULL,
    transfer_date DATE NOT NULL,
    deed_reference VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (share_class_id) REFERENCES share_classes(id),
    FOREIGN KEY (seller_id) REFERENCES shareholders(id),
    FOREIGN KEY (buyer_id) REFERENCES shareholders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Append-only register of title movements (AUSCGIE art. 716)
CREATE TABLE IF NOT EXISTS share_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_type ENUM('issuance','transfer_out','transfer_in') NOT NULL,
    share_class_id INT NOT NULL,
    shareholder_id INT NOT NULL,
    counterparty_id INT DEFAULT NULL,
    quantity INT UNSIGNED NOT NULL,
    movement_date DATE NOT NULL,
    reference VARCHAR(120) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (share_class_id) REFERENCES share_classes(id),
    FOREIGN KEY (shareholder_id) REFERENCES shareholders(id),
    FOREIGN KEY (counterparty_id) REFERENCES shareholders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Read-path indexes: the register is scanned by as-of reconstructions and
-- the movement register page, which filter/sort on these columns.
-- (shareholder_id / counterparty_id are already indexed via their FKs.)
CREATE INDEX idx_movements_class_date ON share_movements (share_class_id, movement_date);
CREATE INDEX idx_movements_date ON share_movements (movement_date);
-- Covering index for as-of reconstructions: the date range plus the
-- columns needed by the aggregation, so the fold is an index-only scan.
CREATE INDEX idx_movements_cover ON share_movements (movement_date, movement_type, shareholder_id, counterparty_id, share_class_id, quantity);

-- Projection of current net holdings, maintained in the same transaction
-- as every register write. Current-state reads go through this table so
-- large registers are never re-aggregated on every request; the append-only
-- share_movements register remains the source of truth.
CREATE TABLE IF NOT EXISTS share_holdings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shareholder_id INT NOT NULL,
    share_class_id INT NOT NULL,
    quantity BIGINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_shareholder_class (shareholder_id, share_class_id),
    FOREIGN KEY (shareholder_id) REFERENCES shareholders(id),
    FOREIGN KEY (share_class_id) REFERENCES share_classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS share_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_number VARCHAR(20) NOT NULL UNIQUE,
    shareholder_id INT NOT NULL,
    share_class_id INT NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    issue_date DATE NOT NULL,
    status ENUM('active','cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shareholder_id) REFERENCES shareholders(id),
    FOREIGN KEY (share_class_id) REFERENCES share_classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(255) NOT NULL,
    ref VARCHAR(120) DEFAULT NULL,
    payload JSON,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- v2: Ownership & Equity Administration
ALTER TABLE share_classes ADD COLUMN liquidation_multiplier DECIMAL(5,2) NOT NULL DEFAULT 1.00;
ALTER TABLE share_classes ADD COLUMN liquidation_priority INT NOT NULL DEFAULT 100;
ALTER TABLE share_classes ADD COLUMN participating TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE users ADD COLUMN shareholder_id INT NULL, ADD FOREIGN KEY users_sh (shareholder_id) REFERENCES shareholders(id);
ALTER TABLE users ADD COLUMN stakeholder_role VARCHAR(20) NULL;
ALTER TABLE settings ADD COLUMN fmv_per_share BIGINT UNSIGNED NULL;

CREATE TABLE IF NOT EXISTS option_grants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shareholder_id INT NOT NULL,
    share_class_id INT NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    exercised_qty INT UNSIGNED NOT NULL DEFAULT 0,
    strike_price BIGINT UNSIGNED NOT NULL DEFAULT 0,
    granted_at DATE NOT NULL,
    vest_months INT UNSIGNED NOT NULL DEFAULT 48,
    cliff_months INT UNSIGNED NOT NULL DEFAULT 12,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shareholder_id) REFERENCES shareholders(id),
    FOREIGN KEY (share_class_id) REFERENCES share_classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS option_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grant_id INT NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    exercise_date DATE NOT NULL,
    reference VARCHAR(120) DEFAULT NULL,
    share_issuance_id INT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grant_id) REFERENCES option_grants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- v3: OHADA compliance, governance, convertibles, UBO
ALTER TABLE settings ADD COLUMN secondary_currency VARCHAR(8) NULL;
ALTER TABLE settings ADD COLUMN fx_rate DECIMAL(12,6) NOT NULL DEFAULT 1.0;
ALTER TABLE settings ADD COLUMN option_tax_rate DECIMAL(5,4) NOT NULL DEFAULT 0.30;
ALTER TABLE settings ADD COLUMN default_language VARCHAR(5) NOT NULL DEFAULT 'en';

ALTER TABLE share_classes ADD COLUMN category VARCHAR(20) NOT NULL DEFAULT 'ordinary';
ALTER TABLE share_classes ADD COLUMN voting_weight INT NOT NULL DEFAULT 1;
ALTER TABLE share_classes ADD COLUMN requires_approval TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE share_classes ADD COLUMN lockup_until DATE NULL;

ALTER TABLE share_movements ADD COLUMN rccm_reference VARCHAR(120) NULL;
ALTER TABLE share_movements ADD COLUMN rccm_filed_at DATE NULL;
ALTER TABLE share_movements ADD COLUMN notary_reference VARCHAR(120) NULL;

ALTER TABLE share_transfers ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'executed';
ALTER TABLE share_transfers ADD COLUMN approval_date DATE NULL;
ALTER TABLE share_transfers ADD COLUMN preemption_deadline DATE NULL;

ALTER TABLE option_grants ADD COLUMN vesting_type VARCHAR(20) NOT NULL DEFAULT 'time';
ALTER TABLE option_grants ADD COLUMN milestone_label VARCHAR(190) NULL;
ALTER TABLE option_grants ADD COLUMN milestone_achieved_at DATE NULL;

ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'viewer';

CREATE TABLE IF NOT EXISTS beneficial_owners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    id_number VARCHAR(120) NULL,
    nationality VARCHAR(90) DEFAULT 'Camerounaise',
    ownership_pct DECIMAL(6,3) NOT NULL DEFAULT 0,
    control_nature VARCHAR(255) NULL,
    shareholder_id INT NULL,
    declared_at DATE NULL,
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shareholder_id) REFERENCES shareholders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS convertibles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(10) NOT NULL,
    holder VARCHAR(190) NOT NULL,
    principal_amount BIGINT UNSIGNED NOT NULL,
    currency VARCHAR(8) NOT NULL DEFAULT 'XAF',
    discount_pct DECIMAL(5,4) NOT NULL DEFAULT 0,
    valuation_cap BIGINT UNSIGNED NULL,
    issue_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'outstanding',
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE share_transfers ADD COLUMN notary_reference VARCHAR(120) NULL;
