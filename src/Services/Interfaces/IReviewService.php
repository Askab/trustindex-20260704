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
     * Get reviews by company name
     * @param string $companyName
     * @return array
     */
    public function getReviewsByCompanyName(string $companyName): array;

    /**
     * Create a review
     * @param FormInterface $form
     * @return void
     */
    public function createReview(FormInterface $form): void;

    /**
     * Get statistics
     * Grouped by company
     * @return array
     */
    public function getStatistics(): array;

    /**
     * Search companies
     * @param string $q
     * @return array
     */
    public function searchCompanies(string $q): array;
}