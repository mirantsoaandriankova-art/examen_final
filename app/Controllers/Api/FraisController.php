<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PrefixeModel;

class FraisController extends BaseController
{
    public function calculer()
    {
        $typeOperation = $this->request->getPost('type_operation');
        $montant       = (float) $this->request->getPost('montant');
        $telephoneDest = $this->request->getPost('telephone_dest'); // Nouveau V2
        $fraisInclus   = (bool) $this->request->getPost('frais_inclus', false); // Nouveau V2

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
        // Validation de base
        if ($montant <= 0) {
            return $this->response->setJSON([
                'error' => 'Le montant doit être supérieur à 0'
            ]);
        }

        // === Logique V2 pour transfert ===
        if ($typeOperation === 'transfert' && !empty($telephoneDest)) {
            
            $prefixeModel = new PrefixeModel();
            $autreOperateur = $prefixeModel->getAutreOperateur($telephoneDest);

            $result = calculerFraisTransfert($montant, $autreOperateur, $fraisInclus);

            return $this->response->setJSON($result);
        }

        // === Comportement V1 (dépôt, retrait, ou transfert sans numéro) ===
        $result = calculerFrais($typeOperation, $montant);

        return $this->response->setJSON([
            'frais' => $result['frais'],
            'total' => $result['total']
        ]);
    }
}
