<?php

namespace App\Twig;

class StarRatingExtension extends \Twig\Extension\AbstractExtension
{
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('star_rating', [$this, 'getStarRating']),
        ];
    }

    public function getStarRating(int $rating): string
    {
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }
}