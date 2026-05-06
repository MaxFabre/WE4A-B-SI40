<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditAccountDataType;
use App\Form\EditAccountEmailType;
use App\Form\EditAccountPasswordType;
use App\Form\EditPersonPhotoType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/account', name: 'account')]
class AccountController extends AbstractController {
    #[Route('/{id}', name: '.index', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(User $user): Response {
        return $this->render('account/index.html.twig', [
            'controller_name' => 'AccountController',
            'user' => $user,
        ]);
    }

    /**
     * Fonction permettant aux utilisateurs de mettre à jour leurs profils:
     * @param User $user
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordHasherInterface $passwordHasher
     * @param Request $request
     * @return Response
     */
    #[Route('/{id}/edit', name: '.edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response {
        //Vérification de l'utilisateur:
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        if ($user->getId() !== $this->getUser()->getId()) {
            //Si l'utilisateur ne correspond pas en renvoie une erreur 403:
            throw $this->createAccessDeniedException();
        }

        //Chargement des formulaires:
        $personalDataFrom = $this->createForm(EditAccountDataType::class, $user);
        $photoForm = $this->createForm(EditPersonPhotoType::class, $user->getPerson());
        $emailForm = $this->createForm(EditAccountEmailType::class, $user);
        $passwordForm = $this->createForm(EditAccountPasswordType::class, $user);

        //Traitement des différents formulaires:
        $personalDataFrom->handleRequest($request);
        if ($personalDataFrom->isSubmitted() && $personalDataFrom->isValid()) {
            $user->getPerson()->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil à bien été mis à jour.');
            return $this->redirectToRoute('account.index', [
                'id' => $user->getId(),
            ]);
        }

        $photoForm->handleRequest($request);
        if ($photoForm->isSubmitted() && $photoForm->isValid()) {
            //La gestion des fichiers dont les photos de profile sont gérées automatiquement par VitchUploader:
            $user->getPerson()->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre photo de profil à bien été mis à jour.');
            return $this->redirectToRoute('account.index', [
                'id' => $user->getId(),
            ]);
        }

        $emailForm->handleRequest($request);
        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            //Vérification du mot de passe:
            if (!$passwordHasher->isPasswordValid($user, $emailForm->get('plainPassword')->getData())) {
                //Redirection avec échec:
                $this->addFlash('danger', 'Votre mot de passe n\'est pas valide.');
                return $this->redirectToRoute('account.index', [
                    'id' => $user->getId(),
                ]);
            }
            //Mise à jour de l'email:
            $user->setEmail($emailForm->get('email')->getData());

            //Mise à jour de updated at:
            $user->getPerson()->setUpdatedAt(new \DateTimeImmutable());

            //Mise à jour en DB:
            $entityManager->flush();

            //Redirection avec succès:
            $this->addFlash('danger', 'Votre adresse email à bien été mis à jour.');
            return $this->redirectToRoute('account.index', [
                'id' => $user->getId(),
            ]);
        }

        $passwordForm->handleRequest($request);
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            //Vérification du mot de passe:
            if (!$passwordHasher->isPasswordValid($user, $passwordForm->get('plainPassword')->getData())) {
                //Redirection avec échec:
                $this->addFlash('danger', 'Votre mot de passe n\'est pas valide.');
                return $this->redirectToRoute('account.index', [
                    'id' => $user->getId(),
                ]);
            }
            //Mise à jour du mot de passe:
            $newPassword = $passwordForm->get('newPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

            //Mise à jour de updated at:
            $user->getPerson()->setUpdatedAt(new \DateTimeImmutable());

            //Mise à jour en DB:
            $entityManager->flush();

            //Redirection avec succès:
            $this->addFlash('success', 'Votre mot de passe à bien été mis à jour.');
            return $this->redirectToRoute('account.index', [
                'id' => $user->getId(),
            ]);
        }

        //Génération du template:
        return $this->render('account/edit.html.twig', [
            'user' => $user,
            'personalDataFrom' => $personalDataFrom->createView(),
            'photoForm' => $photoForm->createView(),
            'emailForm' => $emailForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }
}
