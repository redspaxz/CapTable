-- CapTable — T&Tech Consulting Group (OHADA / Cameroon)
-- MySQL / MariaDB schema

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','finance','viewer') NOT NULL DEFAULT 'viewer',
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
