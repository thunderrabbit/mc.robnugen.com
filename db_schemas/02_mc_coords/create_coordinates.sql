CREATE TABLE coordinates (
    coordinate_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coordinate_set_id INT UNSIGNED NOT NULL,
    x INT NOT NULL,
    y INT NOT NULL,
    z INT NOT NULL,
    label VARCHAR(255),
    color VARCHAR(50),
    segment_id INT UNSIGNED,
    sort INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY coordinate_set_id (coordinate_set_id),
    KEY sort_index (coordinate_set_id, sort),
    CONSTRAINT fk_coordinates_coordinate_set_id
        FOREIGN KEY (coordinate_set_id) REFERENCES coordinate_sets(coordinate_set_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
