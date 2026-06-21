<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\LogEntryLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController {
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(TokenStorageInterface $tokenStorage, LogEntryLogger $logEntryLogger): Response {
        $user = $this->getUser();

        if ($user instanceof User) {
            try {
                $logEntryLogger->log(
                    $user->getId(),
                    'logout',
                    'success',
                    [
                        'userId' => $user->getId(),
                        'username' => $user->getUsername(),
                        'source' => 'web',
                    ]
                );
            } catch (\Throwable) {
            }
        }

        if ($this->container->has('session')) {
            $session = $this->container->get('session');
            if ($session && method_exists($session, 'invalidate')) {
                $session->invalidate();
            }
        }

        $tokenStorage->setToken(null);

        return $this->redirectToRoute('app_login');
    }
}
