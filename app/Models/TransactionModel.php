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
        'compte_contrepartie_id', // NULL sauf transfert ; ce n'est pas forcément le "receveur", voir sens
    ];

    // date_operation a un DEFAULT CURRENT_TIMESTAMP côté SQLite
    protected $useTimestamps = false;

    protected $validationRules = [
        'compte_id'         => 'required|integer',
        'type_operation_id' => 'required|integer',
        'montant'           => 'required|decimal|greater_than[0]',
        'frais'             => 'permit_empty|decimal|greater_than_equal_to[0]',
        'solde_apres'       => 'required|decimal|greater_than_equal_to[0]',
        'sens'                   => 'required|in_list[credit,debit]',
        'compte_contrepartie_id' => 'permit_empty|integer',
    ];

    protected $skipValidation = false;

    /**
     * Historique des transactions d'un compte (contrat commun).
     * dashboard() appelle avec $limit = 5, historique() sans limite.
     */
    public function getByCompte(int $compteId, ?int $limit = null): array
    {
        $builder = $this->select('transactions.*, types_operation.libelle as type_libelle, types_operation.code as type_code')
                         ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
                         ->where('transactions.compte_id', $compteId)
                         ->orderBy('transactions.date_operation', 'DESC');

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
}