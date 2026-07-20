<?php

namespace App\Controllers;

use App\Models\CompteModel;

class AuthController extends BaseController
{
    protected CompteModel $compteModel;

    public function __construct()
    {
        $this->compteModel = new CompteModel();
    }

    /**
     * GET /login
     */
    public function showLogin()
    {
        return view('auth/login', ['title' => 'Connexion — MobiMoney']);
    }

    /**
     * POST /login
     */
    public function login()
    {
        $telephone = trim((string) $this->request->getPost('telephone'));

        if ($telephone === '') {
            session()->setFlashdata('error', 'Veuillez saisir un numéro de téléphone.');
            return redirect()->to('/login');
        }

        $compte = $this->compteModel->findByTelephone($telephone);

        if (! $compte) {
            session()->setFlashdata('error', 'Numéro non reconnu.');
            return redirect()->to('/login');
        }

        session()->regenerate();
        session()->set([
            'compte_id'  => $compte['id'],
            'telephone'  => $compte['telephone'],
            'nom'        => $compte['nom'],
            'solde'      => $compte['solde'],
            'role'       => $compte['role'],
            'isLoggedIn' => true,
        ]);

        return redirect()->to($compte['role'] === 'admin' ? '/admin' : '/client');
    }

    /**
     * GET /logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
