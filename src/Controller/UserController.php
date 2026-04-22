<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users', name: 'admin.user')]
final class UserController extends AbstractController {
    #[Route('/create', name: '.create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Enregistrement en db:
            $entityManager->persist($user);
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'L\'utilisateur à bien été créé.');
            return $this->redirectToRoute('admin.user.index');
        }

        return $this->render('admin_pages/user/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id}', name: '.edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager): Response {
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Enregistrement en db:
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success','L\'utilisateur à bien été modifié.');
            return $this->redirectToRoute('admin.user.index');
        }

        return $this->render('admin_pages/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/admin/film/{id}', name: '.delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $entityManager) {
        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash('success', 'L\'utilisateur à bien été supprimé.');
        return $this->redirectToRoute('admin.user.index');
    }
}
