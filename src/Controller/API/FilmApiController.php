<?php

namespace App\Controller\API;

use App\Entity\CarouselItem;
use App\Entity\Film;
use App\Repository\CarouselItemRepository;
use App\Repository\FilmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/film', name: 'api.film')]
class FilmApiController extends AbstractController {

    #[Route('/', name: 'fetchAll', methods: ['GET'])]
    public function fetchAll(FilmRepository $filmRepository): JsonResponse {
        $films = $filmRepository->findAll();
        return $this->json($films, 200, [], ['groups' => ['film.details']]);
    }

    #[Route('/search', name: '.search', methods: ['GET'])]
    public function filmsSearch(Request $request, FilmRepository $repository): JsonResponse {
        $query = $request->query->get('q', '');

        $films = $repository->findByTitle($query);

        return $this->json($films, 200, [], ['groups' => ['film.search']]);
    }

    #[Route('/pined', name: '.pined.fetch', methods: ['GET'])]
    public function getPinedFilm(CarouselItemRepository $carouselItemRepository) {
        $items = $carouselItemRepository->findAll();
        return $this->json($items, 200, [], ['groups' => ['film.pined']]);
    }

    #[Route('/pined', name: '.pined.update', methods: ['POST'])]
    #[IsGranted('ROLE_FUND_MANAGER')]
    public function setPinedFilm(CarouselItemRepository $carouselItemRepository, FilmRepository $filmRepository, Request $request, EntityManagerInterface $entityManager): JsonResponse {
        //Initialisation:
        $data = json_decode($request->getContent(), true);

        //Vérification que la requête n'est pas vide:
        if (!$data || !is_array($data)) {
            return new JsonResponse(['error' => 'Données invalides ou manquantes.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            //On supprime l'ancien carousel:
            $anciensItems = $carouselItemRepository->findAll();
            foreach ($anciensItems as $ancienItem) {
                $entityManager->remove($ancienItem);
            }
            $entityManager->flush();

            //Enregistrement des nouveaux éléments:
            foreach ($data as $pinedFilmData) {
                $filmId = $pinedFilmData['film']['id'] ?? null;
                $position = $pinedFilmData['position'] ?? null;
                if (!$filmId || $position === null) {
                    continue;
                }
                $film = $filmRepository->find($filmId);
                if ($film) {
                    $carouselItem = new CarouselItem();
                    $carouselItem->setFilm($film);
                    $carouselItem->setPosition((int)$position);

                    $entityManager->persist($carouselItem);
                }
            }

            //Enregistrement en DB:
            $entityManager->flush();

            return new JsonResponse(['success' => "Les films épinglés ont bien été mis à jour."], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de l\'enregistrement.',
                'details' => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/reservable', name: '.reservable', methods: ['GET'])]
    public function getreservableFilms(FilmRepository $filmRepository): JsonResponse {
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

        return $this->json($films, 200, [], ['groups' => ['film.pined']]);
    }

    #[Route('/{slug}', name: '.fetchOne', methods: ['GET'])]
    public function fetchOne(String $slug, FilmRepository $filmRepository): JsonResponse {
        $film = $filmRepository->findBy(['slug' => $slug]);
        return $this->json($film, 200, [], ['groups' => ['film.details']]);
    }
}
