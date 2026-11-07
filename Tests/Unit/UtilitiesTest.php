<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

class UtilitiesTest extends Unit
{
    private function appPath(): string
    {
        return dirname(__DIR__, 2);
    }

    public function testSchemaPathRejectsEmptyVersion(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be empty');
        \Utilities::getSchemaFilePath($this->appPath(), '');
    }

    public function testSchemaPathRejectsTraversal(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('traversal not allowed');
        \Utilities::getSchemaFilePath($this->appPath(), '../etc/passwd');
    }

    public function testSchemaPathRejectsInvalidFormat(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid migration path format');
        \Utilities::getSchemaFilePath($this->appPath(), 'invalid_format.sql');
    }

    public function testSchemaPathRejectsMissingFile(): void
    {
        $this->expectException(\Exception::class);
        \Utilities::getSchemaFilePath($this->appPath(), '99_nope/create_nope.sql');
    }

    public function testSchemaPathResolvesRealMigration(): void
    {
        $path = \Utilities::getSchemaFilePath($this->appPath(), '00_bedrock/create_users.sql');
        $this->assertSame(realpath($this->appPath() . '/db_schemas/00_bedrock/create_users.sql'), $path);
    }

    public function testRandomStringHasRequestedLengthAndAlphabet(): void
    {
        $s = \Utilities::randomString(32, 'ab');
        $this->assertSame(32, strlen($s));
        $this->assertMatchesRegularExpression('/^[ab]{32}$/', $s);
    }
}
