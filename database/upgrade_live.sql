-- CapTable — mise à niveau de la base MySQL de production
-- Ajoute les colonnes et tables v2 (options, waterfall), v3 (conformité
-- OHADA, convertibles, gouvernance) et la table de projection
-- share_holdings. Sans ce script, toutes les pages authentifiées
-- renvoient une erreur 500 (colonnes manquantes).
--
-- Si une ligne échoue avec « Duplicate column name », cette colonne
-- existe déjà : supprimez la ligne concernée et relancez le reste.

ALTER TABLE settings ADD COLUMN fmv_per_share BIGINT UNSIGNED NULL;
ALTER TABLE settings ADD COLUMN secondary_currency VARCHAR(8) NULL;
ALTER TABLE settings ADD COLUMN fx_rate DECIMAL(12,6) NOT NULL DEFAULT 1.0;
ALTER TABLE settings ADD COLUMN option_tax_rate DECIMAL(5,4) NOT NULL DEFAULT 0.30;

ALTER TABLE share_classes ADD COLUMN liquidation_multiplier DECIMAL(5,2) NOT NULL DEFAULT 1.00;
ALTER TABLE share_classes ADD COLUMN liquidation_priority INT NOT NULL DEFAULT 100;
ALTER TABLE share_classes ADD COLUMN participating TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE share_classes ADD COLUMN category VARCHAR(20) NOT NULL DEFAULT 'ordinary';
ALTER TABLE share_classes ADD COLUMN voting_weight INT NOT NULL DEFAULT 1;
ALTER TABLE share_classes ADD COLUMN requires_approval TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE share_classes ADD COLUMN lockup_until DATE NULL;

ALTER TABLE users ADD COLUMN shareholder_id INT NULL;
ALTER TABLE users ADD COLUMN stakeholder_role VARCHAR(20) NULL;
ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'viewer';

ALTER TABLE share_movements ADD COLUMN rccm_reference VARCHAR(120) NULL;
ALTER TABLE share_movements ADD COLUMN rccm_filed_at DATE NULL;
ALTER TABLE share_movements ADD COLUMN notary_reference VARCHAR(120) NULL;

ALTER TABLE share_transfers ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'executed';
ALTER TABLE share_transfers ADD COLUMN approval_date DATE NULL;
ALTER TABLE share_transfers ADD COLUMN preemption_deadline DATE NULL;
ALTER TABLE share_transfers ADD COLUMN notary_reference VARCHAR(120) NULL;

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
    vesting_type VARCHAR(20) NOT NULL DEFAULT 'time',
    milestone_label VARCHAR(190) NULL,
    milestone_achieved_at DATE NULL,
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

CREATE TABLE IF NOT EXISTS share_holdings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shareholder_id INT NOT NULL,
    share_class_id INT NOT NULL,
    quantity BIGINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_shareholder_class (shareholder_id, share_class_id),
    FOREIGN KEY (shareholder_id) REFERENCES shareholders(id),
    FOREIGN KEY (share_class_id) REFERENCES share_classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE settings SET secondary_currency = 'EUR', fx_rate = 655.957,
       option_tax_rate = 0.30, fmv_per_share = 10000;
