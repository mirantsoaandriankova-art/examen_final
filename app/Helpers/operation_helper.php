<?php

use App\Models\BaremeFraisModel;
use App\Models\TypeOperationModel;

if (! function_exists('calculerFrais')) {
    /**
     * Calcule les frais applicables pour une opération donnée.
     *
     * @param string $typeOperationCode 'depot' | 'retrait' | 'transfert'
     * @param float  $montant
     * @return array ['frais' => float, 'total' => float]
     *               frais = 0 automatiquement si le type n'a pas de frais
     */
    function calculerFrais(string $typeOperationCode, float $montant): array
    {
        $typeModel = new TypeOperationModel();
        $type      = $typeModel->findByCode($typeOperationCode);

        // Type inconnu ou sans frais (frais_applicable = 0) -> pas de frais
        if ($type === null || (int) $type['frais_applicable'] === 0) {
            return [
                'frais' => 0.0,
                'total' => $montant,
            ];
        }

        $baremeModel = new BaremeFraisModel();
        $tranche     = $baremeModel->getTranche((int) $type['id'], $montant);

        $frais = (float) ($tranche['frais'] ?? 0.0);

        return [
            'frais' => $frais,
            'total' => $montant + $frais,
        ];
    }
}

if (! function_exists('calculerFraisTransfert')) {
    /**
     * Calcule un transfert interne ou externe, avec frais inclus ou ajoutés.
     * Les transferts externes n'appliquent pas le barème de retrait : seule la
     * commission configurée pour l'autre opérateur est retenue.
     *
     * @param array|null $autreOperateur Préfixe externe ou null pour notre opérateur
     * @return array{frais: float, commission: float, montant_debite: float, montant_recu: float}
     */
    function calculerFraisTransfert(float $montant, ?array $autreOperateur, bool $fraisInclus): array
    {
        $frais = $autreOperateur === null
            ? calculerFrais('transfert', $montant)['frais']
            : 0.0;
        $commission = $autreOperateur === null
            ? 0.0
            : round($montant * (float) $autreOperateur['commission_pourcentage'] / 100, 2);
        $coutTotal = $frais + $commission;

        return [
            'frais'           => $frais,
            'commission'      => $commission,
            'montant_debite'  => $fraisInclus ? $montant : $montant + $coutTotal,
            'montant_recu'    => $fraisInclus ? $montant - $coutTotal : $montant,
        ];
    }
}
