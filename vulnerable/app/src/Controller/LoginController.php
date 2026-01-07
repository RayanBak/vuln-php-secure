<?php

namespace App\Controller;

use App\Repository\UserRepository;  // Assurez-vous que le bon repository est utilisé
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    private $userRepository;

    // Injecter le repository UserRepository
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // Message d'erreur unifié pour prévenir l'énumération d'utilisateurs
        // Ne jamais révéler si l'email existe ou si c'est le mot de passe qui est incorrect
        $errorMessage = '';
        
        if ($error) {
            // Message générique pour tous les cas d'échec d'authentification
            $errorMessage = 'Identifiants invalides';
        }

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $errorMessage,
        ]);
    }
}
