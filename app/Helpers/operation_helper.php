<?php

use App\Models\BaremeFraisModel;
use App\Models\TypeOperationModel;

if (! function_exists('calculerFrais')) {
    /**
     * Calcule les frais applicables pour une opération donnée.
     *
     * @return array{frais: float, total: float}
     */
    function calculerFrais(string $typeOperationCode, float $montant): array
    {
        $type = (new TypeOperationModel())->findByCode($typeOperationCode);

        if ($type === null || (int) $type['frais_applicable'] === 0) {
            return ['frais' => 0.0, 'total' => $montant];
        }

        $tranche = (new BaremeFraisModel())->getTranche((int) $type['id'], $montant);
        $frais = (float) ($tranche['frais'] ?? 0.0);

        return ['frais' => $frais, 'total' => $montant + $frais];
    }
}

if (! function_exists('calculerFraisTransfert')) {
    /**
     * Calcule un transfert interne ou externe.
     *
     * Un transfert vers un autre opérateur déduit du montant débité les frais de
     * transfert et la commission de cet opérateur. L'option de prise en charge
     * du retrait ne concerne que notre propre réseau.
     *
     * @return array{frais: float, commission: float, frais_retrait: float, montant_debite: float, montant_recu: float, montant_net_apres_retrait: float}
     */
    function calculerFraisTransfert(
        float $montant,
        ?array $autreOperateur = null,
        bool $fraisInclus = true,
        bool $inclureFraisRetrait = false
    ): array
    {
        if ($inclureFraisRetrait && $autreOperateur === null) {
            $montantAvantRetrait = calculerMontantAvantRetrait($montant);
            $fraisRetrait = $montantAvantRetrait - $montant;
            $fraisTransfert = calculerFrais('transfert', $montantAvantRetrait)['frais'];

            return [
                'frais' => round($fraisTransfert, 2),
                'commission' => 0.0,
                'frais_retrait' => round($fraisRetrait, 2),
                'montant_debite' => round($montantAvantRetrait + $fraisTransfert, 2),
                'montant_recu' => round($montantAvantRetrait, 2),
                'montant_net_apres_retrait' => round($montant, 2),
            ];
        }

        $frais = calculerFrais('transfert', $montant)['frais'];
        $commission = $autreOperateur === null
            ? 0.0
            : round($montant * (float) $autreOperateur['commission_pourcentage'] / 100, 2);
        $coutTotal = $frais + $commission;

        return [
            'frais' => round($frais, 2),
            'commission' => $commission,
            'frais_retrait' => 0.0,
            'montant_debite' => round($fraisInclus ? $montant : $montant + $coutTotal, 2),
            'montant_recu' => round($fraisInclus ? $montant - $coutTotal : $montant, 2),
            'montant_net_apres_retrait' => round($fraisInclus ? $montant - $coutTotal : $montant, 2),
        ];
    }
}

if (! function_exists('calculerMontantAvantRetrait')) {
    /**
     * Détermine le montant à créditer pour qu'après un retrait, le destinataire
     * conserve le montant demandé. Les tranches sont recalculées jusqu'à ce que
     * leur frais soit stable.
     */
    function calculerMontantAvantRetrait(float $montantNet): float
    {
        $montantBrut = $montantNet;

        for ($iteration = 0; $iteration < 20; $iteration++) {
            $fraisRetrait = calculerFrais('retrait', $montantBrut)['frais'];
            $nouveauMontantBrut = $montantNet + $fraisRetrait;

            if (round($nouveauMontantBrut, 2) === round($montantBrut, 2)) {
                return round($montantBrut, 2);
            }

            $montantBrut = $nouveauMontantBrut;
        }

        return round($montantBrut, 2);
    }
}
