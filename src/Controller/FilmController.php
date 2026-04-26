<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Film;
use App\Form\CommentType;
use App\Form\FilmType;
use App\Repository\CommentRepository;
use App\Repository\FilmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FilmController extends AbstractController {
    #[Route('/film', name: 'film.index', methods: ['GET'])]
    public function index(): Response {
        return $this->render('film/index.html.twig', [
            'controller_name' => 'FilmController',
        ]);
    }

    #[Route('/film/{slug}', name: 'film.show', methods: ['GET', 'POST'])]
    public function show(string $slug, FilmRepository $filmRepository, CommentRepository $commentRepository, Request $request, EntityManagerInterface $entityManager): Response {
        //Chargement du film et de ses commentaires:
        $film = $filmRepository->findBy(['slug' => $slug]);
        $comments = $commentRepository->findBy(['film' => $film]);

        //Formulaire de nouveau commentaire:
        $newComment = new Comment();
        $form = $this->createForm(CommentType::class, $newComment);

        //Publication du commentaire:
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                //Remplissage des champs cachés:
                $newComment->setFilm($film[0]);
                $newComment->setAuthor($this->getUser());
                $newComment->setCreatedAt(new \DateTimeImmutable());
                $newComment->setUpdatedAt(new \DateTimeImmutable());
                $newComment->setIsVisible(true);

                //Enregistrement en db:
                $entityManager->persist($newComment);
                $entityManager->flush();

                //Redirection avec message:
                $this->addFlash('success', 'Le commentaire à bien été créé.');
            }
            catch (\Doctrine\DBAL\Exception\DriverException $e) {
                // Problème de taille là
                $form->addError(new FormError('Y a un problème quelque part dans les données...'));
            }
        }

        //Chargement du template:
        return $this->render('film/show.html.twig', [
            'film' => $film[0],
            'comments' => $comments,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tools/film/create', name: 'admin.film.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $film = new Film();
        $filmForm = $this->createForm(FilmType::class, $film);

        $filmForm->handleRequest($request);
        if ($filmForm->isSubmitted() && $filmForm->isValid()) {
            try {

                //Enregistrement en db:
                $entityManager->persist($film);
                $entityManager->flush();

                //Redirection avec message:
                $this->addFlash('success', 'Le film à bien été créé.');
                return $this->redirectToRoute('admin.film.index');
            }
            catch (\Doctrine\DBAL\Exception\DriverException $e) {
                // Problème de taille là
                $filmForm->addError(new FormError('Y a un problème quelque part dans les données...'));
            }
        }

        return $this->render('admin_pages/film/create.html.twig', [
            'filmForm' => $filmForm,
        ]);
    }

    #[Route('/tools/film/edit/{id}', name: 'admin.film.edit', methods: ['GET', 'POST'])]
    public function edit(Film $film, Request $request, EntityManagerInterface $entityManager): Response {
        $filmForm = $this->createForm(FilmType::class, $film);
        $filmForm->handleRequest($request);

        if ($filmForm->isSubmitted() && $filmForm->isValid()) {
            //Enregistrement en db:
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success','Le film à bien été modifié.');
            return $this->redirectToRoute('admin.film.index');
        }

        return $this->render('admin_pages/film/edit.html.twig', [
            'film' => $film,
            'filmForm' => $filmForm,
        ]);
    }

    #[Route('/admin/film/{id}', name: 'admin.film.delete', methods: ['DELETE'])]
    public function delete(Film $film, Request $request, EntityManagerInterface $entityManager) {
        if ($this->isCsrfTokenValid('delete'.$film->getId(), $request->request->get('_token'))) {
            $entityManager->remove($film);
            $entityManager->flush();
            $this->addFlash('success', 'Le film à bien été supprimé.');
        }
        return $this->redirectToRoute('admin.film.index');
    }
}
