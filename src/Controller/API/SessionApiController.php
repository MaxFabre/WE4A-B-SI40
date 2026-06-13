<?php

namespace App\Controller\API;

use App\Entity\Person;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/session', name: 'api.session')]
final class SessionApiController extends AbstractController {

    #[Route('/register', name: '.register', methods: ['POST'])]
    /**
     * Inscription d'un utilisateur depuis l'API.
     */
    public function register(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse {
        //Initialisation:
        $email = trim($request->request->get('email', ''));
        $username = trim($request->request->get('username', ''));
        $password = $request->request->get('password', '');
        $firstname = trim($request->request->get('firstname', ''));
        $lastname = trim($request->request->get('lastname', ''));
        $birthdateStr = $request->request->get('birthdate', '');

        /** @var UploadedFile|null $photo */
        $photo = $request->files->get('photo');

        if ($email === '' || $username === '' || $password === '' || $firstname === '' || $lastname === '') {
            return $this->json([
                'message' => 'Email, username, mot de passe, prénom et nom sont obligatoires.',
            ], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'message' => 'Email invalide.',
            ], 400);
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->json([
                'message' => 'Un compte existe déjà avec cet email.',
            ], 409);
        }

        $now = new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris"));

        $person = new Person();
        $person
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        if ($birthdateStr !== '') {
            $person->setBirthdate(new \DateTime($birthdateStr, new \DateTimeZone("Europe/Paris")));
        }

        if ($photo) {
            $person->setPhotoFile($photo);
        }

        $user = new User();
        $user
            ->setEmail($email)
            ->setUsername($username)
            ->setRoles(['ROLE_USER'])
            ->setPerson($person)
            ->setApiToken(hash('sha256', $email.$password));

        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $entityManager->persist($person);
        $entityManager->persist($user);
        $entityManager->flush();

        //Une fois le compte créer on connecte automatiquement l'utilisateur:
        return $this->json([
            'AUTH_TOKEN' => $user->getApiToken(),
        ]);
    }

    #[Route('/login', name: '.login', methods: ['POST'])]
    /**
     * Connexion d'un utilisateur depuis l'API.
     */
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
