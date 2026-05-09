<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('tools/lang', name: 'admin.lang')]
final class LangController extends AbstractController {

    #[Route('/', name: '.index', methods: ['GET'])]
    public function index(): Response {
        return $this->render('lang/index.html.twig', [
            'controller_name' => 'LangController',
        ]);
    }
}
