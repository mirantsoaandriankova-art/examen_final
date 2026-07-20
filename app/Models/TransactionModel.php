<?php
 
namespace App\Models;
 
use CodeIgniter\Model;
 
class TransactionModel extends Model
{
    protected $table            = 'transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
 
    protected $allowedFields = [
        'compte_id',
        'type_operation_id',
        'montant',
        'frais',
        'solde_apres',
        'sens',
        'compte_lie_id', // NULL sauf transfert ; ce n'est pas forcément le "receveur", voir sens
        'prefixe_id',
        'commission',
        'frais_inclus',
        'groupe_envoi_id',
        'compte_lie_id',
        'prefixe_id',      // V2 : renseigné seulement si transfert vers un autre opérateur
        'commission',      // V2 : commission externe (0 si transfert interne, dépôt, retrait)
        'frais_inclus',    // V2 : 0 = frais en plus (défaut), 1 = frais inclus dans le montant saisi
        'groupe_envoi_id', // V2 : regroupe les lignes d'un même envoi multiple, null sinon
    ];
 
    // date_operation a un DEFAULT CURRENT_TIMESTAMP côté SQLite
    protected $useTimestamps = false;
 
    protected $validationRules = [
        'compte_id'         => 'required|integer',
        'type_operation_id' => 'required|integer',
        'montant'           => 'required|decimal|greater_than[0]',
        'frais'             => 'permit_empty|decimal|greater_than_equal_to[0]',
        'solde_apres'       => 'required|decimal|greater_than_equal_to[0]',
        'sens'            => 'required|in_list[credit,debit]',
        'compte_lie_id'   => 'permit_empty|integer',
        'prefixe_id'       => 'permit_empty|integer',
        'commission'       => 'permit_empty|decimal|greater_than_equal_to[0]',
        'frais_inclus'     => 'permit_empty|in_list[0,1]',
        'groupe_envoi_id'  => 'permit_empty|max_length[64]',
        'sens'              => 'required|in_list[credit,debit]',
        'compte_lie_id'     => 'permit_empty|integer',
        'prefixe_id'        => 'permit_empty|integer',
        'commission'        => 'permit_empty|decimal|greater_than_equal_to[0]',
        'frais_inclus'      => 'permit_empty|in_list[0,1]',
        'groupe_envoi_id'   => 'permit_empty|max_length[50]',
    ];
 
    protected $skipValidation = false;

    /**
     * Historique des transactions d'un compte (contrat commun).
     * dashboard() appelle avec $limit = 5, historique() sans limite.
     */
    public function getByCompte(int $compteId, ?int $limit = null): array
    {
        $builder = $this->select('transactions.*, types_operation.libelle as type_libelle, types_operation.code as type_code, prefixes.prefixe as prefixe_externe')
                         ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
                         ->join('prefixes', 'prefixes.id = transactions.prefixe_id', 'left')
                         ->where('transactions.compte_id', $compteId)
                         ->orderBy('transactions.date_operation', 'DESC')
                         ->orderBy('transactions.id', 'DESC');

        if ($limit !== null) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Enregistre une écriture de transaction (une ligne = un mouvement sur UN compte).
     * Pour un transfert : 2 appels (débit émetteur + crédit destinataire, compte_lie_id croisé).
     */
    public function enregistrer(array $data): int|false
    {
        $inserted = $this->insert($data);

        return $inserted ? $this->getInsertID() : false;
    }

    /**
     * Historique global de toutes les transactions (vue admin).
     */
    public function getAll(?int $limit = null): array
    {
        $builder = $this->select('transactions.*, types_operation.libelle as type_libelle, types_operation.code as type_code, comptes.telephone as telephone, comptes.nom as nom_client')
                         ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
                         ->join('comptes', 'comptes.id = transactions.compte_id')
                         ->orderBy('transactions.date_operation', 'DESC');

        if ($limit !== null) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Total des frais perçus, groupé par type d'opération (dashboard admin).
     */
    public function getGainsParType(): array
    {
        return $this->select('types_operation.code as type_code, types_operation.libelle as type_libelle, SUM(transactions.frais) as total_frais, COUNT(transactions.id) as nombre_operations')
                    ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
                    ->groupBy('transactions.type_operation_id')
                    ->findAll();
    }

    /**
     * Récapitulatif des gains internes et commissions dues aux autres opérateurs.
     */
    public function getSituationOperateurs(): array
    {
        return $this->select('prefixes.id, prefixes.prefixe, prefixes.description, prefixes.commission_pourcentage, COALESCE(SUM(transactions.commission), 0) AS total_commission, COUNT(transactions.id) AS nombre_transferts')
                    ->join('prefixes', 'prefixes.id = transactions.prefixe_id')
                    ->where('transactions.sens', 'debit')
                    ->groupBy('prefixes.id, prefixes.prefixe, prefixes.description, prefixes.commission_pourcentage')
                    ->orderBy('prefixes.prefixe', 'ASC')
        /**
     * Gains (frais + commission) séparés en 2 blocs (contrat commun V2) :
     *  - 'principal' : total sur les opérations internes (prefixe_id IS NULL)
     *  - 'externes'  : détail par autre opérateur
     *
     * IMPORTANT : Le "gain réel" de l'entreprise = seulement les frais du barème.
     * La commission est collectée mais reversée à l'autre opérateur → elle n'est pas un gain.
     */
    public function getGainsParOperateur(): array
    {
        // Notre opérateur (interne)
        $principal = $this->select('SUM(frais) as total_frais, SUM(commission) as total_commission, COUNT(id) as nombre_operations')
                           ->where('prefixe_id', null)  // ou IS NULL selon le driver
                           ->first();

        $principal['total_gains'] = (float) ($principal['total_frais'] ?? 0);
        // On peut garder total_commission pour info, mais total_gains = frais uniquement

        // Autres opérateurs
        $externes = $this->select('prefixes.id as prefixe_id, prefixes.description as operateur, prefixes.prefixe, 
                                   SUM(transactions.frais) as total_frais, 
                                   SUM(transactions.commission) as total_commission, 
                                   COUNT(transactions.id) as nombre_operations')
                          ->join('prefixes', 'prefixes.id = transactions.prefixe_id')
                          ->where('transactions.prefixe_id IS NOT NULL')
                          ->groupBy('transactions.prefixe_id')
                          ->findAll();

        foreach ($externes as &$e) {
            $e['total_gains'] = (float) ($e['total_frais'] ?? 0);  // ← Correction ici : seulement les frais
        }

        return [
            'principal' => $principal,
            'externes'  => $externes,
        ];
    }
 
    /**
     * Montants principaux (hors frais/commission) transférés vers chaque autre opérateur
     * (contrat commun V2). C'est le montant à reverser à cet opérateur, pas un gain.
     * Ne compte que les sorties (sens = 'debit') vers un préfixe externe.
     */
    public function getMontantsAEnvoyerParOperateur(): array
    {
        return $this->select('prefixes.id as prefixe_id, prefixes.description as operateur, prefixes.prefixe, SUM(transactions.montant) as total_a_envoyer, COUNT(transactions.id) as nombre_operations')
                    ->join('prefixes', 'prefixes.id = transactions.prefixe_id')
                    ->where('transactions.sens', 'debit')
                    ->where('transactions.prefixe_id IS NOT NULL')
                    ->groupBy('transactions.prefixe_id')
                    ->findAll();
    }
}
