<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController {
    #[Route('/account/{id}', name: 'account.index', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(User $user): Response {
        return $this->render('account/index.html.twig', [
            'controller_name' => 'AccountController',
            'user' => $user,
        ]);
    }
}
