# GET_STARTED: Load Sensible Defaults for New Users

## Overview

Provide a better onboarding experience for new users by auto-loading demo coordinate/chunk data when they first visit the visualizer. This gives them something to interact with immediately without requiring them to understand the input format.

## User Experience Flow

1. **New User (no saved data):**
   - Logs in and visits `/`
   - System detects they have no coordinate sets
   - Auto-loads demo set (coordinate_set_id = 12, owned by "librarian")
   - User can interact, modify, and save if they want
   - Update button is NOT shown (read-only demo)

2. **Existing User (has saved data):**
   - Logs in and visits `/`
   - System detects they have coordinate sets
   - Shows empty textarea (normal behavior)
   - User can load their own sets from dropdown

3. **After Saving Demo Data:**
   - User now has data
   - Next login shows empty textarea (normal behavior)
   - Demo is no longer auto-loaded

---

## Current Bug to Fix First

### Bug: Update Button Shows When No Data Loaded

**Problem:** The Update button is currently visible even when no coordinate set has been loaded.

**Expected Behavior:** Update button should only be enabled/visible when the user has loaded their OWN coordinate set.

**Current Code:** `templates/index.tpl.php` around line 107
```javascript
<button id="btn-update" class="btn-primary" style="width: 100%; margin-top: 10px;" disabled>Update</button>
```

**Issue:** Button exists in DOM but is just disabled. Should not be shown at all until user loads their own data.

**Fix Required:**
- Hide button by default: `style="width: 100%; margin-top: 10px; display: none;"`
- Show button only when loading user's own data (not demo data)
- In load handler (line ~861): `btnUpdate.style.display = 'block';`
- In clear handler (line ~565): `btnUpdate.style.display = 'none';`

---

## Implementation Plan

### 1. Fix Update Button Bug ✅ DO THIS FIRST

**File:** `templates/index.tpl.php`

#### Changes:

**A. Hide button by default (line 107):**
```javascript
<button id="btn-update" class="btn-primary" style="width: 100%; margin-top: 10px; display: none;" disabled>Update</button>
```

**B. Show button when loading user's own data (around line 861):**
```javascript
// Enable and update the Update button text
btnUpdate.style.display = 'block'; // NEW: Show the button
btnUpdate.disabled = false;
```

**C. Hide button when clearing (around line 565):**
```javascript
// Clear loaded set tracking
currentLoadedSetId = null;
currentLoadedSetName = null;
currentLoadedCoordCount = 0;
btnUpdate.disabled = true;
btnUpdate.style.display = 'none'; // NEW: Hide the button
btnUpdate.textContent = 'Update';
```

---

### 2. Create Demo Load API Endpoint

**New File:** `wwwroot/mc/api/load-demo.php`

**Purpose:** Load demo coordinate sets without ownership checks, but with security whitelist.

**Security:** Only allow loading from a whitelist of demo set IDs.

```php
<?php

# Must include here because DH runs FastCGI
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!$is_logged_in->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'You must be logged in']);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed', 'message' => 'Only GET requests are accepted']);
    exit;
}

// Get coordinate set ID from query parameter
$set_id = $_GET['set_id'] ?? null;

if (!$set_id || !is_numeric($set_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation Error', 'message' => 'set_id parameter is required']);
    exit;
}

// SECURITY: Whitelist of demo coordinate sets that can be loaded
$DEMO_SET_WHITELIST = [12]; // coordinate_set_id = 12 owned by librarian

if (!in_array((int)$set_id, $DEMO_SET_WHITELIST)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden', 'message' => 'This coordinate set is not available as a demo']);
    exit;
}

try {
    $pdo = \Database\Base::getPDO($config);

    // Fetch the coordinate set (NO user_id check - it's a demo)
    $stmt = $pdo->prepare("
        SELECT coordinate_set_id, name, description, created_at, updated_at
        FROM coordinate_sets
        WHERE coordinate_set_id = :set_id
    ");

    $stmt->execute([':set_id' => $set_id]);
    $set = $stmt->fetch();

    if (!$set) {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found', 'message' => 'Demo coordinate set not found']);
        exit;
    }

    // Get all coordinates for this set
    $stmt = $pdo->prepare("
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
    $coordinates = $stmt->fetchAll();

    // Format coordinates
    $formatted_coords = array_map(function($coord) {
        return [
            'x' => (int)$coord['x'],
            'y' => (int)$coord['y'],
            'z' => (int)$coord['z'],
            'label' => $coord['label'],
            'color' => $coord['color'],
            'segmentId' => $coord['segment_id'] !== null ? (int)$coord['segment_id'] : null
        ];
    }, $coordinates);

    // Get all chunks for this set
    $stmt = $pdo->prepare("
        SELECT chunk_x, chunk_z, chunk_type
        FROM chunks
        WHERE coordinate_set_id = :set_id
        ORDER BY chunk_type, chunk_x, chunk_z
    ");

    $stmt->execute([':set_id' => $set_id]);
    $chunks = $stmt->fetchAll();

    // Format chunks
    $formatted_chunks = array_map(function($chunk) {
        return [
            'chunk_x' => (int)$chunk['chunk_x'],
            'chunk_z' => (int)$chunk['chunk_z'],
            'chunk_type' => $chunk['chunk_type']
        ];
    }, $chunks);

    echo json_encode([
        'success' => true,
        'is_demo' => true, // NEW: Flag to indicate this is demo data
        'set' => [
            'coordinate_set_id' => (int)$set['coordinate_set_id'],
            'name' => $set['name'],
            'description' => $set['description'],
            'created_at' => $set['created_at'],
            'updated_at' => $set['updated_at']
        ],
        'coordinates' => $formatted_coords,
        'chunks' => $formatted_chunks
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database Error',
        'message' => $e->getMessage()
    ]);
}
```

