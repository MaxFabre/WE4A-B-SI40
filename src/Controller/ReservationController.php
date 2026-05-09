<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\Programme;
use App\Repository\BasketRepository;
use App\Entity\Basket;
use App\Entity\User;

final class ReservationController extends AbstractController
{
    #[Route('/reservation', name: 'reservation.index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('reservation/index.html.twig', [
            'controller_name' => 'ReservationController',
        ]);
    }

    #[Route('/reservation/update-seats/{id}', name: 'reservation_update_seats', methods: ['POST'])]
    public function updateSeats(Request $request, Reservation $reservation, EntityManagerInterface $em)
    {
        // Cette ligne sert à récupérer les IDs des sièges sélectionnés
        $seatIds = json_decode($request->request->get('selectedSeats'), true);

        if (!is_array($seatIds)) {
            $seatIds = [];
        }
        // Vider les anciens sièges
        foreach ($reservation->getSeats()->toArray() as $oldSeat) {
            $reservation->removeSeat($oldSeat);
        }
        // Ajouter les nouveaux siège
        foreach ($seatIds as $id) {
            $seat = $em->getRepository(\App\Entity\Seat::class)->find($id);
            if ($seat) {
                $reservation->addSeat($seat);
            }
        }
        $reservation->setIsValidated(false);
        $em->flush();
        return $this->redirectToRoute('basket.index');
    }


    #[Route('/reservation/choose-seats/{id}', name: 'reservation_choose_seats', methods: ['GET','POST'])]
    public function chooseSeats($id, Request $request, Reservation $reservation, EntityManagerInterface $em, $numberWanted=-1)
    {
        $room = $reservation->getProgramme()->getRoom();
        $siegesParRang = 0;
        if ($room->getCapacity()>14){
            $siegesParRang = (int) ($room->getCapacity()/5);
        }else if ($room->getCapacity()>10){
            $siegesParRang = (int) ($room->getCapacity()/4);
        }else {
            $siegesParRang = (int) ($room->getCapacity()/2);
        }
        $rangees = $room->getCapacity() / $siegesParRang;

        $seatsProgramme = [];
        foreach ($reservation->getProgramme()->getReservations() as $r) {
            foreach ($r->getSeats() as $s) {

                $seatsProgramme[] = $s;


            }
        }

        $nombreSieges = count($reservation->getSeats());





        return $this->render('reservation/seats.html.twig', [

            'id' => $id,
            'reservation' => $reservation,
            'room' => $room,
            'siegesParRang' => $siegesParRang,
            'rangees' => $rangees,
            'seatsProgramme' => $seatsProgramme,
            'numberWanted' => $numberWanted,
            'nombreSieges' => $nombreSieges,

        ]);
    }

    #[Route('/reservation/create/{id}', name: 'reservation.create', methods: ['POST'])]
    public function create(Programme $programme, BasketRepository $basketRepository, EntityManagerInterface $entityManager): Response {

        $filmSlug = $programme->getFilm()->getSlug();
        if ($programme->isClosed()) {
            $this->addFlash('danger', 'Cette séance est fermée à la réservation.');//Erreur si séance fermée
            return $this->redirectToRoute('film.show', ['slug' => $filmSlug]);
        }
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('danger', 'Vous devez être connecté pour réserver.');//En cas de déconnexion
            return $this->redirectToRoute('app_login');
        }

        $basket = $basketRepository->findBasketByUserId($user->getId());

        if (!$basket instanceof Basket) {
            $basket = new Basket();
            $basket->setDate(new \DateTime());
            $basket->setIsActive(true);
            $basket->setStatus('active');
            $basket->setUser($user);

            $entityManager->persist($basket);
        }

        foreach ($basket->getReservations() as $reservationPanier) {
            if ($reservationPanier->getProgramme() === $programme) {
                $reservation = $reservationPanier;
            }
        }
        if (!isset($reservation)) {
            $reservation = new Reservation();
            $reservation->setProgramme($programme);
            $reservation->setBasket($basket);
            $entityManager->persist($reservation);
            $entityManager->flush();
        }





        return $this->redirectToRoute('reservation_choose_seats', [
            'id' => $reservation->getId(),
        ]);
    }

    #[Route('/reservation/{id}', name: 'reservation.delete', methods: ['DELETE'])]
    public function delete(Reservation $reservation, Request $request, EntityManagerInterface $entityManager) {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'La réservation à bien été supprimé.');
        }
        return $this->redirectToRoute('basket.index');
    }

}
