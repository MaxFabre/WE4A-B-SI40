<?php

namespace App\Controller;

use App\Repository\FilmRepository;
use App\Repository\PersonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController {
    #[Route('/search', name: 'search', methods: ['GET', 'POST'])]
    public function index(FilmRepository $filmRepository, PersonRepository $personRepository): Response {
        //Récuperation de la recherche:
        if (!isset($_POST['query'])) {
            $query = '';
        } else {
            $query = $_POST['query'];
        }

        //Recuperation des films:
        $films = $filmRepository->findByTitle($query);

        //Recuperation des personnailtés:
        $personalities = $personRepository->findByName($query);

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'films' => $films,
            'personalities' => $personalities,
        ]);
    }
}
