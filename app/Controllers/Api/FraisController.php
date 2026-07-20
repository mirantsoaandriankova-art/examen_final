<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PrefixeModel;

class FraisController extends BaseController
{
    /**
     * Calcule les frais d'une opération depuis un formulaire ou un corps JSON.
     */
    public function calculer()
    {
        $json = $this->request->getJSON(true) ?? [];

        $typeOperation = $json['type_operation'] ?? $this->request->getPost('type_operation');
        $montant = $json['montant'] ?? $this->request->getPost('montant');
        $telephoneDest = $json['telephone_dest']
            ?? $json['telephone_destinataire']
            ?? $this->request->getPost('telephone_dest')
            ?? $this->request->getPost('telephone_destinataire');
        $fraisInclus = filter_var(
            $json['frais_inclus'] ?? $this->request->getPost('frais_inclus') ?? true,
            FILTER_VALIDATE_BOOLEAN
        );
        $inclureFraisRetrait = filter_var(
            $json['inclure_frais_retrait'] ?? $this->request->getPost('inclure_frais_retrait') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (! $this->validateData([
            'type_operation' => $typeOperation,
            'montant' => $montant,
        ], [
            'type_operation' => 'required|in_list[depot,retrait,transfert]',
            'montant' => 'required|decimal|greater_than[0]',
        ])) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['error' => $this->validator->getErrors()]);
        }

        $montant = (float) $montant;

        if ($typeOperation === 'transfert' && ! empty($telephoneDest)) {
            $autreOperateur = (new PrefixeModel())->getAutreOperateur((string) $telephoneDest);

            return $this->response->setJSON(calculerFraisTransfert(
                $montant,
                $autreOperateur,
                $fraisInclus,
                $inclureFraisRetrait
            ) + [
                'autre_operateur' => $autreOperateur !== null,
            ]);
        }

        $resultat = calculerFrais((string) $typeOperation, $montant);

        return $this->response->setJSON([
            'frais' => $resultat['frais'],
            'total' => $resultat['total'],
        ]);
    }
}
