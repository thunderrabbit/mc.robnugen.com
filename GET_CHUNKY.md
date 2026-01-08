# GET_CHUNKY: Chunk Claiming Visualization

## Overview

Add chunk claiming visualization to the coordinate visualizer using a simple text-based format. Users can mark chunks as "mine" (light green) or "unavailable" (light red) using a clean syntax.

## Text Format

```
mine
[-18, 30]
[-18, 31]
[-17, 30]

unavailable
[-15, 28]
[-15, 29]
```

- Chunk coordinates are in chunk space (divide world coords by 16)
- Color keywords: `mine` (light green) or `unavailable` (light red)
- Format: `[chunk_x, chunk_z]` (no Y coordinate needed)

---

## Database Schema

### New Table: `chunks`

```sql
CREATE TABLE chunks (
    chunk_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coordinate_set_id INT UNSIGNED NOT NULL,
    chunk_x INT NOT NULL,
    chunk_z INT NOT NULL,
    chunk_type ENUM('mine', 'unavailable') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (coordinate_set_id)
        REFERENCES coordinate_sets(coordinate_set_id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_chunk_per_set (coordinate_set_id, chunk_x, chunk_z)
);
```

**File:** `db_schemas/02_mc_coords/create_chunks.sql`

---

## Implementation Plan

### 1. Database Migration

**File:** `db_schemas/02_mc_coords/create_chunks.sql`

- [x] Create new `chunks` table
- [x] Add foreign key to `coordinate_sets`
- [x] Add unique constraint to prevent duplicate chunks

---

### 2. Parser Updates

**File:** `templates/index.tpl.php` - `parseCoordinates()` function

**Add chunk parsing logic:**

```javascript
function parseCoordinates(text) {
    const points = [];
    const pathSegments = [];
    const chunks = []; // NEW: Array of chunk objects
    const warnings = [];

    let currentColor = 0x00aaff;
    let currentColorName = null;
    let currentChunkType = null; // NEW: Track 'mine' or 'unavailable'

    // ... existing coordinate parsing ...

    // NEW: Check if line is a chunk type keyword
    if (trimmedLine === 'mine' || trimmedLine === 'unavailable') {
        currentChunkType = trimmedLine;
        return;
    }

    // NEW: Parse chunk format [X, Z]
    const chunkRegex = /\[(-?\d+)\s*,\s*(-?\d+)\]/g;
    if (currentChunkType && chunkRegex.test(line)) {
        const match = chunkRegex.exec(line);
        chunks.push({
            chunk_x: parseInt(match[1]),
            chunk_z: parseInt(match[2]),
            chunk_type: currentChunkType
        });
    }

    return { points, pathSegments, chunks, warnings };
}
```

**Estimated:** ~30 lines

---

### 3. Renderer Updates

**File:** `templates/index.tpl.php` - `MCVisualizer` class

**Add chunk rendering method:**

```javascript
class MCVisualizer {
    constructor(containerId) {
        // ... existing code ...
        this.chunkPlanes = []; // NEW: Array to store chunk plane meshes
    }

    renderChunks(chunks, yLevel = 80) {
        // Clear existing chunk planes
        this.chunkPlanes.forEach(plane => this.scene.remove(plane));
        this.chunkPlanes = [];

        chunks.forEach(chunk => {
            // Convert chunk coords to world coords
            const worldX = chunk.chunk_x * 16 + 8; // Center of chunk
            const worldZ = chunk.chunk_z * 16 + 8;

            // Determine color
            const color = chunk.chunk_type === 'mine'
                ? 0x7CB342  // Light green
                : 0xC55A5A; // Light red

            // Create semi-transparent plane (16x16 blocks)
            const geometry = new THREE.PlaneGeometry(16, 16);
            const material = new THREE.MeshBasicMaterial({
                color: color,
                transparent: true,
                opacity: 0.3,
                side: THREE.DoubleSide
            });

            const plane = new THREE.Mesh(geometry, material);
            plane.rotation.x = -Math.PI / 2; // Rotate to be horizontal
            plane.position.set(worldX, yLevel, worldZ);

            this.scene.add(plane);
            this.chunkPlanes.push(plane);
        });
    }
}
```

**Update `renderPoints()` to call `renderChunks()`:**

```javascript
renderPoints(points, pathSegments = [], chunks = [], showPath = false) {
    // ... existing point rendering ...

    // NEW: Render chunks
    this.renderChunks(chunks);
}
```

**Estimated:** ~40 lines

---

### 4. Save/Load Integration

#### Frontend - Save

**File:** `templates/index.tpl.php` - Save button handler

```javascript
btnSave.addEventListener('click', async function() {
    // ... existing validation ...

    const result = parseCoordinates(text);

    const saveData = {
        name: setName,
        description: '',
        coordinates: result.points.map(/* ... */),
        chunks: result.chunks // NEW: Include chunks array
    };

    // ... send to API ...
});
```

**Estimated:** ~5 lines

---

#### Backend - Save

**File:** `wwwroot/mc/api/save-coords.php`

