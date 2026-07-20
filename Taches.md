# Taches.md - Projet Simulateur Mobile Money (Version 1)

**Équipe :** ETU003929 et ETU004190
**Technologies :** PHP + CodeIgniter 4, SQLite, HTML/CSS/JS, Bootstrap
**Durée :** 4 heures
**Date :** 20 juillet 2026
**Livraison :** Tag `v1` sur le dépôt Git public, à 13h

---

## Règles métier importantes (à respecter par les deux)

- Les **frais s'appliquent uniquement sur le retrait et le transfert**. Le dépôt est **sans frais**.
- Pour un **transfert**, les frais sont prélevés sur le compte émetteur (débit = montant + frais). Le destinataire reçoit le montant net, sans frais.
- Le retrait et le transfert doivent vérifier que `solde >= montant + frais` avant toute exécution.
- Login : recherche du compte par numéro de téléphone existant dans `examenfinals4.db`. Pas de création de compte à la volée (pas d'inscription).
- Les préfixes valides (ex : 033, 037) servent à valider le format du numéro, pas à créer un compte.

---

## Contrat commun (à figer avant de se séparer)

Ces signatures sont décidées ensemble en Heure 1 pour que le développement en parallèle ne casse rien :

| Fonction | Fichier | Rôle |
|---|---|---|
| `CompteModel::findByTelephone(string $telephone): ?array` | `app/Models/CompteModel.php` | Retrouve un compte pour le login |
| `TransactionModel::getByCompte(int $compteId, ?int $limit = null): array` | `app/Models/TransactionModel.php` | Historique (dashboard = limit 5, historique = sans limite) |
| `calculerFrais(string $typeOperationCode, float $montant): array` | `app/Helpers/operation_helper.php` | Retourne `['frais' => float, 'total' => float]`, `frais = 0` si dépôt |

---

## Travaux communs

- [ ] Initialiser le projet CodeIgniter 4 (`composer create-project codeigniter4/appstarter`)
- [ ] **`app/Config/Database.php`** — driver `SQLite3`, base pointant vers `writable/examenfinals4.db`
- [ ] **`script.sql`** (racine, rempli par ETU004190) — schéma complet + données initiales
- [ ] **`app/Views/layout/app.php`** — header/nav Bootstrap conditionnel selon `session('role')` (menu client vs menu admin), footer, includes CSS/JS communs
- [ ] **`public/assets/css/style.css`** — variables de couleurs, breakpoints mobile-first
- [ ] Valider ensemble le contrat commun ci-dessus avant de commencer le travail séparé

---

## ETU004190 (Backend + Opérateur)

### Models

- [ ] **`app/Models/PrefixeModel.php`** — gestion des préfixes valides (033, 037…)
  - Table `prefixes` : `id`, `prefixe`, `description`, `actif`
  - `$table = 'prefixes'` ; `$allowedFields = ['prefixe', 'description', 'actif']`
  - Méthodes :
    - `getActifs(): array` — liste des préfixes actifs
    - `isPrefixeValide(string $numero): bool` — vérifie que le numéro commence par un préfixe actif (format uniquement, ne crée jamais de compte)

- [ ] **`app/Models/TypeOperationModel.php`** — types d'opérations (dépôt, retrait, transfert)
  - Table `types_operation` : `id`, `code` (`depot`/`retrait`/`transfert`), `libelle`, `frais_applicable` (0/1)
  - Méthodes :
    - `findByCode(string $code): ?array`
    - `getAll(): array`

- [ ] **`app/Models/BaremeFraisModel.php`** — barèmes par tranche, appliqués au retrait et au transfert uniquement
  - Table `baremes_frais` : `id`, `type_operation_id`, `montant_min`, `montant_max`, `frais`
  - Méthodes :
    - `getTranche(int $typeOperationId, float $montant): ?array` — ligne du barème correspondant au montant saisi
    - `getAllByType(int $typeOperationId): array` — pour l'affichage/édition CRUD
    - CRUD standard hérité de `Model` (`insert`, `update`, `delete`)

- [ ] **`app/Models/CompteModel.php`** — comptes clients (solde, téléphone, rôle)
  - Table `comptes` : `id`, `telephone`, `nom`, `solde`, `role` (`client`/`admin`), `date_creation`
  - Méthodes :
    - `findByTelephone(string $telephone): ?array` *(contrat commun)*
    - `crediter(int $compteId, float $montant): bool`
    - `debiter(int $compteId, float $montant): bool`
    - `getSolde(int $compteId): float`
    - `getAllClients(): array` — pour le dashboard admin et la page comptes

- [ ] **`app/Models/TransactionModel.php`** — historique complet des mouvements
  - Table `transactions` : `id`, `compte_id`, `type_operation_id`, `montant`, `frais`, `solde_apres`, `sens` (`credit`/`debit`), `compte_lie_id` (nullable, pour tracer l'autre côté d'un transfert), `date_operation`
  - Méthodes :
    - `getByCompte(int $compteId, ?int $limit = null): array` *(contrat commun)*
    - `enregistrer(array $data): int|false` — insère une écriture, retourne l'id
    - `getAll(?int $limit = null): array` — historique global pour l'opérateur
    - `getGainsParType(): array` — `SUM(frais)` groupé par `type_operation_id`, pour le dashboard admin

### Logique métier / calcul des frais

- [ ] **`app/Helpers/operation_helper.php`** — fonction `calculerFrais(string $typeOperationCode, float $montant): array`
  - Retourne `['frais' => float, 'total' => float]`
  - Enchaîne `TypeOperationModel::findByCode()` puis `BaremeFraisModel::getTranche()`
  - Retourne `frais = 0` directement si `frais_applicable = 0` (cas dépôt)
  - Déclaré dans `app/Config/Autoload.php` (`$autoload['helper'] = ['operation']`) pour être utilisable partout, y compris par le contrôleur client

- [ ] **`app/Controllers/Api/FraisController.php`** — endpoint AJAX consommé par `previewFrais()` côté frontend
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
  - `dashboard.php` — cards gains par type d'opération + tableau des comptes
  - `prefixes.php` — tableau + formulaire ajout/édition/suppression
  - `baremes.php` — tableau par type d'opération + formulaire tranche (min, max, frais)
  - `comptes.php` — tableau des comptes clients
  - `transactions.php` — tableau de l'historique global

### Sécurité / routage

- [ ] **`app/Filters/AuthFilter.php`** — implémente `FilterInterface`
  - `before($request, $arguments = null)` — vérifie `session('role')` selon l'argument passé (`client` ou `admin`), redirige vers `/login` sinon
- [ ] **`app/Config/Filters.php`** — déclare les alias `authAdmin` / `authClient` pointant vers `AuthFilter`
- [ ] **`app/Config/Routes.php`** (partie admin) — groupe `admin` protégé : `$routes->group('admin', ['filter' => 'authAdmin'], function($routes) {...})`

### Base de données

- [ ] **`script.sql`** (racine)
  - `CREATE TABLE prefixes (...)`, `types_operation (...)`, `baremes_frais (...)`, `comptes (...)`, `transactions (...)`
  - Vue `vue_gains_par_type` (optionnelle, `SUM(frais)` groupé)
  - Données initiales : préfixes 033/037, types dépôt/retrait/transfert, barème de l'exemple photo (dupliqué pour retrait ET transfert), quelques comptes clients de test avec solde

---

## ETU003929 (Frontend + Côté Client)

### Authentification

- [ ] **`app/Controllers/AuthController.php`**
  - `login()` — GET `/login`, affiche `auth/login.php`
  - `authenticate()` — POST `/login`, reçoit `telephone`, appelle `CompteModel::findByTelephone()`. Si trouvé : `session()->set(['compte_id' => .., 'telephone' => .., 'solde' => .., 'role' => ..])` puis redirection selon rôle (`/client/dashboard` ou `/admin`). Si non trouvé : message flash "Numéro non reconnu"
  - `logout()` — `session()->destroy()`, redirige vers `/login`
- [ ] **`app/Views/auth/login.php`** — formulaire `telephone`, affichage du message flash, validation JS du format avant envoi (`validateTelephone()`)

### Dashboard client

- [ ] **`app/Controllers/ClientController.php::dashboard()`**
  - GET `/client/dashboard`
  - Récupère `compte_id` en session, relit le solde à jour via `CompteModel`, appelle `TransactionModel::getByCompte($compteId, 5)`
- [ ] **`app/Views/client/dashboard.php`** — carte solde en évidence + boutons Dépôt / Retrait / Transfert / Historique + tableau des 5 dernières transactions

### Opérations

- [ ] **`ClientController::depot()`** (GET, formulaire) / **`storeDepot()`** (POST)
  - Valide `montant > 0`
  - Aucun frais (`frais = 0`)
  - `CompteModel::crediter($compteId, $montant)` puis `TransactionModel::enregistrer([...])`
- [ ] **`ClientController::retrait()`** (GET) / **`storeRetrait()`** (POST)
  - Appelle le helper backend `calculerFrais('retrait', $montant)`
  - Vérifie `solde >= montant + frais`, sinon message d'erreur
  - `CompteModel::debiter()` puis `TransactionModel::enregistrer()`
- [ ] **`ClientController::transfert()`** (GET) / **`storeTransfert()`** (POST)
  - Reçoit `telephone_destinataire`, `montant`
  - Vérifie que le destinataire existe (`CompteModel::findByTelephone()`) et diffère de l'émetteur
  - `calculerFrais('transfert', $montant)`, vérifie le solde de l'émetteur
  - Débite l'émetteur (montant + frais), crédite le destinataire (montant net)
  - Enregistre les 2 écritures liées (`compte_lie_id` croisé) via `TransactionModel::enregistrer()`
- [ ] **Vues** — `app/Views/client/depot.php`, `retrait.php`, `transfert.php`
  - Affichage du solde disponible + aperçu du montant total (montant + frais) via AJAX avant confirmation

### Historique

- [ ] **`ClientController::historique()`** — GET `/client/historique`, `TransactionModel::getByCompte($compteId)` sans limite
- [ ] **`app/Views/client/historique.php`** — tableau : type d'opération, montant, frais, solde après, date, sens (crédit/débit)

### UI / UX / JS

- [ ] **`public/assets/js/client.js`**
  - `validateTelephone(numero)` — regex format attendu (préfixe + longueur)
  - `validateMontant(input)` — bloque les montants négatifs ou non numériques
  - `previewFrais(montant, typeOperation)` — `fetch('/api/calculer-frais', ...)` vers `Api\FraisController::calculer()`, met à jour l'aperçu de frais en direct
  - `confirmAction(message)` — modal Bootstrap de confirmation avant retrait/transfert
  - `showToast(type, message)` — toast Bootstrap après chaque action
- [ ] Design responsive + intégration AJAX de mise à jour du solde (optionnel)

---

## Planning suggéré (4 heures)

**Heure 1 :**
- ETU004190 → Initialisation + `script.sql` + Models
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
- [X] Barèmes de frais configurables, applicables **au retrait et au transfert uniquement** (dépôt sans frais)
- [X] Dépôt, Retrait et Transfert fonctionnels
- [X] Historique complet par client et global (opérateur)
- [X] Dashboard Opérateur (situation des gains + comptes clients)
- [X] Design mobile-first avec Bootstrap

---

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
script.sql                      # ETU004190 — création des tables + données initiales
examenfinals4.db                         # généré à partir de script.sql
```