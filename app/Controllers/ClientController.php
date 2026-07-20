<?php

namespace App\Controllers;

use App\Models\CompteModel;
use App\Models\PrefixeModel;
use App\Models\TransactionModel;
use App\Models\TypeOperationModel;
use RuntimeException;

class ClientController extends BaseController
{
    protected CompteModel $compteModel;
    protected TransactionModel $transactionModel;
    protected TypeOperationModel $typeOperationModel;
    protected PrefixeModel $prefixeModel;

    public function __construct()
    {
        $this->compteModel        = new CompteModel();
        $this->transactionModel   = new TransactionModel();
        $this->typeOperationModel = new TypeOperationModel();
        $this->prefixeModel       = new PrefixeModel();

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
    // DEPOT (avec frais)
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

        $result     = calculerFrais('depot', $montant);
        $frais      = $result['frais'];
        $montantNet = $montant - $frais;

        if ($montantNet <= 0) {
            session()->setFlashdata('error', 'Le montant doit être supérieur aux frais de dépôt.');
            return redirect()->to('/client/depot');
        }

        $type = $this->typeOperationModel->findByCode('depot');

        if (! $type) {
            session()->setFlashdata('error', 'Type d\'opération introuvable.');
            return redirect()->to('/client/depot');
        }

        $this->compteModel->crediter($compteId, $montantNet);
        $soldeApres = $this->compteModel->getSolde($compteId);

        $this->transactionModel->enregistrer([
            'compte_id'         => $compteId,
            'type_operation_id' => $type['id'],
            'montant'           => $montant,
            'frais'             => $frais,
            'solde_apres'       => $soldeApres,
            'sens'              => 'credit',
            'compte_lie_id'     => null,
        ]);

        session()->set('solde', $soldeApres);
        session()->setFlashdata(
            'success',
            'Dépôt de ' . $this->formatMontant($montant) . ' Ar effectué. Montant crédité : '
                . $this->formatMontant($montantNet) . ' Ar (frais : '
                . $this->formatMontant($frais) . ' Ar).'
        );

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
    // TRANSFERT (frais ajoutés ou inclus)
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
        $fraisInclus   = true;
        $inclureFraisRetrait = (int) $this->request->getPost('inclure_frais_retrait') === 1;

        if ($montant <= 0) {
            session()->setFlashdata('error', 'Le montant doit être supérieur à 0.');
            return redirect()->to('/client/transfert');
        }

        $emetteur = $this->compteModel->find($compteId);

        if ($telephoneDest === $emetteur['telephone']) {
            session()->setFlashdata('error', 'Vous ne pouvez pas transférer vers votre propre numéro.');
            return redirect()->to('/client/transfert');
        }

        if (! $this->prefixeModel->isPrefixeValide($telephoneDest)) {
            session()->setFlashdata('error', 'Le préfixe du numéro destinataire n’est pas pris en charge.');
            return redirect()->to('/client/transfert');
        }

        $autreOperateur = $this->prefixeModel->getAutreOperateur($telephoneDest);
        $destinataire = $autreOperateur === null
            ? $this->compteModel->findByTelephone($telephoneDest)
            : null;

        if ($autreOperateur === null && ! $destinataire) {
            session()->setFlashdata('error', 'Le numéro de notre réseau est introuvable.');
            return redirect()->to('/client/transfert');
        }

        $result         = calculerFraisTransfert($montant, $autreOperateur, $fraisInclus, $inclureFraisRetrait);

        if ($result['montant_recu'] <= 0) {
            session()->setFlashdata('error', 'Le montant doit être supérieur aux frais et à la commission.');
            return redirect()->to('/client/transfert');
        }

        if ($emetteur['solde'] < $result['montant_debite']) {
            session()->setFlashdata(
                'error',
                'Solde insuffisant pour ce transfert (montant débité = '
                    . $this->formatMontant($result['montant_debite']) . ' Ar).'
            );
            return redirect()->to('/client/transfert');
        }

        try {
            $this->executerTransferts([[
                'emetteur'        => $emetteur,
                'destinataire'    => $destinataire,
                'montant'         => $montant,
                'calcul'          => $result,
                'autre_operateur' => $autreOperateur,
                'frais_inclus'    => $fraisInclus,
            ]]);
        } catch (RuntimeException $exception) {
            session()->setFlashdata('error', $exception->getMessage());
            return redirect()->to('/client/transfert');
        }

        $soldeApres = $this->compteModel->getSolde($compteId);
        session()->set('solde', $soldeApres);
        session()->setFlashdata(
            'success',
            'Transfert de ' . $this->formatMontant($montant) . ' Ar vers ' . ($destinataire['nom'] ?? $telephoneDest)
                . ' effectué. Montant reçu : ' . $this->formatMontant($result['montant_recu'])
                . ' Ar (frais : ' . $this->formatMontant($result['frais'])
                . ' Ar, commission : ' . $this->formatMontant($result['commission']) . ' Ar).'
        );

        return redirect()->to('/client');
    }

    /**
     * GET /client/envoi-multiple
     */
    public function envoiMultiple()
    {
        return view('client/envoi_multiple', [
            'title'  => 'Envoi multiple — MobiMoney',
            'compte' => $this->compteModel->find($this->compteId()),
        ]);
    }

