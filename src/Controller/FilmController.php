<?php

namespace App\Controller;

use App\Entity\Film;
use App\Form\FilmType;
use App\Repository\FilmRepository;
use App\Repository\GenreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tools/film', name: 'admin.film')]
final class FilmController extends AbstractController {
    #[Route('/', name: '.index')]
    public function filmList(FilmRepository $filmRepository, GenreRepository $genreRepository): Response{
        $sortFilm = $this->container->get('request_stack')->getCurrentRequest()?->query->getString('sortFilm', 'id_asc');

        $films = match ($sortFilm) {
            'title_asc' => $filmRepository->findBy([], ['title' => 'ASC']),
            'title_desc' => $filmRepository->findBy([], ['title' => 'DESC']),
            'id_desc' => $filmRepository->findBy([], ['id' => 'DESC']),
            default => $filmRepository->findBy([], ['id' => 'ASC']),
        };


        $sortGenre = $this->container->get('request_stack')->getCurrentRequest()?->query->getString('sortGenre', 'id_asc');

        $genres = match ($sortGenre) {
            'genre_asc' => $genreRepository->findBy([], ['name' => 'ASC']),
            'genre_desc' => $genreRepository->findBy([], ['name' => 'DESC']),
            'id_desc' => $genreRepository->findBy([], ['id' => 'DESC']),
            default => $genreRepository->findBy([], ['id' => 'ASC']),
        };


        return $this->render('admin_pages/film/index.html.twig', [
            'films' => $films,
            'genres' => $genres,
            'sortFilm' => $sortFilm,
            'sortGenre' => $sortGenre,
        ]);
    }
/*
    #[Route('/film/{slug}', name: 'film.show', methods: ['GET'])]
    public function detail(Film $film): Response {
        return $this->render('film/detail.html.twig', []);
    }*/

    #[Route('/tools/film/create', name: '.create', methods: ['GET', 'POST'])]
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

    #[Route('/tools/film/edit/{id}', name: '.edit', methods: ['GET', 'POST'])]
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

    #[Route('/admin/film/{id}', name: '.delete', methods: ['DELETE'])]
    public function delete(Film $film, EntityManagerInterface $entityManager) {
        $entityManager->remove($film);
        $entityManager->flush();
        $this->addFlash('success', 'Le film à bien été supprimé.');
        return $this->redirectToRoute('admin.film.index');
    }
}
