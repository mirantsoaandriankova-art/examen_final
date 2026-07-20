<?php

namespace App\Controllers;

use App\Models\CompteModel;
use App\Models\TransactionModel;
use App\Models\TypeOperationModel;

class ClientController extends BaseController
{
    protected CompteModel $compteModel;
    protected TransactionModel $transactionModel;
    protected TypeOperationModel $typeOperationModel;

    public function __construct()
    {
        $this->compteModel        = new CompteModel();
        $this->transactionModel   = new TransactionModel();
        $this->typeOperationModel = new TypeOperationModel();

        helper('operation'); // calculerFrais()
    }

    private function compteId(): int
    {
        return (int) session()->get('compte_id');
    }

    private function formatMontant(float $montant): string
    {
        return number_format($montant, 0, ',', ' ');
    }

    // -------------------------------------------------
    // DASHBOARD
    // -------------------------------------------------

    /**
     * GET /client/dashboard
     */
    public function dashboard()
    {
        $compteId = $this->compteId();
        $compte   = $this->compteModel->find($compteId);

        if (! $compte) {
            session()->destroy();
            return redirect()->to('/login');
        }

        session()->set('solde', $compte['solde']);

        return view('client/dashboard', [
            'title'        => 'Tableau de bord — MobiMoney',
            'compte'       => $compte,
            'transactions' => $this->transactionModel->getByCompte($compteId, 5),
        ]);
    }

    // -------------------------------------------------
    // DEPOT (sans frais)
    // -------------------------------------------------

    /**
     * GET /client/depot
     */
    public function depot()
    {
        return view('client/depot', [
            'title'  => 'Dépôt — MobiMoney',
            'compte' => $this->compteModel->find($this->compteId()),
        ]);
    }

    /**
     * POST /client/depot
     */
    public function doDepot()
    {
        $compteId = $this->compteId();
        $montant  = (float) $this->request->getPost('montant');

        if ($montant <= 0) {
            session()->setFlashdata('error', 'Le montant doit être supérieur à 0.');
            return redirect()->to('/client/depot');
        }

        $type = $this->typeOperationModel->findByCode('depot');

        if (! $type) {
            session()->setFlashdata('error', 'Type d\'opération introuvable.');
            return redirect()->to('/client/depot');
        }

        $this->compteModel->crediter($compteId, $montant);
        $soldeApres = $this->compteModel->getSolde($compteId);

        $this->transactionModel->enregistrer([
            'compte_id'         => $compteId,
            'type_operation_id' => $type['id'],
            'montant'           => $montant,
            'frais'             => 0,
            'solde_apres'       => $soldeApres,
            'sens'              => 'credit',
            'compte_lie_id'     => null,
        ]);

        session()->set('solde', $soldeApres);
        session()->setFlashdata('success', 'Dépôt de ' . $this->formatMontant($montant) . ' Ar effectué avec succès.');

        return redirect()->to('/client');
    }

    // -------------------------------------------------
    // RETRAIT (avec frais)
    // -------------------------------------------------

    /**
     * GET /client/retrait
     */
    public function retrait()
    {
        return view('client/retrait', [
            'title'  => 'Retrait — MobiMoney',
            'compte' => $this->compteModel->find($this->compteId()),
        ]);
    }

    /**
     * POST /client/retrait
     */
    public function doRetrait()
    {
        $compteId = $this->compteId();
        $montant  = (float) $this->request->getPost('montant');

        if ($montant <= 0) {
            session()->setFlashdata('error', 'Le montant doit être supérieur à 0.');
            return redirect()->to('/client/retrait');
        }

        $compte = $this->compteModel->find($compteId);
        $result = calculerFrais('retrait', $montant);
        $frais  = $result['frais'];
        $total  = $result['total'];

        if ($compte['solde'] < $total) {
            session()->setFlashdata(
                'error',
                'Solde insuffisant pour ce retrait (montant + frais = ' . $this->formatMontant($total) . ' Ar).'
            );
            return redirect()->to('/client/retrait');
        }

        $type = $this->typeOperationModel->findByCode('retrait');

        $this->compteModel->debiter($compteId, $total);
        $soldeApres = $this->compteModel->getSolde($compteId);

        $this->transactionModel->enregistrer([
            'compte_id'         => $compteId,
            'type_operation_id' => $type['id'],
            'montant'           => $montant,
            'frais'             => $frais,
            'solde_apres'       => $soldeApres,
            'sens'              => 'debit',
            'compte_lie_id'     => null,
        ]);

        session()->set('solde', $soldeApres);
        session()->setFlashdata(
            'success',
            'Retrait de ' . $this->formatMontant($montant) . ' Ar effectué (frais : ' . $this->formatMontant($frais) . ' Ar).'
        );

        return redirect()->to('/client');
    }

