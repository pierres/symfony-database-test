<?php

namespace SymfonyDatabaseTest\Tests;

use PHPUnit\Framework\TestCase;
use SymfonyDatabaseTest\DatabaseTestCase;

class DatabaseTestCaseTest extends TestCase
{
    public function testInstantiation(): void
    {
        $databaseTestCase = new DatabaseTestCase(DatabaseTestCase::class);
        $this->assertObjectHasProperty('client', $databaseTestCase);
    }
}
