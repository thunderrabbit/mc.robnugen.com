<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

/**
 * In-memory SQLite, same reasoning as UserRepositoryTest: hermetic, no server.
 */
class CoordinateSetRepositoryTest extends Unit
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
            "CREATE TABLE coordinate_sets (
                coordinate_set_id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                created_at TEXT,
                updated_at TEXT
            )"
        );
        $this->pdo->exec(
            "CREATE TABLE coordinates (
                coordinate_id INTEGER PRIMARY KEY AUTOINCREMENT,
                coordinate_set_id INTEGER NOT NULL,
                x INTEGER NOT NULL,
                y INTEGER NOT NULL,
                z INTEGER NOT NULL,
                sort INTEGER NOT NULL
            )"
        );
        $this->pdo->exec(
            "INSERT INTO coordinate_sets
                (coordinate_set_id, user_id, name, description, created_at, updated_at)
            VALUES
                (1, 7, 'older', NULL, '2026-01-01 00:00:00', '2026-01-02 00:00:00'),
                (2, 7, 'newer', 'desc', '2026-01-01 00:00:00', '2026-03-01 00:00:00'),
                (3, 8, 'theirs', NULL, '2026-01-01 00:00:00', '2026-02-01 00:00:00')"
        );
        $this->pdo->exec(
            "INSERT INTO coordinates (coordinate_set_id, x, y, z, sort)
            VALUES (1, 0, 64, 0, 0), (1, 5, 64, 5, 1), (2, 9, 70, 9, 0)"
        );
    }

    public function testListSetsForUserReturnsOnlyTheirSetsNewestFirstWithCounts(): void
    {
        $repo = new \Database\CoordinateSetRepository($this->pdo);

        $this->assertSame(
            [
                [
                    'coordinate_set_id' => 2,
                    'name' => 'newer',
                    'description' => 'desc',
                    'created_at' => '2026-01-01 00:00:00',
                    'updated_at' => '2026-03-01 00:00:00',
                    'coordinate_count' => 1,
                ],
                [
                    'coordinate_set_id' => 1,
                    'name' => 'older',
                    'description' => null,
                    'created_at' => '2026-01-01 00:00:00',
                    'updated_at' => '2026-01-02 00:00:00',
                    'coordinate_count' => 2,
                ],
            ],
            $repo->listSetsForUser(7)
        );
    }

    public function testFindSetForUserReturnsTheOwnersSet(): void
    {
        $repo = new \Database\CoordinateSetRepository($this->pdo);

        $this->assertSame(
            [
                'coordinate_set_id' => 1,
                'name' => 'older',
                'description' => null,
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-02 00:00:00',
            ],
            $repo->findSetForUser(1, 7)
        );
    }

    public function testFindSetForUserReturnsNullForSomeoneElsesSet(): void
    {
        $repo = new \Database\CoordinateSetRepository($this->pdo);

        $this->assertNull($repo->findSetForUser(3, 7));
    }

    public function testFindSetReturnsTheSetWhoeverOwnsIt(): void
    {
        $repo = new \Database\CoordinateSetRepository($this->pdo);

        $set = $repo->findSet(3);
        $this->assertNotNull($set);
        $this->assertSame('theirs', $set['name']);
    }

    public function testFindSetReturnsNullWhenNoSetHasTheId(): void
    {
        $repo = new \Database\CoordinateSetRepository($this->pdo);

        $this->assertNull($repo->findSet(99));
    }

    public function testCreateSetStoresTheSetAndReturnsItsId(): void
    {
        $repo = new \Database\CoordinateSetRepository($this->pdo);

        $id = $repo->createSet(7, 'fresh', null);

        $this->assertSame(4, $id);
        $this->assertSame(
            [
                'coordinate_set_id' => 4,
                'name' => 'fresh',
                'description' => null,
                'created_at' => self::NOW,
                'updated_at' => self::NOW,
            ],
            $repo->findSetForUser($id, 7)
        );
    }

    public function testTouchSetUpdatesOnlyThatSet(): void
    {
        $repo = new \Database\CoordinateSetRepository($this->pdo);

        $repo->touchSet(1);

        $this->assertSame(self::NOW, $repo->findSet(1)['updated_at'] ?? null);
        $this->assertSame('2026-03-01 00:00:00', $repo->findSet(2)['updated_at'] ?? null);
    }
}
