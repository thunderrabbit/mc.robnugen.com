<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

/**
 * In-memory SQLite, same reasoning as UserRepositoryTest: hermetic, no server.
 */
class ChunkRepositoryTest extends Unit
{
    private const string NOW = '2026-11-24 08:00:00';

    private \PDO $pdo;

    protected function _before(): void
    {
        $sqlite = new \Pdo\Sqlite('sqlite::memory:');
        // MySQL's NOW(), pinned so the stored timestamps can be compared.
        $sqlite->createFunction('NOW', fn(): string => self::NOW, 0);
        $this->pdo = $sqlite;
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

    public function testDeleteChunksForSetLeavesOtherSetsAlone(): void
    {
        $repo = new \Database\ChunkRepository($this->pdo);

        $repo->deleteChunksForSet(1);

        $this->assertSame([], $repo->listChunksForSet(1));
        $this->assertCount(1, $repo->listChunksForSet(2));
    }

    public function testAddChunkStoresEachChunk(): void
    {
        $repo = new \Database\ChunkRepository($this->pdo);

        $repo->addChunk(5, 3, 4, 'mine');
        $repo->addChunk(5, -1, 0, 'unavailable');

        $this->assertSame(
            [
                ['chunk_x' => 3, 'chunk_z' => 4, 'chunk_type' => 'mine'],
                ['chunk_x' => -1, 'chunk_z' => 0, 'chunk_type' => 'unavailable'],
            ],
            $repo->listChunksForSet(5)
        );
    }
}