```php
// After saving coordinates, save chunks
if (!empty($data['chunks']) && is_array($data['chunks'])) {
    $stmt = $pdo->prepare("
        INSERT INTO chunks (coordinate_set_id, chunk_x, chunk_z, chunk_type)
        VALUES (:set_id, :chunk_x, :chunk_z, :chunk_type)
    ");

    foreach ($data['chunks'] as $chunk) {
        $stmt->execute([
            ':set_id' => $coordinate_set_id,
            ':chunk_x' => (int)$chunk['chunk_x'],
            ':chunk_z' => (int)$chunk['chunk_z'],
            ':chunk_type' => $chunk['chunk_type']
        ]);
    }
}
```

**For UPDATE:** Delete existing chunks before inserting new ones:

```php
if ($is_update) {
    // ... existing coordinate deletion ...

    // Delete existing chunks
    $stmt = $pdo->prepare("DELETE FROM chunks WHERE coordinate_set_id = :set_id");
    $stmt->execute([':set_id' => $coordinate_set_id]);
}
```

**Estimated:** ~25 lines

---

#### Backend - Load

**File:** `wwwroot/mc/api/load-coords.php`

```php
// After fetching coordinates, fetch chunks
$stmt = $pdo->prepare("
    SELECT chunk_x, chunk_z, chunk_type
    FROM chunks
    WHERE coordinate_set_id = :set_id
    ORDER BY chunk_type, chunk_x, chunk_z
");

$stmt->execute([':set_id' => $set_id]);
$chunks = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'set' => [/* ... */],
    'coordinates' => $formatted_coords,
    'chunks' => $chunks // NEW: Include chunks
]);
```

**Estimated:** ~15 lines

---

#### Frontend - Load

**File:** `templates/index.tpl.php` - Load button handler

```javascript
btnLoad.addEventListener('click', async function() {
    // ... existing load logic ...

    if (response.ok && data.success) {
        // Reconstruct coordinates text
        const textLines = [];

        // ... existing coordinate reconstruction ...

        // NEW: Reconstruct chunks text
        if (data.chunks && data.chunks.length > 0) {
            let currentChunkType = null;

            data.chunks.forEach(chunk => {
                // Add chunk type header if it changed
                if (chunk.chunk_type !== currentChunkType) {
                    if (textLines.length > 0) textLines.push(''); // Blank line
                    textLines.push(chunk.chunk_type);
                    currentChunkType = chunk.chunk_type;
                }

                // Add chunk coordinates
                textLines.push(`[${chunk.chunk_x}, ${chunk.chunk_z}]`);
            });
        }

        coordInput.value = textLines.join('\n');

        // Parse and render (will include chunks)
        parseAndRender();
    }
});
```

**Estimated:** ~25 lines

---

### 5. Update Handler Integration

**File:** `templates/index.tpl.php` - Update button handler

```javascript
btnUpdate.addEventListener('click', async function() {
    // ... existing validation ...

    const result = parseCoordinates(text);

    const updateData = {
        coordinate_set_id: currentLoadedSetId,
        name: currentLoadedSetName,
        description: '',
        coordinates: result.points.map(/* ... */),
        chunks: result.chunks // NEW: Include chunks
    };

    // ... send to API ...
});
```

**Estimated:** ~5 lines

---

## Visual Specifications

### Colors

- **Mine (claimed):** `#7CB342` - Light yellowish-green
- **Unavailable (others):** `#C55A5A` - Light red (from screenshot)

### Rendering

- **Opacity:** 0.3 (30% transparent)
- **Y-Level:** 80 (configurable later if needed)
- **Size:** 16×16 blocks (one Minecraft chunk)
- **Position:** Centered on chunk (chunk_x * 16 + 8, chunk_z * 16 + 8)
- **Rotation:** Horizontal plane (visible from all angles)
- **Borders:** None

---

## Example Usage

### Input:

```
red
[-278, 80, 487] home
[-278, -34, 272] bed

mine
[-18, 30]
[-18, 31]
[-17, 30]

unavailable
[-15, 28]
```

### Result:

- Red coordinate points at home and bed
- Light green semi-transparent planes at chunks (-18,30), (-18,31), (-17,30)
- Light red semi-transparent plane at chunk (-15,28)

---

## Testing Checklist

- [ ] Create `chunks` table in database
- [ ] Parser recognizes `mine` and `unavailable` keywords
- [ ] Parser extracts chunk coordinates `[X, Z]`
- [ ] Chunks render as semi-transparent planes
- [ ] Chunks save to database with coordinate set
- [ ] Chunks load from database
- [ ] Update operation handles chunks correctly
- [ ] Multiple chunk types in same set work
- [ ] Chunk planes visible from all angles
- [ ] Colors match specification (green/red)

---

## Code Estimate Summary

| Component | Lines of Code |
|-----------|---------------|
| Database schema | 15 |
| Parser updates | 30 |
| Renderer updates | 40 |
| Save frontend | 5 |
| Save backend | 25 |
| Load backend | 15 |
| Load frontend | 25 |
| Update frontend | 5 |
| **Total** | **~160 lines** |

---

## Migration Notes

> [!IMPORTANT]
> Existing coordinate sets will have no chunks (empty array). This is fine - chunks are optional. Users can add chunks to existing sets by loading, adding chunk text, and updating.

## Future Enhancements

- Toggle visibility of chunk planes
- Different Y-levels for different chunk types
- Chunk ownership labels (hover to see who owns)
- Import/export chunk lists
- Bulk chunk operations (select rectangle of chunks)
