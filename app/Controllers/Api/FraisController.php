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