<?php

namespace App\Services\Interfaces;

use App\Entity\Review;

use Symfony\Component\Form\FormInterface;

interface IReviewService
{
    /**
     * Get all the reviews
     * @return array
     */
    public function getReviews(): array;

    /**
     * Get a Review by ID
     * @param int $id
     * @return Review|null
     */
    public function getReviewById(int $id): ?Review;

    /**
     * Create a review
     * @param FormInterface $form
     * @return void
     */
    public function createReview(FormInterface $form): void;
}