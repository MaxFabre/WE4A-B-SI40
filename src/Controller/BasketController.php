<?php

namespace App\Controller;

use App\Entity\Basket;
use App\Repository\ReservationRepository;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BasketController extends AbstractController
{
    #[Route('/basket', name: 'basket.index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('basket/index.html.twig', [
            'controller_name' => 'BasketController',
        ]);
    }

    /*
        #[Route('/film/{slug}', name: 'film.show', methods: ['GET'])]
        public function detail(Film $film): Response {
            return $this->render('film/detail.html.twig', []);
        }*/

//    #[Route('/tools/basket/create', name: 'admin.basket.create', methods: ['GET', 'POST'])]
//    public function create(Request $request, EntityManagerInterface $entityManager, Reservation $reservation): Response {
//        $basket = new Basket();
////        $filmForm = $this->createForm(FilmType::class, $film);
////
////        $filmForm->handleRequest($request);
////        if ($filmForm->isSubmitted() && $filmForm->isValid()) {
////            try {
////
////                //Enregistrement en db:
////                $entityManager->persist($film);
////                $entityManager->flush();
////
////                //Redirection avec message:
////                $this->addFlash('success', 'Le film à bien été créé.');
////                return $this->redirectToRoute('admin.film.index');
////            }
////            catch (\Doctrine\DBAL\Exception\DriverException $e) {
////                // Problème de taille là
////                $filmForm->addError(new FormError('Y a un problème quelque part dans les données...'));
////            }
//
////        }
//
////        return $this->render('admin_pages/film/create.html.twig', [
////            'filmForm' => $filmForm,
////        ]);
//        return $this->redirectToRoute('admin.basket.index'); //en attendant
//    }

//    #[Route('/tools/film/edit/{id}', name: 'admin.film.edit', methods: ['GET', 'POST'])]
//    public function edit(Film $film, Request $request, EntityManagerInterface $entityManager): Response {
//        $filmForm = $this->createForm(FilmType::class, $film);
//        $filmForm->handleRequest($request);
//
//        if ($filmForm->isSubmitted() && $filmForm->isValid()) {
//            //Enregistrement en db:
//            $entityManager->flush();
//
//            //Redirection avec message:
//            $this->addFlash('success','Le film à bien été modifié.');
//            return $this->redirectToRoute('admin.film.index');
//        }
//
//        return $this->render('admin_pages/film/edit.html.twig', [
//            'film' => $film,
//            'filmForm' => $filmForm,
//        ]);
//    }

//    #[Route('/admin/basket/{id}', name: 'admin.basket.delete', methods: ['DELETE'])]
//    public function delete(Basket $basket, EntityManagerInterface $entityManager) {
//        $entityManager->remove($basket);
//        $entityManager->flush();
//        $this->addFlash('success', 'Le panier à bien été supprimé.');
//        return $this->redirectToRoute('admin.basket.index');
//    }

    #[Route('/basket/add/{id}', name: 'basket.add', methods: ['POST'])]
    public function add(Reservation $reservation, EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $basket = $user->getBasket();

        if (!$basket) {
            $basket = new Basket();
            $basket->setUser($user);
            $entityManager->persist($basket);
        }

        $reservation->setBasket($basket);

        $entityManager->flush();

        return $this->redirectToRoute('basket');
    }


}
