<?php

namespace App\Models;

use CodeIgniter\Model;

class PrefixeModel extends Model
{
    protected $table            = 'prefixes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = ['prefixe', 'description', 'actif'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'prefixe' => 'required|is_unique[prefixes.prefixe,id,{id}]|max_length[10]',
        'actif'   => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'prefixe' => [
            'required'  => 'Le préfixe est obligatoire.',
            'is_unique' => 'Ce préfixe existe déjà.',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Liste des préfixes actifs.
     */
    public function getActifs(): array
    {
        return $this->where('actif', 1)
                    ->orderBy('prefixe', 'ASC')
                    ->findAll();
    }

    /**
     * Vérifie que le numéro commence par un préfixe actif.
     * Sert uniquement à valider le FORMAT, ne crée jamais de compte.
     */
    public function isPrefixeValide(string $numero): bool
    {
        foreach ($this->getActifs() as $p) {
            if (str_starts_with($numero, $p['prefixe'])) {
                return true;
            }
        }

        return false;
    }
}