**Estimated:** ~120 lines

---

### 3. Frontend: Auto-load Demo for New Users

**File:** `templates/index.tpl.php`

**Location:** After `loadCoordinateSets()` call (around line 886)

#### Changes:

**A. Modify loadCoordinateSets to return a promise and check for empty data:**

```javascript
// Load saved coordinate sets into dropdown
async function loadCoordinateSets() {
    try {
        const response = await fetch('/mc/api/list-coords.php');
        const data = await response.json();

        if (response.ok && data.success) {
            // Clear existing options except the first one
            coordSetSelect.innerHTML = '<option value="">Select a saved set...</option>';

            // Add each set as an option
            data.sets.forEach(set => {
                const option = document.createElement('option');
                option.value = set.coordinate_set_id;

                // Format: "Name - Jan 7, 2026"
                const date = new Date(set.updated_at);
                const dateStr = date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                option.textContent = `${set.name} - ${dateStr}`;

                coordSetSelect.appendChild(option);
            });

            // NEW: Return whether user has data
            return data.sets.length > 0;
        }
        return false;
    } catch (error) {
        console.error('Failed to load coordinate sets:', error);
        return false;
    }
}
```

**B. Add demo loading function:**

```javascript
// Load demo coordinate set for new users
async function loadDemoSet(setId) {
    try {
        const response = await fetch(`/mc/api/load-demo.php?set_id=${setId}`);
        const data = await response.json();

        if (response.ok && data.success) {
            // Reconstruct the text format from coordinates
            const textLines = [];
            let currentColor = null;

            data.coordinates.forEach((coord, index) => {
                // Add color line if it changed
                if (coord.color && coord.color !== currentColor) {
                    if (index > 0) textLines.push(''); // Blank line before new color
                    textLines.push(coord.color);
                    currentColor = coord.color;
                }

                // Build coordinate line
                let line = `[${coord.x}, ${coord.y}, ${coord.z}]`;
                if (coord.label) {
                    line += ` ${coord.label}`;
                }

                // Add comma if this is the last coord of a segment
                const nextCoord = data.coordinates[index + 1];
                if (nextCoord && coord.segmentId !== nextCoord.segmentId) {
                    line += ',';
                }

                textLines.push(line);
            });

            // Reconstruct chunks text
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

            // Set the textarea content
            coordInput.value = textLines.join('\n');
            originalCoordText = coordInput.value.trim();
            hasUnsavedChanges = false;
            unsavedWarning.style.display = 'none';

            // DO NOT track as loaded set (this is demo data)
            currentLoadedSetId = null;
            currentLoadedSetName = null;
            currentLoadedCoordCount = 0;

            // DO NOT enable Update button (demo is read-only)
            btnUpdate.disabled = true;
            btnUpdate.style.display = 'none';

            // Parse and render
            parseAndRender();

            // Show subtle hint in load status
            loadStatus.className = 'mc-status';
            loadStatus.textContent = `Example data loaded - save it to keep your changes!`;
        }
    } catch (error) {
        console.error('Failed to load demo set:', error);
    }
}
```

**C. Update page initialization to check for new users:**

```javascript
// Load coordinate sets on page load, then check if we should load demo
loadCoordinateSets().then(hasData => {
    if (!hasData) {
        // New user - load demo set
        loadDemoSet(12);
    }
});

// Auto-parse on load if there's content
if (coordInput.value.trim()) {
    parseAndRender();
}
```

**Estimated:** ~80 lines added/modified

---

## Testing Checklist

- [ ] Bug Fix: Update button is hidden by default
- [ ] Bug Fix: Update button shows only when user loads their own data
- [ ] Bug Fix: Update button hides when clearing
- [ ] Demo API: Can load coordinate_set_id = 12
- [ ] Demo API: Cannot load non-whitelisted sets (security test)
- [ ] Demo API: Returns `is_demo: true` flag
- [ ] Frontend: New user (no data) auto-loads demo set
- [ ] Frontend: Existing user (has data) sees empty textarea
- [ ] Frontend: Demo data does not enable Update button
- [ ] Frontend: Demo data shows hint message
- [ ] Frontend: After saving demo, next login shows empty textarea

---

## Code Estimate Summary

| Component | Lines of Code |
|-----------|---------------|
| Bug fix: Update button visibility | 10 |
| New API: load-demo.php | 120 |
| Frontend: loadCoordinateSets promise | 5 |
| Frontend: loadDemoSet function | 60 |
| Frontend: Page init check | 10 |
| **Total** | **~205 lines** |

---

## Security Considerations

- **Whitelist Enforcement:** Only coordinate sets in `$DEMO_SET_WHITELIST` can be loaded via demo endpoint
- **Authentication Required:** User must be logged in to load demo (prevents anonymous access)
- **No Ownership Check:** Demo endpoint skips user_id verification (intentional for sharing)
- **Read-Only:** Demo data cannot be updated (Update button hidden)

---

## Future Enhancements

- Allow multiple demo sets (beginner, advanced, chunk examples)
- Allow anonymous loading but no saving of data
- Add welcome message explaining demo data
- Create admin interface to manage demo set whitelist
- Track demo usage analytics
