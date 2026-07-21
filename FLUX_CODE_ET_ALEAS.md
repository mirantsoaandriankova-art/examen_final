# Flux du code — MobiMoney

Ce document décrit le parcours d'une requête dans l'application, depuis l'URL jusqu'à la base SQLite et la vue HTML. Il indique également les principaux aléas possibles et les corrections à appliquer.

## 1. Architecture générale

```text
Navigateur
    ↓
app/Config/Routes.php
    ↓ filtres d'authentification
Contrôleur (app/Controllers/)
    ↓
Helper métier + Modèles (app/Helpers/, app/Models/)
    ↓
SQLite (examenfinals4.db, structure définie dans base.sql)
    ↓
Vue PHP (app/Views/)
    ↓
Réponse HTML ou JSON
```

Les rôles sont séparés :

- `admin` consulte les comptes, les barèmes, les préfixes, les gains et toutes les transactions ;
- `client` effectue un dépôt, un retrait ou un transfert et consulte son propre historique ;
- le filtre `AuthFilter` protège les groupes `/admin`, `/client` et `/api`.

## 2. Connexion et redirection

### Fichiers concernés

- Route : `app/Config/Routes.php`, lignes 10 à 14.
- Contrôleur : `app/Controllers/AuthController.php`, méthodes `showLogin()`, `login()` et `logout()` lignes 19 à 63.
- Modèle : `app/Models/CompteModel.php`, méthode `findByTelephone()` lignes 41 à 44.
- Vue : `app/Views/auth/login.php`.

### Flux

1. `GET /` ou `GET /login` affiche le formulaire de connexion.
2. `POST /login` lit le téléphone.
3. `CompteModel::findByTelephone()` recherche le compte dans `comptes`.
4. Si le compte existe, la session contient `compte_id`, `role`, `solde` et `isLoggedIn`.
5. Le rôle redirige vers `/admin` ou `/client`.

Extrait central :

```php
$compte = $this->compteModel->findByTelephone($telephone);

session()->set([
    'compte_id'  => $compte['id'],
    'role'       => $compte['role'],
    'isLoggedIn' => true,
]);

return redirect()->to($compte['role'] === 'admin' ? '/admin' : '/client');
```

Position : `app/Controllers/AuthController.php:36-53`.

## 3. Calcul des frais

### Fichiers concernés

- Helper : `app/Helpers/operation_helper.php`.
- Type d'opération : `app/Models/TypeOperationModel.php`, méthode `findByCode()` lignes 39 à 42.
- Barème : `app/Models/BaremeFraisModel.php`, méthode `getTranche()` lignes 43 à 53.
- API d'aperçu : `app/Controllers/Api/FraisController.php`, méthode `calculer()` lignes 13 à 64.
- Routes API : `app/Config/Routes.php`, lignes 43 à 48.

Pour un dépôt ou un retrait, `calculerFrais()` :

1. cherche le type (`depot`, `retrait` ou `transfert`) ;
2. cherche la tranche où `montant_min <= montant <= montant_max` ;
3. retourne `frais` et `total`.

Pour un transfert externe, `calculerFraisTransfert()` calcule séparément :

```php
$frais       = calculerFrais('transfert', $montant)['frais'];
$commission  = round($montant * $pourcentageOperateur / 100, 2);
$coutTotal   = $frais + $commission;
```

Position : `app/Helpers/operation_helper.php:59-71`.

Exemple Airtel : montant `5 000 Ar`, préfixe `031`, commission `8 %`.

```text
Frais de transfert : 30 Ar
Commission Airtel  : 5 000 × 8 % = 400 Ar
Coût total         : 30 + 400 = 430 Ar
```

Les `30 Ar` et les `400 Ar` ne représentent donc pas la même donnée.

## 4. Flux d'un dépôt

### Fichiers et positions

- Route : `app/Config/Routes.php:32-34`.
- Contrôleur : `app/Controllers/ClientController.php:82-130`, méthode `doDepot()`.
- Solde : `app/Models/CompteModel.php:49-57`, méthode `crediter()`.
- Historique : `app/Models/TransactionModel.php:53-56`, méthode `enregistrer()`.
- Vue : `app/Views/client/depot.php`.

### Déroulement

1. Le montant est contrôlé.
2. Le barème de dépôt est recherché.
3. Le montant net est calculé : `montant - frais`.
4. Le compte est crédité.
5. Une transaction `credit` est enregistrée avec le montant, les frais et le solde après opération.
6. Le client est redirigé vers `/client` avec un message de succès.

## 5. Flux d'un retrait

### Fichiers et positions

- Route : `app/Config/Routes.php:34-36`.
- Contrôleur : `app/Controllers/ClientController.php:150-195`, méthode `doRetrait()`.
- Solde : `app/Models/CompteModel.php:64-76`, méthode `debiter()`.
- Vue : `app/Views/client/retrait.php`.

