<?php

namespace App\Controller;

use App\Entity\Film;
use App\Form\FilmType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FilmController extends AbstractController {
    #[Route('/film', name: 'film.index', methods: ['GET'])]
    public function index(): Response {
        return $this->render('film/index.html.twig', [
            'controller_name' => 'FilmController',
        ]);
    }
/*
    #[Route('/film/{slug}', name: 'film.show', methods: ['GET'])]
    public function detail(Film $film): Response {
        return $this->render('film/detail.html.twig', []);
    }*/

    #[Route('/tools/film/create', name: 'admin.film.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $film = new Film();
        $filmForm = $this->createForm(FilmType::class, $film);

        $filmForm->handleRequest($request);
        if ($filmForm->isSubmitted() && $filmForm->isValid()) {
            try {

                //Enregistrement en db:
                $entityManager->persist($film);
                $entityManager->flush();

                //Redirection avec message:
                $this->addFlash('success', 'Le film à bien été créé.');
                return $this->redirectToRoute('admin.film.index');
            }
            catch (\Doctrine\DBAL\Exception\DriverException $e) {
                // Problème de taille là
                $filmForm->addError(new FormError('Y a un problème quelque part dans les données...'));
            }
        }

        return $this->render('admin_pages/film/create.html.twig', [
            'filmForm' => $filmForm,
        ]);
    }

    #[Route('/tools/film/edit/{id}', name: 'admin.film.edit', methods: ['GET', 'POST'])]
    public function edit(Film $film, Request $request, EntityManagerInterface $entityManager): Response {
        $filmForm = $this->createForm(FilmType::class, $film);
        $filmForm->handleRequest($request);

        if ($filmForm->isSubmitted() && $filmForm->isValid()) {
            //Enregistrement en db:
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success','Le film à bien été modifié.');
            return $this->redirectToRoute('admin.film.index');
        }

        return $this->render('admin_pages/film/edit.html.twig', [
            'film' => $film,
            'filmForm' => $filmForm,
        ]);
    }

    #[Route('/admin/film/{id}', name: 'admin.film.delete', methods: ['DELETE'])]
    public function delete(Film $film, EntityManagerInterface $entityManager) {
        $entityManager->remove($film);
        $entityManager->flush();
        $this->addFlash('success', 'Le film à bien été supprimé.');
        return $this->redirectToRoute('admin.film.index');
    }
}
