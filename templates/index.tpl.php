<div class="PagePanel">
    <h1>🎮 Minecraft Coordinate Visualizer</h1>
    <p>Welcome back, <?= $username ?>! Paste your coordinates below to visualize them in 3D.</p>
</div>

<div class="mc-visualizer-container">
    <!-- Left Panel: Input & Controls -->
    <div class="mc-input-panel">
        <h2>Coordinates</h2>

        <textarea id="coord-input" cols="50" rows="10" placeholder="Paste coordinates here...
Examples:
[-278,80,487] base portal
-278 80 487
x=-278 y=80 z=487
Multiple formats supported!"></textarea>

        <div class="mc-button-group">
            <button id="btn-parse" class="btn-primary">Parse & Visualize</button>
            <button id="btn-clear" class="btn-secondary">Clear</button>
        </div>

        <div id="parse-status" class="mc-status"></div>

        <hr>

        <h3>Display Options</h3>
        <div class="mc-controls">
            <label>
                <input type="checkbox" id="toggle-labels" checked>
                Show Labels
            </label>
            <label>
                <input type="checkbox" id="toggle-connect" checked>
                Connect Points (Path)
            </label>
            <label>
                <input type="checkbox" id="toggle-nether">
                Nether Scale (÷8)
            </label>
        </div>

        <hr>

        <h3>Load</h3>
        <div class="mc-form-group">
            <select id="coord-set-select" class="mc-input">
                <option value="">Select a saved set...</option>
            </select>
            <button id="btn-load" class="btn-primary" disabled>Load</button>
        </div>
        <div class="mc-load-warning">
            <span id="unsaved-warning" class="mc-hint" style="display: none;">(unsaved changes)</span>
        </div>
        <div id="load-status" class="mc-status"></div>

        <hr>

        <h3>Save</h3>
        <div class="mc-form-group">
            <input type="text" id="set-name" placeholder="Enter coordinate set name..." class="mc-input">
            <button id="btn-save" class="btn-primary">Save Coordinates</button>
            <button id="btn-update" class="btn-primary" disabled>Update</button>
        </div>
        <div id="save-status" class="mc-status"></div>

        <hr>

        <h3>Export</h3>
        <div class="mc-button-group">
            <button id="btn-export-json" class="btn-secondary">Export JSON</button>
            <button id="btn-export-csv" class="btn-secondary">Export CSV</button>
        </div>

        <hr>

        <div id="point-details" class="mc-details">
            <h3>Selected Point</h3>
            <p class="mc-hint">Click a point to see details</p>
        </div>
    </div>

    <!-- Right Panel: 3D Canvas -->
    <div class="mc-canvas-panel">
        <div id="canvas-container"></div>
        <div class="mc-canvas-controls">
            <button id="btn-top-view" class="btn-view">Top View</button>
            <button id="btn-reset-view" class="btn-view">Reset View</button>
        </div>
    </div>
</div>


<!-- Three.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/three@0.145.0/build/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.145.0/examples/js/controls/OrbitControls.js"></script>

<script>
// Color name to hex mapping
const COLOR_MAP = {
    'red': 0xff0000,
    'dark red': 0x8b0000,
    'green': 0x00ff00,
    'dark green': 0x006400,
    'blue': 0x0000ff,
    'dark blue': 0x00008b,
    'yellow': 0xffff00,
    'orange': 0xffa500,
    'purple': 0x800080,
    'pink': 0xffc0cb,
    'cyan': 0x00ffff,
    'magenta': 0xff00ff,
    'white': 0xffffff,
    'black': 0x000000,
    'gray': 0x808080,
    'grey': 0x808080,
    'brown': 0x8b4513,
    'lime': 0x00ff00,
    'teal': 0x008080,
    'navy': 0x000080
};

