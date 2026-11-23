<?php

declare(strict_types=1);

namespace Database;

/**
 * Persistence for a coordinate set's chunk claims (the `chunks` table).
 */
class ChunkRepository
{
    public function __construct(private readonly \PDO $di_pdo)
    {
    }

    /**
     * The set's chunks, grouped by type.
     *
     * @return list<array{chunk_x: int, chunk_z: int, chunk_type: string}>
     */
    public function listChunksForSet(int $set_id): array
    {
        $stmt = $this->di_pdo->prepare("
            SELECT chunk_x, chunk_z, chunk_type
            FROM chunks
            WHERE coordinate_set_id = :set_id
            ORDER BY chunk_type, chunk_x, chunk_z
        ");

        $stmt->execute([':set_id' => $set_id]);
        /** @var list<array{chunk_x: int, chunk_z: int, chunk_type: string}> $chunks */
        $chunks = $stmt->fetchAll();

        return $chunks;
    }
}
