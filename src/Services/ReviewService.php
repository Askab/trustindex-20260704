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

    /**
     * Get all the reviews
     * @return array
     */
    public function getReviews(): array
    {
        return $this->reviewRepository->getAll();
    }

    /**
     * Get a Review by ID
     * @param int $id
     * @return Review|null
     */
    public function getReviewById(int $id): ?Review
    {
        return $this->reviewRepository->getReviewById($id);
    }

    /**
     * Create a review
     * @param FormInterface $form
     * @return void
     */
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

    /**
     * Get statistics
     * Grouped by company
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->reviewRepository->getStatistics();
    }
}
