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