// Coordinate Parser with Color Groups and Labels
function parseCoordinates(text) {
    const points = [];
    const pathSegments = []; // Array of arrays - each inner array is a connected path
    const warnings = [];

    // Regex for bracket format: [-278, 80, 487]
    const bracketRegex = /\[(-?\d+)\s*,\s*(-?\d+)\s*,\s*(-?\d+)\]\s*([^\n,]*)/g;

    // Split by commas that are OUTSIDE brackets to find path segments
    const segmentTexts = text.split(/,(?![^\[]*\])/);

    let currentColorHex = 0x00aaff; // Default blue color for rendering
    let currentColorName = null; // Track the original color name for saving

    segmentTexts.forEach((segmentText, segmentIdx) => {
        const segmentPoints = [];
        const lines = segmentText.split('\n');

        lines.forEach(line => {
            const trimmedLine = line.trim();

            // Check if this line is a color name (no brackets)
            if (trimmedLine && !trimmedLine.includes('[')) {
                const colorName = trimmedLine.toLowerCase();
                if (COLOR_MAP[colorName] !== undefined) {
                    currentColorHex = COLOR_MAP[colorName];
                    currentColorName = colorName; // Store the original color name
                }
                return; // Skip to next line
            }

            // Parse coordinates with labels
            bracketRegex.lastIndex = 0;
            let match;

            while ((match = bracketRegex.exec(line)) !== null) {
                const label = match[4] ? match[4].trim() : '';
                const point = {
                    x: parseInt(match[1]),
                    y: parseInt(match[2]),
                    z: parseInt(match[3]),
                    label: label,
                    color: currentColorHex, // Hex for rendering
                    colorName: currentColorName, // Original name for saving
                    id: `point-${points.length}`,
                    segmentId: segmentIdx
                };
                points.push(point);
                segmentPoints.push(point);
            }
        });

        if (segmentPoints.length > 0) {
            pathSegments.push(segmentPoints);
        }
    });

    return { points, pathSegments, warnings };
}

// Three.js Visualizer
class MCVisualizer {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.points = [];
        this.pointMeshes = [];
        this.pathLines = []; // Changed from pathLine to pathLines array

        // Origin offset: snap to nearest chunk boundary (16-block chunks)
        // Original coordinate: (-281, 80, 487)
        // Snapped to chunks: (-288, 80, 480) - nearest multiples of 16
        this.originOffset = {
            x: Math.round(-281 / 16) * 16,  // -288
            y: Math.round(80 / 16) * 16,    // 80
            z: Math.round(487 / 16) * 16    // 480
        };