### Déroulement

```text
montant demandé + frais de retrait = total débité
```

Le retrait est refusé si le solde est inférieur au total. En cas de succès, le compte est débité puis la transaction est enregistrée avec `sens = debit`.

## 6. Flux d'un transfert interne ou externe

### Fichiers et positions

- Routes : `app/Config/Routes.php:36-40`.
- Contrôleur simple : `app/Controllers/ClientController.php:215-291`, méthode `doTransfert()`.
- Contrôleur multiple : `app/Controllers/ClientController.php:307-372`, méthode `doEnvoiMultiple()`.
- Écriture atomique : `app/Controllers/ClientController.php:380-455`, méthode privée `executerTransferts()`.
- Détection opérateur : `app/Models/PrefixeModel.php:45-78`.
- Vue : `app/Views/client/transfert.php` et `app/Views/client/envoi_multiple.php`.

### Détection du destinataire

1. `isPrefixeValide()` vérifie que le numéro commence par un préfixe actif.
2. `getAutreOperateur()` retourne l'opérateur externe si le préfixe est marqué `est_operateur_principal = 0`.
3. Si le préfixe est interne, le destinataire est recherché dans `comptes`.
4. Un destinataire externe n'a pas de compte local : le débit est enregistré, mais aucune ligne de crédit locale n'est créée.

### Écriture transactionnelle

`executerTransferts()` ouvre une transaction SQL, puis :

1. débite l'émetteur ;
2. crédite le destinataire interne, s'il existe ;
3. enregistre la ligne débit avec `frais`, `commission`, `prefixe_id` et `compte_lie_id` ;
4. enregistre la ligne crédit interne, si nécessaire ;
5. valide avec `transCommit()` ;
6. annule tout avec `transRollback()` si une étape échoue.

La donnée importante pour le dashboard est enregistrée ici :

```php
'frais'      => $calcul['frais'],
'commission' => $calcul['commission'],
'prefixe_id' => $autreOperateur['id'] ?? null,
'sens'       => 'debit',
```

Position : `app/Controllers/ClientController.php:411-423`.

## 7. Flux du dashboard administrateur

### Fichiers et positions

- Route : `app/Config/Routes.php:16-28`.
- Contrôleur : `app/Controllers/AdminController.php:32-45`, méthode `dashboard()`.
- Agrégations : `app/Models/TransactionModel.php:72-127`.
- Vue : `app/Views/admin/Dashboard.php`.
- Historique admin détaillé : `app/Models/TransactionModel.php:109-118`, méthode `getAdminTransactions()`.

Le contrôleur prépare :

```php
'gains'             => $this->transactionModel->getGainsParType(),
'gainsParOperateur' => $this->transactionModel->getGainsParOperateur(),
'montantsAEnvoyer'  => $this->transactionModel->getMontantsAEnvoyerParOperateur(),
'transactions'      => $this->transactionModel->getAdminTransactions(),
```

La vue distingue désormais :

- `total_frais` : frais de transfert facturés ;
- `total_commission` : commission due à l'opérateur externe ;
- `total_cout` : frais + commission ;
- l'historique de toutes les transactions, 15 lignes par page.

Position de l'affichage : `app/Views/admin/Dashboard.php:9-30`.

## 8. Structure des données principale

La structure est définie dans `base.sql:64-89`.

| Champ | Signification |
|---|---|
| `montant` | Montant de l'opération ou montant demandé par le client |
| `frais` | Frais selon le barème de l'opération |
| `commission` | Commission due à l'opérateur externe |
| `sens` | `credit` ou `debit` |
| `solde_apres` | Solde du compte après le mouvement |
| `prefixe_id` | Opérateur externe associé au transfert |
| `compte_lie_id` | Compte destinataire ou émetteur local lié |
| `groupe_envoi_id` | Identifiant commun d'un envoi multiple |

## 9. Aléas et réponses à appliquer

Les cas ci-dessous sont les réponses recommandées. Les positions indiquées correspondent au code actuel et peuvent changer après une refactorisation.

### Aléa A — Le dashboard affiche les frais à la place de la commission

**Symptôme :** une carte affiche `30 Ar`, alors que la commission à régler est `400 Ar`.

**Cause :** la requête utilise `SUM(transactions.frais)` au lieu de `SUM(transactions.commission)`.

**Réponse :** conserver les deux montants, mais les nommer explicitement.

**Fichiers à modifier :**

- `app/Models/TransactionModel.php`, méthode `getGainsParOperateur()`, autour des lignes 96 à 101.
- `app/Views/admin/Dashboard.php`, carte externe autour des lignes 9 à 12.
- Tableau de commission autour des lignes 15 à 18.

**Code recommandé :**

