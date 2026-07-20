# Tâches.md - Projet Simulateur Mobile Money (Version 1)

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
- Login : recherche du compte par numéro de téléphone existant dans `base.db`. Pas de création de compte à la volée (pas d'inscription).
- Les préfixes valides (ex : 033, 037) servent à valider le format du numéro, pas à créer un compte.

---

## Todo List

### Travaux communs

- [ ] Créer le projet CodeIgniter 4
- [ ] Configuration SQLite (`base.db`)
- [ ] Créer `base.sql` avec le schéma (tables, vues, données initiales — dont le barème de l'exemple)
- [ ] Layout Bootstrap + design mobile-first (`app/Views/layout/app.php`, `public/assets/css/style.css`)
- [ ] Définir en commun le contrat des fonctions partagées : `CompteModel::findByTelephone()`, `TransactionModel::getByCompte()`, `calculerFrais($typeOperation, $montant)`

### ETU004190 (Backend + Opérateur)

- [ ] Tous les Models : `PrefixeModel`, `TypeOperationModel`, `BaremeFraisModel`, `CompteModel`, `TransactionModel`
- [ ] Calcul automatique des frais selon barème (retrait/transfert uniquement)
- [ ] CRUD Préfixes
- [ ] CRUD Types d'opérations + Barèmes de frais
- [ ] Dashboard Admin (gains par type d'opération, liste des comptes)
- [ ] Routes protégées Admin (`AuthFilter` rôle admin)
- [ ] Fonctions métier partagées : mise à jour solde, enregistrement transaction, endpoint `calculerFrais()` réutilisable par le frontend

### ETU003929 (Frontend + Côté Client)

#### Authentification
- [ ] `app/Controllers/AuthController.php`
  - `login()` — GET, affiche le formulaire de connexion (`auth/login.php`)
  - `authenticate()` — POST, reçoit `telephone`, appelle `CompteModel::findByTelephone()`. Si trouvé : crée la session (`compte_id`, `telephone`, `solde`, `role`) et redirige vers le dashboard client. Si non trouvé : message d'erreur "Numéro non reconnu"
  - `logout()` — détruit la session, redirige vers le login
- [ ] `app/Views/auth/login.php` — formulaire numéro de téléphone + validation JS du format avant envoi

#### Dashboard client
- [ ] `app/Controllers/ClientController.php::dashboard()` — récupère le solde via la session, affiche les 5 dernières transactions
- [ ] `app/Views/client/dashboard.php` — cards Bootstrap (solde en évidence) + boutons Dépôt / Retrait / Transfert / Historique

#### Opérations
- [ ] `ClientController::depot()` / `storeDepot()` — formulaire montant, validation montant > 0, **aucun frais**, crédite le compte, enregistre la transaction
- [ ] `ClientController::retrait()` / `storeRetrait()` — formulaire montant, calcul des frais (dépendance : fonction backend `calculerFrais()`), vérification `solde >= montant + frais`, débit, enregistrement
- [ ] `ClientController::transfert()` / `storeTransfert()` — formulaire téléphone destinataire + montant, vérifie que le destinataire existe et diffère de l'émetteur, calcule les frais, débite l'émetteur (montant + frais), crédite le destinataire (montant net), enregistre les deux écritures liées
- [ ] `app/Views/client/depot.php`, `retrait.php`, `transfert.php` — formulaires avec affichage du solde disponible et aperçu du montant total (montant + frais) avant confirmation

#### Historique
- [ ] `ClientController::historique()` — liste complète des transactions du compte connecté
- [ ] `app/Views/client/historique.php` — tableau : type d'opération, montant, frais, solde après, date, sens (crédit/débit)

#### UI / UX / JS
- [ ] Design responsive + messages (toasts succès/erreur)
- [ ] Validation JS + confirmation avant retrait/transfert
- [ ] `public/assets/js/client.js`
  - `validateTelephone(numero)` — vérifie le format du numéro avant soumission
  - `validateMontant(input)` — bloque les montants négatifs ou non numériques
  - `previewFrais(montant, typeOperation)` — appel AJAX vers l'endpoint `calculerFrais()` pour afficher le frais en direct pendant la saisie
  - `confirmAction(message)` — modal Bootstrap de confirmation avant retrait/transfert
  - `showToast(type, message)` — affichage des toasts après chaque action
- [ ] Intégration AJAX pour mise à jour du solde (optionnel)

---

## Planning suggéré (4 heures)

**Heure 1 :**
- ETU004190 → Initialisation + `base.sql` + Models
- ETU003929 → Layout + `AuthController` (login/logout) + Dashboard Client

**Heure 2 :**
- ETU004190 → Calcul des frais (`calculerFrais()`) + Controllers Admin (CRUD)
- ETU003929 → Formulaires Dépôt / Retrait / Transfert (avec appel à `calculerFrais()`)

**Heure 3 :**
- ETU004190 → Transactions + Dashboard Opérateur + `AuthFilter`
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
    Database.php           # Configuration SQLite (base.db)
    Filters.php
  Controllers/
    ClientController.php   # ETU003929
    AdminController.php    # ETU004190
    AuthController.php     # ETU003929
  Filters/
    AuthFilter.php         # ETU004190 — protection selon rôle (client / admin)
  Models/
    PrefixeModel.php       # ETU004190
    TypeOperationModel.php # ETU004190
    BaremeFraisModel.php   # ETU004190
    CompteModel.php        # ETU004190
    TransactionModel.php   # ETU004190
  Views/
    layout/
      app.php              # commun
    auth/
      login.php            # ETU003929
    client/
      dashboard.php        # ETU003929
      depot.php            # ETU003929
      retrait.php          # ETU003929
      transfert.php        # ETU003929
      historique.php       # ETU003929
    admin/
      dashboard.php        # ETU004190
      prefixes.php         # ETU004190
      baremes.php          # ETU004190
      comptes.php          # ETU004190
      transactions.php     # ETU004190
public/
  assets/
    css/style.css          # commun
    js/client.js           # ETU003929
script.sql                 # ETU004190 — création des tables + données initiales
base.db                    # généré à partir de script.sql
```

---

## Fichiers/classes créés

### Models (ETU004190)
- **PrefixeModel.php** → Gestion des préfixes valides (033, 037…)
- **TypeOperationModel.php** → Types d'opérations (dépôt, retrait, transfert)
- **BaremeFraisModel.php** → Barèmes par tranche, appliqués au retrait et au transfert uniquement
- **CompteModel.php** → Gestion des comptes clients (solde, téléphone), avec `findByTelephone()`
- **TransactionModel.php** → Historique complet des mouvements, avec `getByCompte()`

### Controllers
- **AuthController.php** (ETU003929)
  - `login()` → affiche le formulaire de connexion
  - `authenticate()` → login automatique par numéro de téléphone
  - `logout()`
- **ClientController.php** (ETU003929)
  - `dashboard()` → affichage solde + menu + dernières transactions
  - `depot()` / `storeDepot()` → dépôt sans frais
  - `retrait()` / `storeRetrait()` → retrait avec frais et vérification de solde
  - `transfert()` / `storeTransfert()` → transfert avec frais côté émetteur
  - `historique()` → historique complet du client
- **AdminController.php** (ETU004190)
  - `dashboard()` → gains + situation globale
  - `prefixes()` → CRUD préfixes
  - `baremes()` → CRUD barèmes de frais
  - `comptes()` → liste des comptes clients
  - `transactions()` → historique global

### Filters
- **AuthFilter.php** (ETU004190) → séparation Client / Opérateur + protection des routes

### Autres
- **script.sql** (ETU004190) → création des tables + insertion des barèmes de l'exemple photo
- **client.js** (ETU003929) → validation, confirmation, toasts, aperçu des frais en direct
- **Helper `calculerFrais()`** (ETU004190, consommé par ETU003929) → calcul des frais selon tranche, exposé pour un usage AJAX côté clientbal (opérateur)
- [X] Dashboard Opérateur (situation des gains + comptes clients)
- [X] Design mobile-first avec Bootstrap

---

## Structure du projet

```text
app/
  Config/
    Routes.php
    Database.php           # Configuration SQLite (base.db)
    Filters.php
  Controllers/
    ClientController.php   # ETU003929
    AdminController.php    # ETU004190
    AuthController.php     # ETU003929
  Filters/
    AuthFilter.php         # ETU004190 — protection selon rôle (client / admin)
  Models/
    PrefixeModel.php       # ETU004190
    TypeOperationModel.php # ETU004190
    BaremeFraisModel.php   # ETU004190
    CompteModel.php        # ETU004190
    TransactionModel.php   # ETU004190
  Views/
    layout/
      app.php              # commun
    auth/
      login.php            # ETU003929
    client/
      dashboard.php        # ETU003929
      depot.php            # ETU003929
      retrait.php          # ETU003929
      transfert.php        # ETU003929
      historique.php       # ETU003929
    admin/
      dashboard.php        # ETU004190
      prefixes.php         # ETU004190
      baremes.php          # ETU004190
      comptes.php          # ETU004190
      transactions.php     # ETU004190
public/
  assets/
    css/style.css          # commun
    js/client.js           # ETU003929
script.sql                 # ETU004190 — création des tables + données initiales
base.db                    # généré à partir de script.sql
```

---

## Fichiers/classes créés

### Models (ETU004190)

- **PrefixeModel.php** → Gestion des préfixes valides (033, 037…)
- **TypeOperationModel.php** → Types d'opérations (dépôt, retrait, transfert)
- **BaremeFraisModel.php** → Barèmes par tranche, appliqués au retrait et au transfert uniquement
- **CompteModel.php** → Gestion des comptes clients (solde, téléphone), avec `findByTelephone()`
- **TransactionModel.php** → Historique complet des mouvements, avec `getByCompte()`

### Controllers

- **AuthController.php** (ETU003929)
  - `login()` → affiche le formulaire de connexion
  - `authenticate()` → login automatique par numéro de téléphone
  - `logout()`
- **ClientController.php** (ETU003929)
  - `dashboard()` → affichage solde + menu + dernières transactions
  - `depot()` / `storeDepot()` → dépôt sans frais
  - `retrait()` / `storeRetrait()` → retrait avec frais et vérification de solde
  - `transfert()` / `storeTransfert()` → transfert avec frais côté émetteur
  - `historique()` → historique complet du client
- **AdminController.php** (ETU004190)
  - `dashboard()` → gains + situation globale
  - `prefixes()` → CRUD préfixes
  - `baremes()` → CRUD barèmes de frais
  - `comptes()` → liste des comptes clients
  - `transactions()` → historique global

### Filters

- **AuthFilter.php** (ETU004190) → séparation Client / Opérateur + protection des routes

### Autres

- **script.sql** (ETU004190) → création des tables + insertion des barèmes de l'exemple photo
- **client.js** (ETU003929) → validation, confirmation, toasts, aperçu des frais en direct
- **Helper `calculerFrais()`** (ETU004190, consommé par ETU003929) → calcul des frais selon tranche, exposé pour un usage AJAX côté client
