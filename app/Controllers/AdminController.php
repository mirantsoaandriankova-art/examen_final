<?php

namespace App\Controllers;

use App\Models\BaremeFraisModel;
use App\Models\CompteModel;
use App\Models\PrefixeModel;
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
            'gains'   => $this->transactionModel->getGainsParType(),
            'comptes' => $this->compteModel->getAllClients(),
        ];

        return view('admin/dashboard', $data);
    }

    // =========================================================
    // PREFIXES
    // =========================================================

    /**
     * GET /admin/prefixes
     */
    public function prefixes()
    {
        $data = [
            'prefixes' => $this->prefixeModel->orderBy('prefixe', 'ASC')->findAll(),
        ];

        return view('admin/prefixes', $data);
    }

    /**
     * POST /admin/prefixes/store
     */
    public function storePrefixe()
    {
        $data = [
            'prefixe'     => $this->request->getPost('prefixe'),
            'description' => $this->request->getPost('description'),
            'actif'       => $this->request->getPost('actif') ? 1 : 0,
        ];

        if (! $this->prefixeModel->insert($data)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->prefixeModel->errors());
        }

        return redirect()->to('/admin/prefixes')
            ->with('success', 'Préfixe ajouté avec succès.');
    }

    /**
     * POST /admin/prefixes/update/{id}
     */
    public function updatePrefixe($id)
    {
        $data = [
            'prefixe'     => $this->request->getPost('prefixe'),
            'description' => $this->request->getPost('description'),
            'actif'       => $this->request->getPost('actif') ? 1 : 0,
        ];

        if (! $this->prefixeModel->update($id, $data)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->prefixeModel->errors());
        }

        return redirect()->to('/admin/prefixes')
            ->with('success', 'Préfixe modifié avec succès.');
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
        $baremes  = [];

        if ($typeCode) {
            $type = $this->typeOperationModel->findByCode($typeCode);
            if ($type) {
                $baremes = $this->baremeFraisModel->getAllByType((int) $type['id']);
            }
        } else {
            // Sans filtre : tout afficher, groupé par type
            $baremes = $this->baremeFraisModel
                ->select('baremes_frais.*, types_operation.code as type_code, types_operation.libelle as type_libelle')
                ->join('types_operation', 'types_operation.id = baremes_frais.type_operation_id')
                ->orderBy('types_operation.code', 'ASC')
                ->orderBy('baremes_frais.montant_min', 'ASC')
                ->findAll();
        }

        $data = [
            'types'        => $types,
            'baremes'      => $baremes,
            'typeSelected' => $typeCode,
        ];

        return view('admin/baremes', $data);
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
            'comptes' => $this->compteModel->getAllClients(),
        ];

        return view('admin/comptes', $data);
    }

    /**
     * GET /admin/transactions
     */
    public function transactions()
    {
        $data = [
            'transactions' => $this->transactionModel->getAll(),
        ];

        return view('admin/transactions', $data);
    }
}