<?php

declare(strict_types=1);

namespace Database;

/**
 * Persistence for saved coordinate sets (the `coordinate_sets` table).
 */
class CoordinateSetRepository
{
    public function __construct(private readonly \PDO $di_pdo)
    {
    }

    /**
     * Every set the user owns, newest change first, with its coordinate count.
     *
     * @return list<array{coordinate_set_id: int, name: string, description: string|null, created_at: string|null, updated_at: string|null, coordinate_count: int}>
     */
    public function listSetsForUser(int $user_id): array
    {
        $stmt = $this->di_pdo->prepare("
            SELECT
                coordinate_set_id,
                name,
                description,
                created_at,
                updated_at,
                (SELECT COUNT(*) FROM coordinates WHERE coordinate_set_id = cs.coordinate_set_id) as coordinate_count
            FROM coordinate_sets cs
            WHERE user_id = :user_id
            ORDER BY updated_at DESC
        ");

        $stmt->execute([':user_id' => $user_id]);
        /** @var list<array{coordinate_set_id: int, name: string, description: string|null, created_at: string|null, updated_at: string|null, coordinate_count: int}> $sets */
        $sets = $stmt->fetchAll();

        return $sets;
    }

    /**
     * The set, only when this user owns it.
     *
     * @return array{coordinate_set_id: int, name: string, description: string|null, created_at: string|null, updated_at: string|null}|null
     */
    public function findSetForUser(int $set_id, int $user_id): ?array
    {
        $stmt = $this->di_pdo->prepare("
            SELECT coordinate_set_id, name, description, created_at, updated_at
            FROM coordinate_sets
            WHERE coordinate_set_id = :set_id AND user_id = :user_id
        ");

        $stmt->execute([
            ':set_id' => $set_id,
            ':user_id' => $user_id
        ]);

        /** @var array{coordinate_set_id: int, name: string, description: string|null, created_at: string|null, updated_at: string|null}|false $set */
        $set = $stmt->fetch();

        return $set === false ? null : $set;
    }

    /**
     * The set, whoever owns it. Does not check the owner: the caller decides
     * who may see it (load-demo.php allows only its whitelist).
     *
     * @return array{coordinate_set_id: int, name: string, description: string|null, created_at: string|null, updated_at: string|null}|null
     */
    public function findSet(int $set_id): ?array
    {
        $stmt = $this->di_pdo->prepare("
            SELECT coordinate_set_id, name, description, created_at, updated_at
            FROM coordinate_sets
            WHERE coordinate_set_id = :set_id
        ");

        $stmt->execute([':set_id' => $set_id]);
        /** @var array{coordinate_set_id: int, name: string, description: string|null, created_at: string|null, updated_at: string|null}|false $set */
        $set = $stmt->fetch();

        return $set === false ? null : $set;
    }

    /**
     * Stores a new set for the user and returns its coordinate_set_id.
     */
    public function createSet(int $user_id, string $name, ?string $description): int
    {
        $stmt = $this->di_pdo->prepare("
            INSERT INTO coordinate_sets (user_id, name, description, created_at, updated_at)
            VALUES (:user_id, :name, :description, NOW(), NOW())
        ");

        $stmt->execute([
            ':user_id' => $user_id,
            ':name' => $name,
            ':description' => $description
        ]);

        return (int)$this->di_pdo->lastInsertId();
    }

    /**
     * Marks the set as changed now.
     */
    public function touchSet(int $set_id): void
    {
        $stmt = $this->di_pdo->prepare("
            UPDATE coordinate_sets
            SET updated_at = NOW()
            WHERE coordinate_set_id = :set_id
        ");
        $stmt->execute([':set_id' => $set_id]);
    }
}
