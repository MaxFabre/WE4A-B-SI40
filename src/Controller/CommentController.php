<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\CommentReport;
use App\Entity\Film;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Flow\FormFlowInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController {
    #[Route('/comment/{id}', name: 'comment.delete', methods: ['DELETE'])]
    public function delete(Comment $comment, Request $request, EntityManagerInterface $entityManager): Response {
        //Initialisation:
        $filmSlug = $comment->getFilm()->getSlug();

        //Vérification des droits:
        if ($this->getUser() == $comment->getAuthor() || $this->isGranted('ROLE_MODERATOR')) {

            //Vérification du formulaire:
            if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {

                //Suppression en DB:
                $entityManager->remove($comment);
                $entityManager->flush();
                $this->addFlash('success', 'Le commentaire à bien été supprimé.');
            }
        }

        //Retour à la page du film:
        return $this->redirectToRoute('film.show', ['slug' => $filmSlug]);
    }

    #[Route('/comment/report/{id}', name: 'comment.report', methods: ['POST'])]
    public function report(Comment $comment, Request $request, EntityManagerInterface $entityManager): Response {
        //Verification de connexion:
        if ($this->isCsrfTokenValid('report'.$comment->getId(), $request->request->get('_token'))) {
            //Initialisation:
            $report = new CommentReport();

            //Remplissage des champs du signalement:
            $report->setComment($comment);
            $report->setComplainant($this->getUser());
            $report->setCreatedAt(new \DateTimeImmutable());
            $report->setIsActive(true);
            $report->setStatut("En attente");

            //Enregistrement en DB:
            $entityManager->persist($report);
            $entityManager->flush();
        }

        //Retour vers la page du film:
        return $this->redirectToRoute('film.show', ['slug' => $comment->getFilm()->getSlug()]);
    }
}