    /**
     * POST /client/envoi-multiple
     */
    public function doEnvoiMultiple()
    {
        $compteId     = $this->compteId();
        $montantTotal = (float) $this->request->getPost('montant_total');
        $telephonesBruts = array_values(array_filter(array_map(
            static fn ($telephone) => trim((string) $telephone),
            (array) $this->request->getPost('telephones')
        )));
        $telephones = array_values(array_unique($telephonesBruts));

        if ($montantTotal <= 0 || count($telephones) < 2 || count($telephones) !== count($telephonesBruts)) {
            session()->setFlashdata('error', 'Saisissez un montant positif et au moins deux destinataires différents.');
            return redirect()->to('/client/envoi-multiple');
        }

        $emetteur = $this->compteModel->find($compteId);
        $part     = floor($montantTotal / count($telephones));
        $reliquat = $montantTotal - ($part * count($telephones));
        $transferts = [];
        $totalDebite = 0.0;

        foreach ($telephones as $index => $telephoneDest) {
            if ($telephoneDest === $emetteur['telephone'] || ! $this->prefixeModel->isPrefixeValide($telephoneDest)) {
                session()->setFlashdata('error', 'Un numéro destinataire est invalide ou correspond à votre propre compte.');
                return redirect()->to('/client/envoi-multiple');
            }

            $autreOperateur = $this->prefixeModel->getAutreOperateur($telephoneDest);
            $destinataire = $autreOperateur === null
                ? $this->compteModel->findByTelephone($telephoneDest)
                : null;

            if ($autreOperateur === null && ! $destinataire) {
                session()->setFlashdata('error', 'Le numéro de notre réseau ' . $telephoneDest . ' est introuvable.');
                return redirect()->to('/client/envoi-multiple');
            }

            $montantPart = $part + ($index === count($telephones) - 1 ? $reliquat : 0);
            $calcul = calculerFraisTransfert($montantPart, $autreOperateur, true);
            $totalDebite += $calcul['montant_debite'];
            $transferts[] = [
                'emetteur'        => $emetteur,
                'destinataire'    => $destinataire,
                'montant'         => $montantPart,
                'calcul'          => $calcul,
                'autre_operateur' => $autreOperateur,
                'frais_inclus'    => true,
            ];
        }

        if ($emetteur['solde'] < $totalDebite) {
            session()->setFlashdata('error', 'Solde insuffisant : le total à débiter est de ' . $this->formatMontant($totalDebite) . ' Ar.');
            return redirect()->to('/client/envoi-multiple');
        }

        try {
            $this->executerTransferts($transferts, bin2hex(random_bytes(8)));
        } catch (RuntimeException $exception) {
            session()->setFlashdata('error', $exception->getMessage());
            return redirect()->to('/client/envoi-multiple');
        }

        session()->set('solde', $this->compteModel->getSolde($compteId));
        session()->setFlashdata('success', 'Envoi multiple effectué vers ' . count($telephones) . ' destinataires.');

        return redirect()->to('/client');
    }

    /**
     * Applique un ou plusieurs transferts dans une unique transaction SQL.
     *
     * @param array<int, array<string, mixed>> $transferts
     */
    private function executerTransferts(array $transferts, ?string $groupeEnvoiId = null): void
    {
        $type = $this->typeOperationModel->findByCode('transfert');
        if (! $type) {
            throw new RuntimeException('Type d’opération transfert introuvable.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            foreach ($transferts as $transfert) {
                $emetteur = $transfert['emetteur'];
                $destinataire = $transfert['destinataire'];
                $calcul = $transfert['calcul'];
                $autreOperateur = $transfert['autre_operateur'];

                if (! $this->compteModel->debiter((int) $emetteur['id'], (float) $calcul['montant_debite'])) {
                    throw new RuntimeException('Impossible d’enregistrer le transfert.');
                }

                if ($destinataire !== null
                    && ! $this->compteModel->crediter((int) $destinataire['id'], (float) $calcul['montant_recu'])) {
                    throw new RuntimeException('Impossible de créditer le destinataire.');
                }

                $soldeEmetteur = $this->compteModel->getSolde((int) $emetteur['id']);
                $soldeDestinataire = $destinataire === null
                    ? null
                    : $this->compteModel->getSolde((int) $destinataire['id']);

                $debitEnregistre = $this->transactionModel->enregistrer([
                    'compte_id'         => $emetteur['id'],
                    'type_operation_id' => $type['id'],
                    'montant'           => $transfert['montant'],
                    'frais'             => $calcul['frais'],
                    'commission'        => $calcul['commission'],
                    'frais_inclus'      => $transfert['frais_inclus'] ? 1 : 0,
                    'prefixe_id'        => $autreOperateur['id'] ?? null,
                    'groupe_envoi_id'   => $groupeEnvoiId,
                    'solde_apres'       => $soldeEmetteur,
                    'sens'              => 'debit',
                    'compte_lie_id'     => $destinataire['id'] ?? null,
                ]);
                $creditEnregistre = true;

                if ($destinataire !== null) {
                    $creditEnregistre = $this->transactionModel->enregistrer([
                        'compte_id'         => $destinataire['id'],
                        'type_operation_id' => $type['id'],
                        'montant'           => $calcul['montant_recu'],
                        'frais'             => 0,
                        'commission'        => 0,
                        'frais_inclus'      => $transfert['frais_inclus'] ? 1 : 0,
                        'groupe_envoi_id'   => $groupeEnvoiId,
                        'solde_apres'       => $soldeDestinataire,
                        'sens'              => 'credit',
                        'compte_lie_id'     => $emetteur['id'],
                    ]);
                }

                if (! $debitEnregistre || ! $creditEnregistre) {
                    throw new RuntimeException('Impossible d’enregistrer l’historique du transfert.');
                }
            }

            if (! $db->transStatus()) {
                throw new RuntimeException('Le transfert n’a pas pu être finalisé.');
            }

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();
            throw new RuntimeException($exception->getMessage());
        }
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
