<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Services\Interfaces\IReviewService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ReviewController extends AbstractController
{

    public function __construct(
        protected IReviewService $reviewService
    ) {}

    #[Route('/review/success', name: 'task_success', methods: ['GET'])]
    public function taskSuccess(): Response
    {
        return $this->render('review/success.html.twig');
    }

    #[Route('/review', name: 'app_review', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $review = new Review();

        $form = $this->createForm(ReviewType::class, $review);

        $form->handleRequest($request);

        /**
         * Handle form submit
         */
        if ($form->isSubmitted()) {

            if ($form->isValid()) {
                
                $this->reviewService->createReview($form);

                return $this->redirectToRoute('task_success');
            } else {
                $this->addFlash('error', 'Please check your form');
            }
        }

        return $this->render('review/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}