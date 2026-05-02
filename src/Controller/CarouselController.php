<?php

namespace App\Controller;

use App\Entity\CarouselItem;
use App\Form\CarouselType;
use App\Repository\CarouselItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function Adminer\first;

#[Route('/tools/carousel', name: 'admin.carousel')]
final class CarouselController extends AbstractController {

    #[Route('/', name: '.index')]
    public function carousel(CarouselItemRepository $repository, Request $request, EntityManagerInterface $entityManager): Response {
        $films = $repository->findBy([], ['position' => 'ASC']);
        $item = new CarouselItem();
        $form = $this->createForm(CarouselType::class, $item);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {

                //Enregistrement en db:
                $entityManager->persist($item);
                $entityManager->flush();

                //Redirection avec message:
                $this->addFlash('success', 'Le film à bien été créé.');
                return $this->redirectToRoute('admin.carousel.index');
            }
            catch (\Doctrine\DBAL\Exception\DriverException $e) {
                // Problème de taille là
                $form->addError(new FormError('Y a un problème quelque part dans les données...'));
            }
        }

        return $this->render('admin_pages/carousel/index.html.twig', [
            'form' => $form->createView(),
            'films' => $films,
        ]);
    }

    #[Route('/up/{id}/', name: '.up')]
    public function up(CarouselItem $item, CarouselItemRepository $repository, EntityManagerInterface $entityManager): Response {
        return $this->changePosition($item, $repository, $entityManager, 1);
    }

    #[Route('/down/{id}/', name: '.down')]
    public function down(CarouselItem $item, CarouselItemRepository $repository, EntityManagerInterface $entityManager) {
        return $this->changePosition($item, $repository, $entityManager, -1);
    }

    private function changePosition(CarouselItem $item, CarouselItemRepository $repository, EntityManagerInterface $entityManager, int $modifier) {
        //Initialisation:
        $currentPosition = $item->getPosition();
        $newPosition = $currentPosition + $modifier;

        //Cible:
        $destItem = $repository->findOneBy(['position' => $newPosition]);
        if (!$destItem) {
            return $this->redirectToRoute('admin.carousel.index');
        }

        //Modification des postions:
        $destItem->setPosition($currentPosition);
        $item->setPosition($newPosition);

        //Enregistrement en DB:
        $entityManager->flush();

        //Redirection vers la page index:
        return $this->redirectToRoute('admin.carousel.index');
    }

    #[Route('/{id}', name: '.delete')]
    public function delete(CarouselItem $item, Request $request, EntityManagerInterface $entityManager): Response {
        $form = $this->createForm(CarouselType::class, $item);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->remove($item);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin.carousel.index');
    }
}
