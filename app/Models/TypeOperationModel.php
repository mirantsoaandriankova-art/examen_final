<?php

namespace App\Models;

use CodeIgniter\Model;

class TypeOperationModel extends Model
{
    protected $table            = 'types_operation';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = ['code', 'libelle', 'frais_applicable'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'code'             => 'required|is_unique[types_operation.code,id,{id}]|in_list[depot,retrait,transfert]',
        'libelle'          => 'required|max_length[100]',
        'frais_applicable' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'code' => [
            'required'  => 'Le code du type d\'opération est obligatoire.',
            'is_unique' => 'Ce type d\'opération existe déjà.',
            'in_list'   => 'Le code doit être depot, retrait ou transfert.',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Récupère un type d'opération par son code (ex: 'depot', 'retrait', 'transfert').
     * Utilisé par le helper calculerFrais().
     */
    public function findByCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }

    /**
     * Liste tous les types d'opérations.
     */
    public function getAll(): array
    {
        return $this->findAll();
    }
}