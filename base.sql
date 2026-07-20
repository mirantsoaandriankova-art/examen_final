-- =============================================
-- SCHEMA BASE DE DONNÉES - MOBILE MONEY v1
-- Base : examenfinals4.db
-- =============================================

PRAGMA foreign_keys = ON;

-- 1. Préfixes de l'opérateur (validation du format du numéro uniquement)
CREATE TABLE IF NOT EXISTS prefixes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prefixe TEXT NOT NULL UNIQUE,
    description TEXT,
    actif INTEGER NOT NULL DEFAULT 1 CHECK (actif IN (0, 1))
);

-- 2. Types d'opérations
CREATE TABLE IF NOT EXISTS types_operation (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,                  -- depot | retrait | transfert
    libelle TEXT NOT NULL,
    frais_applicable INTEGER NOT NULL DEFAULT 1 CHECK (frais_applicable IN (0, 1))
);

-- 3. Barèmes de frais applicables au dépôt, retrait et transfert
CREATE TABLE IF NOT EXISTS baremes_frais (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type_operation_id INTEGER NOT NULL,
    montant_min REAL NOT NULL,
    montant_max REAL,                            -- NULL = illimité
    frais REAL NOT NULL,
    FOREIGN KEY (type_operation_id) REFERENCES types_operation(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CHECK (montant_max IS NULL OR montant_max > montant_min),
    CHECK (frais >= 0)
);

-- 4. Comptes (clients ET admin)
CREATE TABLE IF NOT EXISTS comptes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telephone TEXT NOT NULL UNIQUE,
    nom TEXT,
    solde REAL NOT NULL DEFAULT 0.0,
    role TEXT NOT NULL DEFAULT 'client' CHECK (role IN ('client', 'admin')),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    CHECK (solde >= 0)
);

-- 5. Transactions : une ligne = un mouvement sur UN compte
--    Un transfert = 2 lignes liées (débit émetteur + crédit destinataire via compte_lie_id)
--    compte_lie_id n'est PAS forcément le "receveur" : c'est juste l'autre compte du transfert,
--    c'est "sens" (credit/debit) qui indique qui paie et qui reçoit sur chaque ligne.
CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    compte_id INTEGER NOT NULL,
    type_operation_id INTEGER NOT NULL,
    montant REAL NOT NULL,
    frais REAL NOT NULL DEFAULT 0,
    solde_apres REAL NOT NULL,
    sens TEXT NOT NULL CHECK (sens IN ('credit', 'debit')),
    compte_lie_id INTEGER,                       -- NULL sauf pour un transfert
    date_operation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (compte_id) REFERENCES comptes(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    FOREIGN KEY (compte_lie_id) REFERENCES comptes(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    FOREIGN KEY (type_operation_id) REFERENCES types_operation(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CHECK (montant > 0),
    CHECK (frais >= 0),
    CHECK (solde_apres >= 0)
);

-- Index pour historique client / admin
CREATE INDEX IF NOT EXISTS idx_transactions_compte ON transactions(compte_id);
CREATE INDEX IF NOT EXISTS idx_transactions_compte_lie ON transactions(compte_lie_id);
CREATE INDEX IF NOT EXISTS idx_transactions_type ON transactions(type_operation_id);
CREATE INDEX IF NOT EXISTS idx_baremes_type ON baremes_frais(type_operation_id);

-- Vue : gains totaux par type d'opération (dashboard admin)
CREATE VIEW IF NOT EXISTS vue_gains_par_type AS
SELECT
    t.id AS type_operation_id,
    t.code AS type_code,
    t.libelle AS type_libelle,
    COALESCE(SUM(tr.frais), 0) AS total_frais,
    COUNT(tr.id) AS nombre_operations
FROM types_operation t
LEFT JOIN transactions tr ON tr.type_operation_id = t.id
GROUP BY t.id, t.code, t.libelle;

-- =============================================
-- DONNÉES INITIALES
-- =============================================

-- Préfixes
INSERT OR IGNORE INTO prefixes (prefixe, description, actif) VALUES
('033', 'Opérateur A', 1),
('037', 'Opérateur B', 1);

-- Types d'opérations (dépôt, retrait et transfert avec frais)
INSERT OR IGNORE INTO types_operation (code, libelle, frais_applicable) VALUES
('depot', 'Dépôt d''argent', 1),
('retrait', 'Retrait d''argent', 1),
('transfert', 'Transfert d''argent', 1);

-- Utilise une sous-requête sur "code" pour ne pas dépendre de l'ordre d'insertion des id
INSERT OR IGNORE INTO baremes_frais (type_operation_id, montant_min, montant_max, frais)

-- Dépôt (frais réduits)
SELECT id, 100, 1000, 20 FROM types_operation WHERE code = 'depot'
UNION ALL SELECT id, 1001, 5000, 20 FROM types_operation WHERE code = 'depot'
UNION ALL SELECT id, 5001, 10000, 40 FROM types_operation WHERE code = 'depot'
UNION ALL SELECT id, 10001, 25000, 100 FROM types_operation WHERE code = 'depot'
UNION ALL SELECT id, 25001, 50000, 150 FROM types_operation WHERE code = 'depot'
UNION ALL SELECT id, 50001, 100000, 250 FROM types_operation WHERE code = 'depot'
UNION ALL SELECT id, 100001, 250000, 400 FROM types_operation WHERE code = 'depot'
UNION ALL SELECT id, 250001, 500000, 600 FROM types_operation WHERE code = 'depot'
UNION ALL SELECT id, 500001, 1000000, 800 FROM types_operation WHERE code = 'depot'
UNION ALL SELECT id, 1000001, NULL, 1000 FROM types_operation WHERE code = 'depot'

-- Retrait (frais les plus élevés)
UNION ALL SELECT id, 100, 1000, 50 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 1001, 5000, 50 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 5001, 10000, 100 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 10001, 25000, 200 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 25001, 50000, 400 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 50001, 100000, 800 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 100001, 250000, 1500 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 250001, 500000, 2500 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 500001, 1000000, 3000 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 1000001, NULL, 5000 FROM types_operation WHERE code = 'retrait'

-- Transfert (intermédiaire entre dépôt et retrait)
UNION ALL SELECT id, 100, 1000, 30 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 1001, 5000, 30 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 5001, 10000, 60 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 10001, 25000, 120 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 25001, 50000, 250 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 50001, 100000, 500 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 100001, 250000, 900 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 250001, 500000, 1500 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 500001, 1000000, 2000 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 1000001, NULL, 3000 FROM types_operation WHERE code = 'transfert';

-- Comptes de test (login = numéro de téléphone existant, pas d'inscription)
INSERT OR IGNORE INTO comptes (telephone, nom, solde, role) VALUES
('0331234567', 'Rakoto Jean', 500000, 'client'),
('0372345678', 'Rasoa Marie', 250000, 'client'),
('0339999999', 'Randria Paul', 10000, 'client'),
('0330000000', 'Administrateur', 0, 'admin');
