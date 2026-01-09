CREATE TABLE chunks (
    chunk_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coordinate_set_id INT UNSIGNED NOT NULL,
    chunk_x INT NOT NULL,
    chunk_z INT NOT NULL,
    chunk_type ENUM('mine', 'unavailable') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY coordinate_set_id (coordinate_set_id),
    UNIQUE KEY unique_chunk_per_set (coordinate_set_id, chunk_x, chunk_z),
    CONSTRAINT fk_chunks_coordinate_set_id
        FOREIGN KEY (coordinate_set_id) REFERENCES coordinate_sets(coordinate_set_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