```php
COALESCE(SUM(transactions.frais), 0) AS total_frais,
COALESCE(SUM(transactions.commission), 0) AS total_commission,
COALESCE(SUM(transactions.frais + transactions.commission), 0) AS total_cout
```

Ne pas remplacer `frais` par `commission` partout : les deux indicateurs ont des significations différentes.

### Aléa B — Le montant transféré est inférieur aux frais et à la commission

**Symptôme :** le destinataire recevrait un montant nul ou négatif.

**Fichiers à modifier :**

- `app/Controllers/ClientController.php`, validation simple autour des lignes 250 à 255.
- `app/Helpers/operation_helper.php`, calcul autour des lignes 63 à 71.

**Réponse actuelle :** refuser le transfert si `montant_recu <= 0`.

```php
if ($result['montant_recu'] <= 0) {
    session()->setFlashdata('error', 'Le montant doit être supérieur aux frais et à la commission.');
    return redirect()->to('/client/transfert');
}
```

**Amélioration possible :** afficher le détail avant validation dans `app/Views/client/transfert.php` et via `app/Controllers/Api/FraisController.php:46-56`.

### Aléa C — Solde insuffisant

**Symptôme :** le client tente une opération supérieure à son solde.

**Fichiers à modifier :**

- `app/Models/CompteModel.php`, méthode `debiter()`, lignes 64 à 76.
- `app/Controllers/ClientController.php`, contrôles retrait/transfert lignes 165 à 170 et 257 à 264.

**Réponse :** lever une erreur métier et ne créer aucune transaction.

```php
if ($compte['solde'] < $montant) {
    throw new RuntimeException('Solde insuffisant.');
}
```

Toujours effectuer ce contrôle côté serveur ; le contrôle JavaScript de la vue ne suffit pas.

### Aléa D — Préfixe inconnu ou opérateur désactivé

**Symptôme :** le numéro destinataire n'est pas reconnu.

**Fichiers à modifier :**

- `app/Models/PrefixeModel.php`, méthodes `getActifs()` et `isPrefixeValide()`, lignes 40 à 64.
- `app/Controllers/ClientController.php`, validation autour des lignes 235 à 248.
- `app/Views/admin/Prefixes.php`, formulaire d'activation/désactivation.

**Réponse :** refuser le transfert et demander à l'administrateur d'ajouter ou réactiver le préfixe.

```php
if (! $this->prefixeModel->isPrefixeValide($telephoneDest)) {
    session()->setFlashdata('error', 'Le préfixe du numéro destinataire n’est pas pris en charge.');
    return redirect()->to('/client/transfert');
}
```

### Aléa E — Numéro interne absent de la table des comptes

**Symptôme :** le préfixe est interne, mais aucun compte ne correspond au numéro.

**Fichiers à modifier :**

- `app/Controllers/ClientController.php:240-248`.
- `app/Models/CompteModel.php:41-44`.

**Réponse :** ne pas créer de compte automatiquement ; refuser l'opération avec un message clair.

```php
if ($autreOperateur === null && ! $destinataire) {
    session()->setFlashdata('error', 'Le numéro de notre réseau est introuvable.');
    return redirect()->to('/client/transfert');
}
```

### Aléa F — Échec pendant un transfert multiple

**Symptôme :** un des destinataires est invalide ou une écriture échoue au milieu du traitement.

**Fichiers à modifier :**

- `app/Controllers/ClientController.php:307-372`, préparation de l'envoi multiple.
- `app/Controllers/ClientController.php:380-455`, transaction SQL.

**Réponse :** valider tous les destinataires avant le premier débit, puis utiliser une seule transaction SQL.

```php
$db->transBegin();
// débits, crédits et insertions
if (! $db->transStatus()) {
    throw new RuntimeException('Le transfert n’a pas pu être finalisé.');
}
$db->transCommit();
```

En cas d'exception :

```php
$db->transRollback();
throw new RuntimeException($exception->getMessage());
```

### Aléa G — Double clic ou double soumission d'un formulaire

**Symptôme :** deux transactions identiques sont créées.

**Fichiers à modifier :**

- Vue : `app/Views/client/transfert.php`, `depot.php`, `retrait.php`.
- Contrôleur : `app/Controllers/ClientController.php`.
- Base : `base.sql`, table `transactions`.

**Réponse recommandée :** ajouter un jeton d'idempotence côté serveur, stocké avec la transaction, et désactiver le bouton après le premier clic côté navigateur.

Exemple de position côté vue :

```html
<button type="submit" class="btn btn-primary" data-submit-once>
    Valider
</button>
```

Exemple de contrôle serveur à ajouter dans le contrôleur :

```php
$cleOperation = (string) $this->request->getPost('cle_operation');
// Vérifier que cette clé n'a pas déjà été traitée avant d'écrire.
```

