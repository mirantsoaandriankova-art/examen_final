<?php

namespace App\Models;

use CodeIgniter\Model;

class BaremeFraisModel extends Model
{
    protected $table            = 'baremes_frais';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'type_operation_id',
        'montant_min',
        'montant_max',
        'frais',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'type_operation_id' => 'required|integer',
        'montant_min'       => 'required|decimal|greater_than_equal_to[0]',
        'montant_max'       => 'permit_empty|decimal|greater_than[montant_min]',
        'frais'             => 'required|decimal|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'montant_max' => [
            'greater_than' => 'Le montant maximum doit être supérieur au montant minimum.',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Retourne la ligne de barème (tranche) correspondant au montant saisi,
     * pour un type d'opération donné.
     */
    public function getTranche(int $typeOperationId, float $montant): ?array
    {
        return $this->where('type_operation_id', $typeOperationId)
                    ->where('montant_min <=', $montant)
                    ->groupStart()
                        ->where('montant_max >=', $montant)
                        ->orWhere('montant_max', null)
                    ->groupEnd()
                    ->orderBy('montant_min', 'DESC')
                    ->first();
    }

    /**
     * Liste toutes les tranches d'un type d'opération, triées (pour CRUD/affichage).
     */
    public function getAllByType(int $typeOperationId): array
    {
        return $this->where('type_operation_id', $typeOperationId)
                    ->orderBy('montant_min', 'ASC')
                    ->findAll();
    }
}