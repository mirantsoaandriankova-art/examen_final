<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Vérifie que l'utilisateur est connecté et possède le bon rôle.
     * $arguments[0] attendu : 'client' ou 'admin'
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $role    = $session->get('role');

        // Pas connecté du tout
        if (! $session->get('isLoggedIn') || $role === null) {
            $session->setFlashdata('error', 'Veuillez vous connecter.');
            return redirect()->to('/login');
        }

        // Un rôle précis est exigé pour ce groupe de routes
        if (is_array($arguments) && count($arguments) > 0) {
            $roleRequis = $arguments[0];

            if ($role !== $roleRequis) {
                $session->setFlashdata('error', 'Accès non autorisé.');
                return redirect()->to('/login');
            }
        }

        // Sinon, on laisse passer
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Rien à faire après la requête
    }
}