<?php

namespace App\Controllers;

use App\Models\BaremeFraisModel;
use App\Models\CompteModel;
use App\Models\PrefixeModel;
use App\Models\PromotionModel;
use App\Models\TransactionModel;
use App\Models\TypeOperationModel;

class AdminController extends BaseController
{
    protected PrefixeModel $prefixeModel;
    protected TypeOperationModel $typeOperationModel;
    protected BaremeFraisModel $baremeFraisModel;
    protected CompteModel $compteModel;
    protected TransactionModel $transactionModel;

    public function __construct()
    {
        $this->prefixeModel       = new PrefixeModel();
        $this->typeOperationModel = new TypeOperationModel();
        $this->baremeFraisModel   = new BaremeFraisModel();
        $this->compteModel        = new CompteModel();
        $this->transactionModel   = new TransactionModel();
    }

    /**
     * GET /admin
     * Dashboard : gains par type d'opération + liste des comptes clients.
     */
    public function dashboard()
    {
        $data = [
            'gains'                => $this->transactionModel->getGainsParType(),
            'situationOperateurs'  => $this->transactionModel->getSituationOperateurs(),
            'gainsParOperateur'    => $this->transactionModel->getGainsParOperateur(),
            'montantsAEnvoyer'     => $this->transactionModel->getMontantsAEnvoyerParOperateur(),
            'transactions'         => $this->transactionModel->getAdminTransactions(),
            'transactionsPager'    => $this->transactionModel->pager,
            'comptes'              => $this->compteModel->getAllClients(),
        ];

        return view('admin/Dashboard', $data);
    }

    // ====================== PREFIXES ======================

    public function prefixes()
    {
        $data = [
            'prefixes' => $this->prefixeModel->orderBy('prefixe', 'ASC')->paginate(15, 'prefixes'),
            'pager' => $this->prefixeModel->pager,
        ];

        return view('admin/Prefixes', $data);
    }

    public function storePrefixe()
    {
        $data = [
            'prefixe' => $this->request->getPost('prefixe'),
            'description' => $this->request->getPost('description'),
            'actif' => $this->request->getPost('actif') ? 1 : 0,
            'est_operateur_principal' => $this->request->getPost('est_operateur_principal') ? 1 : 0,
            'commission_pourcentage'  => $this->request->getPost('commission_pourcentage') ?: 0,
        ];

        if (! $this->prefixeModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->prefixeModel->errors());
        }

        return redirect()->to('/admin/prefixes')->with('success', 'Préfixe ajouté avec succès.');
    }

    public function updatePrefixe($id)
    {
        $data = [
            'prefixe' => $this->request->getPost('prefixe'),
            'description' => $this->request->getPost('description'),
            'actif' => $this->request->getPost('actif') ? 1 : 0,
            'est_operateur_principal' => $this->request->getPost('est_operateur_principal') ? 1 : 0,
            'commission_pourcentage'  => $this->request->getPost('commission_pourcentage') ?: 0,
        ];

        if (! $this->prefixeModel->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $this->prefixeModel->errors());
        }

        return redirect()->to('/admin/prefixes')->with('success', 'Préfixe modifié avec succès.');
    }

    /**
     * POST /admin/prefixes/delete/{id}
     */
    public function deletePrefixe($id)
    {
        $this->prefixeModel->delete($id);

        return redirect()->to('/admin/prefixes')
            ->with('success', 'Préfixe supprimé.');
    }

    // =========================================================
    // BAREMES DE FRAIS
    // =========================================================

    /**
     * GET /admin/baremes
     * Optionnel : ?type=retrait pour filtrer l'affichage sur un type d'opération.
     */
    public function baremes()
    {
        $types = $this->typeOperationModel->getAll();

        $typeCode = $this->request->getGet('type');
        $baremesQuery = $this->baremeFraisModel
            ->select('baremes_frais.*, types_operation.code as type_code, types_operation.libelle as type_libelle')
            ->join('types_operation', 'types_operation.id = baremes_frais.type_operation_id');

        if ($typeCode) {
            $type = $this->typeOperationModel->findByCode($typeCode);
            if ($type) {
                $baremesQuery->where('baremes_frais.type_operation_id', (int) $type['id']);
            } else {
                $baremesQuery->where('baremes_frais.id', 0);
            }
        }

        $baremes = $baremesQuery
            ->orderBy('types_operation.code', 'ASC')
            ->orderBy('baremes_frais.montant_min', 'ASC')
            ->paginate(15, 'baremes');

        $data = [
            'types'        => $types,
            'baremes'      => $baremes,
            'typeSelected' => $typeCode,
            'pager'        => $this->baremeFraisModel->pager,
        ];

        return view('admin/Baremes', $data);
    }

    /**
     * POST /admin/baremes/store
     */
    public function storeBareme()
    {
        $data = [
            'type_operation_id' => $this->request->getPost('type_operation_id'),
            'montant_min'       => $this->request->getPost('montant_min'),
            'montant_max'       => $this->request->getPost('montant_max') ?: null,
            'frais'             => $this->request->getPost('frais'),
        ];

        if (! $this->baremeFraisModel->insert($data)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->baremeFraisModel->errors());
        }

        return redirect()->to('/admin/baremes')
            ->with('success', 'Tranche de frais ajoutée avec succès.');
    }

    /**
     * POST /admin/baremes/update/{id}
     */
    public function updateBareme($id)
    {
        $data = [
            'type_operation_id' => $this->request->getPost('type_operation_id'),
            'montant_min'       => $this->request->getPost('montant_min'),
            'montant_max'       => $this->request->getPost('montant_max') ?: null,
            'frais'             => $this->request->getPost('frais'),
        ];

        if (! $this->baremeFraisModel->update($id, $data)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->baremeFraisModel->errors());
        }

        return redirect()->to('/admin/baremes')
            ->with('success', 'Tranche de frais modifiée avec succès.');
    }

    /**
     * POST /admin/baremes/delete/{id}
     */
    public function deleteBareme($id)
    {
        $this->baremeFraisModel->delete($id);

        return redirect()->to('/admin/baremes')
            ->with('success', 'Tranche de frais supprimée.');
    }

    // =========================================================
    // COMPTES / TRANSACTIONS (lecture seule)
    // =========================================================

    /**
     * GET /admin/comptes
     */
    public function comptes()
    {
        $data = [
            'comptes' => $this->compteModel
                ->where('role', 'client')
                ->orderBy('solde', 'DESC')
                ->paginate(15, 'comptes'),
            'pager' => $this->compteModel->pager,
        ];

        return view('admin/Comptes', $data);
    }

    /**
     * GET /admin/transactions
     */
    public function transactions()
    {
        $data = [
            'transactions' => $this->transactionModel
                ->select('transactions.*, types_operation.libelle as type_libelle, types_operation.code as type_code, comptes.telephone as telephone, comptes.nom as nom_client')
                ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
                ->join('comptes', 'comptes.id = transactions.compte_id')
                ->orderBy('transactions.date_operation', 'DESC')
                ->orderBy('transactions.id', 'DESC')
                ->paginate(15, 'transactions'),
            'pager' => $this->transactionModel->pager,
        ];

        return view('admin/Transactions', $data);
    }

    public function promotion(){
        $promotionModel= new PromotionModel();
        return view ('admin/promotions', [
            'promotions' => $promotionModel->findAll()
        ]);
    }
}
