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
            ], RESPONSE::HTTP_BAD_REQUEST);
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
            ], RESPONSE::HTTP_BAD_REQUEST);
        }

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            return $this->json([
                'message' => 'Email et mot de passe obligatoires.',
            ], RESPONSE::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json([
                'message' => 'Identifiants invalides.',
            ], RESPONSE::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'AUTH_TOKEN' => $user->getApiToken(),
        ]);
    }

    #[Route('/me', name: '.me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    /**
     * Récupère les informations de l'utilisateur actuellement connecté.
     */
    public function me(): JsonResponse {
        return $this->json($this->getUser(), 200, [], ['groups' => ['user.details']]);
    }

    #[Route('/user/create', name: '.create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    /**
     * Créer un utilisateur, via le menu admin.
     */
    public function create(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, UserRepository $userRepository): JsonResponse {
        //Initialisation:
        $firstname = $request->request->get('firstname');
        $lastname = $request->request->get('lastname');
        $username = $request->request->get('username');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $birthdateStr = $request->request->get('birthdate');
        $roles = $request->request->all()['roles'] ?? [];
        $parisTimeZone = new \DateTimeZone("Europe/Paris");
        $now = new \DateTimeImmutable("now", $parisTimeZone);

        //Vérifications des champs vides:
        if (empty($firstname) || empty($lastname) || empty($username) || empty($password) || empty($email)) {
            return $this->json([
                'message' => 'Veuillez remplir tous les champs obligatoires.',
            ], Response::HTTP_BAD_REQUEST);
        }

        //Vérification de la disponibilité de l'email:
        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->json([
                'message' => 'Cet email est déjà utilisé.'
            ], Response::HTTP_BAD_REQUEST);
        }

        //Person:
        $person = new Person();
        $person->setFirstname($firstname);
        $person->setLastname($lastname);
        $person->setUpdatedAt($now);
        $person->setCreatedAt($now);

        if (!empty($birthdateStr)) {
            $person->setBirthdate(new \DateTime($birthdateStr, $parisTimeZone));
        }

        //User:
        $user = new User();
        $user->setPerson($person);
        $user
            ->setUsername($username)
            ->setEmail($email)
            ->setPassword($userPasswordHasher->hashPassword($user, $password))
            ->setRoles($roles)
            ->setApiToken(hash('sha256', $email.$password));

        //Gestion de l'image de profile par VichUploader:
        $photoFile = $request->files->get('photo');
        if ($photoFile) {
            $person->setPhotoFile($photoFile);
        }

        try {
            $entityManager->persist($user);
            $entityManager->flush();
            return new JsonResponse([
                'message' => 'Utilisateur créé avec succès !'
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->json([
                'message' => 'Erreur lors de la création de l’utilisateur.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/users/', name: '.users', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    /**
     * Récupère tous les utilisateurs.
     */
    public function fetchAll(UserRepository $userRepository): JsonResponse {
        $users = $userRepository->findAll();
        return $this->json($users, 200, [], ['groups' => ['user.list']]);
    }

    #[Route('/user/{id}', name: '.user', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    /**
     * Récupère les informations d'un utilisateur.
     */
    public function fetchOne(User $user): JsonResponse {
        return $this->json($user, 200, [], ['groups' => ['user.details']]);
    }

    #[Route('/user/{id}', name: '.update', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    /**
     * Met à jour un utilisateur, via le menu admin.
     */
    public function update(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, UserRepository $userRepository): JsonResponse {
        //Initialisation:
        $person = $user->getPerson();
        $parisTimeZone = new \DateTimeZone("Europe/Paris");
        $now = new \DateTimeImmutable("now", $parisTimeZone);

        //Vérification de Person:
        if (!$person) {
            return $this->json([
                'message' => 'Entité Personne associée introuvable.'
            ], Response::HTTP_NOT_FOUND);
        }

        //Mise à jour de l'email avec vérification:
        if ($request->request->has('email')) {
            $email = $request->request->get('email');
            if ($email !== $user->getEmail()) {
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser) {
                    return $this->json([
                        'message' => 'Cet email est déjà utilisé par un autre utilisateur.'
                    ], Response::HTTP_BAD_REQUEST);
                }
                $user->setEmail($email);
            }
        }

        //Mise à jour du mot de passe et du token d'API:
        $password = $request->request->get('password');
        if (!empty($password)) {
            $user->setPassword($userPasswordHasher->hashPassword($user, $password));
            $user->setApiToken(hash('sha256', $user->getEmail().$password));
        }

        //Autres champs:
        if ($request->request->has('username')) {
            $user->setUsername($request->request->get('username'));
        }

        if ($request->request->has('roles')) {
            $roles = $request->request->all()['roles'] ?? [];
            $user->setRoles($roles);
        }

        if ($request->request->has('firstname')) {
            $person->setFirstname($request->request->get('firstname'));
        }

        if ($request->request->has('lastname')) {
            $person->setLastname($request->request->get('lastname'));
        }

        if ($request->request->has('birthdate')) {
            $birthdateStr = $request->request->get('birthdate');
            if (!empty($birthdateStr)) {
                $person->setBirthdate(new \DateTime($birthdateStr, $parisTimeZone));
            } else {
                $person->setBirthdate(null);
            }
        }

        //Gestion de l'image automatique par VichUploader:
        if ($request->request->get('deletePhoto') === '1') {}
        $photoFile = $request->files->get('photo');
        if ($photoFile) {
            $person->setPhotoFile($photoFile);
        }

        $person->setUpdatedAt($now);

        //Enregistrement en DB:
        try {
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Utilisateur mis à jour avec succès !'
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json([
                'message' => 'Erreur lors de la mise à jour de l’utilisateur.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user/{id}', name: '.delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    /**
     * Supprime un utilisateur.
     */
    public function delete(User $user, EntityManagerInterface $entityManager): JsonResponse {
        $entityManager->remove($user);
        $entityManager->flush();
        return new JsonResponse([
            'message' => 'L\'utilisateur à bien été supprimé.'
        ], RESPONSE::HTTP_OK);
    }

    #[Route('/profile/{id}', name: '.profile', methods: ['GET'], requirements: ['id' => '\d+'])]
    /**
     * Récupère le profile d'un utilisateur.
     */
    public function profile(User $user): JsonResponse {
        return $this->json($user, 200, [], ['groups' => ['user.profile']]);
    }

    #[Route('/upadte-photo', name: '.upadatePhoto', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    /**
     * Met à jour la photo de profile d'un utilisateur.
     */
    public function updatePhoto(Request $request, EntityManagerInterface $entityManager): JsonResponse {
        //Initialisation:
        $user = $this->getUser();

        //Suppresion de la photo de profile:
        $deletePhoto = $request->request->get('deletePhoto');
        if ($deletePhoto === 'true' || $deletePhoto === '1' || $deletePhoto === true) {
            //Modification de l'entité:
            $user->getPerson()->setPhotoFile(null);
            $user->getPErson()->setPhoto(null);
            $user->getPerson()->setUpdatedAt(new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris")));

            //Enregistrement en DB:
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Photo de profil supprimée avec succès',
                'photoUrl' => null
            ], Response::HTTP_OK);
        }

        //Remplacement de la photo:
        $file = $request->files->get('photo');
        if ($file) {
            //Modification de l'entité:
            $user->getPerson()->setPhotoFile($file);
            $user->getPerson()->setUpdatedAt(new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris")));

            //Enregitrement en DB:
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Photo de profil mise à jour avec succès',
            ], Response::HTTP_OK);
        }

        return new JsonResponse(['error' => 'Veuillez uploader une image ou cocher la checkbox.'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/update-personal-datas', name: '.updatePersonalDatas', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    /**
     * Met à jour les informations personnelles d'un utilisateur.
     */
    public function updatePersonalDatas(Request $request, EntityManagerInterface $entityManager): JsonResponse {
        //Initialisation:
        $data = json_decode($request->getContent(), true);
        $firstname = isset($data['firstname']) ? trim($data['firstname']) : '';
        $lastname = isset($data['lastname']) ? trim($data['lastname']) : '';
        $username = isset($data['username']) ? trim($data['username']) : '';
        $birthdate = isset($data['birthdate']) ? trim($data['birthdate']) : '';
        $user = $this->getUser();

        //Cas du formulaire vide:
        if ($firstname === '' && $lastname === '' && $username === '' && $birthdate === '') {
            return $this->json([
                'message' => 'Veuillez remplir au moins un champ.',
            ], Response::HTTP_BAD_REQUEST);
        }

        //Champ prénom:
        if (!empty($firstname)) {
            $user->getPerson()->setFirstname($firstname);
        }

        //Champ nom:
        if (!empty($lastname)) {
            $user->getPerson()->setLastname($lastname);
        }

        //Champ nom d'utilisateur:
        if (!empty($username)) {
            $user->setUsername($username);
        }

        //Champ anniversaire:
        if (!empty($birthdate)) {
            $user->getPerson()->setBirthdate(new \DateTime($birthdate, new \DateTimeZone("Europe/Paris")));
        }

        //Mise à jour de updated_At:
        $user->getPerson()->setUpdatedAt(new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris")));

        //Enregistrement en DB:
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Informations mise à jour avec succès.'
        ], Response::HTTP_OK);
    }

    #[Route('/update-email', name: '.updateEmail', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    /**
     * Met à jour l'email d'un utilisateur.
     */
    public function updateEmail(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse {
        //Initialisation:
        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        $user = $this->getUser();

        //Vérification du format de l'adresse email:
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'message' => 'Format de l\'adresse email invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        //Verification du mot de passe:
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json([
                'message' => 'Identifiants invalides.',
            ], RESPONSE::HTTP_UNAUTHORIZED);
        } else {
            //Mise à jour de l'email:
            $user->setEmail($email);
            $user->getPerson()->setUpdatedAt(new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris")));

            //Enregistrement en DB:
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Email mis à jour avec succès.'
            ], RESPONSE::HTTP_OK);
        }
    }

    #[Route('/update-password', name: '.updatePassword', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    /**
     * Met à jour le mot de passe d'un utilisateur.
     */
    public function updatePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse {
        //Initialisation:
        $data = json_decode($request->getContent(), true);
        $password = trim($data['password']);
        $newPassword = trim($data['newPassword']);
        $confirmPassword = trim($data['confirmPassword']);
        $user = $this->getUser();

        //Vérification des champs vides:
        if ($password === '' || $newPassword === '' || $confirmPassword === '') {
            return $this->json([
                'message' => 'Veuillez remplir tous les champs.',
            ], Response::HTTP_BAD_REQUEST);
        }

        //Vérification du nouveau mot de passe:
        if ($newPassword !== $confirmPassword) {
            return $this->json([
                'message' => 'Les nouveaux mots de passe ne correspondent pas.',
            ], Response::HTTP_BAD_REQUEST);
        }

        //Vérification de la taille:
        if (strlen($newPassword) < 6) {
            return $this->json([
                'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères.',
            ], Response::HTTP_BAD_REQUEST);
        } elseif (strlen($newPassword) > 255) {
            return $this->json([
                'message' => 'Le nouveau mot de passe doit contenir moins de 255 caractères.',
            ], Response::HTTP_BAD_REQUEST);
        }

        //Vérification du mot de passe:
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json([
                'message' => 'Mot de passe actuel incorrect.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        //Modification du mot de passe et du token d'API:
        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $user->setApiToken(hash('sha256', $user->getEmail().$newPassword));
        $user->getPerson()->setUpdatedAt(new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris")));

        //Enregistrement en DB:
        $entityManager->flush();

        return $this->json([
            'message' => 'Mot de passe mis à jour avec succès.'
        ], Response::HTTP_OK);
    }
}
