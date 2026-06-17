<?php

namespace App\Controller\API;

use App\Entity\Basket;
use App\Entity\Film;
use App\Entity\Genre;
use App\Entity\Person;
use App\Entity\Programme;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\Seat;
use App\Entity\User;
use App\Repository\BasketRepository;
use App\Repository\FilmRepository;
use App\Repository\GenreRepository;
use App\Repository\LangRepository;
use App\Repository\PersonRepository;
use App\Repository\ProgrammeRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\SeatRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'api')]
final class ApiController extends AbstractController {


    //--------------------------------SECTION FILMS----------------------------------------------------------------------------------
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

    #[Route('/film', name: '.film.all', methods: ['GET'])]
    public function filmAll(FilmRepository $filmRepository, SerializerInterface $serializer): JsonResponse
    {
        $films = $filmRepository->findAll();
        $results = [];

        foreach ($films as $film) {
            $results[] = [
                'id' => $film->getId(),
                'title' => $film->getTitle(),
                'duration' => $film->getDuration(),
            ];
        }

        return $this->json($results);
    }

    //--------------------------------SECTION RESERVATION----------------------------------------------------------------------------------

    #[Route('/reservation/{id}', name: '.reservation.details', methods: ['GET'])]
    public function reservation(?Reservation $reservation, SerializerInterface $serializer): JsonResponse {

        if (!$reservation) {
            return $this->json(['message' => 'Reservation introuvable.'], 404);
        }
        return $this->json($reservation, 200, [], ['groups' => ['reservation.details']]);
    }

