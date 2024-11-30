<?php

namespace SymfonyDatabaseTest;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DatabaseTestCase extends WebTestCase
{
    protected static KernelBrowser $client;

    /**
     * @param class-string<object> $className
     * @return ObjectRepository<object>
     */
    protected static function getRepository(string $className): ObjectRepository
    {
        return static::getEntityManager()->getRepository($className);
    }

    protected static function getEntityManager(): EntityManagerInterface
    {
        $container = static::getClient()->getContainer();
        $managerRegistry = $container->get('doctrine');
        static::assertInstanceOf(ManagerRegistry::class, $managerRegistry);
        $entityManager = $managerRegistry->getManager();
        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        return $entityManager;
    }

    /**
     * @param AbstractBrowser<Request, Response>|null $newClient
     */
    protected static function getClient(?AbstractBrowser $newClient = null): KernelBrowser
    {
        return $newClient instanceof KernelBrowser ? $newClient : static::$client;
    }

    /**
     * @param array<string, string> $options
     * @param array<string, string> $server
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

        if (self::isPersistentDatabase()) {
            self::dropDatabase();
            self::createDatabase();
        }
        self::createDatabaseSchema();
    }

    private static function isPersistentDatabase(): bool
    {
        $params = static::getEntityManager()->getConnection()->getParams();
        return !empty($params['path']) || !empty($params['dbname']);
    }

    private static function dropDatabase(): void
    {
        $connection = static::getEntityManager()->getConnection();
        $params = $connection->getParams();
        static::assertArrayHasKey('dbname', $params);
        $dbname =  $params['dbname'];
        static::assertIsString($dbname);

        try {
            $connection->createSchemaManager()->dropDatabase(
                $connection->getDatabasePlatform()->quoteSingleIdentifier($dbname)
            );
        } catch (\Exception $e) {
        }
    }

    private static function createDatabase(): void
    {
        // @see Doctrine\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand
        $connection = static::getEntityManager()->getConnection();
        $params = $connection->getParams();
        static::assertArrayHasKey('dbname', $params);
        $dbname =  $params['dbname'];
        static::assertIsString($dbname);
        // @phpstan-ignore-next-line
        unset($params['dbname'], $params['path'], $params['url']);
        // @phpstan-ignore-next-line
        $tmpConnection = DriverManager::getConnection($params);

        $tmpConnection->createSchemaManager()->createDatabase(
            $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($dbname)
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
        if (self::isPersistentDatabase()) {
            self::dropDatabase();
        }
        self::shutdownKernel();
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
