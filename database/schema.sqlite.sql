-- CapTable — SQLite schema (local dev / demo)

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'viewer',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_name TEXT NOT NULL,
    legal_form TEXT NOT NULL DEFAULT 'SA',
    rccm TEXT,
    niu TEXT,
    head_office TEXT,
    currency TEXT NOT NULL DEFAULT 'XAF'
);

CREATE TABLE IF NOT EXISTS shareholders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL DEFAULT 'individual',
    name TEXT NOT NULL,
    id_type TEXT NOT NULL DEFAULT 'CNI',
    id_number TEXT NOT NULL,
    address TEXT,
    email TEXT,
    phone TEXT,
    nationality TEXT DEFAULT 'Camerounaise',
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS share_classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL,
    name TEXT NOT NULL,
    nominal_value INTEGER NOT NULL,
    shares_authorized INTEGER NOT NULL,
    rights TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS share_issuances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    share_class_id INTEGER NOT NULL REFERENCES share_classes(id),
    shareholder_id INTEGER NOT NULL REFERENCES shareholders(id),
    quantity INTEGER NOT NULL,
    apport_type TEXT NOT NULL DEFAULT 'cash',
    issuance_date TEXT NOT NULL,
    reference TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS share_transfers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    share_class_id INTEGER NOT NULL REFERENCES share_classes(id),
    seller_id INTEGER NOT NULL REFERENCES shareholders(id),
    buyer_id INTEGER NOT NULL REFERENCES shareholders(id),
    quantity INTEGER NOT NULL,
    price_per_share INTEGER,
    transfer_date TEXT NOT NULL,
    deed_reference TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS share_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    movement_type TEXT NOT NULL,
    share_class_id INTEGER NOT NULL REFERENCES share_classes(id),
    shareholder_id INTEGER NOT NULL REFERENCES shareholders(id),
    counterparty_id INTEGER,
    quantity INTEGER NOT NULL,
    movement_date TEXT NOT NULL,
    reference TEXT,
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Read-path indexes: the register is scanned by as-of reconstructions and
-- the movement register page, which filter/sort on these columns.
CREATE INDEX IF NOT EXISTS idx_movements_class_date ON share_movements (share_class_id, movement_date);
CREATE INDEX IF NOT EXISTS idx_movements_date ON share_movements (movement_date);
-- Covering index for as-of reconstructions: the date range plus the
-- columns needed by the aggregation, so the fold is an index-only scan.
CREATE INDEX IF NOT EXISTS idx_movements_cover ON share_movements (movement_date, movement_type, shareholder_id, counterparty_id, share_class_id, quantity);

-- Projection of current net holdings, maintained in the same transaction
-- as every register write. Current-state reads go through this table so
-- large registers are never re-aggregated on every request; the append-only
-- share_movements register remains the source of truth.
CREATE TABLE IF NOT EXISTS share_holdings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shareholder_id INTEGER NOT NULL REFERENCES shareholders(id),
    share_class_id INTEGER NOT NULL REFERENCES share_classes(id),
    quantity INTEGER NOT NULL DEFAULT 0,
    UNIQUE (shareholder_id, share_class_id)
);

CREATE TABLE IF NOT EXISTS share_certificates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    certificate_number TEXT NOT NULL UNIQUE,
    shareholder_id INTEGER NOT NULL REFERENCES shareholders(id),
    share_class_id INTEGER NOT NULL REFERENCES share_classes(id),
    quantity INTEGER NOT NULL,
    issue_date TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,
    title TEXT NOT NULL,
    ref TEXT,
    payload TEXT,
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- v2: Ownership & Equity Administration
ALTER TABLE share_classes ADD COLUMN liquidation_multiplier REAL NOT NULL DEFAULT 1.0;
ALTER TABLE share_classes ADD COLUMN liquidation_priority INTEGER NOT NULL DEFAULT 100;
ALTER TABLE share_classes ADD COLUMN participating INTEGER NOT NULL DEFAULT 1;

ALTER TABLE users ADD COLUMN shareholder_id INTEGER REFERENCES shareholders(id);
ALTER TABLE users ADD COLUMN stakeholder_role TEXT;

ALTER TABLE settings ADD COLUMN fmv_per_share INTEGER;

