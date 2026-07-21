1. Envoi multiple avancé + Programmation de transferts (le plus difficile)
   Description : Permettre d'envoyer à plusieurs numéros + option "programmer pour plus tard" (date/heure).
   Modifications :
   1.1 app/Models/TransactionModel.php (ajouter méthode pour transactions programmées)
   PHP// Ajouter dans la classe
   public function programmerTransfert(array $data, string $dateExecution): int|false
   {
   $db = $this->db;
   $db->transStart();

   // ... logique existante de storeEnvoiMultiple ...

   // Ajouter champ date_execution dans INSERT
   $data['date_execution'] = $dateExecution; // nouvelle colonne

   $id = $this->insert($data);

   $db->transComplete();
   return $id;
   }
   1.2 base.sql (ajouter colonne)
   SQLALTER TABLE transactions ADD COLUMN date_execution DATETIME;
   ALTER TABLE transactions ADD COLUMN statut_execution TEXT DEFAULT 'en_attente' CHECK (statut_execution IN ('en_attente', 'execute', 'annule'));
   1.3 app/Controllers/ClientController.php (nouveau méthode)
   PHPpublic function storeEnvoiProgramme()
   {
   $date = $this->request->getPost('date_execution'); // validation strtotime
   // ... appel à TransactionModel::programmerTransfert
   }
   1.4 Vue + JS (envoi_multiple.php + client.js) : Ajouter datepicker Bootstrap + AJAX preview par destinataire.
   Impact : Transaction SQL complexe + cron-like (via Spark command pour exécuter les programmés).
2. Paiement de factures / Marchands (QR Code)
   Description : Ajouter type "facture" avec QR code pour marchands.
   Modifications :
   2.1 app/Models/ Créer MarchandModel.php (similaire à CompteModel)
   PHPclass MarchandModel extends Model
   {
   protected $table = 'marchands';
   // ... findByQrCode(), etc.
   }
   2.2 base.sql
   SQLCREATE TABLE marchands (
   id INTEGER PRIMARY KEY,
   nom TEXT,
   qr_code TEXT UNIQUE,
   telephone TEXT
   );
   2.3 ClientController.php + Views : Nouvelle page payer_facture.php avec scanner JS (ou input QR).
   Impact : Bibliothèque JS pour QR (ex: qrcode.js) + nouveau type_operation 'facture'.
3. Statistiques avancées + Export PDF/CSV (Admin)
   Description : Dashboard admin avec graphiques + export.
   Modifications :
   3.1 app/Controllers/AdminController.php
   PHPpublic function statistiques()
   {
   $data['stats'] = $this->transactionModel->getStatistiquesMensuelles();
   return view('admin/statistiques', $data);
   }
   3.2 TransactionModel.php
   PHPpublic function getStatistiquesMensuelles(): array
   {
   return $this->db->query("SELECT strftime('%Y-%m', date_operation) as mois, ...")->getResultArray();
   }
   3.3 Vue : Intégrer Chart.js.
   Export : Utiliser dompdf ou simple CSV avec response->download().
4. Limites quotidiennes / Plafonds par compte
   Description : Limiter montants/jour par utilisateur.
   Modifications :
   4.1 CompteModel.php
   PHPpublic function verifierLimiteQuotidienne(int $compteId, float $montant, string $type): bool
   {
   $today = date('Y-m-d');
   $total = $this->db->table('transactions')
   ->where('compte_id', $compteId)
   ->where('DATE(date_operation)', $today)
   ->where('type_operation_id', /* id du type */)
   ->selectSum('montant')
   ->get()->getRow()->montant ?? 0;

   return ($total + $montant) <= 500000; // exemple plafond
   }
   Appeler dans storeDepot(), storeTransfert(), etc.
   4.2 base.sql : Optionnel, table plafonds.
5. Notifications (Email/SMS simulé + Historique notifications)
   Description : Envoyer notification après chaque opération.
   Modifications :
   5.1 Helpers/operation_helper.php
   PHPfunction envoyerNotification(int $compteId, string $message)
   {
   // Simuler SMS ou insérer dans table notifications
   $notifModel = new \App\Models\NotificationModel();
   $notifModel->insert([...]);
   }
   Créer NotificationModel.php + table.
   Impact : Simple mais utile pour UX.
6. Recherche / Filtrage avancé Historique
   Description : Filtres par date, type, montant dans historique client/admin.
   Modifications :
   ClientController.php / TransactionModel.php : Ajouter params à getByCompte() (where clauses dynamiques).
   Vue : Ajouter formulaire de filtres + pagination.
   Difficulté : Moyenne (surtout UI).
7. Changement de PIN / 2FA simple (code SMS simulé)
   Description : Après login, demander PIN ou code de confirmation.
   Modifications :
   AuthController.php : Ajouter verifierPin().
   Ajouter colonne pin (hashé) dans comptes.
8. Thème sombre / Personnalisation UI
   Description : Toggle dark mode.
   Modifications : Principalement public/assets/css/style.css + JS (client.js) + cookie/session pour persistance.
   Très simple.
9. Validation renforcée numéros + Internationalisation basique
   Description : Meilleure regex + support multi-langue (fr/en).
   Modifications : PrefixeModel::isPrefixeValide() + fichiers Language/.
10. Backup / Export base de données (le plus simple)
    Description : Bouton admin pour exporter DB SQLite.
    Modifications :
    AdminController.php
    PHPpublic function backup()
    {
    $dbPath = WRITEPATH . 'examenfinals4.db';
    return $this->response->download($dbPath, null)->setFileName('backup_' . date('Ymd') . '.db');
    }






promotion frais de transfert même opérateur

créer en base la configuration

bonus: page pour modifier la pourcentage
