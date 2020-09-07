<?php

namespace SymfonyDatabaseTest;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DatabaseTestCase extends WebTestCase
{
    /** @var KernelBrowser */
    protected static $client;

    /**
     * @param class-string<mixed> $className
     * @return ObjectRepository
     */
    protected static function getRepository(string $className): ObjectRepository
    {
        return static::getEntityManager()->getRepository($className);
    }

    /**
     * @return EntityManagerInterface
     */
    protected static function getEntityManager(): EntityManagerInterface
    {
        $container = static::getClient()->getContainer();
        static::assertNotNull($container);
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $container->get('doctrine');
        static::assertNotNull($managerRegistry);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $managerRegistry->getManager();
        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        return $entityManager;
    }

    /**
     * @return KernelBrowser
     */
    protected static function getClient(): KernelBrowser
    {
        return static::$client;
    }

    /**
     * @param array<mixed> $options
     * @param array<mixed> $server
     * @return KernelBrowser
     */
    protected static function createClient(array $options = [], array $server = []): KernelBrowser
    {
        return static::getClient();
    }

    protected function setUp(): void
    {
        parent::setUp();
        static::$client = parent::createClient();
        static::$client->disableReboot();

        if (static::isPersistentDatabase()) {
            static::dropDatabase();
            static::createDatabase();
        }
        static::createDatabaseSchema();
    }

    /**
     * @return bool
     */
    private static function isPersistentDatabase(): bool
    {
        $params = static::getEntityManager()->getConnection()->getParams();
        return !empty($params['path']) || !empty($params['dbname']);
    }

    private static function dropDatabase(): void
    {
        $connection = static::getEntityManager()->getConnection();
        try {
            $connection->getSchemaManager()->dropDatabase(
                $connection->getDatabasePlatform()->quoteSingleIdentifier($connection->getDatabase())
            );
        } catch (DBALException $e) {
        }
    }

    private static function createDatabase(): void
    {
        $connection = static::getEntityManager()->getConnection();
        $params = $connection->getParams();
        unset($params['dbname'], $params['path'], $params['url']);

        /** @phpstan-ignore-next-line */
        $tmpConnection = DriverManager::getConnection($params);

        $tmpConnection->getSchemaManager()->createDatabase(
            $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($connection->getDatabase())
        );
    }

    private static function createDatabaseSchema(): void
    {
        (new SchemaTool(static::getEntityManager()))->createSchema(
            static::getEntityManager()->getMetadataFactory()->getAllMetadata()
        );
    }

    protected function tearDown(): void
    {
        if (static::isPersistentDatabase()) {
            static::dropDatabase();
        }
        static::shutdownKernel();
        parent::tearDown();
    }

    private static function shutdownKernel(): void
    {
        static::$client->getKernel()->shutdown();
        if (isset(static::$booted)) {
            static::$booted = false;
        }
    }
}
