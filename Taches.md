# Tâches.md - Projet Simulateur Mobile Money (Version 1)

**Équipe :** ETU003929 et ETU004190
**Technologies :** PHP + CodeIgniter 4, SQLite, HTML/CSS/JS, Bootstrap  
**Durée :** 4 heures

## Répartition des tâches 
### ETU004190(Backend + Opérateur)
**Responsable principal :** Côté Opérateur + Base de données + Authentification

#### Tâches à réaliser :

1. **Initialisation du projet CodeIgniter 4**
   - Installation et configuration de base
   - Configuration de SQLite (database.php)
   - Création des dossiers nécessaires

2. **Base de données (Migrations + Models)**
   - Création des tables :
     - `prefixes_operateur` (id, prefixe)
     - `types_operation` (id, nom, description)
     - `baremes_frais` (id, type_operation_id, tranche_min, tranche_max, frais)
     - `comptes` (id, telephone, solde, created_at, updated_at)
     - `transactions` (id, type, telephone_source, telephone_dest, montant, frais, montant_total, date, statut)
     - `gains_operateur` (vue ou table pour suivi des gains)

3. **Côté Opérateur (Admin)**
   - Routes et Controller `AdminController`
   - Gestion des préfixes (CRUD)
   - Gestion des types d'opérations et barèmes de frais (CRUD avec tranches)
   - Dashboard Opérateur :
     - Situation des gains (retraits + transferts)
     - Liste des comptes clients (solde, téléphone)
     - Historique global des transactions

4. **Authentification & Sécurité**
   - Login automatique par numéro de téléphone (session)
   - Middleware pour séparer Client / Opérateur
   - Validation des numéros selon préfixes

5. **Fonctions métier communes**
   - Calcul des frais selon barème
   - Enregistrement des transactions
   - Mise à jour des soldes

---

### ETU003929 (Frontend + Côté Client)
**Responsable principal :** Interface Client + Expérience utilisateur

#### Tâches à réaliser :

1. **Interface Client (Frontend)**
   - Page d'accueil / Login automatique (saisie numéro)
   - Dashboard Client (après login) :
     - Affichage du solde
     - Menu des opérations

2. **Opérations Client**
   - **Voir le solde** (mise à jour en temps réel)
   - **Dépôt** (formulaire + confirmation)
   - **Retrait** (formulaire + confirmation)
   - **Transfert** (vers autre numéro + validation préfixe)
   - **Historique des transactions** (filtré par client)

3. **Design & UI**
   - Utilisation de Bootstrap
   - Design responsive et mobile-first (simulation téléphone)
   - Messages de succès/erreur (toasts)
   - Historique avec cartes ou tableau clair

4. **JavaScript**
   - Validation des formulaires en JS
   - Mise à jour dynamique du solde (AJAX si possible)
   - Confirmation avant opérations sensibles (retrait/transfert)

5. **Intégration**
   - Connexion avec les controllers du backend
   - Affichage des données venant de l’Étudiant A

---

## Planning pour la version 1 (4 heures)

**Heure 1 :**
- 4190 → Initialisation projet + base de données + modèles
- 3929 → Création des vues Client (HTML + Bootstrap)

**Heure 2 :**
- 4190 → CRUD Opérateur + calcul des frais
- 3929 → Dashboard Client + formulaires des opérations

**Heure 3 :**
- 4190 → Authentification + controllers des opérations
- 3929 → Historique + JS + polish UI

**Heure 4 :**
- Intégration + tests croisés + corrections + préparation démo

---


**Fonctionnalités validées pour la version 1:**
- [ ] Login automatique par numéro
- [ ] Gestion des préfixes
- [ ] Barèmes de frais configurables
- [ ] Dépôt / Retrait / Transfert
- [ ] Historique
- [ ] Dashboard Opérateur (gains + comptes)

---
**Dernière mise à jour :** 20 juillet 2026