Cette évolution nécessite une nouvelle colonne ou une table dédiée ; elle ne doit pas être ajoutée uniquement dans la vue.

### Aléa H — Barème absent ou tranches qui se chevauchent

**Symptôme :** frais à `0 Ar` ou montant associé à une mauvaise tranche.

**Fichiers à modifier :**

- `app/Models/BaremeFraisModel.php:43-53`.
- `app/Controllers/AdminController.php:112-142`.
- `app/Views/admin/Baremes.php`.
- `base.sql:41-51`.

**Réponse :** refuser ou signaler l'opération si aucune tranche n'est trouvée et empêcher les tranches incohérentes.

```php
$tranche = $this->baremeFraisModel->getTranche($typeId, $montant);

if ($tranche === null) {
    throw new RuntimeException('Aucun barème ne correspond à ce montant.');
}
```

Une vérification d'absence de chevauchement peut être ajoutée dans `storeBareme()` et `updateBareme()` avant `insert()` ou `update()`.

### Aléa I — Historique incomplet dans le dashboard

**Symptôme :** seules quelques transactions apparaissent ou les crédits internes manquent.

**Fichiers à modifier :**

- `app/Models/TransactionModel.php:109-118`, méthode `getAdminTransactions()`.
- `app/Controllers/AdminController.php:32-45`.
- `app/Views/admin/Dashboard.php:27-30`.

**Réponse actuelle :** charger toutes les transactions avec pagination de 15 lignes, triées par date puis par identifiant.

```php
->orderBy('transactions.date_operation', 'DESC')
->orderBy('transactions.id', 'DESC')
->paginate($perPage, $group);
```

« Toutes » signifie ici que tout l'historique est accessible via les pages, et non que toutes les lignes sont chargées dans une seule page HTML.

### Aléa J — Incohérence entre le solde et l'historique

**Symptôme :** le solde est modifié, mais l'insertion de la transaction échoue, ou inversement.

**Fichiers à modifier :**

- `app/Controllers/ClientController.php`, dépôt/retrait autour des lignes 108 à 119 et 175 à 186.
- `app/Controllers/ClientController.php:380-455` pour les transferts.

**Réponse recommandée :** envelopper chaque opération financière, y compris dépôt et retrait, dans une transaction SQL unique contenant la modification du compte et l'insertion historique.

Structure recommandée :

```php
$db->transBegin();
try {
    $this->compteModel->debiter($compteId, $total);
    $this->transactionModel->enregistrer($data);
    $db->transCommit();
} catch (Throwable $exception) {
    $db->transRollback();
    throw $exception;
}
```

### Aléa K — Commission modifiée après une ancienne transaction

**Symptôme :** un administrateur change le pourcentage du préfixe et l'ancien historique semble changer de signification.

**Cause :** le pourcentage actuel est stocké dans `prefixes`, alors que la valeur réellement appliquée est déjà stockée dans `transactions.commission`.

**Fichiers à modifier si une traçabilité complète est requise :**

- `base.sql:64-89`, ajouter éventuellement `commission_pourcentage_applique` dans `transactions`.
- `app/Controllers/ClientController.php:411-423`, enregistrer le pourcentage utilisé.
- `app/Models/TransactionModel.php:16-32`, autoriser et valider le nouveau champ.
- `app/Views/admin/Dashboard.php`, afficher ce champ seulement si nécessaire.

**Réponse actuelle :** les totaux financiers restent corrects car le montant calculé est conservé dans `transactions.commission`.

## 10. Checklist de modification

Avant de modifier une règle métier :

1. identifier la route dans `app/Config/Routes.php` ;
2. trouver la méthode du contrôleur appelée ;
3. vérifier le helper de calcul ;
4. vérifier le modèle et les champs SQL ;
5. vérifier la vue qui affiche le résultat ;
6. traiter les cas d'erreur avant l'écriture en base ;
7. utiliser une transaction SQL pour toute opération qui modifie à la fois le solde et l'historique ;
8. tester avec un cas interne, un cas externe, un montant limite de barème et un solde insuffisant.

## 11. Vérifications rapides

Syntaxe PHP :

```bash
php -l app/Controllers/ClientController.php
php -l app/Controllers/AdminController.php
php -l app/Models/TransactionModel.php
php -l app/Views/admin/Dashboard.php
```

Vérification du cas Airtel :

```bash
sqlite3 examenfinals4.db \
  "SELECT p.description, p.prefixe, COUNT(t.id),
          COALESCE(SUM(t.frais), 0),
          COALESCE(SUM(t.commission), 0)
   FROM transactions t
   JOIN prefixes p ON p.id = t.prefixe_id
   WHERE t.sens = 'debit'
   GROUP BY p.id;"
```

Le résultat attendu pour le cas signalé est :

```text
Airtel Money (Autre Opérateur) | 031 | 1 | 30 | 400
```

