<?php

namespace App\Tests\Repository;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Tests\Support\InMemoryManagerRegistry;
use DateTimeImmutable;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

/**
 * Runs against a real Doctrine EntityManager backed by an in-memory sqlite
 * database. getStatistics() builds a DQL query (GROUP BY / COUNT / AVG /
 * ORDER BY / LIKE) whose correctness can only be verified by executing it,
 * so mocking the QueryBuilder chain would not exercise the actual behaviour.
 */
final class ReviewRepositoryTest extends TestCase
{
    private EntityManager $entityManager;
    private ReviewRepository $repository;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfig(
            [dirname(__DIR__, 2) . '/src/Entity'],
            true,
        );
        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema([$this->entityManager->getClassMetadata(Review::class)]);

        $this->repository = new ReviewRepository(new InMemoryManagerRegistry($this->entityManager));
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
    }

    private function makeReview(string $company, int $rating): Review
    {
        $now = new DateTimeImmutable();

        return (new Review())
            ->setCompanyName($company)
            ->setRating($rating)
            ->setReviewText('Some review text')
            ->setAuthorEmail('reviewer@example.com')
            ->setCreatedAt($now)
            ->setUpdatedAt($now);
    }

    /** @param Review[] $reviews */
    private function persist(array $reviews): void
    {
        foreach ($reviews as $review) {
            $this->repository->save($review);
        }

        $this->entityManager->flush();
    }

    private function statsFor(array $stats, string $companyName): ?array
    {
        foreach ($stats as $row) {
            if ($row['company_name'] === $companyName) {
                return $row;
            }
        }

        return null;
    }

    // --- getAll -----------------------------------------------------------

    public function testGetAllReturnsEmptyArrayWhenNoReviewsExist(): void
    {
        self::assertSame([], $this->repository->getAll());
    }

    public function testGetAllReturnsEveryPersistedReview(): void
    {
        $this->persist([
            $this->makeReview('Acme', 5),
            $this->makeReview('Beta', 3),
        ]);

        $all = $this->repository->getAll();

        self::assertCount(2, $all);
        self::assertContainsOnlyInstancesOf(Review::class, $all);
    }

    // --- getReviewById ------------------------------------------------------

    public function testGetReviewByIdReturnsNullWhenNotFound(): void
    {
        self::assertNull($this->repository->getReviewById(999));
    }

    public function testGetReviewByIdReturnsTheMatchingReview(): void
    {
        $review = $this->makeReview('Acme', 4);
        $this->persist([$review]);

        $found = $this->repository->getReviewById($review->getId());

        self::assertNotNull($found);
        self::assertSame($review->getId(), $found->getId());
        self::assertSame('Acme', $found->getCompanyName());
    }

    // --- findByCompanyName --------------------------------------------------

    public function testFindByCompanyNameReturnsOnlyMatchingReviews(): void
    {
        $this->persist([
            $this->makeReview('Acme', 5),
            $this->makeReview('Acme', 3),
            $this->makeReview('Beta', 1),
        ]);

        $acmeReviews = $this->repository->findByCompanyName('Acme');

        self::assertCount(2, $acmeReviews);
        foreach ($acmeReviews as $review) {
            self::assertSame('Acme', $review->getCompanyName());
        }
    }

    public function testFindByCompanyNameReturnsEmptyArrayForUnknownCompany(): void
    {
        $this->persist([$this->makeReview('Acme', 5)]);

        self::assertSame([], $this->repository->findByCompanyName('Unknown'));
    }

    public function testFindByCompanyNameRequiresAnExactMatch(): void
    {
        $this->persist([$this->makeReview('Acme Inc', 5)]);

        self::assertSame([], $this->repository->findByCompanyName('Acme'));
    }

    // --- save / update -----------------------------------------------------

    public function testSaveWithoutFlushDoesNotPersistToTheDatabaseYet(): void
    {
        $review = $this->makeReview('Acme', 5);

        $this->repository->save($review, false);

        self::assertNull($review->getId());
    }

    public function testSaveWithFlushTruePersistsImmediately(): void
    {
        $review = $this->makeReview('Acme', 5);

        $this->repository->save($review, true);

        self::assertNotNull($review->getId());
        self::assertNotNull($this->repository->getReviewById($review->getId()));
    }

    public function testUpdatePersistsChangesToAnExistingEntity(): void
    {
        $review = $this->makeReview('Acme', 5);
        $this->persist([$review]);

        $review->setRating(2);
        $this->repository->update($review, true);
        $this->entityManager->clear();

        $reloaded = $this->repository->getReviewById($review->getId());

        self::assertSame(2, $reloaded->getRating());
    }

    // --- delete -------------------------------------------------------------

    public function testDeleteRemovesTheEntityOnceFlushed(): void
    {
        $review = $this->makeReview('Acme', 5);
        $this->persist([$review]);
        $id = $review->getId();

        $this->repository->delete($id);
        $this->entityManager->flush();

        self::assertNull($this->repository->getReviewById($id));
    }

    public function testDeleteWithAnUnknownIdIsANoop(): void
    {
        $this->repository->delete(999999);
        $this->entityManager->flush();

        self::assertCount(0, $this->repository->getAll());
    }

    // --- getStatistics -------------------------------------------------------
    //
    // The most important behaviour in the repository: grouping, counting,
    // averaging, ordering and searching are all expressed in a single DQL
    // query, so each aspect is covered independently below.

    public function testGetStatisticsReturnsEmptyArrayWhenThereAreNoReviews(): void
    {
        self::assertSame([], $this->repository->getStatistics());
    }

    public function testGetStatisticsReturnsOneRowPerDistinctCompany(): void
    {
        $this->persist([
            $this->makeReview('Acme', 5),
            $this->makeReview('Acme', 3),
            $this->makeReview('Beta', 4),
        ]);

        $stats = $this->repository->getStatistics();

        self::assertCount(2, $stats);
        self::assertNotNull($this->statsFor($stats, 'Acme'));
        self::assertNotNull($this->statsFor($stats, 'Beta'));
    }

    public function testGetStatisticsCountsReviewsPerCompany(): void
    {
        $this->persist([
            $this->makeReview('Acme', 5),
            $this->makeReview('Acme', 4),
            $this->makeReview('Acme', 3),
            $this->makeReview('Beta', 2),
        ]);

        $stats = $this->repository->getStatistics();

        self::assertSame(3, $this->statsFor($stats, 'Acme')['count']);
        self::assertSame(1, $this->statsFor($stats, 'Beta')['count']);
    }

    public function testGetStatisticsComputesTheAverageRatingPerCompany(): void
    {
        $this->persist([
            $this->makeReview('Acme', 5),
            $this->makeReview('Acme', 4),
            $this->makeReview('Acme', 3),
        ]);

        $stats = $this->repository->getStatistics();

        self::assertEqualsWithDelta(4.0, $this->statsFor($stats, 'Acme')['average'], 0.0001);
    }

    public function testGetStatisticsAverageHandlesNonTerminatingDecimals(): void
    {
        $this->persist([
            $this->makeReview('Acme', 1),
            $this->makeReview('Acme', 2),
            $this->makeReview('Acme', 2),
        ]);

        $stats = $this->repository->getStatistics();

        self::assertEqualsWithDelta(5 / 3, $this->statsFor($stats, 'Acme')['average'], 0.0001);
    }

    public function testGetStatisticsAverageEqualsRatingWhenOnlyOneReviewExists(): void
    {
        $this->persist([$this->makeReview('Acme', 3)]);

        $stats = $this->repository->getStatistics();

        self::assertSame(3.0, $this->statsFor($stats, 'Acme')['average']);
    }

    public function testGetStatisticsOrdersCompaniesByAverageDescending(): void
    {
        $this->persist([
            $this->makeReview('Low', 1),
            $this->makeReview('High', 5),
            $this->makeReview('Mid', 3),
        ]);

        $stats = $this->repository->getStatistics();

        self::assertSame(
            ['High', 'Mid', 'Low'],
            array_column($stats, 'company_name'),
        );
    }

    public function testGetStatisticsReturnsCountAsIntAndAverageAsFloat(): void
    {
        $this->persist([$this->makeReview('Acme', 5)]);

        $row = $this->statsFor($this->repository->getStatistics(), 'Acme');

        self::assertIsInt($row['count']);
        self::assertIsFloat($row['average']);
    }

    public function testGetStatisticsWithEmptySearchQueryReturnsAllCompanies(): void
    {
        $this->persist([
            $this->makeReview('Acme', 5),
            $this->makeReview('Beta', 3),
        ]);

        self::assertCount(2, $this->repository->getStatistics(''));
    }

    public function testGetStatisticsSearchFiltersByCompanyNameSubstring(): void
    {
        $this->persist([
            $this->makeReview('Acme', 5),
            $this->makeReview('Acme Corp', 4),
            $this->makeReview('Beta', 3),
        ]);

        $stats = $this->repository->getStatistics('Acme');

        self::assertCount(2, $stats);
        self::assertNotNull($this->statsFor($stats, 'Acme'));
        self::assertNotNull($this->statsFor($stats, 'Acme Corp'));
        self::assertNull($this->statsFor($stats, 'Beta'));
    }

    public function testGetStatisticsSearchMatchesRegardlessOfSubstringPosition(): void
    {
        $this->persist([
            $this->makeReview('Global Acme Holdings', 5),
            $this->makeReview('Beta', 3),
        ]);

        $stats = $this->repository->getStatistics('Acme');

        self::assertCount(1, $stats);
        self::assertSame('Global Acme Holdings', $stats[0]['company_name']);
    }

    public function testGetStatisticsSearchIsCaseInsensitive(): void
    {
        $this->persist([$this->makeReview('Acme', 5)]);

        $stats = $this->repository->getStatistics('ACME');

        self::assertCount(1, $stats);
        self::assertSame('Acme', $stats[0]['company_name']);
    }

    public function testGetStatisticsSearchReturnsEmptyArrayWhenNothingMatches(): void
    {
        $this->persist([$this->makeReview('Acme', 5)]);

        self::assertSame([], $this->repository->getStatistics('NoSuchCompany'));
    }

    public function testGetStatisticsSearchPreservesCountAndAverageForFilteredCompany(): void
    {
        $this->persist([
            $this->makeReview('Acme', 5),
            $this->makeReview('Acme', 3),
            $this->makeReview('Beta', 1),
        ]);

        $stats = $this->repository->getStatistics('Acme');

        self::assertCount(1, $stats);
        self::assertSame(2, $stats[0]['count']);
        self::assertEqualsWithDelta(4.0, $stats[0]['average'], 0.0001);
    }

    /**
     * Characterization test: the search term is passed through a plain
     * SQL LIKE with only '%' wrapped around it, so LIKE metacharacters
     * inside the user-supplied query are not escaped. A lone "_" therefore
     * acts as a single-character wildcard and matches every company name.
     * This documents current behaviour rather than an intended feature.
     */
    public function testGetStatisticsSearchDoesNotEscapeLikeWildcards(): void
    {
        $this->persist([
            $this->makeReview('A', 5),
            $this->makeReview('Beta', 3),
        ]);

        $stats = $this->repository->getStatistics('_');

        self::assertCount(2, $stats);
    }

    // --- searchCompanies -----------------------------------------------------

    public function testSearchCompaniesIsAnAliasForGetStatisticsWithTheSameQuery(): void
    {
        $this->persist([
            $this->makeReview('Acme', 5),
            $this->makeReview('Acme Corp', 4),
            $this->makeReview('Beta', 3),
        ]);

        self::assertSame(
            $this->repository->getStatistics('Acme'),
            $this->repository->searchCompanies('Acme'),
        );
    }
}
