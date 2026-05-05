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
//        $req = $request->request->get('selectPremiereClasse');
//        //TROUVER COMMENT AJOUTER DES SIEGES
//        $reservation->($req);
//        $em->flush();

        return $this->redirectToRoute('basket.index'); // ou la page actuelle
    }

    #[Route('/reservation/choose-seats/{id}', name: 'reservation_choose_seats', methods: ['GET','POST'])]
    public function chooseSeats($id, Request $request, Reservation $reservation, EntityManagerInterface $em)
    {
        return $this->render('reservation/seats.html.twig', [
            'id' => $id,
            'reservation' => $reservation,
        ]);
    }
}
