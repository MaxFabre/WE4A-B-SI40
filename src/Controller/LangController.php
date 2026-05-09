<?php

namespace App\Controller;

use App\Entity\Lang;
use App\Form\LangType;
use App\Repository\LangRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('tools/lang', name: 'admin.lang')]
final class LangController extends AbstractController {

    #[Route('/', name: '.index', methods: ['GET'])]
    public function index(LangRepository $repository): Response {
        //Récuperations des langues:
        $langs = $repository->findAll();

        return $this->render('admin_pages/lang/index.html.twig', [
            'langs' => $langs,
        ]);
    }

    #[Route('/create', name: '.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $lang = new Lang();
        $form = $this->createForm(LangType::class, $lang);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Enregistrement en db:
            $entityManager->persist($lang);
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'La langue de film à bien été créé.');
            return $this->redirectToRoute('admin.lang.index');
        }
        return $this->render('admin_pages/lang/form.html.twig', [
            'form' => $form,
            'title' => 'Ajout d\'une langue',
        ]);
    }

    #[Route('/edit/{id}', name: '.edit', methods: ['GET', 'POST'])]
    public function edit(Lang $lang, Request $request, EntityManagerInterface $entityManager): Response {
        $form = $this->createForm(LangType::class, $lang);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Enregistrement en db:
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'La langue de film à bien été modifié.');
            return $this->redirectToRoute('admin.lang.index');
        }
        return $this->render('admin_pages/lang/form.html.twig', [
            'genre' => $lang,
            'form' => $form->createView(),
            'title' => 'Modifier '.$lang->getName(),
        ]);
    }

    #[Route('/delete/{id}', name: '.delete', methods: ['DELETE'])]
    public function delete(Lang $lang, Request $request, EntityManagerInterface $entityManager): Response {
        if ($this->isCsrfTokenValid('delete'.$lang->getId(), $request->request->get('_token'))) {
            $entityManager->remove($lang);
            $entityManager->flush();
            $this->addFlash('success', 'La langue de film à bien été supprimée.');
        }
        return $this->redirectToRoute('admin.lang.index');
    }
}
