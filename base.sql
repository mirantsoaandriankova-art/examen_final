-- =============================================
-- SCHEMA BASE DE DONNÉES - MOBILE MONEY v1
-- =============================================

-- 1. Préfixes de l'opérateur
CREATE TABLE IF NOT EXISTS prefixes_operateur (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prefixe TEXT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Types d'opérations
CREATE TABLE IF NOT EXISTS types_operation (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL UNIQUE,           -- dépôt, retrait, transfert
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Barèmes de frais (selon ton exemple)
CREATE TABLE IF NOT EXISTS baremes_frais (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type_operation_id INTEGER NOT NULL,
    tranche_min INTEGER NOT NULL,
    tranche_max INTEGER,                -- NULL = illimité
    frais INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_operation_id) REFERENCES types_operation(id)
);

-- 4. Comptes clients
CREATE TABLE IF NOT EXISTS comptes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telephone TEXT NOT NULL UNIQUE,
    solde REAL DEFAULT 0.0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 5. Historique des transactions (mouvements)
CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type_operation_id INTEGER NOT NULL,
    telephone_source TEXT NOT NULL,
    telephone_dest TEXT,
    montant REAL NOT NULL,
    frais REAL NOT NULL DEFAULT 0,
    montant_total REAL NOT NULL,
    statut TEXT DEFAULT 'succes',
    date_transaction DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_operation_id) REFERENCES types_operation(id)
);

-- =============================================
-- DONNÉES INITIALES (selon ta photo)
-- =============================================

-- Préfixes
INSERT OR IGNORE INTO prefixes_operateur (prefixe) VALUES ('033'), ('037');

-- Types d'opérations
INSERT OR IGNORE INTO types_operation (nom, description) VALUES 
('depot', 'Dépôt d\'argent'),
('retrait', 'Retrait d\'argent'),
('transfert', 'Transfert d\'argent');

-- Barèmes de frais selon ton exemple (je les ai associés au retrait)
-- Tu pourras les modifier facilement dans l'interface admin
INSERT OR IGNORE INTO baremes_frais (type_operation_id, tranche_min, tranche_max, frais) VALUES 

-- Retrait (id=2)
(2, 100, 1000, 50),
(2, 1001, 5000, 50),
(2, 5001, 10000, 100),
(2, 10001, 25000, 200),
(2, 25001, 50000, 400),
(2, 50001, 100000, 800),
(2, 100001, 250000, 1500),
(2, 250001, 500000, 2500),
(2, 500001, 1000000, 3000),
(2, 1000001, NULL, 5000);   -- au-delà de 1 000 000

-- Tu peux créer d'autres barèmes pour "transfert" plus tard