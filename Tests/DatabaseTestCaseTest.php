<?php

namespace SymfonyDatabaseTest\Tests;

use PHPUnit\Framework\TestCase;
use SymfonyDatabaseTest\DatabaseTestCase;

class DatabaseTestCaseTest extends TestCase
{
    public function testInstanciation()
    {
        $this->assertInstanceOf(DatabaseTestCase::class, new DatabaseTestCase());
    }
}
