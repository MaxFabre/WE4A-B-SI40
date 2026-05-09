<?php

namespace App\Controller;

use App\Entity\Basket;
use App\Repository\BasketRepository;
use App\Repository\ReservationRepository;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;


final class BasketController extends AbstractController
{
    #[Route('/basket', name: 'basket.index', methods: ['GET','POST'])]
    public function index(EntityManagerInterface $entityManager, BasketRepository $basketRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $basket = $basketRepository->findBasketByUserId($user->getId());

        if (!$basket instanceof Basket) {
            $basket = new Basket();
            $basket->setDate(new \DateTime());
            $entityManager->persist($basket);
            $entityManager->flush();
        }

        return $this->render('basket/index.html.twig', [
            'controller_name' => 'BasketController',
            'basket' => $basket,
        ]);
    }

    /*
        #[Route('/film/{slug}', name: 'film.show', methods: ['GET'])]
        public function detail(Film $film): Response {
            return $this->render('film/detail.html.twig', []);
        }*/

    #[Route('/basket/create', name: 'basket.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour créer un panier.');

            return $this->redirectToRoute('app_login');
        }

        $basket = new Basket();
        $basket->setDate(new \DateTime());
        $basket->setIsActive(true);
        $basket->setStatus('active');
        $basket->setUser($user);

        $entityManager->persist($basket);
        $entityManager->flush();

        return $this->redirectToRoute('basket.index');
    }

    #[Route('/basket/pay', name: 'basket.pay', methods: ['GET','POST'])]
    public function pay(Request $request, EntityManagerInterface $entityManager, BasketRepository $basketRepository): Response {
        $user = $this->getUser();
        $basket = $basketRepository->findBasketByUserId($user->getId());

        foreach ($basket->getReservations() as $reservation) {
            $reservation->setIsValidated(true);
        }

        $basket->setStatus('paid');
        $basket->setIsActive(false);

        $entityManager->flush();


        $this->addFlash('success', 'Votre panier a bien été payé.');

        return $this->redirectToRoute('basket.index');
    }

    #[Route('/basket/add/{id}', name: 'basket.add', methods: ['POST'])]
    public function add(Reservation $reservation, EntityManagerInterface $entityManager, BasketRepository $basketRepository): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $basket = $basketRepository->findBasketByUserId($user->getId());

        if (!$basket) {
            $basket = new Basket();
            $basket->setDate(new \DateTime());
            $basket->setIsActive(true);
            $basket->setStatus('active');
            $basket->setUser($user);

            $entityManager->persist($basket);
        }

        $reservation->setBasket($basket);

        $entityManager->flush();

        return $this->redirectToRoute('basket');
    }


}
