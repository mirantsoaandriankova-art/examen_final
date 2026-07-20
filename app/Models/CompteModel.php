<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

class CompteModel extends Model
{
    protected $table            = 'comptes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = ['telephone', 'nom', 'solde', 'role'];

    // Table comptes n'a que date_creation (pas de updated_at) -> géré par SQLite (DEFAULT)
    protected $useTimestamps = false;

    protected $validationRules = [
        'telephone' => 'required|is_unique[comptes.telephone,id,{id}]|max_length[20]',
        'nom'       => 'permit_empty|max_length[100]',
        'solde'     => 'permit_empty|decimal',
        'role'      => 'permit_empty|in_list[client,admin]',
    ];

    protected $validationMessages = [
        'telephone' => [
            'required'  => 'Le numéro de téléphone est obligatoire.',
            'is_unique' => 'Un compte existe déjà pour ce numéro.',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Retrouve un compte par numéro de téléphone (contrat commun).
     * Utilisé pour le login : PAS de création à la volée si non trouvé.
     */
    public function findByTelephone(string $telephone): ?array
    {
        return $this->where('telephone', $telephone)->first();
    }

    /**
     * Crédite le solde d'un compte (dépôt, réception de transfert).
     */
    public function crediter(int $compteId, float $montant): bool
    {
        $compte = $this->find($compteId);

        if ($compte === null) {
            throw new RuntimeException("Compte introuvable (id={$compteId}).");
        }

        return $this->update($compteId, ['solde' => $compte['solde'] + $montant]);
    }

    /**
     * Débite le solde d'un compte (retrait, envoi de transfert).
     * Lève une exception si le solde est insuffisant.
     */
    public function debiter(int $compteId, float $montant): bool
    {
        $compte = $this->find($compteId);

        if ($compte === null) {
            throw new RuntimeException("Compte introuvable (id={$compteId}).");
        }

        if ($compte['solde'] < $montant) {
            throw new RuntimeException('Solde insuffisant.');
        }

        return $this->update($compteId, ['solde' => $compte['solde'] - $montant]);
    }

    /**
     * Retourne le solde actuel d'un compte.
     */
    public function getSolde(int $compteId): float
    {
        $compte = $this->find($compteId);
        return (float) ($compte['solde'] ?? 0);
    }

    /**
     * Liste tous les comptes clients (role = 'client'), pour le dashboard admin et la page comptes.
     */
    public function getAllClients(): array
    {
        return $this->where('role', 'client')
                    ->orderBy('solde', 'DESC')
                    ->findAll();
    }
}