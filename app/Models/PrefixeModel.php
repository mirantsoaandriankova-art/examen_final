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

    protected $allowedFields = [
        'prefixe',
        'description',
        'actif',
        'est_operateur_principal', // V2 : 1 = notre opérateur, 0 = autre opérateur
        'commission_pourcentage',  // V2 : uniquement pertinent si est_operateur_principal = 0
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'prefixe'                 => 'required|is_unique[prefixes.prefixe,id,{id}]|max_length[10]',
        'actif'                   => 'permit_empty|in_list[0,1]',
        'est_operateur_principal' => 'permit_empty|in_list[0,1]',
        'commission_pourcentage'  => 'permit_empty|decimal|greater_than_equal_to[0]',
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
    /**
     * Liste des préfixes "autres opérateurs" actifs (est_operateur_principal = 0).
     */
    public function getAutresOperateurs(): array
    {
        return $this->where('actif', 1)
                    ->where('est_operateur_principal', 0)
                    ->orderBy('prefixe', 'ASC')
                    ->findAll();
    }

    /**
     * Retrouve le préfixe "autre opérateur" correspondant à un numéro de téléphone
     * (contrat commun V2). Utilisé par calculerFraisTransfert() pour savoir si une
     * commission externe doit s'appliquer.
     *
     * @return array|null Le préfixe (avec sa commission_pourcentage), ou null si le
     *                     numéro appartient à notre réseau (ou à aucun préfixe connu).
     */
    public function getAutreOperateur(string $numero): ?array
    {
        foreach ($this->getAutresOperateurs() as $p) {
            if (str_starts_with($numero, $p['prefixe'])) {
                return $p;
            }
        }

        return null;
    }
}