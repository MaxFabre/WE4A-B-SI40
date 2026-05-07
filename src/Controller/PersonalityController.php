<?php

namespace App\Controller;

use App\Entity\Person;
use App\Form\PersonalityType;
use App\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PersonalityController extends AbstractController {
    #[Route('/personality/{id}', name: 'personality.show', methods: ['GET'])]
    public function show(Person $person): Response {
        //Récuperation des films réalisé:
        $directedFilms = $person->getDirectedFilms();

        //Récuperation des films joué:
        $playedFilms = $person->getPlayedFilms();

        return $this->render('personality/show.html.twig', [
            'personality' => $person,
            'directedFilms' => $directedFilms,
            'playedFilms' => $playedFilms,
        ]);
    }

    #[Route('tools/personality/', name: 'admin.personality.index', methods: ['GET'])]
    public function list(PersonRepository $repository): Response {
        //Récuperation des personalités:
        $personalities = $repository->findAllPersonalities();

        //Génération du template:
        return $this->render('admin_pages/personality/index.html.twig', [
            'personalities' => $personalities,
        ]);
    }

    #[Route('tools/personality/create', name: 'admin.personality.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $personality = new Person();
        $form = $this->createForm(PersonalityType::class, $personality);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Champs cachés:
            $personality->setCreatedAt(new \DateTimeImmutable());
            $personality->setUpdatedAt(new \DateTimeImmutable());

            //Enregistrement en db:
            $entityManager->persist($personality);
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'Le film à bien été créé.');
            return $this->redirectToRoute('admin.personality.index');
        }

        return $this->render('admin_pages/personality/create.html.twig', [
            'personality' => $personality,
            'form' => $form->createView(),
        ]);
    }

    #[Route('tools/personality/edit/{id}/', name: 'admin.personality.edit')]
    public function edit(Person $person, Request $request, EntityManagerInterface $entityManager): Response {
        $form = $this->createForm(PersonalityType::class, $person);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Champs cachés:
            $person->setCreatedAt(new \DateTimeImmutable());
            $person->setUpdatedAt(new \DateTimeImmutable());

            //Enregistrement en db:
            $entityManager->persist($person);
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'Le film à bien été modifié.');
            return $this->redirectToRoute('admin.personality.index');
        }

        return $this->render('admin_pages/personality/edit.html.twig', [
            'personality' => $person,
            'form' => $form->createView(),
        ]);
    }

    #[Route('tools/personality/delete/{id}', name: 'admin.personality.delete')]
    public function delete(Person $person, EntityManagerInterface $entityManager): Response {
        $entityManager->remove($person);
        $entityManager->flush();
        $this->addFlash('success', 'La personnalité à bien été supprimé.');
        return $this->redirectToRoute('admin.personality.index');
    }
}
