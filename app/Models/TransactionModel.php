<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'compte_id', 'type_operation_id', 'montant', 'frais', 'solde_apres',
        'sens', 'compte_lie_id', 'prefixe_id', 'commission', 'frais_inclus', 'groupe_envoi_id',
    ];

    protected $validationRules = [
        'compte_id' => 'required|integer',
        'type_operation_id' => 'required|integer',
        'montant' => 'required|decimal|greater_than[0]',
        'frais' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'solde_apres' => 'required|decimal|greater_than_equal_to[0]',
        'sens' => 'required|in_list[credit,debit]',
        'compte_lie_id' => 'permit_empty|integer',
        'prefixe_id' => 'permit_empty|integer',
        'commission' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'frais_inclus' => 'permit_empty|in_list[0,1]',
        'groupe_envoi_id' => 'permit_empty|max_length[64]',
    ];

    protected $skipValidation = false;

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

    public function enregistrer(array $data): int|false
    {
        return $this->insert($data) ? $this->getInsertID() : false;
    }

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

    public function getGainsParType(): array
    {
        return $this->select('types_operation.code as type_code, types_operation.libelle as type_libelle, SUM(transactions.frais) as total_frais, COUNT(transactions.id) as nombre_operations')
            ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
            ->groupBy('transactions.type_operation_id')
            ->findAll();
    }

    public function getSituationOperateurs(): array
    {
        return $this->select('prefixes.id, prefixes.prefixe, prefixes.description, prefixes.commission_pourcentage, COALESCE(SUM(transactions.commission), 0) AS total_commission, COUNT(transactions.id) AS nombre_transferts')
            ->join('prefixes', 'prefixes.id = transactions.prefixe_id')
            ->where('transactions.sens', 'debit')
            ->groupBy('prefixes.id, prefixes.prefixe, prefixes.description, prefixes.commission_pourcentage')
            ->orderBy('prefixes.prefixe', 'ASC')
            ->findAll();
    }

    public function getGainsParOperateur(): array
    {
        $principal = $this->select('COALESCE(SUM(frais), 0) as total_gains, COUNT(id) as nombre_operations')
            ->where('prefixe_id', null)
            ->first() ?? ['total_gains' => 0, 'nombre_operations' => 0];

        $externes = $this->select('prefixes.id as prefixe_id, prefixes.description as operateur, prefixes.prefixe, COALESCE(SUM(transactions.frais), 0) as total_frais, COALESCE(SUM(transactions.commission), 0) as total_commission, COALESCE(SUM(transactions.frais + transactions.commission), 0) as total_cout, COUNT(transactions.id) as nombre_operations')
            ->join('prefixes', 'prefixes.id = transactions.prefixe_id')
            ->where('transactions.sens', 'debit')
            ->groupBy('transactions.prefixe_id, prefixes.id, prefixes.description, prefixes.prefixe')
            ->orderBy('prefixes.prefixe', 'ASC')
            ->findAll();

        return ['principal' => $principal, 'externes' => $externes];
    }

    /**
     * Retourne l'historique complet des mouvements administratifs, paginé.
     */
    public function getAdminTransactions(int $perPage = 15, string $group = 'dashboard_transactions'): array
    {
        return $this->select('transactions.*, types_operation.libelle as type_libelle, types_operation.code as type_code, comptes.telephone as telephone, comptes.nom as nom_client, prefixes.prefixe as prefixe_externe, prefixes.description as operateur_externe')
            ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
            ->join('comptes', 'comptes.id = transactions.compte_id')
            ->join('prefixes', 'prefixes.id = transactions.prefixe_id', 'left')
            ->orderBy('transactions.date_operation', 'DESC')
            ->orderBy('transactions.id', 'DESC')
            ->paginate($perPage, $group);
    }

    public function getMontantsAEnvoyerParOperateur(): array
    {
        return $this->select('prefixes.id as prefixe_id, prefixes.description as operateur, prefixes.prefixe, COALESCE(SUM(transactions.commission), 0) as total_a_envoyer, COUNT(transactions.id) as nombre_operations')
            ->join('prefixes', 'prefixes.id = transactions.prefixe_id')
            ->where('transactions.sens', 'debit')
            ->groupBy('transactions.prefixe_id, prefixes.id, prefixes.description, prefixes.prefixe')
            ->orderBy('prefixes.prefixe', 'ASC')
            ->findAll();
    }
}