    #[Route('/reservation/update/{id?}', name: '.reservation.update', methods: ['POST'])]
    public function updateReservation(Request $request, EntityManagerInterface $em, ReservationRepository $reservationRepo, SeatRepository $seatRepo, ?int $id = null): JsonResponse {

        // Récupérer seatIds depuis le body JSON
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['seatIds']) || !is_array($data['seatIds']) || count($data['seatIds']) === 0) {
            return $this->json(['message' => 'seatIds (liste non vide) requis.'], Response::HTTP_BAD_REQUEST);
        }
        $seatIds = array_map('intval', $data['seatIds']);

        // Si pas d'id ou réservation introuvable : ne rien faire (204 No Content)
        if ($id === null) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        $reservation = $reservationRepo->find($id);
        if (!$reservation) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        // Récupérer les sièges demandés
        $newSeats = $seatRepo->findBy(['id' => $seatIds]);
        if (count($newSeats) !== count($seatIds)) {
            return $this->json(['message' => 'Un ou plusieurs sièges introuvables.'], Response::HTTP_NOT_FOUND);
        }

        // Remplacer les anciens sièges par les nouveaux (aucune autre vérification métier)
        foreach ($reservation->getSeats()->toArray() as $oldSeat) {
            $reservation->removeSeat($oldSeat);
        }
        foreach ($newSeats as $seat) {
            $reservation->addSeat($seat);
        }

        $em->persist($reservation);
        $em->flush();

        return $this->json([
            'message' => 'Sièges mis à jour',
            'reservation' => [
                'id' => $reservation->getId(),
                'seatIds' => array_map(fn($s) => $s->getId(), $reservation->getSeats()->toArray()),
            ]
        ], Response::HTTP_OK);
    }


    #[Route('/reservation/create', name: '.reservation.create', methods: ['POST'])]
    public function createReservation(Request $request, EntityManagerInterface $em, ProgrammeRepository $programmeRepo, SeatRepository $seatRepo, BasketRepository $basketRepo, UserRepository $userRepo): JsonResponse {
//        $user = $this->getUser();
//        if (!$user) {
//            return $this->json(['message' => 'Utilisateur non authentifié.'], Response::HTTP_UNAUTHORIZED);
//        }



        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $programmeId = $data['programmeId'] ?? null;
        $seatIds = $data['seatIds'] ?? null; // attend un tableau d'IDs
        $basketId = $data['basketId'] ?? null;
        $userId = $data['userId'] ?? null; //Je sais pas trop ce que je fais mais dans l'idée quand le user sera connecté on récupèrera direct son ID avec this->user ?
        $user = $userRepo->find($userId);

        if (!$programmeId || !is_array($seatIds) || count($seatIds) === 0) {
            return $this->json(['message' => 'programmeId et seatIds (liste non vide) sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        $programme = $programmeRepo->find($programmeId);
        if (!$programme) {
            return $this->json(['message' => 'Programme introuvable.'], Response::HTTP_NOT_FOUND);
        }

        // Récupération ou création du basket
        $basket = null;
        if ($basketId) {
            $basket = $basketRepo->find($basketId);
            if (!$basket) {
                return $this->json(['message' => 'Basket introuvable.'], Response::HTTP_NOT_FOUND);
            }
            // Optionnel : vérifier que le basket appartient bien à l'utilisateur
            if ($basket->getUser() !== $user) {
                return $this->json(['message' => 'Basket non autorisé pour cet utilisateur.'], Response::HTTP_FORBIDDEN);
            }
        } else {
            $basket = $basketRepo->findOneBy(['user' => $user, 'isActive' => true]);
            if (!$basket) {
                // Si vous préférez renvoyer une erreur au lieu de créer, remplacez par un 404/400
                $basket = new Basket();
                $basket->setUser($user);
                $basket->setIsActive(true);
                $em->persist($basket);
                // flush plus bas après création de la reservation
            }
        }

        // Récupérer les sièges et vérifier
        $seats = $seatRepo->findBy(['id' => $seatIds]);
        if (count($seats) !== count($seatIds)) {
            return $this->json(['message' => 'Un ou plusieurs sièges introuvables.'], Response::HTTP_NOT_FOUND);
        }

        // Vérifications sièges dans la même salle que le programme et tous non réservés
        $roomOfProgramme = $programme->getRoom();
        foreach ($seats as $seat) {
            if ($seat->getRoom() !== $roomOfProgramme) {
                return $this->json(['message' => sprintf('Le siège %d n\'appartient pas à la salle du programme.', $seat->getId())], Response::HTTP_BAD_REQUEST);
            }
            // Vérifier si le siège est déjà réservé pour ce programme
            foreach ($seat->getReservations() as $existingReservation) {
                if ($existingReservation->getProgramme() && $existingReservation->getProgramme()->getId() === $programme->getId() && $existingReservation->getBasket()->getUser()->getId() !== $userId) {
                    return $this->json(['message' => sprintf('Le siège %d est déjà réservé pour ce programme.', $seat->getId())], Response::HTTP_CONFLICT);
                }
            }
        }

        // Création de la reservation
        $reservation = new Reservation();
        $reservation->setProgramme($programme);
        $reservation->setBasket($basket);
        $reservation->setIsValidated(false);

        foreach ($seats as $seat) {
            $reservation->addSeat($seat);
        }

        // Persister
        $em->persist($reservation);
        $em->flush();

        // Réponse
        return $this->json([
            'message' => 'Réservation créée.',
            'reservation' => [
                'id' => $reservation->getId(),
                'programmeId' => $programme->getId(),
                'seatIds' => array_map(fn($s) => $s->getId(), $seats),
                'basketId' => $basket->getId(),
                'isValidated' => $reservation->isValidated(),
            ]
        ], Response::HTTP_CREATED);
    }


    //--------------------------------SECTION SALLE----------------------------------------------------------------------------------

    #[Route('/room/{id}', name: '.room.details', methods: ['GET'])]
    public function room(Room $room, SerializerInterface $serializer): JsonResponse {
        return $this->json($room, 200, [], ['groups' => ['room.details']]);
    }


    #[Route('/room', name: '.room.all', methods: ['GET'])]
    public function roomAll(RoomRepository $roomRepository, SerializerInterface $serializer): JsonResponse
    {
//        $user = $this->getUser();
//
//        if (!$user) {
//            return $this->json([
//                'message' => 'Vous n\'êtes pas connecté',
//            ], Response::HTTP_UNAUTHORIZED);
//        }
//
//        if (!$this->isGranted('ROLE_ADMIN')) {
//            return $this->json([
//                'message' => 'Vous n\'êtes pas admin',
//            ], Response::HTTP_FORBIDDEN);
//        }
        $rooms = $roomRepository->findAll();
        $results = [];

        foreach ($rooms as $room) {
            $results[] = [
                'id' => $room->getId(),
                'name' => $room->getName(),
                'capacity' => $room->getCapacity(),
            ];
        }

        return $this->json($results);
    }


    #[Route('/room/{id}/seats', name: 'api.room.seats', methods: ['GET'])]
    public function roomSeat(int $id, RoomRepository $roomRepo, SeatRepository $seatRepo, SerializerInterface $serializer): JsonResponse {
        $room = $roomRepo->find($id);
        if (!$room) {
            return $this->json(['message' => 'Salle introuvable.'], Response::HTTP_NOT_FOUND);
        }
        $seats = $seatRepo->findByRoomIdWithRelations($id);
        $json = $serializer->serialize($seats, 'json', ['groups' => ['seat.details']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/room/create', name: '.room.create', methods: ['POST'])]
    public function createRoom(Request $request, EntityManagerInterface $em, RoomRepository $roomRepository): JsonResponse {
//        $user = $this->getUser();
//
//        if (!$user) {
//            return $this->json(['message' => 'Vous n\'êtes pas connecté.'], Response::HTTP_UNAUTHORIZED);
//        }
//
//        if (!$this->isGranted('ROLE_ADMIN')) {
//            return $this->json(['message' => 'Vous n\'êtes pas admin.'], Response::HTTP_FORBIDDEN);
//        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }


        $name = trim($data['name'] ?? '');
        $firstClassSeats = $data['firstClassSeats'] ?? null;
        $secondClassSeats = $data['secondClassSeats'] ?? null;

        // On check si une salle à déjà ce nom
        $existing = $roomRepository->findOneBy(['name' => $name]);
        if ($existing) {
            return $this->json(['message' => 'Une salle existe déjà avec ce nom.'], Response::HTTP_CONFLICT);
        }


        if ($name === '' || $firstClassSeats === null || $secondClassSeats === null) {
            return $this->json(['message' => 'Les champs name, firstClassSeats et secondClassSeats sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_int($firstClassSeats) && !ctype_digit((string)$firstClassSeats) && !is_int($secondClassSeats) && !ctype_digit((string)$secondClassSeats)) {
            return $this->json(['message' => 'capacity doit être un entier.'], Response::HTTP_BAD_REQUEST);
        }

        $firstClassSeats = (int) $firstClassSeats;
        $secondClassSeats = (int) $secondClassSeats;
        if ($firstClassSeats <= 0 || $secondClassSeats <= 0) {
            return $this->json(['message' => 'le nombre de sièges doit être supérieur à 0.'], Response::HTTP_BAD_REQUEST);
        }

        $room = new Room();

        $room->setCapacity($firstClassSeats + $secondClassSeats);

        $seatNumber = 1;

        for ($i = 0; $i < $firstClassSeats; $i++) {
            $seat = new Seat();
            $seat->setNumber($seatNumber);
            $seat->setClass(1);
            $room->addSeat($seat);

            $em->persist($seat);
            $seatNumber++;
        }

        for ($i = 0; $i < $secondClassSeats; $i++) {
            $seat = new Seat();
            $seat->setNumber($seatNumber);
            $seat->setClass(2);
            $room->addSeat($seat);

            $em->persist($seat);
            $seatNumber++;
        }

        $room->setName($name);
        //Enregistrement en db:
        $em->persist($room);
        $em->flush();


        return $this->json([
            'message' => 'Salle créée.',
            'room' => [
                'id' => $room->getId(),
                'name' => $room->getName(),
                'capacity' => $room->getCapacity(),
            ],
        ], Response::HTTP_CREATED);
    }
    //--------------------------------SECTION GENRE-------------------------------------------------------------------

    #[Route('/genre/create', name: '.genre.create', methods: ['POST'])]
    public function createGenre(Request $request, EntityManagerInterface $em, GenreRepository $genreRepository): JsonResponse {
//        $user = $this->getUser();
//
//        if (!$user) {
//            return $this->json(['message' => 'Vous n\'êtes pas connecté.'], Response::HTTP_UNAUTHORIZED);
//        }
//
//        if (!$this->isGranted('ROLE_ADMIN')) {
//            return $this->json(['message' => 'Vous n\'êtes pas admin.'], Response::HTTP_FORBIDDEN);
//        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name'] ?? '');


        if ($name === '') {
            return $this->json(['message' => 'Le champ name est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        // On check si un genre à déjà ce nom
        $existing = $genreRepository->findOneBy(['name' => $name]);
        if ($existing) {
            return $this->json(['message' => 'Un genre existe déjà avec ce nom.'], Response::HTTP_CONFLICT);
        }

        $genre = new Genre();
        $genre->setName($name);

        $em->persist($genre);
        $em->flush();

        return $this->json([
            'message' => 'Genre créée.',
            'genre' => [
                'id' => $genre->getId(),
                'name' => $genre->getName(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/genre/{id}', name: '.genre.details', methods: ['GET'])]
    public function genre(Genre $genre, SerializerInterface $serializer): JsonResponse {
        return $this->json($genre, 200, [], ['groups' => ['genre.details']]);
    }


    #[Route('/genre', name: '.genre.all', methods: ['GET'])]
    public function genreAll(GenreRepository $genreRepository, SerializerInterface $serializer): JsonResponse
    {
//        $user = $this->getUser();
//
//        if (!$user) {
//            return $this->json([
//                'message' => 'Vous n\'êtes pas connecté',
//            ], Response::HTTP_UNAUTHORIZED);
//        }
//
//        if (!$this->isGranted('ROLE_ADMIN')) {
//            return $this->json([
//                'message' => 'Vous n\'êtes pas admin',
//            ], Response::HTTP_FORBIDDEN);
//        }
        $genres = $genreRepository->findAll();
        $results = [];

        foreach ($genres as $genre) {
            $results[] = [
                'id' => $genre->getId(),
                'name' => $genre->getName(),
            ];
        }

        return $this->json($results);
    }




    //--------------------------------SECTION CONNEXION/INSCRIPTION-------------------------------------------------------------------

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

    // ------------------------------------ SECTION PROGRAMME ------------------------------------------------------------------------------

    #[Route('/programme/{id}', name: '.programme.details', methods: ['GET'])]
    public function programme(?Programme $programme, SerializerInterface $serializer): JsonResponse {

        if (!$programme) {
            return $this->json(['message' => 'Programme introuvable.'], 404);
        }
        return $this->json($programme, 200, [], ['groups' => ['programme.details']]);
    }

    #[Route('/programme/{id}/reservations', name: '.programme.reservations.details', methods: ['GET'])]
    public function programmeReservations(?Programme $programme): JsonResponse
    {
        if (!$programme) {
            return $this->json(['message' => 'Programme introuvable.'], Response::HTTP_NOT_FOUND);
        }

        // Récupère les réservations
        $reservations = $programme->getReservations();

        $result = [];
        foreach ($reservations as $reservation) {
            // Récupére l'id de l'utilisateur via le basket (si exite)
            $basket = $reservation->getBasket();
            $userId = null;
            if ($basket && method_exists($basket, 'getUser') && $basket->getUser()) {
                $userId = $basket->getUser()->getId();
            }

            // Récupére la liste des id de sièges
            $seatIds = [];
            foreach ($reservation->getSeats() as $seat) {
                $seatIds[] = $seat->getId();
            }

            $result[] = [
                'reservationId' => $reservation->getId(),
                'userId' => $userId,
                'seatIds' => $seatIds,
            ];
        }

        return $this->json(['programmeId' => $programme->getId(), 'reservations' => $result], Response::HTTP_OK);
    }


    #[Route('/programme/create', name: '.programme.create', methods: ['POST'])]
    public function createProgramme(Request $request, EntityManagerInterface $em, FilmRepository $filmRepo, LangRepository $langRepo, RoomRepository $roomRepo, ProgrammeRepository $programmeRepo
    ): JsonResponse {

        $user = $this->getUser(); //On récupère l'utilisateur connecté

//        if (!$user) {
//            return $this->json([
//                'message' => 'Vous n\'êtes pas connecté',
//            ], Response::HTTP_UNAUTHORIZED);
//        }
//
//        if (!$this->isGranted('ROLE_ADMIN')) {
//            return $this->json([
//                'message' => 'Vous n\'êtes pas admin',
//            ], Response::HTTP_FORBIDDEN);
//        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $filmId = $data['filmId'] ?? null;
        $langId = $data['langId'] ?? null;
        $roomId = $data['roomId'] ?? null;
        $dateStr = $data['date'] ?? null;
        $isClosed = $data['isClosed'] ?? false;

        if (!$filmId || !$langId || !$roomId || !$dateStr) {
            return $this->json(['message' => 'filmId, langId, roomId et date sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        $film = $filmRepo->find($filmId);
        $lang = $langRepo->find($langId);
        $room = $roomRepo->find($roomId);

        if (!$film || !$lang || !$room) {
            return $this->json(['message' => 'Film, langue ou salle introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $date = new \DateTimeImmutable($dateStr);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Format de date invalide. Utiliser ISO 8601. AAAA-MM-JJTHH:MM:SS'], Response::HTTP_BAD_REQUEST);
        }

        // On vérifie qu'une programmation n'éxiste pas déjà dans la même salle au même moment
        $conflict = $programmeRepo->findConflicting($room, $date, $film->getDuration());
        if ($conflict) {
            return $this->json(['message' => 'Conflit d\'horaire : un programme existe déjà dans cette salle à cette date.'], Response::HTTP_CONFLICT);
        }

        $programme = new Programme();
        $programme->setFilm($film);
        $programme->setLang($lang);
        $programme->setRoom($room);
        $programme->setDate(\DateTime::createFromImmutable($date));
        $programme->setIsClosed((bool)$isClosed);

        $em->persist($programme);
        $em->flush();

        return $this->json([
            'message' => 'Programme créé.',
            'programme' => [
                'id' => $programme->getId(),
                'filmId' => $film->getId(),
                'langId' => $lang->getId(),
                'roomId' => $room->getId(),
                'date' => $programme->getDate()->format(\DateTime::ATOM),
                'isClosed' => $programme->isClosed(),
            ]
        ], Response::HTTP_CREATED);
    }


}
