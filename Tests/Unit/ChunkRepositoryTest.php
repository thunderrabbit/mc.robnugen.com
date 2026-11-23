<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

/**
 * In-memory SQLite, same reasoning as UserRepositoryTest: hermetic, no server.
 */
class ChunkRepositoryTest extends Unit
{
    private \PDO $pdo;

    protected function _before(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->pdo->exec(
            "CREATE TABLE chunks (
                chunk_id INTEGER PRIMARY KEY AUTOINCREMENT,
                coordinate_set_id INTEGER NOT NULL,
                chunk_x INTEGER NOT NULL,
                chunk_z INTEGER NOT NULL,
                chunk_type TEXT NOT NULL
            )"
        );
        $this->pdo->exec(
            "INSERT INTO chunks (coordinate_set_id, chunk_x, chunk_z, chunk_type)
            VALUES
                (1, 2, 0, 'unavailable'),
                (1, 1, 5, 'mine'),
                (1, 1, 2, 'mine'),
                (2, 0, 0, 'mine')"
        );
    }

    public function testListChunksForSetReturnsOnlyThatSetOrderedByTypeThenPosition(): void
    {
        $repo = new \Database\ChunkRepository($this->pdo);

        $this->assertSame(
            [
                ['chunk_x' => 1, 'chunk_z' => 2, 'chunk_type' => 'mine'],
                ['chunk_x' => 1, 'chunk_z' => 5, 'chunk_type' => 'mine'],
                ['chunk_x' => 2, 'chunk_z' => 0, 'chunk_type' => 'unavailable'],
            ],
            $repo->listChunksForSet(1)
        );
    }

    public function testListChunksForSetIsEmptyForASetWithNone(): void
    {
        $repo = new \Database\ChunkRepository($this->pdo);

        $this->assertSame([], $repo->listChunksForSet(99));
    }
}
