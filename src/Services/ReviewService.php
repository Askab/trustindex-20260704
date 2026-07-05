<?php

namespace App\Services;

use App\Services\Interfaces\IReviewService;
use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;

use Symfony\Component\Form\FormInterface;

class ReviewService implements IReviewService
{

    public function __construct(
        protected ReviewRepository $reviewRepository, 
    ) {}

    public function createReview(FormInterface $form): void
    {
        /**
         * @var Review
         */
        $review = $form->getData();

        // Set createdAt and updatedAt
        $review->setCreatedAt(new \DateTimeImmutable());
        $review->setUpdatedAt(new \DateTimeImmutable());

        $this->reviewRepository->save($review, true);
    }
}
