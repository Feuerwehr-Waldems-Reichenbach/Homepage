<?php

include 'rootpath.php';
include BASE_PATH . '/Private/Database/db_connect.php';

class DatabaseTest extends PHPUnit\Framework\TestCase
{
    public function testDatabaseConnection()
    {
        $this->assertNotNull($conn);
    }
}
