<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Form\GenreType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tools/genre', name: 'admin.genre')]
final class GenreController extends AbstractController {
    #[Route('/', name: '.index', methods: ['GET'])]
    public function list(): Response {
        return $this->render('admin_pages/genre/index.html.twig', [
            'controller_name' => 'GenreController',
        ]);
    }

    #[Route('/create', name: '.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $genre = new Genre();
        $genreForm = $this->createForm(GenreType::class, $genre);

        $genreForm->handleRequest($request);
        if ($genreForm->isSubmitted() && $genreForm->isValid()) {
            //Enregistrement en db:
            $entityManager->persist($genre);
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'Le genre de film à bien été créé.');
            return $this->redirectToRoute('admin.film.index');
        }
        return $this->render('admin_pages/genre/create.html.twig', [
            'genreForm' => $genreForm,
        ]);
    }

    #[Route('/edit/{id}', name: '.edit', methods: ['GET', 'POST'])]
    public function edit(Genre $genre, Request $request, EntityManagerInterface $entityManager): Response {
        $genreForm = $this->createForm(GenreType::class, $genre);
        $genreForm->handleRequest($request);
        if ($genreForm->isSubmitted() && $genreForm->isValid()) {
            //Enregistrement en db:
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'Le genre de film à bien été modifié.');
            return $this->redirectToRoute('admin.film.index');
        }
        return $this->render('admin_pages/genre/edit.html.twig', [
            'genre' => $genre,
            'genreForm' => $genreForm->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: '.delete', methods: ['DELETE'])]
    public function delete(Genre $genre, Request $request, EntityManagerInterface $entityManager): Response {
        $entityManager->remove($genre);
        $entityManager->flush();
        $this->addFlash('success', 'Le genre de film à bien été supprimé.');
        return $this->redirectToRoute('admin.film.index');
    }
}
