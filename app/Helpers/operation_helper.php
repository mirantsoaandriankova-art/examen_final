<?php

use App\Models\BaremeFraisModel;
use App\Models\TypeOperationModel;

if (! function_exists('calculerFrais')) {
    /**
     * Calcule les frais pour dépôt, retrait (comportement V1).
     */
    function calculerFrais(string $typeOperationCode, float $montant): array
    {
        $typeModel = new TypeOperationModel();
        $type      = $typeModel->findByCode($typeOperationCode);

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
     * Nouvelle fonction V2 pour les transferts (interne ou vers autre opérateur)
     * 
     * @param float       $montant
     * @param array|null  $autreOperateur  Résultat de PrefixeModel::getAutreOperateur()
     * @param bool        $fraisInclus     false = "frais en plus" (V1), true = "frais inclus"
     * @return array
     */
    function calculerFraisTransfert(float $montant, ?array $autreOperateur = null, bool $fraisInclus = false): array
    {
        // 1. Récupérer le type "transfert"
        $typeModel = new TypeOperationModel();
        $type = $typeModel->findByCode('transfert');

        // 2. Calcul du frais de base via barème
        $baremeModel = new BaremeFraisModel();
        $tranche = $baremeModel->getTranche((int) $type['id'], $montant);
        $frais = (float) ($tranche['frais'] ?? 0.0);

        // 3. Commission (uniquement si transfert vers autre opérateur)
        $commission = 0.0;
        if ($autreOperateur !== null && isset($autreOperateur['commission_pourcentage'])) {
            $commission = $montant * ($autreOperateur['commission_pourcentage'] / 100);
        }

        // 4. Calcul selon le mode frais_inclus
        if ($fraisInclus === false) {
            // Comportement V1 : frais + commission en plus
            $montant_debite = $montant + $frais + $commission;
            $montant_recu   = $montant;
        } else {
            // Nouveau mode : frais et commission déduits du montant saisi
            $montant_debite = $montant;
            $montant_recu   = $montant - $frais - $commission;
        }

        return [
            'frais'           => round($frais, 2),
            'commission'      => round($commission, 2),
            'montant_debite'  => round($montant_debite, 2),
            'montant_recu'    => round($montant_recu, 2),
        ];
    }
}