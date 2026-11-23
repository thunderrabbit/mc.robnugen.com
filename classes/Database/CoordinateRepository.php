<?php

declare(strict_types=1);

namespace Database;

/**
 * Persistence for the points in a coordinate set (the `coordinates` table).
 */
class CoordinateRepository
{
    public function __construct(private readonly \PDO $di_pdo)
    {
    }

    /**
     * The set's coordinates in their saved order.
     *
     * @return list<array{coordinate_id: int, x: int, y: int, z: int, label: string|null, color: string|null, segment_id: int|null, sort: int}>
     */
    public function listCoordinatesForSet(int $set_id): array
    {
        $stmt = $this->di_pdo->prepare("
            SELECT
                coordinate_id,
                x, y, z,
                label,
                color,
                segment_id,
                sort
            FROM coordinates
            WHERE coordinate_set_id = :set_id
            ORDER BY sort ASC
        ");

        $stmt->execute([':set_id' => $set_id]);
        /** @var list<array{coordinate_id: int, x: int, y: int, z: int, label: string|null, color: string|null, segment_id: int|null, sort: int}> $coordinates */
        $coordinates = $stmt->fetchAll();

        return $coordinates;
    }
}
