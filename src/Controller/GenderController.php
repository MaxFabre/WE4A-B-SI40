<?php

namespace App\Controller;

use App\Entity\Gender;
use App\Form\GenderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GenderController extends AbstractController {
    #[Route('/gender', name: 'gender.index', methods: ['GET'])]
    public function list(): Response {
        return $this->render('gender/index.html.twig', [
            'controller_name' => 'GenderController',
        ]);
    }

    #[Route('/gender/create', name: 'gender.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $gender = new Gender();
        $genderForm = $this->createForm(GenderType::class, $gender);

        $genderForm->handleRequest($request);
        if ($genderForm->isSubmitted() && $genderForm->isValid()) {
            //Enregistrement en db:
            $entityManager->persist($gender);
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'Le film à bien été créé.');
            return $this->redirectToRoute('gender.index');
        }
        return $this->render('gender/create.html.twig', [
            'genderForm' => $genderForm,
        ]);
    }
}
