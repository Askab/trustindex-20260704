<?php

namespace App\Tests\Support;

use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * Minimal ManagerRegistry that always hands back a single, pre-built
 * ObjectManager. Lets repository tests run against a real Doctrine
 * EntityManager (e.g. sqlite in-memory) without booting the Symfony kernel.
 */
final class InMemoryManagerRegistry extends AbstractManagerRegistry
{
    public function __construct(private readonly ObjectManager $entityManager)
    {
        parent::__construct(
            'ORM',
            ['default' => 'default_connection'],
            ['default' => 'default_manager'],
            'default',
            'default',
        );
    }

    protected function getService(string $name): object
    {
        return $this->entityManager;
    }

    protected function resetService(string $name): void
    {
    }
}
