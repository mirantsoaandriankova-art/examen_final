-- =============================================
-- SCHEMA BASE DE DONNÉES - MOBILE MONEY v2
-- Base : examenfinals4.db
-- =============================================

PRAGMA foreign_keys = ON;

-- =============================================
-- SUPPRESSION DES TABLES EXISTANTES 
-- =============================================
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS baremes_frais;
DROP TABLE IF EXISTS types_operation;
DROP TABLE IF EXISTS prefixes;
DROP TABLE IF EXISTS comptes;

DROP VIEW IF EXISTS vue_gains_par_type;

-- =============================================
-- CRÉATION DES TABLES V2
-- =============================================

-- 1. Préfixes
CREATE TABLE prefixes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prefixe TEXT NOT NULL UNIQUE,
    description TEXT,
    actif INTEGER NOT NULL DEFAULT 1 CHECK (actif IN (0, 1)),
    est_operateur_principal INTEGER NOT NULL DEFAULT 1 CHECK (est_operateur_principal IN (0, 1)),
    commission_pourcentage REAL NOT NULL DEFAULT 0 CHECK (commission_pourcentage >= 0)
);

-- 2. Types d'opérations
CREATE TABLE types_operation (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    libelle TEXT NOT NULL,
    frais_applicable INTEGER NOT NULL DEFAULT 1 CHECK (frais_applicable IN (0, 1))
);

-- 3. Barèmes de frais
CREATE TABLE baremes_frais (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type_operation_id INTEGER NOT NULL,
    montant_min REAL NOT NULL,
    montant_max REAL,
    frais REAL NOT NULL,
    FOREIGN KEY (type_operation_id) REFERENCES types_operation(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CHECK (montant_max IS NULL OR montant_max > montant_min),
    CHECK (frais >= 0)
);

-- 4. Comptes
CREATE TABLE comptes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telephone TEXT NOT NULL UNIQUE,
    nom TEXT,
    solde REAL NOT NULL DEFAULT 0.0,
    role TEXT NOT NULL DEFAULT 'client' CHECK (role IN ('client', 'admin')),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    CHECK (solde >= 0)
);