        this.init();
    }

    init() {
        // Scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x1a1a1a);

        // Camera - positioned relative to origin
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;
        this.camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 10000);
        this.camera.position.set(
            this.originOffset.x + 300,
            this.originOffset.y + 300,
            this.originOffset.z + 300
        );
        this.camera.lookAt(this.originOffset.x, this.originOffset.y, this.originOffset.z);

        // Renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true });
        this.renderer.setSize(width, height);
        this.container.appendChild(this.renderer.domElement);

        // Controls - orbit around the origin
        this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
        this.controls.target.set(this.originOffset.x, this.originOffset.y, this.originOffset.z);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.05;

        // Lights
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        this.scene.add(ambientLight);

        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(100, 100, 50);
        this.scene.add(directionalLight);

        // Grid aligned with Minecraft chunks (16×16 blocks)
        const gridSize = 1024; // 64 chunks × 16 blocks = 1024 blocks
        const gridDivisions = 64; // 64 divisions = 16 blocks per cell
        const gridHelper = new THREE.GridHelper(gridSize, gridDivisions, 0x444444, 0x222222);
        gridHelper.position.set(this.originOffset.x, this.originOffset.y, this.originOffset.z);
        this.scene.add(gridHelper);

        // Axis helpers (RGB = XYZ) - positioned at the origin offset
        const axesHelper = new THREE.AxesHelper(500);
        axesHelper.position.set(this.originOffset.x, this.originOffset.y, this.originOffset.z);
        this.scene.add(axesHelper);

        // Handle window resize
        window.addEventListener('resize', () => this.onWindowResize());

        // Start animation loop
        this.animate();
    }

    renderPoints(points, pathSegments = [], showPath = false) {
        // Clear existing points and paths
        this.pointMeshes.forEach(mesh => this.scene.remove(mesh));
        this.pointMeshes = [];

        this.pathLines.forEach(line => this.scene.remove(line));
        this.pathLines = [];

        this.points = points;

        if (points.length === 0) return;

        // Create point meshes with individual colors
        const geometry = new THREE.SphereGeometry(1.25, 16, 16);

        points.forEach(point => {
            // Create material with point's color
            const material = new THREE.MeshPhongMaterial({
                color: point.color,
                emissive: point.color,
                emissiveIntensity: 0.3,
                shininess: 30
            });

            const mesh = new THREE.Mesh(geometry, material);
            // Use actual Minecraft coordinates
            mesh.position.set(point.x, point.y, point.z);
            mesh.userData = point;
            this.scene.add(mesh);
            this.pointMeshes.push(mesh);
        });

        // Create path lines for each segment if requested
        if (showPath && pathSegments.length > 0) {
            pathSegments.forEach(segment => {
                if (segment.length > 1) {
                    // Use actual Minecraft coordinates
                    const pathPoints = segment.map(p => new THREE.Vector3(p.x, p.y, p.z));
                    const lineGeometry = new THREE.BufferGeometry().setFromPoints(pathPoints);

                    // Use the color of the first point in the segment
                    const segmentColor = segment[0].color || 0xffaa00;
                    const lineMaterial = new THREE.LineBasicMaterial({
                        color: segmentColor,
                        linewidth: 2
                    });
                    const pathLine = new THREE.Line(lineGeometry, lineMaterial);
                    this.scene.add(pathLine);
                    this.pathLines.push(pathLine);
                }
            });
        }

        // Center camera on points
        this.centerCameraOnPoints();
    }

    centerCameraOnPoints() {
        if (this.points.length === 0) return;

        // Calculate bounding box
        let minX = Infinity, minY = Infinity, minZ = Infinity;
        let maxX = -Infinity, maxY = -Infinity, maxZ = -Infinity;

        this.points.forEach(p => {
            minX = Math.min(minX, p.x);
            minY = Math.min(minY, p.y);
            minZ = Math.min(minZ, p.z);
            maxX = Math.max(maxX, p.x);
            maxY = Math.max(maxY, p.y);
            maxZ = Math.max(maxZ, p.z);
        });

        const centerX = (minX + maxX) / 2;
        const centerY = (minY + maxY) / 2;
        const centerZ = (minZ + maxZ) / 2;

        const rangeX = maxX - minX;
        const rangeY = maxY - minY;
        const rangeZ = maxZ - minZ;
        const maxRange = Math.max(rangeX, rangeY, rangeZ);

        // Position camera to see all points
        const distance = maxRange * 2;
        this.camera.position.set(
            centerX + distance,
            centerY + distance,
            centerZ + distance
        );
        this.controls.target.set(centerX, centerY, centerZ);
        this.controls.update();
    }

    setTopView() {
        if (this.points.length === 0) return;

        const target = this.controls.target;
        const distance = 500;
        this.camera.position.set(target.x, target.y + distance, target.z);
        this.controls.update();
    }

    resetView() {
        this.centerCameraOnPoints();
    }

    onWindowResize() {
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    }

    animate() {
        requestAnimationFrame(() => this.animate());
        this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }
}

