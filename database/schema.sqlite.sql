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
    code TEXT NOT NULL UNIQUE,
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