CREATE TABLE IF NOT EXISTS option_grants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shareholder_id INTEGER NOT NULL REFERENCES shareholders(id),
    share_class_id INTEGER NOT NULL REFERENCES share_classes(id),
    quantity INTEGER NOT NULL,
    exercised_qty INTEGER NOT NULL DEFAULT 0,
    strike_price INTEGER NOT NULL DEFAULT 0,
    granted_at TEXT NOT NULL,
    vest_months INTEGER NOT NULL DEFAULT 48,
    cliff_months INTEGER NOT NULL DEFAULT 12,
    status TEXT NOT NULL DEFAULT 'active',
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS option_exercises (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    grant_id INTEGER NOT NULL REFERENCES option_grants(id),
    quantity INTEGER NOT NULL,
    exercise_date TEXT NOT NULL,
    reference TEXT,
    share_issuance_id INTEGER,
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- v3: OHADA compliance, governance, convertibles, UBO
ALTER TABLE settings ADD COLUMN secondary_currency TEXT;
ALTER TABLE settings ADD COLUMN fx_rate REAL NOT NULL DEFAULT 1.0;      -- XAF per 1 unit of secondary currency
ALTER TABLE settings ADD COLUMN option_tax_rate REAL NOT NULL DEFAULT 0.30;
ALTER TABLE settings ADD COLUMN default_language VARCHAR(5) NOT NULL DEFAULT 'en';

ALTER TABLE share_classes ADD COLUMN category TEXT NOT NULL DEFAULT 'ordinary';  -- ordinary|preference|adpsdv
ALTER TABLE share_classes ADD COLUMN voting_weight INTEGER NOT NULL DEFAULT 1;   -- 0 = sans droit de vote, 2 = double vote
ALTER TABLE share_classes ADD COLUMN requires_approval INTEGER NOT NULL DEFAULT 0; -- clause d'agrement
ALTER TABLE share_classes ADD COLUMN lockup_until TEXT;

ALTER TABLE share_movements ADD COLUMN rccm_reference TEXT;
ALTER TABLE share_movements ADD COLUMN rccm_filed_at TEXT;
ALTER TABLE share_movements ADD COLUMN notary_reference TEXT;

ALTER TABLE share_transfers ADD COLUMN status TEXT NOT NULL DEFAULT 'executed'; -- pending|approved|rejected
ALTER TABLE share_transfers ADD COLUMN approval_date TEXT;
ALTER TABLE share_transfers ADD COLUMN preemption_deadline TEXT;

ALTER TABLE option_grants ADD COLUMN vesting_type TEXT NOT NULL DEFAULT 'time'; -- time|milestone
ALTER TABLE option_grants ADD COLUMN milestone_label TEXT;
ALTER TABLE option_grants ADD COLUMN milestone_achieved_at TEXT;

CREATE TABLE IF NOT EXISTS beneficial_owners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    id_number TEXT,
    nationality TEXT DEFAULT 'Camerounaise',
    ownership_pct REAL NOT NULL DEFAULT 0,
    control_nature TEXT,
    shareholder_id INTEGER REFERENCES shareholders(id),
    declared_at TEXT,
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS convertibles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,                    -- OCA | BSA | SAFE
    holder TEXT NOT NULL,
    principal_amount INTEGER NOT NULL,     -- in XAF equivalent
    currency TEXT NOT NULL DEFAULT 'XAF',
    discount_pct REAL NOT NULL DEFAULT 0,
    valuation_cap INTEGER,
    issue_date TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'outstanding',
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE share_transfers ADD COLUMN notary_reference TEXT;

-- Multi-tenancy: one deployment serves several companies.
CREATE TABLE IF NOT EXISTS tenants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO tenants (id, name) VALUES (1, 'T&Tech Consulting Group');

ALTER TABLE settings ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE users ADD COLUMN tenant_id INTEGER NULL DEFAULT 1;
ALTER TABLE shareholders ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE share_classes ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE share_issuances ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE share_transfers ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE share_movements ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE share_holdings ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE share_certificates ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE documents ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE option_grants ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE option_exercises ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE beneficial_owners ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
ALTER TABLE convertibles ADD COLUMN tenant_id INTEGER NOT NULL DEFAULT 1;
CREATE UNIQUE INDEX IF NOT EXISTS uq_class_tenant_code ON share_classes (tenant_id, code);
