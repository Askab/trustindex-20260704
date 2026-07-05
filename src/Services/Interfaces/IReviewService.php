<?php

namespace App\Services\Interfaces;

use App\Entity\Review;

use Symfony\Component\Form\FormInterface;

interface IReviewService
{
    public function createReview(FormInterface $form): void;
}