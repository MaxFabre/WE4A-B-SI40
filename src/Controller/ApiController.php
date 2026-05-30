<?php

namespace App\Controller;

use App\Entity\Film;
use App\Entity\Person;
use App\Entity\Programme;
use App\Entity\Room;
use App\Entity\User;

use App\Repository\FilmRepository;
use App\Repository\PersonRepository;
use App\Repository\ProgrammeRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api', name: 'api')]
final class ApiController extends AbstractController {
    #[Route('/film/search', name: '.film.search', methods: ['GET'])]
    public function filmsSearch(Request $request, FilmRepository $repository): JsonResponse {
        $query = $request->query->get('q', '');

        $films = $repository->findByTitle($query);

        $results = [];
        foreach ($films as $film) {
            $results[] = [
                'id'   => $film['id'],
                'text' => $film['title'],
            ];
        }

        return new JsonResponse(['results' => $results]);
    }

    #[Route('/film/{id}', name: '.film.details', methods: ['GET'])]
    public function film(Film $film, SerializerInterface $serializer): JsonResponse {
        return $this->json($film, 200, [], ['groups' => ['film.details']]);
    }

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
            'message' => 'Connexion réussie.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'person' => [
                    'id' => $user->getPerson()->getId(),
                    'firstname' => $user->getPerson()->getFirstname(),
                    'lastname' => $user->getPerson()->getLastname(),
                ],
            ],
        ]);
    }

    #[Route('/room/{id}', name: '.room.details', methods: ['GET'])]
    public function room(Room $room, SerializerInterface $serializer): JsonResponse {
        return $this->json($room, 200, [], ['groups' => ['room.details']]);
    }

    #[Route('/register', name: '.register', methods: ['POST'])]
    public function register(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'JSON invalide.',
            ], 400);
        }

        $email = trim($data['email'] ?? '');
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $firstname = trim($data['firstname'] ?? '');
        $lastname = trim($data['lastname'] ?? '');

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

        $now = new \DateTimeImmutable();

        $person = new Person();
        $person
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $user = new User();
        $user
            ->setEmail($email)
            ->setUsername($username)
            ->setRoles(['ROLE_USER'])
            ->setPerson($person);

        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $entityManager->persist($person);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'Inscription réussie.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'person' => [
                    'id' => $person->getId(),
                    'firstname' => $person->getFirstname(),
                    'lastname' => $person->getLastname(),
                ],
            ],
        ], 201);
    }

    #[Route('/personalities/search', name: '.personality.search', methods: ['GET'])]
    public function personalitySearch(Request $request, PersonRepository $repository): JsonResponse {
        $query = $request->query->get('q', '');

        $personalities = $repository->findByName($query);

        $results = [];
        foreach ($personalities as $personality) {
            $results[] = [
                'id'   => $personality['id'],
                'text' => $personality['firstname'].' '.$personality['lastname'],
            ];
        }

        return new JsonResponse(['results' => $results]);
    }

    #[Route('/search', name: '.search', methods: ['GET', 'POST'])]
    public function search(Request $request, PersonRepository $personRepository, FilmRepository $filmRepository): JsonResponse {
        if (isset($_POST['query'])) {
            $query = $_POST['query'];
        } elseif ($request->query->get('q', '') !== null) {
            $query = $request->query->get('q');
        } else {
            $query = '';
        }

        //Récuperation des films
        $films = $filmRepository->findByTitle($query);
        $results = [];
        foreach ($films as $film) {
            $results[] = [
                'id' => $film['id'],
                'title' => $film['title'],
            ];
        }

        //Recupération des personnalités:
        $personalities = $personRepository->findByName($query);
        foreach ($personalities as $personality) {
            $results[] = [
                'id' => $personality['id'],
                'fullName' => $personality['firstname'].' '.$personality['lastname'],
            ];
        }

        return $this->json($results);
    }
}
