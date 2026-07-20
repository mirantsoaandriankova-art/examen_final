# Tâches.md - Projet Simulateur Mobile Money (Version 1)

**Équipe :** ETU003929 et ETU004190
**Technologies :** PHP + CodeIgniter 4, SQLite, HTML/CSS/JS, Bootstrap
**Durée :** 4 heures
**Date :** 20 juillet 2026

---

## Todo List

### Travaux communs

- [ ] Créer le projet CodeIgniter 4
- [ ] Configuration SQLite (base.db)
- [ ] Créer le fichier base.sql avec le schéma mis à jour
- [ ] Ajouter l'authentification automatique par numéro
- [ ] Créer le layout Bootstrap + design mobile-first

### ETU004190 (Backend + Opérateur)

- [ ] Créer tous les Models
- [ ] Implémenter le calcul automatique des frais selon barème
- [ ] CRUD Préfixes
- [ ] CRUD Types d'opérations + Barèmes de frais
- [ ] Dashboard Admin (gains par type d'opération, liste comptes)
- [ ] Routes protégées Admin
- [ ] Fonctions métier (mise à jour solde, enregistrement transaction)

### ETU003929 (Frontend + Côté Client)

- [ ] Page de login automatique
- [ ] Dashboard Client (affichage solde)
- [ ] Formulaires : Dépôt, Retrait, Transfert
- [ ] Historique des transactions du client
- [ ] Design responsive + messages (toasts succès/erreur)
- [ ] Validation JS + confirmation avant retrait/transfert
- [ ] Intégration AJAX pour mise à jour solde (optionnel)

---

## Planning suggéré (4 heures)

**Heure 1 :**
- ETU004190 → Initialisation + base.sql + Models
- ETU003929 → Layout + Page Login + Dashboard Client

**Heure 2 :**
- ETU004190 → Calcul frais + Controllers Admin (CRUD)
- ETU003929 → Formulaires Dépôt / Retrait / Transfert

**Heure 3 :**
- ETU004190 → Auth + Transactions + Dashboard Opérateur
- ETU003929 → Historique Client + JavaScript + UI/UX

**Heure 4 :**
- Intégration + Tests + Corrections + Préparation démonstration

---

## Fonctionnalités validées pour la Version 1

- [x] Login automatique par numéro de téléphone (pas d'inscription)
- [x] Gestion des préfixes opérateurs
- [x] Barèmes de frais configurables (avec l'exemple de la photo)
- [x] Dépôt, Retrait et Transfert fonctionnels
- [x] Historique complet par client et global (opérateur)
- [x] Dashboard Opérateur (situation des gains + comptes clients)
- [x] Design mobile-first avec Bootstrap

---
## Structure du projet

```text
app/
  Config/
    Routes.php
    Database.php           # Configuration SQLite (base.db)
    Filters.php
  Controllers/
    ClientController.php
    AdminController.php
    AuthController.php
  Filters/
    AuthFilter.php         # Protection selon rôle (client / admin)
  Models/
    PrefixeModel.php
    TypeOperationModel.php
    BaremeFraisModel.php
    CompteModel.php
    TransactionModel.php
  Views/
    layout/
      app.php              # Template principal
    auth/
      login.php
    client/
      dashboard.php
      depot.php
      retrait.php
      transfert.php
      historique.php
    admin/
      dashboard.php
      prefixes.php
      baremes.php
      comptes.php
      transactions.php
public/
  assets/css/style.css
script.sql                 # Création des tables + données initiales
base.db                    # Base SQLite (à générer)
```

---

## Fichiers/classes créés

### Models

- **PrefixeModel.php** → Gestion des préfixes valides (033, 037…)
- **TypeOperationModel.php** → Types d'opérations (dépôt, retrait, transfert)
- **BaremeFraisModel.php** → Barèmes par tranche (selon l'exemple fourni)
- **CompteModel.php** → Gestion des comptes clients (solde, téléphone)
- **TransactionModel.php** → Historique complet des mouvements

### Controllers

- **AuthController.php**
  - `login()` → Login automatique par numéro de téléphone
  - `logout()`
- **ClientController.php**
  - `dashboard()` → Affichage solde + menu
  - `depot()` / `storeDepot()`
  - `retrait()` / `storeRetrait()`
  - `transfert()` / `storeTransfert()`
  - `historique()`
- **AdminController.php**
  - `dashboard()` → Gains + situation globale
  - `prefixes()` → CRUD préfixes
  - `baremes()` → CRUD barèmes de frais
  - `comptes()` → Liste des comptes clients
  - `transactions()` → Historique global

### Filters

- **AuthFilter.php** → Séparation Client / Opérateur + protection des routes

### Autres

- **script.sql** → Création des tables + insertion des barèmes de l'exemple photo
- **Helpers** pour le calcul des frais selon tranche

