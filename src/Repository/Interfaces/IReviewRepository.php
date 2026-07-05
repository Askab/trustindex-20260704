<?php

namespace App\Repository\Interfaces;

use App\Entity\Review;

interface IReviewRepository {
    public function getAll();

    public function getReviewById(int $id);

    public function findByCompanyName(string $companyName): array;

    public function save(Review $entity, bool $flush = false);

    public function update(Review $entity, bool $flush = false);

    public function delete(int $id);

    public function getStatistics(string $searchQuery = '');
    
    public function searchCompanies(string $q);
}