<?php

namespace App\Controller;

use App\Entity\CommentReport;
use App\Repository\CommentReportRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/comment', name: 'admin.comment-reports')]
final class CommentReportController extends AbstractController {
    #[Route('/', name: '.index')]
    public function index(CommentReportRepository $repository): Response {
        //Récuperation de tous les signalements groupés par commentaires:
        $comments = $repository->findByComment();

        return $this->render('admin_pages/comment/index.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/report/{id}', name: '.show')]
    public function show(CommentRepository $commentRepository, CommentReportRepository $commentReportRepository, $id): Response {
        //Initialisation:
        $comment = $commentRepository->find($id);
        $reports = $commentReportRepository->findBy(["comment" => $comment]);

        return $this->render('admin_pages/comment/show.html.twig', [
            'comment' => $comment,
            'reports' => $reports,
            'dialogObjectId' => $comment->getId(),
            'dialogRoute' => 'admin.comment-reports.moderate',
        ]);
    }

    #[Route('/moderate/', name: '.moderate', methods: ['POST'])]
    public function moderate(CommentRepository $commentRepository, CommentReportRepository $commentReportRepository, Request $request, EntityManagerInterface $entityManager): Response {
        //Initialisation:
        $comment = $commentRepository->find($request->get('id'));
        $reports = $commentReportRepository->findBy(["comment" => $comment]);

        //Soft delete:
        $comment->SetIsVisible(false);

        //Clôturer tous les signalements:
        foreach ($reports as $report) {
            $report->setIsActive(false);
            $report->setStatut("Validé");
            $report->addModerator($this->getUser());

            //Pré-enregistrement en DB:
            $entityManager->persist($report);
        }

        //Enregistrement en DB:
        $entityManager->persist($comment);
        $entityManager->flush();

        //Retour à la page d'index:
        return $this->redirectToRoute('admin.comment-reports.index');
    }

    #[Route('/refuse/{id}', name: '.refuse', methods: ['POST'])]
    public function refuse(CommentRepository $commentRepository, CommentReportRepository $commentReportRepository, Request $request, EntityManagerInterface $entityManager): Response {
        //Initialisation:
        $comment = $commentRepository->find($request->get('id'));
        $reports = $commentReportRepository->findBy(["comment" => $comment]);

        //Clôturer tous les signalements:
        foreach ($reports as $report) {
            $report->setIsActive(false);
            $report->setStatut("Refusé");
            $report->addModerator($this->getUser());

            //Pré-enrigstrement en DB:
            $entityManager->persist($report);
        }

        //Enregistrement en DB:
        $entityManager->persist($comment);
        $entityManager->flush();

        //Retour à la page d'index:
        return $this->redirectToRoute('admin.comment-reports.index');
    }
}
