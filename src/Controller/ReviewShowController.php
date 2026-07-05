<?php

namespace App\Controller;

use App\Services\Interfaces\IReviewService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReviewShowController extends AbstractController
{
    public function __construct(
        protected IReviewService $reviewService
    ){}

    #[Route(
        '/review/{id}/show', 
        name: 'app_review_show', 
        methods: ['GET'], 
        requirements: ['id' => '\d+']
    )]
    public function index(int $id): Response
    {

        $review = $this->reviewService->getReviewById($id);

        if (!$review) {
            return $this->redirectToRoute('app_review_list');
        }

        return $this->render('review_show/index.html.twig', [
            'review' => $review
        ]);
    }
}
