<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tools/users', name: 'admin.user')]
final class UserController extends AbstractController {

    #[Route('/', name: '.index')]
    public function userList(UserRepository $repository): Response{
        $users = $repository->findAll();
        return $this->render('admin_pages/user/index.html.twig', [
            'users' => $users,
        ]);
    }
    #[Route('/create', name: '.create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user);

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
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Gestion des champs cachés:
            $user->getPerson()->setUpdatedAt(new \DateTimeImmutable());

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

    #[Route('/{id}', name: '.detlete', methods: ['DELETE'])]
    public function delete(User $user, Request $request, EntityManagerInterface $entityManager) {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'L utilisateur à bien été supprimé.');
        }
        return $this->redirectToRoute('home.index');
    }
}
