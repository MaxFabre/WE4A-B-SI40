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

    #[Route('/test', name: '.test')]
    public function test() {
        return $this->render('test/index.html.twig');
    }

    #[Route('/film/search', name: '.film.search', methods: ['GET'])]
    public function filmSearch(Request $request ,FilmRepository $repository): JsonResponse {
        //Préparation de la requête:
        $query = $request->query->get('q', '');

        //Recherche des films correspondant à la chaîne de caractères reçue:
        $films = $repository->findByTitle($query);

        //Récuperation des résultats:
        $results = [];
        foreach ($films as $film) {
            $results[] = [
                'id' => $film['id'],
                'title' => $film['title'],
            ];
        }

        //Retour des résultats au format json:
        return $this->json($results);
    }

    #[Route('/film/{id}', name: '.film.details', methods: ['GET'])]
    public function film(Film $film, SerializerInterface $serializer): JsonResponse {
        return $this->json($film, 200, [], ['groups' => ['film.details']]);
    }

    #[Route('/personalities/search', name: '.personality.search', methods: ['GET'])]
    public function personalitySearch(Request $request, PersonRepository $repository): JsonResponse {
        // 1. On récupère 'query' pour correspondre au JS (ou on change le JS)
        $query = $request->query->get('query', '');

        $personalities = $repository->findByName($query);

        $results = [];
        foreach ($personalities as $personality) {
            $results[] = [
                'id'    => $personality['id'],
                // 'value' est la clé standard pour l'affichage dans Tokenfield
                'value' => $personality['firstname'].' '.$personality['lastname'],
                'label' => $personality['firstname'].' '.$personality['lastname']
            ];
        }

        return new JsonResponse($results);
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
