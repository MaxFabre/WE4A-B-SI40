<?php

namespace App\Controller;

use App\Entity\CommentReport;
use App\Entity\User;
use App\Repository\CommentReportRepository;
use App\Repository\CommentRepository;
use App\Service\LogEntryLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function PHPUnit\Framework\equalTo;

#[Route('/tools/reports', name: 'admin.reports')]
final class CommentReportController extends AbstractController {
    #[Route('/active', name: '.index', methods: ['GET'])]
    public function index(CommentReportRepository $repository): Response {
        //Récuperation de tous les signalements groupés par commentaires:
        $comments = $repository->findByComment();

        return $this->render('admin_pages/comment/index.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/', name: '.list')]
    public function list(CommentReportRepository $repository): Response {
        $sort = $this->container->get('request_stack')->getCurrentRequest()?->query->getString('sort', 'id_asc');
        $state = $this->container->get('request_stack')->getCurrentRequest()?->query->getString('state', 'all');

        $reports = $repository->findByFilters($sort, $state);

        return $this->render('admin_pages/comment/list-reports.html.twig', [
            'reports' => $reports,
            'sort' => $sort,
            'state' => $state,
        ]);
    }

    #[Route('/{id}', name: '.show')]
    public function moderate(CommentRepository $commentRepository, CommentReportRepository $commentReportRepository, $id): Response {
        //Initialisation:
        $comment = $commentRepository->find($id);
        $reports = $commentReportRepository->findBy(["comment" => $comment]);

        return $this->render('admin_pages/comment/moderate.html.twig', [
            'comment' => $comment,
            'reports' => $reports,
            'dialogObjectId' => $comment->getId(),
            'dialogRoute' => 'admin.reports.moderate',
        ]);
    }

    #[Route('/delete/', name: '.moderate', methods: ['DELETE'])]
    public function delete(CommentRepository $commentRepository, CommentReportRepository $commentReportRepository, Request $request, EntityManagerInterface $entityManager, LogEntryLogger $logEntryLogger): Response {
        //Initialisation:
        $comment = $commentRepository->find($request->get('id'));
        $reports = $commentReportRepository->findBy(["comment" => $comment]);
        $currentUser = $this->getUser();
        $moderator = $currentUser instanceof User ? $currentUser : null;
        $commentAuthor = $comment?->getAuthor();
        $closedAt = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
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

            $reportList = array_map(
                static fn (CommentReport $report): array => [
                    'reportId' => $report->getId(),
                    'complainantId' => $report->getComplainant()?->getId(),
                    'complainantUsername' => $report->getComplainant()?->getUsername(),
                    'reportedAt' => $report->getCreatedAt()?->format(DATE_ATOM),
                    'status' => $report->getStatut(),
                ],
                $reports
            );

            try {
                $logEntryLogger->log(
                    $moderator?->getId(),
                    'Comment reports closed',
                    'success',
                    [
                        'decision' => 'Validé',
                        'commentId' => $comment?->getId(),
                        'commentTitle' => $comment?->getTitle(),
                        'commentContent' => $comment?->getContent(),
                        'commentAuthorId' => $commentAuthor?->getId(),
                        'commentAuthorUsername' => $commentAuthor?->getUsername(),
                        'reports' => array_values($reportList),
                        'moderators' => [
                            [
                                'id' => $moderator?->getId(),
                                'username' => $moderator?->getUsername(),
                            ],
                        ],
                        'closedAt' => $closedAt->format(DATE_ATOM),
                        'source' => 'web',
                    ]
                );
            } catch (\Throwable) {
            }

            $this->addFlash('success', 'Le commentaire à bien été supprimé.');
        }


        //Retour à la page d'index:
        return $this->redirectToRoute('admin.reports.index');
    }

    #[Route('/refuse/{id}', name: '.refuse', methods: ['POST'])]
    public function refuse(CommentRepository $commentRepository, CommentReportRepository $commentReportRepository, Request $request, EntityManagerInterface $entityManager, LogEntryLogger $logEntryLogger): Response {
        //Initialisation:
        $comment = $commentRepository->find($request->get('id'));
        $reports = $commentReportRepository->findBy(["comment" => $comment]);
        $currentUser = $this->getUser();
        $moderator = $currentUser instanceof User ? $currentUser : null;
        $commentAuthor = $comment?->getAuthor();
        $closedAt = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

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

        $reportList = array_map(
            static fn (CommentReport $report): array => [
                'reportId' => $report->getId(),
                'complainantId' => $report->getComplainant()?->getId(),
                'complainantUsername' => $report->getComplainant()?->getUsername(),
                'reportedAt' => $report->getCreatedAt()?->format(DATE_ATOM),
                'status' => $report->getStatut(),
            ],
            $reports
        );

        try {
            $logEntryLogger->log(
                $moderator?->getId(),
                'Comment reports closed',
                'success',
                [
                    'decision' => 'Refusé',
                    'commentId' => $comment?->getId(),
                    'commentTitle' => $comment?->getTitle(),
                    'commentContent' => $comment?->getContent(),
                    'commentAuthorId' => $commentAuthor?->getId(),
                    'commentAuthorUsername' => $commentAuthor?->getUsername(),
                    'reports' => array_values($reportList),
                    'moderators' => [
                        [
                            'id' => $moderator?->getId(),
                            'username' => $moderator?->getUsername(),
                        ],
                    ],
                    'closedAt' => $closedAt->format(DATE_ATOM),
                    'source' => 'web',
                ]
            );
        } catch (\Throwable) {
        }

        //Retour à la page d'index:
        return $this->redirectToRoute('admin.reports.index');
    }
}
