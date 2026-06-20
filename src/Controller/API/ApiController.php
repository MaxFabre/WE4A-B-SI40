<?php

namespace App\Controller\API;

use App\Controller\RoomController;
use App\Entity\Basket;
use App\Entity\Film;
use App\Entity\Genre;
use App\Entity\Lang;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Timezone;

#[Route('/api', name: 'api')]
final class ApiController extends AbstractController {

    //--------------------------------SECTION RESERVATION----------------------------------------------------------------------------------

    #[Route('/reservation/{id}', name: '.reservation.details', methods: ['GET'])]
    public function fetchOneRservation(?Reservation $reservation, SerializerInterface $serializer): JsonResponse {
        if (!$reservation) {
            return $this->json(['message' => 'Reservation introuvable.'], 404);
        }
        return $this->json($reservation, 200, [], ['groups' => ['reservation.details']]);
    }

    #[Route('/reservation/update/{id?}', name: '.reservation.update', methods: ['POST'])]
    public function updateReservation(Request $request, EntityManagerInterface $em, ReservationRepository $reservationRepo, SeatRepository $seatRepo, ?int $id = null): JsonResponse {

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['seatIds']) || !is_array($data['seatIds']) || count($data['seatIds']) === 0) {
            return $this->json(['message' => 'seatIds (liste non vide) requis.'], Response::HTTP_BAD_REQUEST);
        }
        $seatIds = array_map('intval', $data['seatIds']);

        if ($id === null) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        $reservation = $reservationRepo->find($id);
        if (!$reservation) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        $newSeats = $seatRepo->findBy(['id' => $seatIds]);
        if (count($newSeats) !== count($seatIds)) {
            return $this->json(['message' => 'Un ou plusieurs sièges introuvables.'], Response::HTTP_NOT_FOUND);
        }

        $programme = $reservation->getProgramme();
        $reservedSeats = [];
        foreach ($programme->getReservations() as $existingReservation) {
            if ($existingReservation->getId() !== $reservation->getId()) {
                $reservedSeats = array_merge($reservedSeats, $existingReservation->getSeats()->toArray());
            }
        }
        foreach ($newSeats as $seat) {
            if (in_array($seat, $reservedSeats) ) {
                return $this->json(['message' => 'Un ou plusieurs sièges déjà réservés'], Response::HTTP_BAD_REQUEST);
            }
        }

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
    #[IsGranted("ROLE_USER")]
    public function createReservation(Request $request, EntityManagerInterface $em, ProgrammeRepository $programmeRepo, SeatRepository $seatRepo, BasketRepository $basketRepo, UserRepository $userRepo): JsonResponse {
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

        $basket = null;
        if ($basketId) {
            $basket = $basketRepo->find($basketId);
            if (!$basket) {
                return $this->json(['message' => 'Basket introuvable.'], Response::HTTP_NOT_FOUND);
            }
            if ($basket->getUser() !== $user) {
                return $this->json(['message' => 'Basket non autorisé pour cet utilisateur.'], Response::HTTP_FORBIDDEN);
            }
        } else {
            $basket = $basketRepo->findOneBy(['user' => $user, 'isActive' => true]);
            if (!$basket) {
                $basket = new Basket();
                $basket->setUser($user);
                $basket->setIsActive(true);
                $em->persist($basket);
            }
        }

        $seats = $seatRepo->findBy(['id' => $seatIds]);
        if (count($seats) !== count($seatIds)) {
            return $this->json(['message' => 'Un ou plusieurs sièges introuvables.'], Response::HTTP_NOT_FOUND);
        }

        $roomOfProgramme = $programme->getRoom();
        foreach ($seats as $seat) {
            if ($seat->getRoom() !== $roomOfProgramme) {
                return $this->json(['message' => sprintf('Le siège %d n\'appartient pas à la salle du programme.', $seat->getId())], Response::HTTP_BAD_REQUEST);
            }
            foreach ($seat->getReservations() as $existingReservation) {
                if ($existingReservation->getProgramme() && $existingReservation->getProgramme()->getId() === $programme->getId() && $existingReservation->getBasket()->getUser()->getId() !== $userId) {
                    return $this->json(['message' => sprintf('Le siège %d est déjà réservé pour ce programme.', $seat->getId())], Response::HTTP_CONFLICT);
                }
            }
        }

        $reservation = new Reservation();
        $reservation->setProgramme($programme);
        $reservation->setBasket($basket);
        $reservation->setIsValidated(false);

        foreach ($seats as $seat) {
            $reservation->addSeat($seat);
        }

        $em->persist($reservation);
        $em->flush();

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

    #[Route('/reservation/delete/{id}', name: 'api.reservation.delete', methods: ['DELETE'])]
    public function deleteReservation(int $id, EntityManagerInterface $em): JsonResponse {
        $reservation = $em->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            return new JsonResponse(['message' => 'Réservation non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($reservation);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    //--------------------------------SECTION SALLE----------------------------------------------------------------------------------

    #[Route('/room/{id}', name: '.room.details', methods: ['GET'])]
    public function room(Room $room, SerializerInterface $serializer): JsonResponse {
        $firstClassSeats = 0;
        $secondClassSeats = 0;

        foreach ($room->getSeats() as $seat) {
            if ($seat->getClass() === 1) {
                $firstClassSeats++;
            }

            if ($seat->getClass() === 2) {
                $secondClassSeats++;
            }
        }
        return $this->json([
            'id' => $room->getId(),
            'name' => $room->getName(),
            'capacity' => $room->getCapacity(),
            'firstClassSeats' => $firstClassSeats,
            'secondClassSeats' => $secondClassSeats,
        ], RESPONSE::HTTP_OK);
    }

    #[Route('/room/{id}', name: '.room.update', methods: ['PUT'])]
    public function updateRoom(Room $room, Request $request, EntityManagerInterface $entityManager): JsonResponse {
        //Initialisation:
        $name = $request->request->get('name');
        $firstClassSeatsStr = $request->request->get('firstClassSeats');
        $secondClassSeatsStr = $request->request->get('secondClassSeats');

        if (!isset($name) || !isset($firstClassSeatsStr) || !isset($secondClassSeatsStr)) {
            return $this->json(['message' => 'Veuillez remplir tout les champs.'], Response::HTTP_BAD_REQUEST);
        }

        $firstClassSeats = (int) $firstClassSeatsStr;
        $secondClassSeats = (int) $secondClassSeatsStr;
        if ($firstClassSeats <= 0 || $secondClassSeats <= 0) {
            return $this->json(['message' => 'Le nombre de sièges doit être supérieur à 0.'], Response::HTTP_BAD_REQUEST);
        }

        $roomController = new RoomController();

        $newFirstClassSeats = (int) $firstClassSeatsStr;
        $newSecondClassSeats = (int) $secondClassSeatsStr;
        $roomController->updateRoomSeats($room, 1, $firstClassSeats, $newFirstClassSeats, $entityManager);
        $roomController->updateRoomSeats($room, 2, $secondClassSeats, $newSecondClassSeats, $entityManager);
        $room->setCapacity($firstClassSeats + $secondClassSeats);

        try {
            //Enregistrement en DB:
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'La salle à été modifiée avec succès.'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de l\'enregistrement'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/room', name: '.room.all', methods: ['GET'])]
    #[IsGranted('ROLE_FUND_MANAGER')]
    public function roomAll(RoomRepository $roomRepository, SerializerInterface $serializer): JsonResponse {
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
    #[IsGranted('ROLE_FUND_MANAGER')]
    public function createRoom(Request $request, EntityManagerInterface $em, RoomRepository $roomRepository): JsonResponse {
        //Initialisation:
        $name = $request->request->get('name');
        $firstClassSeatsStr = $request->request->get('firstClassSeats');
        $secondClassSeatsStr = $request->request->get('secondClassSeats');

        if (!isset($name) || !isset($firstClassSeatsStr) || !isset($secondClassSeatsStr)) {
            return $this->json(['message' => 'Veuillez remplir tout les champs.'], Response::HTTP_BAD_REQUEST);
        }

        //Création de l'objet:
        $lang = new Lang();
        $lang->setName($name);

        //On check si une salle à déjà ce nom:
        $existing = $roomRepository->findOneBy(['name' => $name]);
        if ($existing) {
            return $this->json(['message' => 'Une salle existe déjà avec ce nom.'], Response::HTTP_CONFLICT);
        }

        $firstClassSeats = (int) $firstClassSeatsStr;
        $secondClassSeats = (int) $secondClassSeatsStr;
        if ($firstClassSeats <= 0 || $secondClassSeats <= 0) {
            return $this->json(['message' => 'Le nombre de sièges doit être supérieur à 0.'], Response::HTTP_BAD_REQUEST);
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
            'message' => 'Salle créée avec succès.',
            'room' => [
                'id' => $room->getId(),
                'name' => $room->getName(),
                'capacity' => $room->getCapacity(),
            ],
        ], Response::HTTP_CREATED);
    }

    //--------------------------------SECTION GENRE-------------------------------------------------------------------

    #[Route('/genre/create', name: '.genre.create', methods: ['POST'])]
    #[IsGranted('ROLE_FUND_MANAGER')]
    public function createGenre(Request $request, EntityManagerInterface $entityManager): JsonResponse {
        //Initialisation:
        $name = $request->request->get('name');

        if (!isset($name)) {
            return $this->json(['message' => 'Veuillez donner un nom au genre.'], Response::HTTP_BAD_REQUEST);
        }

        //Création de l'objet:
        $genre = new Genre();
        $genre->setName($name);

        try {
            //Enregistrement en DB:
            $entityManager->persist($genre);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Genre créé avec succès !'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la création du genre.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/genre/{id}', name: '.genre.fetchOne', methods: ['GET'])]
    public function genre(Genre $genre): JsonResponse {
        return $this->json($genre, RESPONSE::HTTP_OK, [], ['groups' => ['genre.details']]);
    }

    #[Route('/genre/{id}', name: '.genre.update', methods: ['PUT'])]
    #[IsGranted('ROLE_FUND_MANAGER')]
    public function updateGenre(Genre $genre, Request $request, EntityManagerInterface $entityManager): JsonResponse {
        //Initialisation:
        $name = $request->request->get('name');

        if (!isset($name)) {
            return $this->json(['message' => 'Veuillez donner un nom au genre.'], Response::HTTP_BAD_REQUEST);
        }

        //Modification de l'objet:
        $genre->setName($name);

        try {
            //Enregistrement en DB:
            $entityManager->persist($genre);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Genre modifié avec succès !'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la modification du genre.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/genre/{id}', name: '.genre.details', methods: ['DELETE'])]
    public function deleteGenre(Genre $genre, EntityManagerInterface $entityManager): JsonResponse {
        $entityManager->remove($genre);
        $entityManager->flush();
        return $this->json([
            'message' => 'Genre supprimé avec succès.'
        ], RESPONSE::HTTP_OK);
    }


    #[Route('/genre', name: '.genre.all', methods: ['GET'])]
    #[IsGranted('ROLE_FUND_MANAGER')]
    public function genreAll(GenreRepository $genreRepository): JsonResponse {
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

    //--------------------------------SECTION LANGUE-------------------------------------------------------------------

    #[Route('/lang', name: '.langs.fetchAll', methods: ['GET'])]
    public function langFetchAll(LangRepository $langRepository): JsonResponse {
        $langs = $langRepository->findAll();
        return $this->json($langs, RESPONSE::HTTP_OK, [], ['groups' => ['programme.details']]);
    }

    #[Route('/lang/create', name: '.langs.create', methods: ['POST'])]
    public function langCreate(Request $request, EntityManagerInterface $entityManager): JsonResponse {
        //Initialisation:
        $name = $request->request->get('name');

        if (!isset($name)) {
            return $this->json(['message' => 'Veuillez donner un nom à la langue.'], Response::HTTP_BAD_REQUEST);
        }

        //Création de l'objet:
        $lang = new Lang();
        $lang->setName($name);

        try {
            //Enregistrement en DB:
            $entityManager->persist($lang);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Langue créée avec succès !'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la création de la langue.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/lang/{id}', name: '.langs.fetchOne', methods: ['GET'])]
    public function langFetchOne(Lang $lang): JsonResponse {
        return $this->json($lang, 200, [], ['groups' => ['programme.details']]);
    }

    #[Route('/lang/{id}', name: '.langs.update', methods: ['PUT'])]
    public function updateLang(Lang $lang, Request $request, EntityManagerInterface $entityManager): JsonResponse {
        //Initialisation:
        $name = $request->request->get('name');

        if (!isset($name)) {
            return $this->json(['message' => 'Veuillez donner un nom à la langue.'], Response::HTTP_BAD_REQUEST);
        }

        $lang->setName($name);

        try {
            //Enregistrement en DB:
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Langue modifiée avec succès !'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la modification de la langue.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/lang/{id}', name: '.langs.delete', methods: ['DELETE'])]
    public function delete(Lang $lang, EntityManagerInterface $entityManager): JsonResponse {
        $entityManager->remove($lang);
        return $this->json([
            'message' => 'La langue à bien été supprimée.'
        ], RESPONSE::HTTP_OK, [], ['groups' => ['programme.details']]);
    }

    //--------------------------------SECTION BASKET-------------------------------------------------------------------

    #[Route('/basket', name: 'fetchAll', methods: ['GET'])]
    public function basketfetchAll(BasketRepository $basketRepository): JsonResponse {
        $baskets = $basketRepository->findAll();
        return $this->json($baskets, 200, [], ['groups' => ['basket.details']]);
    }

    #[Route('/basket/{id}', name: '.basket.details', methods: ['GET'])]
    public function basket(Basket $basket, SerializerInterface $serializer): JsonResponse {
        return $this->json($basket, 200, [], ['groups' => ['basket.details']]);
    }

    // ------------------------------------ SECTION RECHERCHE ------------------------------------------------------------------------------

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

    #[Route('/programme/', name: '.programme.fetchAll', methods: ['GET'])]
    public function fetchAllProgramme(ProgrammeRepository $programmeRepository): JsonResponse {
        //Initialisation:
        $programmes = $programmeRepository->findAll();
        return $this->json($programmes, RESPONSE::HTTP_OK, [], ['groups' => ['programme.details']]);
    }

    #[Route('/programme/{id}', name: '.programme.details', methods: ['GET'])]
    public function programme(?Programme $programme, SerializerInterface $serializer): JsonResponse {

        if (!$programme) {
            return $this->json(['message' => 'Programme introuvable.'], 404);
        }
        return $this->json($programme, 200, [], ['groups' => ['programme.details']]);
    }

    #[Route('/programme/{id}', name: '.programme.update', methods: ['PUT'])]
    #[IsGranted('ROLE_FUND_MANAGER')]
    public function updateProgramme(Programme $programme, Request $request, EntityManagerInterface $entityManager, FilmRepository $filmRepo, LangRepository $langRepo, RoomRepository $roomRepo, ProgrammeRepository $programmeRepo
    ): JsonResponse {
        //Initialisation:
        $dateStr = $request->request->get('date');
        $film = $request->request->get('film');
        $langId = $request->request->get('langId');
        $roomId = $request->request->get('roomId');

        if (!isset($dateStr) || !isset($film) || !isset($langId) || !isset($roomId)) {
            return $this->json(['message' => 'Veuillez remplir tout les champs.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $date = new \DateTime($dateStr, new \DateTimeZone('Europe/Paris'));
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur dans le choix de la date.'], Response::HTTP_BAD_REQUEST);
        }

        //Création des objets:
        $film = $filmRepo->find($film);
        $lang = $langRepo->findOneBy(['id' => $langId]);
        $room = $roomRepo->findOneBy(['id' => $roomId]);

        //Vérification des entrées utilisateurs:
        if (!$film || !$lang || !$room) {
            return $this->json(['message' => 'Film, langue ou salle introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $programme
            ->setDate($date)
            ->setFilm($film)
            ->setLang($lang)
            ->setRoom($room)
            ->setIsClosed(false);

        try {
            //Enregistrement en DB:
            $entityManager->persist($programme);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Le programme à été modifié avec succès !'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur de lors de l\'enregistrement du programme.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/programme/{id}', name: '.programme.delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_FUND_MANAGER')]
    public function deleteProgramme(Programme $programme, EntityManagerInterface $entityManager): JsonResponse {
        $entityManager->remove($programme);
        $entityManager->flush();
        return new JsonResponse([
            'message' => 'Le programme à bien été supprimé.'
        ], RESPONSE::HTTP_NO_CONTENT);
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
    #[IsGranted('ROLE_FUND_MANAGER')]
    public function createProgramme(Request $request, EntityManagerInterface $entityManager, FilmRepository $filmRepo, LangRepository $langRepo, RoomRepository $roomRepo, ProgrammeRepository $programmeRepo
    ): JsonResponse {
        //Initialisation:
        $dateStr = $request->request->get('date');
        $film = $request->request->get('film');
        $langId = $request->request->get('langId');
        $roomId = $request->request->get('roomId');

        if (!isset($dateStr) || !isset($film) || !isset($langId) || !isset($roomId)) {
            return $this->json(['message' => 'Veuillez remplir tout les champs.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $date = new \DateTime($dateStr, new \DateTimeZone('Europe/Paris'));
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur dans le choix de la date.'], Response::HTTP_BAD_REQUEST);
        }

        //Création des objets:
        $programme = new Programme();
        $film = $filmRepo->find($film);
        $lang = $langRepo->findOneBy(['id' => $langId]);
        $room = $roomRepo->findOneBy(['id' => $roomId]);

        //Vérification des entrées utilisateurs:
        if (!$film || !$lang || !$room) {
            return $this->json(['message' => 'Film, langue ou salle introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $programme
            ->setDate($date)
            ->setFilm($film)
            ->setLang($lang)
            ->setRoom($room)
            ->setIsClosed(false);

        try {
            //Enregistrement en DB:
            $entityManager->persist($programme);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Programme créé avec succès !'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur de lors de l\'enregistrement du programme.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
