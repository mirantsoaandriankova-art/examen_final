<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PrefixeModel;

class FraisController extends BaseController
{
    /**
     * POST api/calculer-frais
     * Reçoit : type_operation (depot|retrait|transfert), montant
     * Répond en JSON : { frais: float, total: float }
     *
     * Utilisé par previewFrais() côté JS pour afficher un aperçu
     * des frais avant confirmation (retrait / transfert).
     */
    public function calculer()
    {
        // Supporte à la fois un POST classique (form) et un body JSON (fetch)
        $json = $this->request->getJSON(true);

        $typeOperation = $json['type_operation'] ?? $this->request->getPost('type_operation');
        $montant       = $json['montant'] ?? $this->request->getPost('montant');
        $telephoneDest  = $json['telephone_destinataire'] ?? $this->request->getPost('telephone_destinataire');
        $fraisInclus    = $json['frais_inclus'] ?? $this->request->getPost('frais_inclus') ?? 0;

        $rules = [
            'type_operation' => 'required|in_list[depot,retrait,transfert]',
            'montant'        => 'required|decimal|greater_than[0]',
        ];

        if (! $this->validateData(
            ['type_operation' => $typeOperation, 'montant' => $montant],
            $rules
        )) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['error' => $this->validator->getErrors()]);
        }

        if ($typeOperation === 'transfert') {
            $autreOperateur = $telephoneDest
                ? (new PrefixeModel())->getAutreOperateur((string) $telephoneDest)
                : null;
            $resultat = calculerFraisTransfert((float) $montant, $autreOperateur, (bool) $fraisInclus);

            return $this->response->setJSON($resultat + [
                'autre_operateur' => $autreOperateur !== null,
            ]);
        }

        $resultat = calculerFrais((string) $typeOperation, (float) $montant);

        return $this->response->setJSON([
            'frais' => $resultat['frais'],
            'total' => $resultat['total'],
        ]);
    }
}
