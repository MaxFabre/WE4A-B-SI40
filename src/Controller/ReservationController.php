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
        // Ajouter les nouveaux sièges
        foreach ($seatIds as $id) {
            $seat = $em->getRepository(\App\Entity\Seat::class)->find($id);
            if ($seat) {
                $reservation->addSeat($seat);
            }
        }
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
}
