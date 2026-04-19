<?php

namespace App\Controller;

use App\Entity\Film;
use App\Repository\FilmRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tools', name: 'admin')]
final class AdminPagesController extends AbstractController {
    #[Route('/', name: '.index')]
    public function index(): Response{
        return $this->render('admin_pages/index.html.twig', [
            'controller_name' => 'AdminPagesController',
        ]);
    }

    #[Route('/film', name: '.film.index')]
    public function filmList(FilmRepository $repository): Response{
        $films = $repository->findAll();
        return $this->render('admin_pages/films.html.twig', [
            'films' => $films,
        ]);
    }
}
