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
    public function filmSearch(Request $request ,FilmRepository $repository): JsonResponse {
        //Préparation de la requête:
        $query = $request->query->get('q', '');

        //Recherche des films correspondant à la chaîne de caractères reçue:
        $films = $repository->createQueryBuilder('f')
            ->where('LOWER(f.title) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        //Récuperation des résultats:
        $results = [];
        foreach ($films as $film) {
            $results[] = [
                'id' => $film->getId(),
                'title' => $film->getTitle(),
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
        //Préparation de la requête:
        $query = $request->query->get('q', '');

        //Recherche des films correspondant à la chaîne de caractères reçue:
        $qb = $repository->createQueryBuilder('p');
        $qb->where('LOWER(p.firstname) LIKE LOWER(:query) OR LOWER(p.lastname) LIKE LOWER(:query)')
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        'SELECT u.id FROM App\Entity\User u WHERE u.person = p'
                    )
                )
            )
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10);

        //Récuperation des résultats:
        $personalities = $qb->getQuery()->getResult();
        $results = array_map(static function ($personality) {
            return [
                'id' => $personality->getId(),
                'fullName' => $personality->getFullName(),
            ];
        }, $personalities);

        //Retour des résultats au format json:
        return new JsonResponse($results);
    }
}
