<?php

namespace App\Tests\Services;

use App\Entity\Review;
use App\Repository\Interfaces\IReviewRepository;
use App\Services\ReviewService;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

final class ReviewServiceTest extends TestCase
{
    private IReviewRepository&MockObject $repository;
    private ReviewService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(IReviewRepository::class);
        $this->service = new ReviewService($this->repository);
    }

    public function testGetReviewsDelegatesToRepositoryGetAll(): void
    {
        $reviews = [new Review(), new Review()];

        $this->repository->expects(self::once())
            ->method('getAll')
            ->willReturn($reviews);

        self::assertSame($reviews, $this->service->getReviews());
    }

    public function testGetReviewByIdDelegatesToRepositoryWithTheGivenId(): void
    {
        $review = new Review();

        $this->repository->expects(self::once())
            ->method('getReviewById')
            ->with(42)
            ->willReturn($review);

        self::assertSame($review, $this->service->getReviewById(42));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGetReviewByIdReturnsNullWhenRepositoryFindsNothing(): void
    {
        $this->repository->method('getReviewById')->willReturn(null);

        self::assertNull($this->service->getReviewById(1));
    }

    public function testGetReviewsByCompanyNameDelegatesToRepository(): void
    {
        $reviews = [new Review()];

        $this->repository->expects(self::once())
            ->method('findByCompanyName')
            ->with('Acme')
            ->willReturn($reviews);

        self::assertSame($reviews, $this->service->getReviewsByCompanyName('Acme'));
    }

    public function testCreateReviewSetsCreatedAndUpdatedAtBeforeSaving(): void
    {
        $review = new Review();

        $form = $this->createStub(FormInterface::class);
        $form->method('getData')->willReturn($review);

        $this->repository->expects(self::once())
            ->method('save')
            ->with($review, true);

        $before = new DateTimeImmutable('-1 second');
        $this->service->createReview($form);
        $after = new DateTimeImmutable('+1 second');

        self::assertNotNull($review->getCreatedAt());
        self::assertNotNull($review->getUpdatedAt());
        self::assertGreaterThan($before, $review->getCreatedAt());
        self::assertLessThan($after, $review->getCreatedAt());
        self::assertGreaterThan($before, $review->getUpdatedAt());
        self::assertLessThan($after, $review->getUpdatedAt());
    }

    public function testCreateReviewPassesTheFormDataToRepositorySave(): void
    {
        $review = new Review();
        $review->setCompanyName('Acme');

        $form = $this->createStub(FormInterface::class);
        $form->method('getData')->willReturn($review);

        $saved = null;
        $this->repository->expects(self::once())
            ->method('save')
            ->willReturnCallback(function ($entity) use (&$saved) {
                $saved = $entity;
            });

        $this->service->createReview($form);

        self::assertSame($review, $saved);
    }

    public function testGetStatisticsDelegatesToRepository(): void
    {
        $stats = [['company_name' => 'Acme', 'count' => 2, 'average' => 4.5]];

        $this->repository->expects(self::once())
            ->method('getStatistics')
            ->willReturn($stats);

        self::assertSame($stats, $this->service->getStatistics());
    }

    public function testSearchCompaniesDelegatesToRepositoryWithTheGivenQuery(): void
    {
        $stats = [['company_name' => 'Acme', 'count' => 1, 'average' => 5.0]];

        $this->repository->expects(self::once())
            ->method('searchCompanies')
            ->with('Ac')
            ->willReturn($stats);

        self::assertSame($stats, $this->service->searchCompanies('Ac'));
    }
}
