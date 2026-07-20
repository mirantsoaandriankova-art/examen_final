<?php

namespace App\Models;

use CodeIgniter\Model;

class PrefixeModel extends Model
{
    protected $table = 'prefixes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'prefixe',
        'description',
        'actif',
        'est_operateur_principal',
        'commission_pourcentage',
    ];

    protected $validationRules = [
        'prefixe' => 'required|is_unique[prefixes.prefixe,id,{id}]|max_length[10]',
        'actif' => 'permit_empty|in_list[0,1]',
        'est_operateur_principal' => 'permit_empty|in_list[0,1]',
        'commission_pourcentage' => 'permit_empty|decimal|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'prefixe' => [
            'required' => 'Le préfixe est obligatoire.',
            'is_unique' => 'Ce préfixe existe déjà.',
        ],
    ];

    protected $skipValidation = false;

    public function getActifs(): array
    {
        return $this->where('actif', 1)->orderBy('prefixe', 'ASC')->findAll();
    }

    public function getAutresOperateurs(): array
    {
        return $this->where('actif', 1)
            ->where('est_operateur_principal', 0)
            ->orderBy('LENGTH(prefixe)', 'DESC', false)
            ->findAll();
    }

    /**
     * Vérifie que le numéro commence par un préfixe actif.
     */
    public function isPrefixeValide(string $numero): bool
    {
        foreach ($this->getActifs() as $prefixe) {
            if (str_starts_with($numero, $prefixe['prefixe'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne le préfixe externe actif correspondant au numéro.
     */
    public function getAutreOperateur(string $numero): ?array
    {
        foreach ($this->getAutresOperateurs() as $prefixe) {
            if (str_starts_with($numero, $prefixe['prefixe'])) {
                return $prefixe;
            }
        }

        return null;
    }
}
