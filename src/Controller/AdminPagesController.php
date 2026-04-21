<?php

namespace App\Controller;

use App\Entity\Film;
use App\Repository\FilmRepository;
use App\Repository\GenderRepository;
use App\Repository\UserRepository;
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
    public function filmList(FilmRepository $filmRepository, GenderRepository $genderRepository): Response{
        $films = $filmRepository->findAll();
        $genders = $genderRepository->findAll();
        return $this->render('admin_pages/film/index.html.twig', [
            'films' => $films,
            'genders' => $genders,
        ]);
    }

    #[Route('/user', name: '.user.index')]
    public function userList(UserRepository $repository): Response{
        $users = $repository->findAll();
        return $this->render('admin_pages/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/logs', name: '.logs')]
    public function logs() {
        return $this->render('admin_pages/logs.html.twig', [
            //'logs' => $logs,
        ]);
    }
}
