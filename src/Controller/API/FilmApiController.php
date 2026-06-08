<?php

namespace App\Controller\API;

use App\Entity\Film;
use App\Repository\FilmRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/film', name: 'api.film')]
class FilmApiController extends AbstractController {

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

    #[Route('/{id}', name: '.film.details', methods: ['GET'])]
    public function film(Film $film, SerializerInterface $serializer): JsonResponse {
        return $this->json($film, 200, [], ['groups' => ['film.details']]);
    }
}