// Main Application
document.addEventListener('DOMContentLoaded', function() {
    const coordInput = document.getElementById('coord-input');
    const btnParse = document.getElementById('btn-parse');
    const btnClear = document.getElementById('btn-clear');
    const btnTopView = document.getElementById('btn-top-view');
    const btnResetView = document.getElementById('btn-reset-view');
    const toggleConnect = document.getElementById('toggle-connect');
    const parseStatus = document.getElementById('parse-status');

    // Initialize visualizer
    const visualizer = new MCVisualizer('canvas-container');

    // Parse and render function
    function parseAndRender() {
        const text = coordInput.value.trim();
        if (!text) {
            parseStatus.className = 'mc-status error';
            parseStatus.textContent = 'Please enter some coordinates first.';
            return;
        }

        const result = parseCoordinates(text);

        if (result.points.length === 0) {
            parseStatus.className = 'mc-status error';
            parseStatus.textContent = 'No valid coordinates found. Use format: [-278, 80, 487]';
            return;
        }

        const showPath = toggleConnect.checked;
        visualizer.renderPoints(result.points, result.pathSegments, showPath);

        const segmentText = result.pathSegments.length > 1 ? ` in ${result.pathSegments.length} segments` : '';
        parseStatus.className = 'mc-status success';
        parseStatus.textContent = `Parsed ${result.points.length} point${result.points.length !== 1 ? 's' : ''}${segmentText} successfully!`;
    }

    // Event listeners
    btnParse.addEventListener('click', parseAndRender);

    btnClear.addEventListener('click', function() {
        coordInput.value = '';
        parseStatus.className = 'mc-status';
        parseStatus.textContent = '';
        visualizer.renderPoints([]);

        // Clear loaded set tracking
        currentLoadedSetId = null;
        currentLoadedSetName = null;
        currentLoadedCoordCount = 0;
        btnUpdate.disabled = true;
        btnUpdate.textContent = 'Update';
    });

    btnTopView.addEventListener('click', () => visualizer.setTopView());
    btnResetView.addEventListener('click', () => visualizer.resetView());

    toggleConnect.addEventListener('change', parseAndRender);

    // Save coordinates
    const btnSave = document.getElementById('btn-save');
    const setNameInput = document.getElementById('set-name');
    const saveStatus = document.getElementById('save-status');

    btnSave.addEventListener('click', async function() {
        const text = coordInput.value.trim();
        const setName = setNameInput.value.trim();

        // Validate
        if (!setName) {
            saveStatus.className = 'mc-status error';
            saveStatus.textContent = 'Please enter a name for this coordinate set.';
            return;
        }

        if (!text) {
            saveStatus.className = 'mc-status error';
            saveStatus.textContent = 'Please enter some coordinates first.';
            return;
        }

        // Parse coordinates
        const result = parseCoordinates(text);

        if (result.points.length === 0) {
            saveStatus.className = 'mc-status error';
            saveStatus.textContent = 'No valid coordinates found to save.';
            return;
        }

        // Prepare data for API
        const saveData = {
            name: setName,
            description: '', // Could add a description field later
            coordinates: result.points.map((point, index) => ({
                x: point.x,
                y: point.y,
                z: point.z,
                label: point.label || null,
                color: point.colorName || null, // Use the original color name directly
                segmentId: point.segmentId
            }))
        };

        // Show saving status
        saveStatus.className = 'mc-status';
        saveStatus.textContent = 'Saving...';
        btnSave.disabled = true;

        try {
            const response = await fetch('/mc/api/save-coords.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(saveData)
            });

            const responseData = await response.json();

            if (response.ok) {
                saveStatus.className = 'mc-status success';
                saveStatus.textContent = `Saved "${setName}" with ${responseData.coordinates_count} coordinates!`;
                setNameInput.value = ''; // Clear the name field

                // Clear unsaved changes indicator
                originalCoordText = coordInput.value.trim();
                hasUnsavedChanges = false;
                unsavedWarning.style.display = 'none';

                // Clear loaded set tracking since we saved as a new set
                currentLoadedSetId = null;
                currentLoadedSetName = null;
                currentLoadedCoordCount = 0;
                btnUpdate.disabled = true;
                btnUpdate.textContent = 'Update';
            } else {
                saveStatus.className = 'mc-status error';
                saveStatus.textContent = `Error: ${responseData.message || 'Failed to save coordinates'}`;
            }
        } catch (error) {
            saveStatus.className = 'mc-status error';
            saveStatus.textContent = `Network error: ${error.message}`;
        } finally {
            btnSave.disabled = false;
        }
    });

    // Update existing coordinate set
    btnUpdate.addEventListener('click', async function() {
        if (!currentLoadedSetId) return;

        const text = coordInput.value.trim();

        // Validate
        if (!text) {
            saveStatus.className = 'mc-status error';
            saveStatus.textContent = 'Please enter some coordinates first.';
            return;
        }

        // Parse coordinates
        const result = parseCoordinates(text);

        if (result.points.length === 0) {
            saveStatus.className = 'mc-status error';
            saveStatus.textContent = 'No valid coordinates found to save.';
            return;
        }

        // Check for significant reduction (50% threshold)
        const reductionThreshold = 0.5;
        if (result.points.length < currentLoadedCoordCount * reductionThreshold) {
            const confirmMsg = `Warning: You're reducing coordinates from ${currentLoadedCoordCount} to ${result.points.length}. This will permanently update "${currentLoadedSetName}". Continue?`;
            if (!confirm(confirmMsg)) {
                return; // User cancelled
            }
        }

        // Prepare data for API
        const updateData = {
            coordinate_set_id: currentLoadedSetId,
            name: currentLoadedSetName,
            description: '',
            coordinates: result.points.map((point, index) => ({
                x: point.x,
                y: point.y,
                z: point.z,
                label: point.label || null,
                color: point.colorName || null,
                segmentId: point.segmentId
            }))
        };

        // Show updating status
        saveStatus.className = 'mc-status';
        saveStatus.textContent = 'Updating...';
        btnUpdate.disabled = true;

        try {
            const response = await fetch('/mc/api/save-coords.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(updateData)
            });

            const responseData = await response.json();

            if (response.ok) {
                saveStatus.className = 'mc-status success';
                saveStatus.textContent = `Updated "${currentLoadedSetName}" with ${responseData.coordinates_count} coordinates!`;

                // Update tracking
                currentLoadedCoordCount = result.points.length;
                originalCoordText = coordInput.value.trim();
                hasUnsavedChanges = false;
                unsavedWarning.style.display = 'none';
            } else {
                saveStatus.className = 'mc-status error';
                saveStatus.textContent = `Error: ${responseData.message || 'Failed to update coordinates'}`;
            }
        } catch (error) {
            saveStatus.className = 'mc-status error';
            saveStatus.textContent = `Network error: ${error.message}`;
        } finally {
            btnUpdate.disabled = false;
        }
    });

    // Load functionality
    const coordSetSelect = document.getElementById('coord-set-select');
    const btnLoad = document.getElementById('btn-load');
    const btnUpdate = document.getElementById('btn-update');
    const loadStatus = document.getElementById('load-status');
    const unsavedWarning = document.getElementById('unsaved-warning');

    let originalCoordText = coordInput.value.trim();
    let hasUnsavedChanges = false;

    // Track currently loaded coordinate set for Update functionality
    let currentLoadedSetId = null;
    let currentLoadedSetName = null;
    let currentLoadedCoordCount = 0;

    // Track changes to show unsaved warning
    coordInput.addEventListener('input', function() {
        hasUnsavedChanges = coordInput.value.trim() !== originalCoordText;
        unsavedWarning.style.display = hasUnsavedChanges ? 'inline' : 'none';
    });

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
            }
        } catch (error) {
            console.error('Failed to load coordinate sets:', error);
        }
    }

    // Enable/disable load button based on selection
    coordSetSelect.addEventListener('change', function() {
        btnLoad.disabled = !coordSetSelect.value;
        loadStatus.className = 'mc-status';
        loadStatus.textContent = '';
    });

    // Load selected coordinate set
    btnLoad.addEventListener('click', async function() {
        const setId = coordSetSelect.value;
        if (!setId) return;

        loadStatus.className = 'mc-status';
        loadStatus.textContent = 'Loading...';
        btnLoad.disabled = true;

        try {
            const response = await fetch(`/mc/api/load-coords.php?set_id=${setId}`);
            const data = await response.json();

            if (response.ok && data.success) {
                // Reconstruct the text format from coordinates
                const textLines = [];
                let currentColor = null;
                let currentSegment = null;

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

                // Set the textarea content
                coordInput.value = textLines.join('\n');
                originalCoordText = coordInput.value.trim();
                hasUnsavedChanges = false;
                unsavedWarning.style.display = 'none';

                // Track loaded set for Update functionality
                currentLoadedSetId = data.set.coordinate_set_id;
                currentLoadedSetName = data.set.name;
                currentLoadedCoordCount = data.coordinates.length;

                // Enable and update the Update button text
                btnUpdate.disabled = false;
                // Truncate set name to ~17 chars to keep "Update " + name under 20 chars
                const truncatedName = currentLoadedSetName.length > 13
                    ? currentLoadedSetName.substring(0, 13) + '...'
                    : currentLoadedSetName;
                btnUpdate.textContent = `Update ${truncatedName}`;

                // Parse and render
                parseAndRender();

                loadStatus.className = 'mc-status success';
                loadStatus.textContent = `Loaded "${data.set.name}" with ${data.coordinates.length} coordinates!`;
            } else {
                loadStatus.className = 'mc-status error';
                loadStatus.textContent = `Error: ${data.message || 'Failed to load coordinates'}`;
            }
        } catch (error) {
            loadStatus.className = 'mc-status error';
            loadStatus.textContent = `Network error: ${error.message}`;
        } finally {
            btnLoad.disabled = false;
        }
    });

    // Load coordinate sets on page load
    loadCoordinateSets();

    // Auto-parse on load if there's content
    if (coordInput.value.trim()) {
        parseAndRender();
    }
});
</script>
