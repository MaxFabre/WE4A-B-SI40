<?php

namespace App\Controller;

use App\Entity\Film;
use App\Repository\FilmRepository;
use App\Repository\PersonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'api')]
final class ApiController extends AbstractController {
    #[Route('/film/search', name: '.film.search', methods: ['GET'])]
    public function filmsSearch(Request $request, FilmRepository $repository): JsonResponse {
        $query = $request->query->get('q', '');

        $films = $repository->findByTitle($query);

        $results = [];
        foreach ($films as $film) {
            $results[] = [
                'id'   => $film['id'],
                'text' => $film['title'],
            ];
        }

        return new JsonResponse(['results' => $results]);
    }

    #[Route('/film/{id}', name: '.film.details', methods: ['GET'])]
    public function film(Film $film, SerializerInterface $serializer): JsonResponse {
        return $this->json($film, 200, [], ['groups' => ['film.details']]);
    }

    #[Route('/personalities/search', name: '.personality.search', methods: ['GET'])]
    public function personalitySearch(Request $request, PersonRepository $repository): JsonResponse {
        $query = $request->query->get('q', '');

        $personalities = $repository->findByName($query);

        $results = [];
        foreach ($personalities as $personality) {
            $results[] = [
                'id'   => $personality['id'],
                'text' => $personality['firstname'].' '.$personality['lastname'],
            ];
        }

        return new JsonResponse(['results' => $results]);
    }

    #[Route('/search', name: '.search', methods: ['GET', 'POST'])]
    public function search(Request $request, PersonRepository $personRepository, FilmRepository $filmRepository): JsonResponse {
        if (isset($_POST['query'])) {
            $query = $_POST['query'];
        } elseif ($request->query->get('q', '') !== null) {
            $query = $request->query->get('q');
        } else {
            $query = '';
        }

        //Récuperationd des films
        $films = $filmRepository->findByTitle($query);
        $results = [];
        foreach ($films as $film) {
            $results[] = [
                'id' => $film['id'],
                'title' => $film['title'],
            ];
        }

        //Recuépartion des personnalités:
        $prsonalities = $personRepository->findByName($query);
        foreach ($prsonalities as $personality) {
            $results[] = [
                'id' => $personality['id'],
                'fullName' => $personality['firstname'].' '.$personality['lastname'],
            ];
        }

        return $this->json($results);
    }
}
