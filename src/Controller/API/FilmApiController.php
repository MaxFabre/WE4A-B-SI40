<?php

namespace App\Controller\API;

use App\Entity\CarouselItem;
use App\Entity\Film;
use App\Repository\CarouselItemRepository;
use App\Repository\FilmRepository;
use App\Repository\GenreRepository;
use App\Repository\PersonRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function PHPUnit\Framework\isEmpty;

#[Route('/api/film', name: 'api.film')]
class FilmApiController extends AbstractController {

    #[Route('/create', name: '.create', methods: ['POST'])]
    #[IsGranted("ROLE_FUND_MANAGER")]
    public function create(?Film $film, Request $request, EntityManagerInterface $em, GenreRepository $genreRepository, PersonRepository $personRepository): JsonResponse {
        //Initialisation:
        $title = $request->request->get('title');
        $slug = $request->request->get('slug');
        $description = $request->request->get('description');
        $price = $request->request->get('price');
        $duration = $request->request->get('duration');
        $film = new Film();

        //Vérification des formulaires vides:
        if (!isset($title) || !isset($slug) || !isset($description) || !isset($price) || !isset($duration)) {
            return new JsonResponse(['message' => 'Veuillez remplir tout les champs texte.'], RESPONSE::HTTP_BAD_REQUEST);
        }

        //Remplissage des champs simples:
        $film
            ->setTitle($title)
            ->setSlug($slug)
            ->setDescription($description)
            ->setPrice((float)$price)
            ->setDuration($duration)
            ->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));

        //Gestion de l'affiche par VichUploader:
        $uploadedFile = $request->files->get('cover');
        if ($uploadedFile) {
            $film->setCoverFile($uploadedFile);
        }
        $film->setUpdatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));

        //Gestion des champs relationels:
        $genreIds = json_decode($request->request->get('genres', '[]'), true);
        if (is_array($genreIds)) {
            foreach ($genreIds as $genreId) {
                $genre = $genreRepository->find($genreId);
                if ($genre) {
                    $film->addGenres($genre);
                }
            }
        }

        $actorIds = json_decode($request->request->get('actors', '[]'), true);
        if (is_array($actorIds)) {
            foreach ($actorIds as $actorId) {
                $actor = $personRepository->find($actorId);
                if ($actor) {
                    $film->addActor($actor);
                }
            }
        }

        $directorIds = json_decode($request->request->get('directors', '[]'), true);
        if (is_array($directorIds)) {
            foreach ($directorIds as $directorId) {
                $director = $personRepository->find($directorId);
                if ($director) {
                    $film->addDirector($director);
                }
            }
        }

        try {
            //Enregistrement en DB:
            $em->persist($film);
            $em->flush();

            return new JsonResponse([
                'message' => 'Le film a été créé avec succès !',
            ], RESPONSE::HTTP_OK);
        } catch (UniqueConstraintViolationException $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de l\'enregistrement',
            ], RESPONSE::HTTP_BAD_REQUEST);
        }
    }

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

    #[Route('/{slug}', name: '.fetchOneBySlug', methods: ['GET'])]
    public function fetchOneBySlug(String $slug, FilmRepository $filmRepository): JsonResponse {
        $film = $filmRepository->findBy(['slug' => $slug]);
        return $this->json($film, 200, [], ['groups' => ['film.details']]);
    }

    #[Route('/{id}', name: '.update', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[isGranted("ROLE_FUND_MANAGER")]
    public function update(Film $film, Request $request, EntityManagerInterface $em, GenreRepository $genreRepository, PersonRepository $personRepository): JsonResponse {
        //Initialisation:
        $title = $request->request->get('title');
        $slug = $request->request->get('slug');
        $description = $request->request->get('description');
        $price = $request->request->get('price');
        $duration = $request->request->get('duration');
        $deleteCover = $request->request->get('deleteCover') === '1';

        if (!$film) {
            return new JsonResponse([
                'message' => 'Le film demandé n\'existe pas.'
            ], Response::HTTP_NOT_FOUND);
        }



        //Vérification des formulaires vides:
        if (!isset($title) || !isset($slug) || !isset($description) || !isset($price) || !isset($duration)) {
            return new JsonResponse(['message' => 'Veuillez remplir tout les champs texte.'], RESPONSE::HTTP_BAD_REQUEST);
        }

        //Mise à jour des champs simples:
        $film
            ->setTitle($title)
            ->setSlug($slug)
            ->setDescription($description)
            ->setDuration($duration)
            ->setPrice((float)$price);

        //Gestion de l'affiche apr VichUploader:
        if ($request->request->get('deleteCover') === '1' || $request->request->get('deleteCover') === 'true') {
            $film->setCoverFile(null);
            $film->setCoverPath(null);
        }
        $uploadedFile = $request->files->get('cover');
        if ($uploadedFile) {
            $film->setCoverFile($uploadedFile);
        }
        $film->setUpdatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));

        //Mise à jour des champs relationels (Nettoyage + Réassignation):
        $film->getGenres()->clear();
        $genreIds = json_decode($request->request->get('genres', '[]'), true);
        if (is_array($genreIds)) {
            foreach ($genreIds as $genreId) {
                $genre = $genreRepository->find($genreId);
                if ($genre) {
                    $film->addGenres($genre);
                }
            }
        }

        $film->getActors()->clear();
        $actorIds = json_decode($request->request->get('actors', '[]'), true);
        if (is_array($actorIds)) {
            foreach ($actorIds as $actorId) {
                $actor = $personRepository->find($actorId);
                if ($actor) {
                    $film->addActor($actor);
                }
            }
        }

        $film->getDirectors()->clear();
        $directorIds = json_decode($request->request->get('directors', '[]'), true);
        if (is_array($directorIds)) {
            foreach ($directorIds as $directorId) {
                $director = $personRepository->find($directorId);
                if ($director) {
                    $film->addDirector($director);
                }
            }
        }

        try {
            //Enregistrement en DB:
            $em->persist($film);
            $em->flush();

            return new JsonResponse([
                'message' => 'Le film a été modifié avec succès !',
            ], Response::HTTP_OK);

        } catch (UniqueConstraintViolationException $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la mise à jour.',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/fetch', name: '.fetchOneById', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function fetchOneById(Film $film, FilmRepository $filmRepository): JsonResponse {
        return $this->json($film, RESPONSE::HTTP_OK, [], ['groups' => ['film.details']]);
    }

    #[Route('/{id}/programmes', name: '.programmes', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function programmes(Film $film): JsonResponse {
        $programmes = $film->getProgrammes();
        return $this->json($programmes, 200, [], ['groups' => ['programme.details']]);
    }
}
