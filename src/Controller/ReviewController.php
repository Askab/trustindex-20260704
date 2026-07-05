<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReviewController extends AbstractController
{
    #[Route('/review', name: 'app_review', methods: ['GET'])]
    public function index(): Response
    {
        $review = new Review();

        $form = $this->createForm(ReviewType::class, $review);

        return $this->render('review/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
