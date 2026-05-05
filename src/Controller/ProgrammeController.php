<?php

namespace App\Controller;

use App\Entity\Programme;
use App\Form\ProgrammeType;
use App\Repository\ProgrammeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/tools/programme', name: 'admin.programme')]
final class ProgrammeController extends AbstractController {

    #[Route('/', name: '.index')]
    public function programmeList(ProgrammeRepository $programmeRepository): Response {

        $sortProgramme = $this->container->get('request_stack')->getCurrentRequest()?->query->getString('sortProgramme', 'date_asc');

        $programmes = match ($sortProgramme) {
            'film_asc' => $programmeRepository->findByFilmTitle('ASC'),
            'film_desc' => $programmeRepository->findByFilmTitle('DESC'),
            'date_desc' => $programmeRepository->findBy([], ['date' => 'DESC']),
            default => $programmeRepository->findBy([], ['date' => 'ASC']),
        };

        return $this->render('admin_pages/programme/index.html.twig', [
            'controller_name' => 'ProgrammeController',
            'sortProgramme' => $sortProgramme,
            'programmes' => $programmes,
        ]);


    }
    #[Route('/create', name: '.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response {
        $programme = new Programme();
        $programmeForm = $this->createForm(ProgrammeType::class, $programme);

        $programmeForm->handleRequest($request);
        if ($programmeForm->isSubmitted() && $programmeForm->isValid()) {
            $programme->setIsClosed(false);
            //Enregistrement en db:
            $entityManager->persist($programme);
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'La programmation.');
            return $this->redirectToRoute('admin.programme.index');
        }
        return $this->render('admin_pages/programme/create.html.twig', [
            'programmeForm' => $programmeForm,
        ]);
    }

    #[Route('/edit/{id}', name: '.edit', methods: ['GET', 'POST'])]
    public function edit(Programme $programme, Request $request, EntityManagerInterface $entityManager): Response {
        $programmeForm = $this->createForm(ProgrammeType::class, $programme);
        $programmeForm->handleRequest($request);
        if ($programmeForm->isSubmitted() && $programmeForm->isValid()) {
            //Enregistrement en db:
            $entityManager->flush();

            //Redirection avec message:
            $this->addFlash('success', 'La programmation à bien été modifié.');
            return $this->redirectToRoute('admin.programme.index');
        }
        return $this->render('admin_pages/programme/edit.html.twig', [
            'programme' => $programme,
            'programmeForm' => $programmeForm->createView(),
        ]);
    }


    #[Route('/{id}', name: '.delete', methods: ['DELETE'])]
    public function delete(Programme $programme, EntityManagerInterface $entityManager) {
        $entityManager->remove($programme);
        $entityManager->flush();
        $this->addFlash('success', 'La programmation à bien été supprimé.');
        return $this->redirectToRoute('admin.programme.index');
    }
}
