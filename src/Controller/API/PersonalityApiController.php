<?php

namespace App\Controller\API;

use App\Entity\Person;
use App\Repository\PersonRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/personality', name: 'api.personality')]
class PersonalityApiController extends AbstractController{

    #[Route('/search', name: '.search', methods: ['GET'])]
    public function search(Request $request, PersonRepository $repository): JsonResponse {
        $query = $request->query->get('q', '');

        $personalities = $repository->findByName($query);

        return $this->json($personalities, 200, [], ['groups' => ['personality.search']]);
    }

    #[Route('/', name: '.fetchAll', methods: ['GET'])]
    public function fetchAll(PersonRepository $personRepository): JsonResponse {
        $personalities = $personRepository->findAllPersonalities();
        return $this->json($personalities, 200, [], ['groups' => ['personality.details']]);
    }

    #[Route('/create', name: '.create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse {
        //Initialisation:
        $firstname = $request->request->get('firstname');
        $lastname = $request->request->get('lastname');
        $birthdateStr = $request->request->get('birthdate');
        $parisTimeZone = new \DateTimeZone("Europe/Paris");
        $now = new \DateTimeImmutable("now", $parisTimeZone);

        //Vérifications des champs vides:
        if (empty($firstname) || empty($lastname)) {
            return $this->json(['message' => 'Veuillez remplir tous les champs obligatoires.',], Response::HTTP_BAD_REQUEST);
        }

        //Person:
        $personality = new Person();
        $personality->setFirstname($firstname);
        $personality->setLastname($lastname);
        $personality->setUpdatedAt($now);
        $personality->setCreatedAt($now);

        if (!empty($birthdateStr)) {
            $personality->setBirthdate(new \DateTime($birthdateStr, $parisTimeZone));
        }

        //Gestion de l'image de profile par VichUploader:
        $photoFile = $request->files->get('photo');
        if ($photoFile) {
            $personality->setPhotoFile($photoFile);
        }

        try {
            $entityManager->persist($personality);
            $entityManager->flush();
            return new JsonResponse([
                'message' => 'Personnalité créée avec succès !'
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->json([
                'message' => 'Erreur lors de la création de la personnalité.'. $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: '.fetchOne', methods: ['GET'])]
    public function fetchOne(Person $person) {
        return $this->json($person, RESPONSE::HTTP_OK, [], ['groups' => ['personality.details']]);
    }

    #[Route('/{id}', name: '.update', methods: ['POST'])]
    #[IsGranted("ROLE_ADMIN")]
    public function update(Person $personality, Request $request, EntityManagerInterface $entityManager, PersonRepository $personalityRepository) {
        //Initialisation:
        $parisTimeZone = new \DateTimeZone("Europe/Paris");
        $now = new \DateTimeImmutable("now", $parisTimeZone);

        if ($request->request->has('firstname')) {
            $personality->setFirstname($request->request->get('firstname'));
        }

        if ($request->request->has('lastname')) {
            $personality->setLastname($request->request->get('lastname'));
        }

        if ($request->request->has('birthdate')) {
            $birthdateStr = $request->request->get('birthdate');
            if (!empty($birthdateStr)) {
                $personality->setBirthdate(new \DateTime($birthdateStr, $parisTimeZone));
            } else {
                $personality->setBirthdate(null);
            }
        }

        //Gestion de l'image automatique par VichUploader:
        if ($request->request->get('deletePhoto') === '1') {}
        $photoFile = $request->files->get('photo');
        if ($photoFile) {
            $personality->setPhotoFile($photoFile);
        }

        $personality->setUpdatedAt($now);

        //Enregistrement en DB:
        try {
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Personnalité mise à jour avec succès !'
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json([
                'message' => 'Erreur lors de la mise à jour de la personnalité.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: '.delete', methods: ['DELETE'])]
    #[IsGranted("ROLE_ADMIN")]
    public function delete(Person $person, EntityManagerInterface $entityManager): JsonResponse {
        $entityManager->remove($person);
        return $this->json([
                'message' => 'La personnalité à bien été supprimée.'
            ], RESPONSE::HTTP_OK);
    }
}
