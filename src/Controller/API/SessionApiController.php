<?php

namespace App\Controller\API;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/session', name: 'api.session')]
final class SessionApiController extends AbstractController {
    #[Route('/login', name: '.login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'JSON invalide.',
            ], 400);
        }

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            return $this->json([
                'message' => 'Email et mot de passe obligatoires.',
            ], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json([
                'message' => 'Identifiants invalides.',
            ], 401);
        }

        return $this->json([
            'AUTH_TOKEN' => $user->getApiToken(),
        ]);
    }

    #[Route('/me', name: '.me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse {
        return $this->json($this->getUser(), 200, [], ['groups' => ['user.details']]);
    }
}
