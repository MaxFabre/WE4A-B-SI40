<?php

namespace App\Controller;

use App\Entity\Gender;
use App\Form\GenderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tools/gender', name: 'admin.gender')]
final class GenderController extends AbstractController {
    #[Route('/', name: '.index', methods: ['GET'])]
    public function list(): Response {
        return $this->render('admin_pages/gender/index.html.twig', [
            'controller_name' => 'GenderController',
        ]);
    }

    #[Route('/create', name: '.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $gender = new Gender();
        $genderForm = $this->createForm(GenderType::class, $gender);

        $genderForm->handleRequest($request);
        if ($genderForm->isSubmitted() && $genderForm->isValid()) {
            //Enregistrement en db:
            $entityManager->persist($gender);
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'Le genre de film à bien été créé.');
            return $this->redirectToRoute('admin.film.index');
        }
        return $this->render('admin_pages/gender/create.html.twig', [
            'genderForm' => $genderForm,
        ]);
    }

    #[Route('/edit/{id}', name: '.edit', methods: ['GET', 'POST'])]
    public function edit(Gender $gender, Request $request, EntityManagerInterface $entityManager): Response {
        $genderForm = $this->createForm(GenderType::class, $gender);
        $genderForm->handleRequest($request);
        if ($genderForm->isSubmitted() && $genderForm->isValid()) {
            //Enregistrement en db:
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'Le genre de film à bien été modifié.');
            return $this->redirectToRoute('admin.film.index');
        }
        return $this->render('admin_pages/gender/edit.html.twig', [
            'gender' => $gender,
            'genderForm' => $genderForm->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: '.delete', methods: ['DELETE'])]
    public function delete(Gender $gender, Request $request, EntityManagerInterface $entityManager): Response {
        $entityManager->remove($gender);
        $entityManager->flush();
        $this->addFlash('success', 'Le genre de film à bien été supprimé.');
        return $this->redirectToRoute('admin.film.index');
    }
}
