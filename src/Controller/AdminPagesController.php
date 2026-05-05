<?php

namespace App\Controller;

use App\Entity\Film;
use App\Repository\FilmRepository;
use App\Repository\GenreRepository;
use App\Repository\ProgrammeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Room;
use App\Repository\RoomRepository;
use Symfony\Component\HttpFoundation\Request;

#[Route('/tools', name: 'admin')]
final class AdminPagesController extends AbstractController {
    #[Route('/', name: '.index')]
    public function index(): Response{
        return $this->render('admin_pages/index.html.twig', [
            'controller_name' => 'AdminPagesController',
        ]);
    }





    #[Route('/logs', name: '.logs')]
    public function logs() {
        return $this->render('admin_pages/logs.html.twig', [
            //'logs' => $logs,
        ]);
    }
}