-- 5. Transactions (V2 complète)
CREATE TABLE transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    compte_id INTEGER NOT NULL,
    type_operation_id INTEGER NOT NULL,
    montant REAL NOT NULL,
    frais REAL NOT NULL DEFAULT 0,
    solde_apres REAL NOT NULL,
    sens TEXT NOT NULL CHECK (sens IN ('credit', 'debit')),
    compte_lie_id INTEGER,
    prefixe_id INTEGER,
    commission REAL NOT NULL DEFAULT 0,
    frais_inclus INTEGER NOT NULL DEFAULT 0 CHECK (frais_inclus IN (0, 1)),
    groupe_envoi_id TEXT,
    date_operation DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (compte_id) REFERENCES comptes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (compte_lie_id) REFERENCES comptes(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (prefixe_id) REFERENCES prefixes(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (type_operation_id) REFERENCES types_operation(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    CHECK (montant > 0),
    CHECK (frais >= 0),
    CHECK (commission >= 0),
    CHECK (solde_apres >= 0)
);

-- Index utiles
CREATE INDEX idx_transactions_compte ON transactions(compte_id);
CREATE INDEX idx_transactions_compte_lie ON transactions(compte_lie_id);
CREATE INDEX idx_transactions_prefixe ON transactions(prefixe_id);
CREATE INDEX idx_transactions_type ON transactions(type_operation_id);
CREATE INDEX idx_baremes_type ON baremes_frais(type_operation_id);

-- Vue gains par type
CREATE VIEW vue_gains_par_type AS
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
-- DONNÉES INITIALES V2
-- =============================================

-- Préfixes
INSERT INTO prefixes (prefixe, description, actif, est_operateur_principal, commission_pourcentage) VALUES
('033', 'Opérateur A (Principal)', 1, 1, 0),
('037', 'Opérateur B (Principal)', 1, 1, 0),
('032', 'MVola (Autre Opérateur)', 1, 0, 10),
('031', 'Airtel Money (Autre Opérateur)', 1, 0, 8);

-- Types d'opérations
INSERT INTO types_operation (code, libelle, frais_applicable) VALUES
('depot', 'Dépôt d''argent', 1),
('retrait', 'Retrait d''argent', 1),
('transfert', 'Transfert d''argent', 1);

-- Barèmes de frais (dépôt, retrait, transfert)
INSERT INTO baremes_frais (type_operation_id, montant_min, montant_max, frais)
-- Dépôt
SELECT id, 100, 1000, 20 FROM types_operation WHERE code = 'depot' UNION ALL
SELECT id, 1001, 5000, 20 FROM types_operation WHERE code = 'depot' UNION ALL
SELECT id, 5001, 10000, 40 FROM types_operation WHERE code = 'depot' UNION ALL
SELECT id, 10001, 25000, 100 FROM types_operation WHERE code = 'depot' UNION ALL
SELECT id, 25001, 50000, 150 FROM types_operation WHERE code = 'depot' UNION ALL
SELECT id, 50001, 100000, 250 FROM types_operation WHERE code = 'depot' UNION ALL
SELECT id, 100001, 250000, 400 FROM types_operation WHERE code = 'depot' UNION ALL
SELECT id, 250001, 500000, 600 FROM types_operation WHERE code = 'depot' UNION ALL
SELECT id, 500001, 1000000, 800 FROM types_operation WHERE code = 'depot' UNION ALL
SELECT id, 1000001, NULL, 1000 FROM types_operation WHERE code = 'depot'
UNION ALL
-- Retrait
SELECT id, 100, 1000, 50 FROM types_operation WHERE code = 'retrait' UNION ALL
SELECT id, 1001, 5000, 50 FROM types_operation WHERE code = 'retrait' UNION ALL
SELECT id, 5001, 10000, 100 FROM types_operation WHERE code = 'retrait' UNION ALL
SELECT id, 10001, 25000, 200 FROM types_operation WHERE code = 'retrait' UNION ALL
SELECT id, 25001, 50000, 400 FROM types_operation WHERE code = 'retrait' UNION ALL
SELECT id, 50001, 100000, 800 FROM types_operation WHERE code = 'retrait' UNION ALL
SELECT id, 100001, 250000, 1500 FROM types_operation WHERE code = 'retrait' UNION ALL
SELECT id, 250001, 500000, 2500 FROM types_operation WHERE code = 'retrait' UNION ALL
SELECT id, 500001, 1000000, 3000 FROM types_operation WHERE code = 'retrait' UNION ALL
SELECT id, 1000001, NULL, 5000 FROM types_operation WHERE code = 'retrait'
UNION ALL
-- Transfert
SELECT id, 100, 1000, 30 FROM types_operation WHERE code = 'transfert' UNION ALL
SELECT id, 1001, 5000, 30 FROM types_operation WHERE code = 'transfert' UNION ALL
SELECT id, 5001, 10000, 60 FROM types_operation WHERE code = 'transfert' UNION ALL
SELECT id, 10001, 25000, 120 FROM types_operation WHERE code = 'transfert' UNION ALL
SELECT id, 25001, 50000, 250 FROM types_operation WHERE code = 'transfert' UNION ALL
SELECT id, 50001, 100000, 500 FROM types_operation WHERE code = 'transfert' UNION ALL
SELECT id, 100001, 250000, 900 FROM types_operation WHERE code = 'transfert' UNION ALL
SELECT id, 250001, 500000, 1500 FROM types_operation WHERE code = 'transfert' UNION ALL
SELECT id, 500001, 1000000, 2000 FROM types_operation WHERE code = 'transfert' UNION ALL
SELECT id, 1000001, NULL, 3000 FROM types_operation WHERE code = 'transfert';

-- Comptes de test
INSERT INTO comptes (telephone, nom, solde, role) VALUES
('0331234567', 'Rakoto Jean', 500000, 'client'),
('0372345678', 'Rasoa Marie', 250000, 'client'),
('0339999999', 'Randria Paul', 10000, 'client'),
('0323456789', 'Client opérateur C', 100000, 'client'),
('0314567890', 'Client opérateur D', 100000, 'client'),
('0330000000', 'Administrateur', 0, 'admin');

-- Message de confirmation
SELECT '=== Base de données Mobile Money V2 initialisée avec succès ===' AS message;


CREATE TABLE promotion (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    reduction_pourcentage REAL NOT NULL (reduction_pourcentage CHECK 0 AND 100),
    type_operation_code TEXT DEFAULT 'transfert',
    est_meme_operateur INTEGER DEFAULT 1,
    date_debut DATETIME,
    date_fin DATETIME,
    actif INTEGER DEFAULT 1 CHECK (actif IN (0,1))
);