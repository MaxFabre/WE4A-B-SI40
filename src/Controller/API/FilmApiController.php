<?php

namespace App\Controller\API;

use App\Entity\Film;
use App\Repository\FilmRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/film', name: 'api.film')]
class FilmApiController extends AbstractController {

    #[Route('/', name: 'fetchAll', methods: ['GET'])]
    public function fetchAll(FilmRepository $filmRepository): JsonResponse {
        $films = $filmRepository->findAll();
        return $this->json($films, 200, [], ['groups' => ['film.details']]);
    }

    #[Route('/{slug}', name: '.fetchOne', methods: ['GET'])]
    public function fetchOne(String $slug, FilmRepository $filmRepository): JsonResponse {
        $film=$filmRepository->findBy(['slug' => $slug]);
        return $this->json($film, 200, [], ['groups' => ['film.details']]);
    }

    #[Route('/search', name: '.search', methods: ['GET'])]
    public function filmsSearch(Request $request, FilmRepository $repository): JsonResponse {
        $query = $request->query->get('q', '');

        $films = $repository->findByTitle($query);

        $results = [];
        foreach ($films as $film) {
            $results[] = [
                'id'   => $film['id'],
                'title' => $film['title'],
                'slug' => $film['slug'],
                'cover_path' => $film['cover_path'],
            ];
        }

        return new JsonResponse(['results' => $results]);
    }
}
