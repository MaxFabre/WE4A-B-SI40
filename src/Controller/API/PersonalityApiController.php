<?php

namespace App\Controller\API;

use App\Entity\Person;
use App\Repository\PersonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/personality', name: 'api.personality')]
class PersonalityApiController extends AbstractController{

    #[Route('/search', name: '.search', methods: ['GET'])]
    public function search(Request $request, PersonRepository $repository): JsonResponse {
        $query = $request->query->get('q', '');

        $personalities = $repository->findByName($query);

        return $this->json($personalities, 200, [], ['groups' => ['personality.search']]);
    }

    #[Route('/{id}', name: '.fetchOne', methods: ['GET'])]
    public function fetchOne(Person $person) {
        return $this->json($person, 200, [], ['groups' => ['personality.details']]);
    }
}
