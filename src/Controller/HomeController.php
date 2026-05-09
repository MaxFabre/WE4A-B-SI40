<?php

namespace App\Controller;

use App\Repository\CarouselItemRepository;
use App\Repository\FilmRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController {

    #[Route('/', name: 'home')]
    public function index(CarouselItemRepository $carouselItemRepository, FilmRepository $filmRepository): Response {
        $pinnedFilms = $carouselItemRepository->findBy([], ['position' => 'ASC']);
        $allFilms = $filmRepository->findAll();
        $films = [];

        foreach ($allFilms as $film) {
            foreach ($film->getProgrammes() as $programme){
                if (!$programme->isClosed() AND $programme->getDate()>date('Y-m-d H:i:s')){
                    $films[] = $film;
                    break;
                }
            }
        }


        return $this->render('home/index.html.twig', [
            'pinnedFilms' => $pinnedFilms,
            'allFilms' => $films,
        ]);
    }

}
