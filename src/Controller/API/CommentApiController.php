<?php

namespace App\Controller\API;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\FilmRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function PHPUnit\Framework\isEmpty;

#[Route('/api/comment', name: 'api.comment')]
final class CommentApiController extends AbstractController{

    #[Route('/publish', name: '.publish', methods: ['POST'])]
    //#[IsGranted('ROLE_USER')]
    public function publish(Request $request, FilmRepository $filmRepository) {
        //Initialisation:
        $title = trim($request->request->get('title', ''));
        $content = trim($request->request->get('content', ''));
        $note = trim($request->request->get('note', ''));
        $filmId = trim($request->request->get('film_id', ''));

        //Vérification des champs vides:
        /*if ($apiToken == null || $title == null || $content == null || $note == null || $filmId == null) {
            return $this->json([
                'success' => false,
                'message' => "Veuillez remplir tous les champs!"
            ], Response::HTTP_BAD_REQUEST);
        }*/

        dd($request);

        //Création du commentaire:
        $comment = new Comment();
        $comment
            ->setAuthor($author)
            ->setTitle($title)
            ->setContent($content)
            ->setNote($note)
            ->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))
            ->setUpdatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))
            ->setIsVisible(true)
            ->setFilm($filmRepository->find($filmId));

        return $this->json($comment, 200, [], ['groups' => ['comment.details']]);
    }

    #[Route('/{id}', name: '.fetch', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function fetch() {

    }

    #[Route('/{id}', name: '.delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete() {

    }

    #[Route('/{id}/report', name: '.report', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function report() {

    }
}
