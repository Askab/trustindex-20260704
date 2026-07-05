<?php

namespace App\Controller;

use App\Services\Interfaces\IReviewService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReviewListController extends AbstractController
{
    public function __construct(
        private IReviewService $reviewService
    ){}

    #[Route('/', name: 'app_review_list')]
    public function index(): Response
    {
        $reviews = $this->reviewService->getReviews();

        return $this->render('review_list/index.html.twig', [
            'controller_name' => 'ReviewListController',
            'reviews' => $reviews
        ]);
    }
}