    // -------------------------------------------------
    // TRANSFERT (avec frais, prélevés côté émetteur)
    // -------------------------------------------------

    /**
     * GET /client/transfert
     */
    public function transfert()
    {
        return view('client/transfert', [
            'title'  => 'Transfert — MobiMoney',
            'compte' => $this->compteModel->find($this->compteId()),
        ]);
    }

    /**
     * POST /client/transfert
     */
    public function doTransfert()
    {
        $compteId      = $this->compteId();
        $telephoneDest = trim((string) $this->request->getPost('telephone_destinataire'));
        $montant       = (float) $this->request->getPost('montant');

        if ($montant <= 0) {
            session()->setFlashdata('error', 'Le montant doit être supérieur à 0.');
            return redirect()->to('/client/transfert');
        }

        $emetteur = $this->compteModel->find($compteId);

        if ($telephoneDest === $emetteur['telephone']) {
            session()->setFlashdata('error', 'Vous ne pouvez pas transférer vers votre propre numéro.');
            return redirect()->to('/client/transfert');
        }

        $destinataire = $this->compteModel->findByTelephone($telephoneDest);

        if (! $destinataire) {
            session()->setFlashdata('error', 'Numéro du destinataire introuvable.');
            return redirect()->to('/client/transfert');
        }

        $result = calculerFrais('transfert', $montant);
        $frais  = $result['frais'];
        $total  = $result['total'];

        if ($emetteur['solde'] < $total) {
            session()->setFlashdata(
                'error',
                'Solde insuffisant pour ce transfert (montant + frais = ' . $this->formatMontant($total) . ' Ar).'
            );
            return redirect()->to('/client/transfert');
        }

        $type = $this->typeOperationModel->findByCode('transfert');

        // Débit émetteur : montant + frais
        $this->compteModel->debiter($compteId, $total);
        $soldeEmetteurApres = $this->compteModel->getSolde($compteId);

        // Crédit destinataire : montant net, sans frais
        $this->compteModel->crediter($destinataire['id'], $montant);
        $soldeDestApres = $this->compteModel->getSolde($destinataire['id']);

        // Écriture débit (émetteur), liée au compte destinataire
        $this->transactionModel->enregistrer([
            'compte_id'         => $compteId,
            'type_operation_id' => $type['id'],
            'montant'           => $montant,
            'frais'             => $frais,
            'solde_apres'       => $soldeEmetteurApres,
            'sens'              => 'debit',
            'compte_lie_id'     => $destinataire['id'],
        ]);

        // Écriture crédit (destinataire), liée au compte émetteur
        $this->transactionModel->enregistrer([
            'compte_id'         => $destinataire['id'],
            'type_operation_id' => $type['id'],
            'montant'           => $montant,
            'frais'             => 0,
            'solde_apres'       => $soldeDestApres,
            'sens'              => 'credit',
            'compte_lie_id'     => $compteId,
        ]);

        session()->set('solde', $soldeEmetteurApres);
        session()->setFlashdata(
            'success',
            'Transfert de ' . $this->formatMontant($montant) . ' Ar vers ' . $destinataire['nom']
                . ' effectué (frais : ' . $this->formatMontant($frais) . ' Ar).'
        );

        return redirect()->to('/client');
    }

    // -------------------------------------------------
    // HISTORIQUE
    // -------------------------------------------------

    /**
     * GET /client/historique
     */
    public function historique()
    {
        $compteId = $this->compteId();

        return view('client/historique', [
            'title'        => 'Historique — MobiMoney',
            'compte'       => $this->compteModel->find($compteId),
            'transactions' => $this->transactionModel->getByCompte($compteId),
        ]);
    }
}
