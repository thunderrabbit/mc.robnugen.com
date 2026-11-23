<?php

declare(strict_types=1);

namespace Database;

/**
 * Persistence for a coordinate set's chunk claims (the `chunks` table).
 */
class ChunkRepository
{
    private ?\PDOStatement $insert_chunk = null;

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

    public function deleteChunksForSet(int $set_id): void
    {
        $stmt = $this->di_pdo->prepare("
            DELETE FROM chunks WHERE coordinate_set_id = :set_id
        ");
        $stmt->execute([':set_id' => $set_id]);
    }

    /**
     * Stores one chunk claim. The INSERT is prepared on the first call and
     * reused, since save-coords.php adds a whole set's chunks in a loop.
     */
    public function addChunk(int $set_id, int $chunk_x, int $chunk_z, string $chunk_type): void
    {
        $this->insert_chunk ??= $this->di_pdo->prepare("
            INSERT INTO chunks (coordinate_set_id, chunk_x, chunk_z, chunk_type)
            VALUES (:coordinate_set_id, :chunk_x, :chunk_z, :chunk_type)
        ");

        $this->insert_chunk->execute([
            ':coordinate_set_id' => $set_id,
            ':chunk_x' => $chunk_x,
            ':chunk_z' => $chunk_z,
            ':chunk_type' => $chunk_type
        ]);
    }
}
