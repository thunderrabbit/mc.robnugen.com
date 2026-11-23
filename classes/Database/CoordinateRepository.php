<?php

declare(strict_types=1);

namespace Database;

/**
 * Persistence for the points in a coordinate set (the `coordinates` table).
 */
class CoordinateRepository
{
    private ?\PDOStatement $insert_coordinate = null;

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

    public function deleteCoordinatesForSet(int $set_id): void
    {
        $stmt = $this->di_pdo->prepare("
            DELETE FROM coordinates WHERE coordinate_set_id = :set_id
        ");
        $stmt->execute([':set_id' => $set_id]);
    }

    /**
     * Stores one coordinate. The INSERT is prepared on the first call and
     * reused, since save-coords.php adds a whole set's points in a loop.
     */
    public function addCoordinate(
        int $set_id,
        int $x,
        int $y,
        int $z,
        ?string $label,
        ?string $color,
        ?int $segment_id,
        int $sort
    ): void {
        $this->insert_coordinate ??= $this->di_pdo->prepare("
            INSERT INTO coordinates
            (coordinate_set_id, x, y, z, label, color, segment_id, sort, created_at)
            VALUES
            (:coordinate_set_id, :x, :y, :z, :label, :color, :segment_id, :sort, NOW())
        ");

        $this->insert_coordinate->execute([
            ':coordinate_set_id' => $set_id,
            ':x' => $x,
            ':y' => $y,
            ':z' => $z,
            ':label' => $label,
            ':color' => $color,
            ':segment_id' => $segment_id,
            ':sort' => $sort
        ]);
    }
}
