<?php

namespace App\Controller\API;

use App\Entity\Comment;
use App\Entity\CommentReport;
use App\Repository\CommentReportRepository;
use App\Repository\CommentRepository;
use App\Repository\FilmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/comment', name: 'api.comment')]
final class CommentApiController extends AbstractController{

    #[Route('', name: '.fetchAll', methods: ['GET'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function fetchAll(CommentRepository $commentRepository) {
        $comments = $commentRepository->findAll();
        return $this->json($comments, Response::HTTP_OK, [], ['groups' => ['comment.details']]);
    }

    #[Route('/publish', name: '.publish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function publish(Request $request, FilmRepository $filmRepository, EntityManagerInterface $entityManager): Response {
        //Initialisation:
        $data = json_decode($request->getContent(), true);
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $note = $data['note'] ?? null;
        $filmId = $data['film_id'] ?? null;

        //Vérification des champs vides:
        if (empty($title) || empty($content) || $note === null || empty($filmId)) {
            return $this->json([
                'success' => false,
                'message' => "Veuillez remplir tous les champs !"
            ], Response::HTTP_BAD_REQUEST);
        }

        //Récuperation du film:
        $film = $filmRepository->find((int) $filmId);
        if (!$film) {
            return $this->json([
                'success' => false,
                'message' => "Le film spécifié n'existe pas."
            ], Response::HTTP_NOT_FOUND);
        }

        //Création du commentaire:
        $comment = new Comment();
        $comment
            ->setAuthor($this->getUser())
            ->setTitle($title)
            ->setContent($content)
            ->setNote((int) $note)
            ->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))
            ->setUpdatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))
            ->setIsVisible(true)
            ->setFilm($film);

        //Enregistrement en DB:
        $entityManager->persist($comment);
        $entityManager->flush();

        return $this->json($comment, Response::HTTP_CREATED, [], ['groups' => ['comment.details']]);
    }

    #[Route('/{id}', name: '.fetch', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function fetch(Comment $comment): Response {
        return $this->json($comment, Response::HTTP_OK, [], ['groups' => ['comment.details']]);
    }

    #[Route('/{id}', name: '.delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Comment $comment, EntityManagerInterface $entityManager): Response {
        //Pour supprimer le commentaire on le passe juste en invisible:
        $comment->setIsVisible(false);

        //Enregistrement en DB:
        $entityManager->persist($comment);
        $entityManager->flush();

        //Retour avec succés:
        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}/report', name: '.report', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function report(Comment $comment, EntityManagerInterface $entityManager): Response {
        //Création du signalement:
        $report = new CommentReport();
        $report
            ->setComment($comment)
            ->setComplainant($this->getUser())
            ->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->setIsActive(true)
            ->setStatut("En attente");

        //Enregistrement en DB:
        $entityManager->persist($report);
        $entityManager->flush();

        //Retour avec succès:
        return $this->json([], Response::HTTP_CREATED);
    }

    #[Route('/reports', name: '.all-reports', methods: ['GET'])]
    #[isGranted('ROLE_MODERATOR')]
    public function fetchAllReports(CommentReportRepository $reportRepository): Response {
        $reports = $reportRepository->findAll();
        return $this->json($reports, Response::HTTP_OK, [], ['groups' => ['report.list']]);
    }

    #[Route('/reports/active', name: '.active-reports', methods: ['GET'])]
    #[isGranted('ROLE_MODERATOR')]
    public function fetchActiveReports(CommentReportRepository $reportRepository): Response {
        $reports = $reportRepository->findByComment();
        return $this->json($reports, Response::HTTP_OK, [], ['groups' => ['report.list']]);
    }

    #[Route('/reports/{id}', name: '.reports', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[isGranted('ROLE_MODERATOR')]
    public function fetchReports(Comment $comment, CommentReportRepository $reportRepository): Response {
        $reports = $reportRepository->findBy(["comment" => $comment]);
        return $this->json($reports, Response::HTTP_OK, [], ['groups' => ['report.details']]);
    }

    #[Route('/{id}/report/accept', name: 'report.accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function acceptReport(Comment $comment, CommentReportRepository $reportRepository, EntityManagerInterface $entityManager): Response {
        //Initialisation:
        $reports = $reportRepository->findBy(["comment" => $comment]);

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

        return $this->json([], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}/report/refuse', name: 'report.refuse', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function refuseReport(Comment $comment, CommentReportRepository $reportRepository, EntityManagerInterface $entityManager) {
        //Initialisation:
        $reports = $reportRepository->findBy(["comment" => $comment]);

        //Clôturer tous les signalements:
        foreach ($reports as $report) {
            $report->setIsActive(false);
            $report->setStatut("Refusé");
            $report->addModerator($this->getUser());

            //Pré-enrigstrement en DB:
            $entityManager->persist($report);
        }

        //Enregistrement en DB:
        $entityManager->flush();

        return $this->json([], Response::HTTP_ACCEPTED);
    }
}
