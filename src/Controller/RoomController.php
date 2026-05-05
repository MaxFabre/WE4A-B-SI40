<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\RoomType;
use Symfony\Component\Form\FormError;
use App\Entity\Seat;
use Doctrine\ORM\EntityManager;
use App\Repository\RoomRepository;
#[Route('/tools/room', name: 'admin.room')]
final class RoomController extends AbstractController
{
//    #[Route('/tools/room', name: 'admin.room')]
//    public function index(): Response
//    {
//        return $this->render('room/index.html.twig', [
//            'controller_name' => 'RoomController',
//        ]);
//    }



    #[Route('/', name: '.index')]
    public function roomList(RoomRepository $roomRepository): Response {

        $sortRoom = $this->container->get('request_stack')->getCurrentRequest()?->query->getString('sortRoom', 'id_asc');

        $rooms = match ($sortRoom) {
            'capacity_asc' => $roomRepository->findBy([], ['capacity' => 'ASC']),
            'capacity_desc' => $roomRepository->findBy([], ['capacity' => 'DESC']),
            'id_desc' => $roomRepository->findBy([], ['id' => 'DESC']),
            default => $roomRepository->findBy([], ['id' => 'ASC']),
        };

        return $this->render('admin_pages/room/index.html.twig', [
            'controller_name' => 'RoomController',
            'sortRoom' => $sortRoom,
            'rooms' => $rooms,
        ]);


    }
    #[Route('/edit/{id}', name: '.edit', methods: ['GET', 'POST'])]
    public function edit(Room $room, Request $request, EntityManagerInterface $entityManager): Response {
        $firstClassSeats = 0;
        $secondClassSeats = 0;

        foreach ($room->getSeats() as $seat) { //Ici on divise les sièges en classe 1 et 2 pour préremplir le formulaire d'edit
            if ($seat->getClass() === 1) {
                $firstClassSeats++;
            }

            if ($seat->getClass() === 2) {
                $secondClassSeats++;
            }
        }

        $roomForm = $this->createForm(RoomType::class, $room, [ // On passe en paramètre au formulaire le nombre de sièges
            'first_class_seats' => $firstClassSeats,
            'second_class_seats' => $secondClassSeats,
        ]);


        $roomForm->handleRequest($request);
        if ($roomForm->isSubmitted() && $roomForm->isValid()) {

            // On récupère les entrées utilisateurs
            $newFirstClassSeats = (int) $roomForm->get('firstClassSeats')->getData();
            $newSecondClassSeats = (int) $roomForm->get('secondClassSeats')->getData();

            //On lance la mise à jour des sièges dans la bdd
            $this->updateRoomSeats($room, 1, $firstClassSeats, $newFirstClassSeats, $entityManager);
            $this->updateRoomSeats($room, 2, $secondClassSeats, $newSecondClassSeats, $entityManager);

            $room->setCapacity($newFirstClassSeats + $newSecondClassSeats);// On met à jour la capacité de la salle


            //Enregistrement en db:
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'La salle à bien été modifié.');
            return $this->redirectToRoute('admin.room.index');
        }
        return $this->render('admin_pages/room/edit.html.twig', [
            'room' => $room,
            'roomForm' => $roomForm->createView(),
        ]);
    }

    // Prend en paramètres une salle, une classe de sièges, le nombre d'anciens sièges et le nombre de nouveaux sièges
    private function updateRoomSeats(Room $room, int $seatClass, int $oldCount, int $newCount, EntityManagerInterface $entityManager): void
    {
        if ($newCount > $oldCount) { //Si on doit ajouter des sièges
            $seatNumber = $this->getNextSeatNumber($room);//On récupère le prochain numéro de siège qui n'est pas pris

            for ($i = 0; $i < $newCount - $oldCount; $i++) { //Pour chaque siège à ajouter, on le créé et on le lie correctement à la salle
                $seat = new Seat();
                $seat->setNumber($seatNumber);
                $seat->setClass($seatClass);
                $room->addSeat($seat);

                $entityManager->persist($seat);
                $seatNumber++;
            }
        }

        if ($newCount < $oldCount) { //Si on doit supprimer des sièges
            $seatsToRemove = $oldCount - $newCount;

            foreach ($room->getSeats() as $seat) {
                if ($seatsToRemove <= 0) {
                    break;
                }

                if ($seat->getClass() === $seatClass && $seat->getReservations()->isEmpty()) {

                    $room->removeSeat($seat);
                    $entityManager->remove($seat);
                    $seatsToRemove--;
                }
            }
            if ($seatsToRemove > 0) { //Si la boucle est terminée mais qu'il reste des sièges à supprimer alors il y a trop de réservations
                throw new \RuntimeException('Impossible de supprimer certains sièges car ils sont déjà réservés.');
            }
        }
    }

    private function getNextSeatNumber(Room $room): int // On cherche le prochain numéro de siège disponible (pour éviter les doublons)
    {
        $maxSeatNumber = 0;

        foreach ($room->getSeats() as $seat) {
            if ($seat->getNumber() !== null && $seat->getNumber() > $maxSeatNumber) {
                $maxSeatNumber = $seat->getNumber();
            }
        }

        return $maxSeatNumber + 1;
    }


    #[Route('/create', name: '.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $room = new Room();
        $roomForm = $this->createForm(RoomType::class, $room);

        $roomForm->handleRequest($request);
        if ($roomForm->isSubmitted() && $roomForm->isValid()) {

            $firstClassSeats = (int) $roomForm->get('firstClassSeats')->getData();
            $secondClassSeats = (int) $roomForm->get('secondClassSeats')->getData();

            $room->setCapacity($firstClassSeats + $secondClassSeats);

            $seatNumber = 1;

            for ($i = 0; $i < $firstClassSeats; $i++) {
                $seat = new Seat();
                $seat->setNumber($seatNumber);
                $seat->setClass(1);
                $room->addSeat($seat);

                $entityManager->persist($seat);
                $seatNumber++;
            }

            for ($i = 0; $i < $secondClassSeats; $i++) {
                $seat = new Seat();
                $seat->setNumber($seatNumber);
                $seat->setClass(2);
                $room->addSeat($seat);

                $entityManager->persist($seat);
                $seatNumber++;
            }

            //Enregistrement en db:
            $entityManager->persist($room);
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'La salle.');
            return $this->redirectToRoute('admin.room.index');
        }
        return $this->render('admin_pages/room/create.html.twig', [
            'roomForm' => $roomForm,
        ]);
    }

    #[Route('/{id}', name: '.delete', methods: ['DELETE'])]
    public function delete(Room $room, EntityManagerInterface $entityManager) {
        $entityManager->remove($room);
        $entityManager->flush();
        $this->addFlash('success', 'La salle à bien été supprimé.');
        return $this->redirectToRoute('admin.room.index');
    }
}
