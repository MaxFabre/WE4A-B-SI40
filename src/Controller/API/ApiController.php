<?php

namespace App\Controller\API;

use App\Entity\Film;
use App\Entity\Person;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\FilmRepository;
use App\Repository\PersonRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'api')]
final class ApiController extends AbstractController {

    #[Route('/room/{id}', name: '.room.details', methods: ['GET'])]
    public function room(Room $room, SerializerInterface $serializer): JsonResponse {
        return $this->json($room, 200, [], ['groups' => ['room.details']]);
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

        //Récuperation des films
        $films = $filmRepository->findByTitle($query);
        $results = [];
        foreach ($films as $film) {
            $results[] = [
                'id' => $film['id'],
                'title' => $film['title'],
            ];
        }

        //Recupération des personnalités:
        $personalities = $personRepository->findByName($query);
        foreach ($personalities as $personality) {
            $results[] = [
                'id' => $personality['id'],
                'fullName' => $personality['firstname'].' '.$personality['lastname'],
            ];
        }

        return $this->json($results);
    }
}
