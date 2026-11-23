<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

/**
 * In-memory SQLite, same reasoning as UserRepositoryTest: hermetic, no server.
 */
class CoordinateRepositoryTest extends Unit
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
            "CREATE TABLE coordinates (
                coordinate_id INTEGER PRIMARY KEY AUTOINCREMENT,
                coordinate_set_id INTEGER NOT NULL,
                x INTEGER NOT NULL,
                y INTEGER NOT NULL,
                z INTEGER NOT NULL,
                label TEXT,
                color TEXT,
                segment_id INTEGER,
                sort INTEGER NOT NULL,
                created_at TEXT
            )"
        );
        $this->pdo->exec(
            "INSERT INTO coordinates
                (coordinate_id, coordinate_set_id, x, y, z, label, color, segment_id, sort)
            VALUES
                (1, 1, 5, 64, 5, NULL, NULL, NULL, 1),
                (2, 1, 0, 64, 0, 'spawn', '#ff0000', 3, 0),
                (3, 2, 9, 70, 9, NULL, NULL, NULL, 0)"
        );
    }

    public function testListCoordinatesForSetReturnsOnlyThatSetInSortOrder(): void
    {
        $repo = new \Database\CoordinateRepository($this->pdo);

        $this->assertSame(
            [
                [
                    'coordinate_id' => 2,
                    'x' => 0,
                    'y' => 64,
                    'z' => 0,
                    'label' => 'spawn',
                    'color' => '#ff0000',
                    'segment_id' => 3,
                    'sort' => 0,
                ],
                [
                    'coordinate_id' => 1,
                    'x' => 5,
                    'y' => 64,
                    'z' => 5,
                    'label' => null,
                    'color' => null,
                    'segment_id' => null,
                    'sort' => 1,
                ],
            ],
            $repo->listCoordinatesForSet(1)
        );
    }

    public function testListCoordinatesForSetIsEmptyForASetWithNone(): void
    {
        $repo = new \Database\CoordinateRepository($this->pdo);

        $this->assertSame([], $repo->listCoordinatesForSet(99));
    }

    public function testDeleteCoordinatesForSetLeavesOtherSetsAlone(): void
    {
        $repo = new \Database\CoordinateRepository($this->pdo);

        $repo->deleteCoordinatesForSet(1);

        $this->assertSame([], $repo->listCoordinatesForSet(1));
        $this->assertCount(1, $repo->listCoordinatesForSet(2));
    }

    public function testAddCoordinateStoresEachPoint(): void
    {
        $repo = new \Database\CoordinateRepository($this->pdo);

        $repo->addCoordinate(5, 1, 2, 3, 'a', '#00ff00', 4, 0);
        $repo->addCoordinate(5, 7, 8, 9, null, null, null, 1);

        $stmt = $this->pdo->query(
            "SELECT x, y, z, label, color, segment_id, sort, created_at
            FROM coordinates WHERE coordinate_set_id = 5 ORDER BY sort"
        );
        $rows = $stmt === false ? [] : $stmt->fetchAll();
        $this->assertSame(
            [
                ['x' => 1, 'y' => 2, 'z' => 3, 'label' => 'a', 'color' => '#00ff00', 'segment_id' => 4, 'sort' => 0, 'created_at' => self::NOW],
                ['x' => 7, 'y' => 8, 'z' => 9, 'label' => null, 'color' => null, 'segment_id' => null, 'sort' => 1, 'created_at' => self::NOW],
            ],
            $rows
        );
    }
}
