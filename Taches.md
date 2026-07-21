# Taches.md - Projet Simulateur Mobile Money (Version 1)

**Équipe :** ETU003929 et ETU004190
**Technologies :** PHP + CodeIgniter 4, SQLite, HTML/CSS/JS, Bootstrap
**Durée :** 4 heures
**Date :** 20 juillet 2026
**Livraison :** Tag `v1` sur le dépôt Git public, à 13h

---

## Règles métier importantes (à respecter par les deux)

- Pour un **dépôt**, les frais sont déduits du montant saisi : crédit du compte = `montant - frais`.
- Pour un **transfert**, les frais sont prélevés sur le compte émetteur (débit = montant + frais). Le destinataire reçoit le montant net, sans frais.
- Le retrait et le transfert doivent vérifier que `solde >= montant + frais` avant toute exécution.
- Login : recherche du compte par numéro de téléphone existant dans `examenfinals4.db`. Pas de création de compte à la volée (pas d'inscription).
- Les préfixes valides (ex : 033, 037) servent à valider le format du numéro, pas à créer un compte.

---

## Contrat commun (à figer avant de se séparer)

Ces signatures sont décidées ensemble en Heure 1 pour que le développement en parallèle ne casse rien :

| Fonction                                                                    | Fichier                              | Rôle                                                             |
| --------------------------------------------------------------------------- | ------------------------------------ | ----------------------------------------------------------------- |
| `CompteModel::findByTelephone(string $telephone): ?array`                 | `app/Models/CompteModel.php`       | Retrouve un compte pour le login                                  |
| `TransactionModel::getByCompte(int $compteId, ?int $limit = null): array` | `app/Models/TransactionModel.php`  | Historique (dashboard = limit 5, historique = sans limite)        |
| `calculerFrais(string $typeOperationCode, float $montant): array`         | `app/Helpers/operation_helper.php` | Retourne`['frais' => float, 'total' => float]` selon le barème |

---

## Travaux communs

- [X] Initialiser le projet CodeIgniter 4 (`composer create-project codeigniter4/appstarter`)
- [X] **`app/Config/Database.php`** — driver `SQLite3`, base pointant vers `writable/examenfinals4.db`
- [X] **`base.sql`** — schéma complet + données initiales:
  - créer la table prefixes (id, prefixe, description, actif)
  - créer la table types_operation (id, code, libelle, frais_applicable)
  - créer la table baremes_frais (id, type_operation_id, montant_min, montant_max, frais, created_at)
  - créer la table comptes (id, telephone, nom, solde, role, date_creation)
  - créer la table transactions (id, compte_id, type_operation_id, montant, frais, solde_apres, sens, compte_lie_id, date_operation)
- [ ] **`app/Views/layout/app.php`** — header/nav Bootstrap conditionnel selon `session('role')` (menu client vs menu admin), footer, includes CSS/JS communs
- [ ] **`public/assets/css/style.css`** — variables de couleurs, breakpoints mobile-first
- [ ] Valider ensemble le contrat commun ci-dessus avant de commencer le travail séparé

---

## ETU004190 (Backend + Opérateur)

### Models

- [X] **`app/Models/PrefixeModel.php`** — gestion des préfixes valides (033, 037…)

  - Table `prefixes` : `id`, `prefixe`, `description`, `actif`
  - `$table = 'prefixes'` ; `$allowedFields = ['prefixe', 'description', 'actif']`
  - Méthodes :
    - `getActifs(): array` — liste des préfixes actifs
    - `isPrefixeValide(string $numero): bool` — vérifie que le numéro commence par un préfixe actif (format uniquement, ne crée jamais de compte)
- [X] **`app/Models/TypeOperationModel.php`** — types d'opérations (dépôt, retrait, transfert)

  - Table `types_operation` : `id`, `code` (`depot`/`retrait`/`transfert`), `libelle`, `frais_applicable` (0/1)
  - Méthodes :
    - `findByCode(string $code): ?array`
    - `getAll(): array`
- [X] **`app/Models/BaremeFraisModel.php`** — barèmes par tranche, appliqués au dépôt, retrait et transfert

  - Table `baremes_frais` : `id`, `type_operation_id`, `montant_min`, `montant_max`, `frais`
  - Méthodes :
    - `getTranche(int $typeOperationId, float $montant): ?array` — ligne du barème correspondant au montant saisi
    - `getAllByType(int $typeOperationId): array` — pour l'affichage/édition CRUD
    - CRUD standard hérité de `Model` (`insert`, `update`, `delete`)
- [X] **`app/Models/CompteModel.php`** — comptes clients (solde, téléphone, rôle)

  - Table `comptes` : `id`, `telephone`, `nom`, `solde`, `role` (`client`/`admin`), `date_creation`
  - Méthodes :
    - `findByTelephone(string $telephone): ?array` *(contrat commun)*
    - `crediter(int $compteId, float $montant): bool`
    - `debiter(int $compteId, float $montant): bool`
    - `getSolde(int $compteId): float`
    - `getAllClients(): array` — pour le dashboard admin et la page comptes
- [X] **`app/Models/TransactionModel.php`** — historique complet des mouvements

  - Table `transactions` : `id`, `compte_id`, `type_operation_id`, `montant`, `frais`, `solde_apres`, `sens` (`credit`/`debit`), `compte_lie_id` (nullable, pour tracer l'autre côté d'un transfert), `date_operation`
  - Méthodes :
    - `getByCompte(int $compteId, ?int $limit = null): array` *(contrat commun)*
    - `enregistrer(array $data): int|false` — insère une écriture, retourne l'id
    - `getAll(?int $limit = null): array` — historique global pour l'opérateur
    - `getGainsParType(): array` — `SUM(frais)` groupé par `type_operation_id`, pour le dashboard admin

### Logique métier / calcul des frais

- [X] **`app/Helpers/operation_helper.php`** — fonction `calculerFrais(string $typeOperationCode, float $montant): array`

  - Retourne `['frais' => float, 'total' => float]`
  - Enchaîne `TypeOperationModel::findByCode()` puis `BaremeFraisModel::getTranche()`
  - Retourne `frais = 0` directement si `frais_applicable = 0`
  - Déclaré dans `app/Config/Autoload.php` (`$autoload['helper'] = ['operation']`) pour être utilisable partout, y compris par le contrôleur client
- [X] **`app/Controllers/Api/FraisController.php`** — endpoint AJAX consommé par `previewFrais()` côté frontend

  - `calculer()` — POST `api/calculer-frais`, reçoit `type_operation` + `montant`, appelle `calculerFrais()`, répond en JSON `{frais, total}`

### CRUD Admin

- [ ] **`app/Controllers/AdminController.php`**

  - `dashboard()` — GET `/admin`, appelle `TransactionModel::getGainsParType()` + `CompteModel::getAllClients()`
  - `prefixes()` — GET `/admin/prefixes`, liste + formulaire
  - `storePrefixe()` / `updatePrefixe($id)` / `deletePrefixe($id)` — POST, CRUD via `PrefixeModel`
  - `baremes()` — GET `/admin/baremes`, liste des tranches par type d'opération
  - `storeBareme()` / `updateBareme($id)` / `deleteBareme($id)` — POST, CRUD via `BaremeFraisModel`
  - `comptes()` — GET `/admin/comptes`, liste des comptes clients (solde, téléphone)
  - `transactions()` — GET `/admin/transactions`, historique global via `TransactionModel::getAll()`
- [ ] **Vues admin** (`app/Views/admin/`)

  - `Dashboard.php` — cards gains par type d'opération + tableau des comptes
  - `Prefixes.php` — tableau + formulaire ajout/édition/suppression
  - `Baremes.php` — tableau par type d'opération + formulaire tranche (min, max, frais)
  - `Comptes.php` — tableau des comptes clients
  - `Transactions.php` — tableau de l'historique global

### Sécurité / routage

- [ ] **`app/Filters/AuthFilter.php`** — implémente `FilterInterface`
  - `before($request, $arguments = null)` — vérifie `session('role')` selon l'argument passé (`client` ou `admin`), redirige vers `/login` sinon
- [ ] **`app/Config/Filters.php`** — déclare les alias `authAdmin` / `authClient` pointant vers `AuthFilter`
- [ ] **`app/Config/Routes.php`** (partie admin) — groupe `admin` protégé : `$routes->group('admin', ['filter' => 'authAdmin'], function($routes) {...})`

### Base de données

- [ ] **`base.sql`** (racine)
  - `CREATE TABLE prefixes (...)`, `types_operation (...)`, `baremes_frais (...)`, `comptes (...)`, `transactions (...)`
  - Vue `vue_gains_par_type` (optionnelle, `SUM(frais)` groupé)
  - Données initiales : préfixes 033/037, types dépôt/retrait/transfert, barème de l'exemple photo (dupliqué pour retrait ET transfert), quelques comptes clients de test avec solde

---

## ETU003929 (Frontend + Côté Client)

### Authentification

- [X] **`app/Controllers/AuthController.php`**
  - `login()` — GET `/login`, affiche `auth/login.php`
  - `authenticate()` — POST `/login`, reçoit `telephone`, appelle `CompteModel::findByTelephone()`. Si trouvé : `session()->set(['compte_id' => .., 'telephone' => .., 'solde' => .., 'role' => ..])` puis redirection selon rôle (`/client/dashboard` ou `/admin`). Si non trouvé : message flash "Numéro non reconnu"
  - `logout()` — `session()->destroy()`, redirige vers `/login`
- [X] **`app/Views/auth/login.php`** — formulaire `telephone`, affichage du message flash, validation JS du format avant envoi (`validateTelephone()`)

### Dashboard client

- [X] **`app/Controllers/ClientController.php::dashboard()`**
  - GET `/client/dashboard`
  - Récupère `compte_id` en session, relit le solde à jour via `CompteModel`, appelle `TransactionModel::getByCompte($compteId, 5)`
- [X] **`app/Views/client/dashboard.php`** — carte solde en évidence + boutons Dépôt / Retrait / Transfert / Historique + tableau des 5 dernières transactions

### Opérations

- [X] **`ClientController::depot()`** (GET, formulaire) / **`storeDepot()`** (POST)

  - Valide `montant > 0`
  - Appelle `calculerFrais('depot', $montant)` ; le compte est crédité de `montant - frais`
  - `CompteModel::crediter($compteId, $montant)` puis `TransactionModel::enregistrer([...])` avec les frais

  - [X] **`ClientController::retrait()`** (GET) / **`storeRetrait()`** (POST)
    - Appelle le helper backend `calculerFrais('retrait', $montant)`
    - Vérifie `solde >= montant + frais`, sinon message d'erreur
    - `CompteModel::debiter()` puis `TransactionModel::enregistrer()`
- [X] **`ClientController::transfert()`** (GET) / **`storeTransfert()`** (POST)

  - Reçoit `telephone_destinataire`, `montant`
  - Vérifie que le destinataire existe (`CompteModel::findByTelephone()`) et diffère de l'émetteur
  - `calculerFrais('transfert', $montant)`, vérifie le solde de l'émetteur
  - Débite l'émetteur (montant + frais), crédite le destinataire (montant net)
  - Enregistre les 2 écritures liées (`compte_lie_id` croisé) via `TransactionModel::enregistrer()`
- [X] **Vues** — `app/Views/client/depot.php`, `retrait.php`, `transfert.php`

  - Affichage du solde disponible + aperçu du montant total (montant + frais) via AJAX avant confirmation

### Historique

- [X] **`ClientController::historique()`** — GET `/client/historique`, `TransactionModel::getByCompte($compteId)` sans limite
- [X] **`app/Views/client/historique.php`** — tableau : type d'opération, montant, frais, solde après, date, sens (crédit/débit)

### UI / UX / JS

- [X] **`public/assets/js/client.js`**
  - `validateTelephone(numero)` — regex format attendu (préfixe + longueur)
  - `validateMontant(input)` — bloque les montants négatifs ou non numériques
  - `previewFrais(montant, typeOperation)` — `fetch('/api/calculer-frais', ...)` vers `Api\FraisController::calculer()`, met à jour l'aperçu de frais en direct
  - `confirmAction(message)` — modal Bootstrap de confirmation avant retrait/transfert
  - `showToast(type, message)` — toast Bootstrap après chaque action
- [X] Design responsive + intégration AJAX de mise à jour du solde (optionnel)

---

## Planning suggéré (4 heures)

**Heure 1 :**

- ETU004190 → Initialisation + `base.sql` + Models
- ETU003929 → Layout + `AuthController` (login/logout) + Dashboard Client

**Heure 2 :**

- ETU004190 → `calculerFrais()` + `Api\FraisController` + Controllers Admin (CRUD)
- ETU003929 → Formulaires Dépôt / Retrait / Transfert (avec appel à `calculerFrais()`)

**Heure 3 :**

- ETU004190 → Finalisation Transactions + Dashboard Opérateur + `AuthFilter`
- ETU003929 → Historique Client + JS (validation, confirmation, toasts, aperçu frais)

**Heure 4 :**

- Intégration + Tests + Corrections + Préparation démonstration

---

## Fonctionnalités validées pour la Version 1

- [X] Login automatique par numéro de téléphone (pas d'inscription)
- [X] Gestion des préfixes opérateurs
- [X] Barèmes de frais configurables, applicables **au dépôt, retrait et transfert**
- [X] Dépôt, Retrait et Transfert fonctionnels
- [X] Historique complet par client et global (opérateur)
- [X] Dashboard Opérateur (situation des gains + comptes clients)
- [X] Design mobile-first avec Bootstrap

---

## Version 2 — Extensions

- [X] Préfixes d'opérateurs externes avec commission configurable
- [X] Transfert avec frais ajoutés ou inclus dans le montant
- [X] Transfert externe sans frais de retrait, avec commission de l'opérateur externe
- [X] Envoi multiple atomique avec répartition du reliquat au dernier destinataire
- [X] Regroupement visuel des envois multiples dans l'historique
- [X] Situation des commissions à régulariser par opérateur externe sur le dashboard

---

---

---

## Règles métier importantes — Version 2 (nouveau)

- Un préfixe est maintenant soit **notre opérateur** (`est_operateur_principal = 1`), soit **un autre opérateur** (`= 0`, ex : 032, 031…).
- Chaque **autre opérateur** a une **commission en % propre**, appliquée **en plus** du barème de frais normal, mais **uniquement sur les transferts sortants vers ce préfixe** (jamais sur dépôt/retrait, jamais sur un transfert vers notre propre réseau).
- Deux notions à bien séparer côté admin :
  - **Gain** = frais (barème) + commission externe → ce que l'entreprise encaisse.
  - **Montant à envoyer** = le montant principal (hors frais/commission) transféré vers chaque autre opérateur → ce que l'entreprise doit reverser/régler à cet opérateur (règlement d'interconnexion), **pas un gain**.
- Le mode **"frais inclus"** change qui supporte les frais : soit le destinataire reçoit un montant net (frais en plus, débit = montant + frais), soit le client saisit le débit total et les frais sont prélevés dessus (destinataire reçoit moins).
- **Envoi multiple** : un montant total divisé à parts égales entre N numéros, chaque part traitée comme un transfert normal (frais + commission éventuelle), en une seule transaction SQL atomique.

---

## ETU004190 (Backend + Opérateur) — Version 2

### Models (extensions)

- [ ] **`app/Models/PrefixeModel.php`** — ajout de la gestion des autres opérateurs

  - Nouvelles colonnes table `prefixes` : `est_operateur_principal` (INTEGER, 0/1, défaut 1), `commission_pourcentage` (REAL, défaut 0)
  - `$allowedFields` devient `['prefixe', 'description', 'actif', 'est_operateur_principal', 'commission_pourcentage']`
  - Nouvelles méthodes :
    - `getAutresOperateurs(): array` — liste des préfixes où `est_operateur_principal = 0` et `actif = 1`
    - `getAutreOperateur(string $numero): ?array` — parcourt `getAutresOperateurs()`, retourne le préfixe correspondant au numéro (avec sa `commission_pourcentage`), ou `null` si le numéro appartient à notre réseau *(contrat commun V2)*
- [ ] **`app/Models/TransactionModel.php`** — traçage de l'opérateur externe et de la commission

  - Nouvelles colonnes table `transactions` : `prefixe_id` (INTEGER, nullable, FK vers `prefixes.id`), `commission` (REAL, défaut 0), `frais_inclus` (INTEGER, 0/1, défaut 0), `groupe_envoi_id` (TEXT, nullable — regroupe les lignes d'un même envoi multiple)
  - `$allowedFields` devient `['compte_id', 'type_operation_id', 'montant', 'frais', 'solde_apres', 'sens', 'compte_lie_id', 'prefixe_id', 'commission', 'frais_inclus', 'groupe_envoi_id']`
  - Nouvelles méthodes :
    - `getGainsParOperateur(): array` — retourne 2 lignes : `SUM(frais + commission)` où `prefixe_id IS NULL` (= notre opérateur), et `SUM(frais + commission)` où `prefixe_id IS NOT NULL`, groupé ensuite par `prefixe_id` pour le détail par autre opérateur *(contrat commun V2)*
    - `getMontantsAEnvoyerParOperateur(): array` — `SELECT prefixes.libelle, SUM(transactions.montant) as total_a_envoyer` groupé par `prefixe_id`, uniquement sur les transferts sortants (`sens = 'debit'` et `prefixe_id IS NOT NULL`) *(contrat commun V2)*

### Logique métier / calcul des frais (extension)

- [ ] **`app/Helpers/operation_helper.php`** — nouvelle fonction `calculerFraisTransfert(float $montant, ?array $autreOperateur, bool $fraisInclus): array`

  - `$autreOperateur` = résultat de `PrefixeModel::getAutreOperateur()` (ou `null` si transfert interne)
  - Enchaîne `TypeOperationModel::findByCode('transfert')` puis `BaremeFraisModel::getTranche()` pour le frais de base
  - Si `$autreOperateur !== null` : ajoute `commission = $montant * ($autreOperateur['commission_pourcentage'] / 100)`
  - Si `$fraisInclus === false` (comportement V1) : `montant_debite = $montant + $frais + $commission`, `montant_recu = $montant`
  - Si `$fraisInclus === true` : `montant_debite = $montant`, `montant_recu = $montant - $frais - $commission`
  - Retourne `['frais' => float, 'commission' => float, 'montant_debite' => float, 'montant_recu' => float]`
  - Reste séparée de `calculerFrais()` (qui continue de gérer dépôt/retrait simplement)
- [ ] **`app/Controllers/Api/FraisController.php`** — extension de `calculer()`

  - Accepte en plus `telephone_dest` (optionnel, pour détecter un autre opérateur) et `frais_inclus` (booléen, optionnel, défaut `false`)
  - Si `type_operation === 'transfert'` et `telephone_dest` fourni : appelle `PrefixeModel::getAutreOperateur($telephone_dest)` puis `calculerFraisTransfert()` au lieu de `calculerFrais()`
  - Répond en JSON `{frais, commission, montant_debite, montant_recu}` dans ce cas (au lieu de `{frais, total}`)

### CRUD Admin (extensions)

- [ ] **`app/Controllers/AdminController.php`**

  - `prefixes()` — le formulaire existant intègre les nouveaux champs `est_operateur_principal` (select ou checkbox) et `commission_pourcentage` (input number, affiché seulement si "autre opérateur" est coché, en JS)
  - `storePrefixe()` / `updatePrefixe($id)` — valident et enregistrent les 2 nouveaux champs
  - `dashboard()` — appelle désormais `TransactionModel::getGainsParOperateur()` (en plus ou à la place de `getGainsParType()`) et `TransactionModel::getMontantsAEnvoyerParOperateur()`
- [ ] **Vues admin (mise à jour)**

  - `prefixes.php` — ajoute une colonne "Type" (Notre opérateur / Autre) et "Commission %" dans le tableau + les champs correspondants dans le formulaire d'ajout/édition
  - `dashboard.php` — 2 blocs de cards distincts ("Notre opérateur" vs "Autres opérateurs" pour les gains), + un nouveau tableau "Montants à envoyer par opérateur" (nom de l'opérateur, montant total, à régler)

---

## ETU003929 (Frontend + Côté Client) — Version 2

### Opérations (extension du transfert)

- [ ] **`ClientController::transfert()`** (GET) — le formulaire ajoute un choix radio "Frais en plus" (défaut) / "Frais inclus dans le montant"
- [ ] **`ClientController::storeTransfert()`** (POST) — extension

  - Reçoit en plus `frais_inclus` (0/1)
  - Appelle `PrefixeModel::getAutreOperateur($telephoneDestinataire)` pour savoir si c'est un transfert externe
  - Appelle `calculerFraisTransfert($montant, $autreOperateur, $fraisInclus)` au lieu de l'ancien calcul simple
  - Vérifie `solde émetteur >= montant_debite`
  - Débite l'émetteur de `montant_debite`, crédite le destinataire de `montant_recu`
  - Enregistre les 2 écritures avec `prefixe_id` (si externe), `commission`, `frais_inclus` renseignés
- [ ] **`app/Views/client/transfert.php`** (mise à jour) — radio "frais en plus / frais inclus", aperçu AJAX mis à jour pour afficher `montant_debite` ET `montant_recu` selon le mode choisi

### Envoi multiple (nouveau)

- [ ] **`ClientController::envoiMultiple()`** (GET) — affiche le formulaire (montant total + liste de numéros dynamique)
- [ ] **`ClientController::storeEnvoiMultiple()`** (POST)

  - Reçoit `montant_total` et un tableau `telephones[]`
  - Calcule la part par destinataire : `$part = $montantTotal / count($telephones)` (arrondi à définir — proposition : dernier destinataire récupère le reliquat pour que la somme des parts égale exactement le montant total)
  - Pour chaque destinataire : calcule `calculerFraisTransfert()` sur sa part, vérifie l'existence du compte (`CompteModel::findByTelephone()`)
  - Vérifie que le solde de l'émetteur couvre la **somme de tous les `montant_debite`** avant d'exécuter quoi que ce soit
  - Exécute la boucle de mini-transferts dans une transaction SQL atomique : `$db->transStart()` ... boucle `crediter()`/`debiter()`/`enregistrer()` ... `$db->transComplete()` — si un seul échoue, tout est annulé (`transStatus()`)
  - Génère un `groupe_envoi_id` unique (ex: `uniqid()` ou `bin2hex(random_bytes(8))`) commun à toutes les lignes de cet envoi
- [ ] **`app/Views/client/envoi_multiple.php`** (nouveau)

  - Champ `montant_total`
  - Liste de champs téléphone ajoutable/supprimable dynamiquement (JS)
  - Aperçu AJAX de la part + frais par destinataire avant confirmation
  - Modal de confirmation récapitulant tous les destinataires et montants avant envoi définitif

### Historique (extension)

- [ ] **`app/Views/client/historique.php`** (mise à jour) — les lignes partageant un même `groupe_envoi_id` sont regroupées visuellement (ex: bordure commune ou libellé "Envoi multiple #xxx" avec sous-liste des destinataires)

### UI / UX / JS (extension)

- [ ] **`public/assets/js/client.js`** — nouvelles fonctions
  - `addDestinataireField()` — ajoute dynamiquement un champ numéro de téléphone dans le formulaire d'envoi multiple
  - `removeDestinataireField(index)` — retire un champ
  - `previewFraisTransfert(montant, telephoneDest, fraisInclus)` — remplace/étend `previewFrais()` pour le transfert, appelle `api/calculer-frais` avec les nouveaux paramètres, affiche `montant_debite` et `montant_recu`
  - `previewEnvoiMultiple(montantTotal, telephones[])` — calcule et affiche la part + frais estimés par destinataire avant soumission

---

## Changements de base de données pour la V2

```sql
ALTER TABLE prefixes ADD COLUMN est_operateur_principal INTEGER NOT NULL DEFAULT 1
    CHECK (est_operateur_principal IN (0, 1));
ALTER TABLE prefixes ADD COLUMN commission_pourcentage REAL NOT NULL DEFAULT 0
    CHECK (commission_pourcentage >= 0);

ALTER TABLE transactions ADD COLUMN prefixe_id INTEGER REFERENCES prefixes(id);
ALTER TABLE transactions ADD COLUMN commission REAL NOT NULL DEFAULT 0
    CHECK (commission >= 0);
ALTER TABLE transactions ADD COLUMN frais_inclus INTEGER NOT NULL DEFAULT 0
    CHECK (frais_inclus IN (0, 1));
ALTER TABLE transactions ADD COLUMN groupe_envoi_id TEXT;
```

Note : les préfixes 033/037 existants gardent `est_operateur_principal = 1` (valeur par défaut). Insérer les nouveaux préfixes externes (032, 031…) avec `est_operateur_principal = 0` et leur `commission_pourcentage`.

## Fonctionnalités à développer pour la Version 2

- [ ] Configuration des préfixes pour les autres opérateurs
- [ ] Configuration de la commission % sur les transferts vers les autres opérateurs
- [ ] Dashboard gains séparé : notre opérateur / autres opérateurs
- [ ] Situation des montants à envoyer à chaque autre opérateur
- [ ] Option "frais inclus" lors d'un transfert
- [ ] Envoi multiple vers plusieurs numéros (montant divisé)

## Structure du projet

```text
app/
  Config/
    Routes.php
    Database.php               # commun
    Filters.php                 # ETU004190
    Autoload.php                # ETU004190 (helper operation)
  Controllers/
    ClientController.php        # ETU003929
    AdminController.php         # ETU004190
    AuthController.php          # ETU003929
    Api/
      FraisController.php       # ETU004190
  Filters/
    AuthFilter.php               # ETU004190
  Helpers/
    operation_helper.php         # ETU004190 (calculerFrais)
  Models/
    PrefixeModel.php             # ETU004190
    TypeOperationModel.php       # ETU004190
    BaremeFraisModel.php         # ETU004190
    CompteModel.php               # ETU004190
    TransactionModel.php          # ETU004190
  Views/
    layout/
      app.php                     # commun
    auth/
      login.php                    # ETU003929
    client/
      dashboard.php                 # ETU003929
      depot.php                     # ETU003929
      retrait.php                   # ETU003929
      transfert.php                 # ETU003929
      historique.php                # ETU003929
    admin/
      dashboard.php                  # ETU004190
      prefixes.php                   # ETU004190
      baremes.php                    # ETU004190
      comptes.php                    # ETU004190
      transactions.php               # ETU004190
public/
  assets/
    css/style.css               # commun
    js/client.js                # ETU003929
base.sql                      # ETU004190 — création des tables + données initiales
examenfinals4.db                         # généré à partir de base.sql
```
