<?php
// Sample mode flag - defaults to false for logged-in users
$is_sample_mode = $is_sample_mode ?? false;
?>
<?php if ($is_sample_mode): ?>
    <div class="PagePanel">
        <p>Try the visualizer! <strong><a href="/login/register.php" id="register-link-top" style="color: #667eea; text-decoration: underline;">Create a free account</a></strong> to save your coordinates (no email or credit card required).</p>
    </div>
<?php else: ?>
    <div class="PagePanel">
        <h1>🎮 Minecraft Coordinate Visualizer</h1>
        <p>Welcome back, <?= $username ?>! Paste your coordinates below to visualize them in 3D.</p>
    </div>
<?php endif; ?>


<div class="mc-visualizer-container">
    <!-- Left Panel: Input & Controls -->
    <div class="mc-input-panel">
        <h2>Coordinates</h2>

        <textarea id="coord-input" cols="50" rows="10" placeholder="Paste coordinates here...
Examples:
[-278,80,487] base portal
-278 80 487
x=-278 y=80 z=487
Multiple formats supported!">
</textarea>

        <div class="mc-button-group">
            <button id="btn-parse" class="btn-primary">Parse & Visualize</button>
            <button id="btn-clear" class="btn-secondary">Clear</button>
        </div>

        <div id="parse-status" class="mc-status"></div>

        <?php if ($is_sample_mode): ?>
            <div class="mc-cta-box" style="margin-top: 15px; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; text-align: center;">
                <p style="margin: 0; color: white; font-size: 14px;">
                    💾 Want to save your coordinates?
                    <a href="/login/register.php" id="register-link-cta" style="color: #ffd700; font-weight: bold; text-decoration: underline;">Create a free account</a>
                    <br><span style="font-size: 12px; opacity: 0.9;">(No email or credit card required)</span>
                </p>
            </div>
        <?php endif; ?>

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
                <input type="checkbox" id="toggle-flatten">
                Flatten to Y=80
            </label>
            <label>
                <input type="checkbox" id="toggle-chunky">
                Get Chunky
            </label>
            <div id="chunk-display" style="display: none; margin-left: 10px; color: #000;">
                Chunk: --
            </div>
        </div>

        <?php if (!$is_sample_mode): ?>
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
            <button id="btn-update" class="btn-primary" style="width: 100%; margin-top: 10px; display: none;" disabled>Update</button>
        <?php endif; ?>

        <?php if (!$is_sample_mode): ?>
            <hr>

            <h3>Save</h3>
            <div class="mc-form-group">
                <input type="text" id="set-name" placeholder="Enter coordinate set name..." class="mc-input">
                <button id="btn-save" class="btn-primary">Save Coordinates</button>
            </div>
            <div id="save-status" class="mc-status"></div>
        <?php endif; ?>


        <h3>Curve Overlay</h3>
        <div class="mc-form-group">
            <select id="curve-overlay-select" class="mc-input">
                <option value="">-- None --</option>
            </select>
        </div>
        <div id="overlay-status" class="mc-status"></div>
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

// Coordinate Parser with Color Groups, Labels, and Chunks
function parseCoordinates(text) {
    const points = [];
    const pathSegments = []; // Array of arrays - each inner array is a connected path
    const chunks = []; // NEW: Array of chunk objects
    const warnings = [];

    // Regex for bracket format: [-278, 80, 487]
    const bracketRegex = /\[(-?\d+)\s*,\s*(-?\d+)\s*,\s*(-?\d+)\]\s*([^\n,]*)/g;

    // NEW: Regex for chunk format: [X, Z] (2 coordinates only)
    const chunkRegex = /\[(-?\d+)\s*,\s*(-?\d+)\]/g;

    // Split by commas that are OUTSIDE brackets to find path segments
    const segmentTexts = text.split(/,(?![^\[]*\])/);

    let currentColorHex = 0x00aaff; // Default blue color for rendering
    let currentColorName = null; // Track the original color name for saving
    let currentChunkType = null; // NEW: Track 'mine' or 'unavailable'

    segmentTexts.forEach((segmentText, segmentIdx) => {
        const segmentPoints = [];
        const lines = segmentText.split('\n');

        lines.forEach(line => {
            const trimmedLine = line.trim();

            // NEW: Check if line is a chunk type keyword
            if (trimmedLine === 'mine' || trimmedLine === 'unavailable') {
                currentChunkType = trimmedLine;
                return; // Skip to next line
            }

            // Check if this line is a color name (no brackets)
            if (trimmedLine && !trimmedLine.includes('[')) {
                const colorName = trimmedLine.toLowerCase();
                if (COLOR_MAP[colorName] !== undefined) {
                    currentColorHex = COLOR_MAP[colorName];
                    currentColorName = colorName; // Store the original color name
                    currentChunkType = null; // Reset chunk type when we hit a color
                }
                return; // Skip to next line
            }

            // NEW: If we're in chunk mode, parse chunks
            if (currentChunkType) {
                chunkRegex.lastIndex = 0;
                let match;
                while ((match = chunkRegex.exec(line)) !== null) {
                    chunks.push({
                        chunk_x: parseInt(match[1]),
                        chunk_z: parseInt(match[2]),
                        chunk_type: currentChunkType
                    });
                }
                return; // Skip coordinate parsing
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

    return { points, pathSegments, chunks, warnings };
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
        this.chunkPlanes = []; // NEW: Array to store chunk plane meshes
        this.raycaster = new THREE.Raycaster(); // For mouse picking
        this.mouse = new THREE.Vector2(); // Normalized mouse coordinates
        this.userHasMovedCamera = false; // Track if user has manually moved the camera

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

        // Track when user manually interacts with the camera
        this.controls.addEventListener('start', () => {
            this.userHasMovedCamera = true;
        });

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

    renderPoints(points, pathSegments = [], showPath = false, flatten = false, recenterCamera = true) {
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
            // Use actual Minecraft coordinates, optionally flatten Y to 80
            const yPos = flatten ? 80 : point.y;
            mesh.position.set(point.x, yPos, point.z);
            mesh.userData = point;
            this.scene.add(mesh);
            this.pointMeshes.push(mesh);
        });

        // Create path lines for each segment if requested
        if (showPath && pathSegments.length > 0) {
            pathSegments.forEach(segment => {
                if (segment.length > 1) {
                    // Use actual Minecraft coordinates, optionally flatten Y to 80
                    const pathPoints = segment.map(p => {
                        const yPos = flatten ? 80 : p.y;
                        return new THREE.Vector3(p.x, yPos, p.z);
                    });
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

        // Center camera on points only if requested (not when just toggling flatten)
        if (recenterCamera) {
            this.centerCameraOnPoints();
        }
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

    setChunkyView() {
        // Position camera to look straight down at the scene
        const target = this.controls.target;
        const distance = 500;
        this.camera.position.set(target.x, target.y + distance, target.z);
        this.camera.lookAt(target.x, target.y, target.z);

        // Lock rotation to keep the view perfectly top-down
        this.controls.enableRotate = false;

        this.controls.update();
    }

    resetView() {
        this.centerCameraOnPoints();
    }

    getChunkFromMouse(mouseX, mouseY) {
        // Update mouse coordinates (normalized device coordinates)
        const rect = this.renderer.domElement.getBoundingClientRect();
        this.mouse.x = ((mouseX - rect.left) / rect.width) * 2 - 1;
        this.mouse.y = -((mouseY - rect.top) / rect.height) * 2 + 1;

        // Update raycaster
        this.raycaster.setFromCamera(this.mouse, this.camera);

        // Create a plane at Y=80 (or current target Y) to raycast against
        const planeY = this.controls.target.y;
        const plane = new THREE.Plane(new THREE.Vector3(0, 1, 0), -planeY);
        const intersectPoint = new THREE.Vector3();

        // Get intersection point with the plane
        if (this.raycaster.ray.intersectPlane(plane, intersectPoint)) {
            // Convert world coordinates to chunk coordinates
            const chunkX = Math.floor(intersectPoint.x / 16);
            const chunkZ = Math.floor(intersectPoint.z / 16);
            return { chunkX, chunkZ, worldX: intersectPoint.x, worldZ: intersectPoint.z };
        }

        return null;
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
    const toggleFlatten = document.getElementById('toggle-flatten');
    const toggleChunky = document.getElementById('toggle-chunky');
    const parseStatus = document.getElementById('parse-status');

    // Initialize visualizer
    const visualizer = new MCVisualizer('canvas-container');

    // Auto-save coordinates to localStorage (for anonymous users)
    const STORAGE_KEY = 'mc_temp_coords';
    const isSampleMode = <?= $is_sample_mode ? 'true' : 'false' ?>;

    if (isSampleMode) {
        // Debounced auto-save function
        let saveTimeout;
        coordInput.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                const text = coordInput.value.trim();
                if (text) {
                    localStorage.setItem(STORAGE_KEY, text);
                    localStorage.setItem(STORAGE_KEY + '_timestamp', Date.now());
                }
            }, 1000); // Save 1 second after user stops typing
        });
    }

    // Attach coordinates to registration/login links
    if (isSampleMode) {
        const registerLinkTop = document.getElementById('register-link-top');
        const registerLinkCta = document.getElementById('register-link-cta');

        function attachCoords(event) {
            const text = coordInput.value.trim();
            if (text) {
                // Save to session via hidden form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/api/save-temp-coords.php';
                form.style.display = 'none';

                const input = document.createElement('input');
                input.name = 'temp_coords';
                input.value = text;
                form.appendChild(input);

                const redirect = document.createElement('input');
                redirect.name = 'redirect';
                redirect.value = event.target.href;
                form.appendChild(redirect);

                document.body.appendChild(form);
                form.submit();
                event.preventDefault();
            }
        }

        if (registerLinkTop) registerLinkTop.addEventListener('click', attachCoords);
        if (registerLinkCta) registerLinkCta.addEventListener('click', attachCoords);
    }

    // Track last parsed text to avoid recentering camera on toggle changes
    let lastParsedText = '';
    let isFirstParse = true; // Track if this is the first parse

    // Track chunks in memory for click-to-claim functionality
    let currentChunks = [];

    // Overlay state tracking for curve preview
    let overlayLoadCount = 0; // Track number of overlay loads for color toggling
    let currentOverlayMeshes = []; // Store overlay point meshes for cleanup
    let currentOverlayCoordinates = null; // Store current overlay coordinates for re-rendering

    // Function to render overlay points with alternating colors
    function renderOverlay(coordinates, incrementLoadCount = true) {
        // Clear previous overlay
        clearOverlay();

        if (!coordinates || coordinates.length === 0) return;

        // Store coordinates for re-rendering when flatten changes
        currentOverlayCoordinates = coordinates;

        // Increment load count and determine color (only when loading new curve)
        if (incrementLoadCount) {
            overlayLoadCount++;
        }
        const overlayColor = (overlayLoadCount % 2 === 1) ? 0xff00ff : 0x00ffff; // magenta : cyan

        // Check if flatten is enabled
        const flatten = toggleFlatten.checked;

        // Create smaller spheres for overlay points to distinguish from main points
        const geometry = new THREE.SphereGeometry(0.8, 16, 16);
        const material = new THREE.MeshPhongMaterial({
            color: overlayColor,
            emissive: overlayColor,
            emissiveIntensity: 0.4,
            shininess: 30,
            transparent: true,
            opacity: 0.8
        });

        coordinates.forEach(coord => {
            const mesh = new THREE.Mesh(geometry, material);
            // Use actual Y coordinate, or flatten to 80 if flatten is enabled
            const yPos = flatten ? 80 : coord.y;
            mesh.position.set(coord.x, yPos, coord.z);
            visualizer.scene.add(mesh);
            currentOverlayMeshes.push(mesh);
        });
    }

    // Function to clear overlay points
    function clearOverlay() {
        currentOverlayMeshes.forEach(mesh => visualizer.scene.remove(mesh));
        currentOverlayMeshes = [];
        currentOverlayCoordinates = null;
    }

    // Restore coordinates from session or localStorage (for logged-in users)
    if (!isSampleMode) {
        // Check for server-side session data first
        const sessionCoords = <?= isset($_SESSION['temp_coords']) ? json_encode($_SESSION['temp_coords']) : 'null' ?>;

        if (sessionCoords) {
            coordInput.value = sessionCoords;
            parseAndRender();

            // Clear session data via AJAX
            fetch('/api/clear-temp-coords.php', { method: 'POST' });

            // Show success message
            parseStatus.className = 'mc-status success';
            parseStatus.textContent = 'Your coordinates have been restored!';
        } else {
            // Fallback to localStorage
            const storedCoords = localStorage.getItem(STORAGE_KEY);
            const timestamp = localStorage.getItem(STORAGE_KEY + '_timestamp');

            // Only restore if less than 24 hours old
            if (storedCoords && timestamp) {
                const age = Date.now() - parseInt(timestamp);
                const maxAge = 24 * 60 * 60 * 1000; // 24 hours

                if (age < maxAge) {
                    coordInput.value = storedCoords;
                    parseAndRender();

                    // Show success message
                    parseStatus.className = 'mc-status success';
                    parseStatus.textContent = 'Your coordinates have been restored!';
                }
            }

            // Clear localStorage after restore attempt
            localStorage.removeItem(STORAGE_KEY);
            localStorage.removeItem(STORAGE_KEY + '_timestamp');
        }
    }

    // Populate curve overlay dropdown
    fetch('/api/list-curves.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.curves) {
                const select = document.getElementById('curve-overlay-select');
                data.curves.forEach(curve => {
                    const option = document.createElement('option');
                    option.value = curve.filename;
                    option.textContent = curve.display;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading curve list:', error);
        });

    // Handle curve overlay selection
    const curveOverlaySelect = document.getElementById('curve-overlay-select');
    const overlayStatus = document.getElementById('overlay-status');

    if (curveOverlaySelect) {
        curveOverlaySelect.addEventListener('change', function() {
            const filename = this.value;

            if (!filename) {
                // Clear overlay when "None" is selected
                clearOverlay();
                overlayStatus.textContent = '';
                overlayStatus.className = 'mc-status';
                return;
            }

            // Load and render the selected curve
            fetch('/api/load-curve.php?filename=' + encodeURIComponent(filename))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.coordinates) {
                        renderOverlay(data.coordinates);
                        overlayStatus.className = 'mc-status success';
                        overlayStatus.textContent = `Overlay: ${data.coordinates.length} points`;
                    } else {
                        overlayStatus.className = 'mc-status error';
                        overlayStatus.textContent = 'Error loading curve: ' + (data.error || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Error loading curve:', error);
                    overlayStatus.className = 'mc-status error';
                    overlayStatus.textContent = 'Error loading curve file';
                });
        });
    }

    // Delete curve functionality with # key
    if (curveOverlaySelect) {
        // Function to delete the currently selected curve
        async function deleteCurrentCurve() {
            const filename = curveOverlaySelect.value;

            if (!filename) {
                overlayStatus.className = 'mc-status error';
                overlayStatus.textContent = 'No curve selected to delete';
                return;
            }

            // Get the current option and the next one
            const currentOption = curveOverlaySelect.options[curveOverlaySelect.selectedIndex];
            const nextOption = curveOverlaySelect.options[curveOverlaySelect.selectedIndex + 1];
            const prevOption = curveOverlaySelect.options[curveOverlaySelect.selectedIndex - 1];

            overlayStatus.className = 'mc-status';
            overlayStatus.textContent = 'Deleting...';

            try {
                const formData = new FormData();
                formData.append('filename', filename);

                const response = await fetch('/api/delete-curve.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Remove the option from dropdown
                    currentOption.remove();

                    // Select next curve (or previous if no next, or None if no options left)
                    if (nextOption) {
                        curveOverlaySelect.value = nextOption.value;
                        // Trigger change event to load the next curve
                        curveOverlaySelect.dispatchEvent(new Event('change'));
                    } else if (prevOption && prevOption.value !== '') {
                        curveOverlaySelect.value = prevOption.value;
                        curveOverlaySelect.dispatchEvent(new Event('change'));
                    } else {
                        // No more curves, select "None"
                        curveOverlaySelect.value = '';
                        clearOverlay();
                        overlayStatus.className = 'mc-status success';
                        overlayStatus.textContent = 'Curve deleted (no more curves)';
                    }

                    if (nextOption || (prevOption && prevOption.value !== '')) {
                        overlayStatus.className = 'mc-status success';
                        overlayStatus.textContent = 'Curve deleted';
                    }
                } else {
                    overlayStatus.className = 'mc-status error';
                    overlayStatus.textContent = 'Error deleting curve: ' + (data.error || 'Unknown error');
                }
            } catch (error) {
                console.error('Error deleting curve:', error);
                overlayStatus.className = 'mc-status error';
                overlayStatus.textContent = 'Error deleting curve file';
            }
        }

        // Add keyboard listener for 3 key
        document.addEventListener('keydown', function(event) {
            // Check if 3 key is pressed (without shift)
            if (event.key === '3' && !event.shiftKey) {
                // Only trigger if curve overlay dropdown has focus or a curve is selected
                const activeElement = document.activeElement;
                if (activeElement === curveOverlaySelect || curveOverlaySelect.value) {
                    event.preventDefault();
                    deleteCurrentCurve();
                }
            }
        });
    }



    // Helper function to append a chunk to the textarea
    function appendChunkToTextarea(chunkX, chunkZ, chunkType) {
        let text = coordInput.value.trim();

        // Check if we already have a "mine" section
        const mineIndex = text.indexOf('mine');

        if (mineIndex === -1) {
            // No "mine" section exists, add one
            if (text) text += '\n\n';
            text += 'mine\n';
        }

        // Find the last line of the "mine" section
        const lines = text.split('\n');
        let insertIndex = -1;
        let inMineSection = false;

        for (let i = 0; i < lines.length; i++) {
            if (lines[i].trim() === 'mine') {
                inMineSection = true;
                insertIndex = i + 1;
            } else if (inMineSection && lines[i].trim() !== '' && !lines[i].includes('[')) {
                // Hit another section (like "unavailable" or a color)
                break;
            } else if (inMineSection && lines[i].includes('[')) {
                insertIndex = i + 1;
            }
        }

        // Add the chunk in the format [X,Z]
        const chunkStr = `[${chunkX},${chunkZ}]`;

        if (insertIndex !== -1) {
            lines.splice(insertIndex, 0, chunkStr);
            text = lines.join('\n');
        } else {
            text += `\n${chunkStr}`;
        }

        coordInput.value = text;
    }

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
        const flatten = toggleFlatten.checked;

        // Only recenter camera if:
        // 1. This is the first parse ever, OR
        // 2. The text has changed AND the user hasn't manually moved the camera
        const textChanged = (text !== lastParsedText);
        const recenterCamera = isFirstParse || (textChanged && !visualizer.userHasMovedCamera);

        // Update tracking variables
        if (textChanged) {
            lastParsedText = text;
            isFirstParse = false;
        }

        visualizer.renderPoints(result.points, result.pathSegments, showPath, flatten, recenterCamera);

        // Sync in-memory chunks with parsed chunks
        currentChunks = result.chunks;

        // Render parsed chunks
        visualizer.renderChunks(currentChunks);

        const segmentText = result.pathSegments.length > 1 ? ` in ${result.pathSegments.length} segments` : '';
        const chunkText = currentChunks.length > 0 ? ` + ${currentChunks.length} chunks` : '';
        parseStatus.className = 'mc-status success';
        parseStatus.textContent = `Parsed ${result.points.length} point${result.points.length !== 1 ? 's' : ''}${segmentText}${chunkText} successfully!`;
    }

    // Event listeners
    btnParse.addEventListener('click', parseAndRender);

    btnClear.addEventListener('click', function() {
        coordInput.value = '';
        parseStatus.className = 'mc-status';
        parseStatus.textContent = '';
        visualizer.renderPoints([]);
        visualizer.renderChunks([]); // Clear chunk overlays

        // Clear loaded set tracking
        currentLoadedSetId = null;
        currentLoadedSetName = null;
        currentLoadedCoordCount = 0;
        if (btnUpdate) {
            btnUpdate.disabled = true;
            btnUpdate.style.display = 'none'; // Hide the button
            btnUpdate.textContent = 'Update';
        }
    });

    btnTopView.addEventListener('click', () => visualizer.setTopView());
    btnResetView.addEventListener('click', () => visualizer.resetView());

    toggleConnect.addEventListener('change', parseAndRender);
    toggleFlatten.addEventListener('change', function() {
        parseAndRender();
        // Re-render overlay with new flatten state (don't increment color)
        if (currentOverlayCoordinates) {
            renderOverlay(currentOverlayCoordinates, false);
        }
    });

    toggleChunky.addEventListener('change', function() {
        const chunkDisplay = document.getElementById('chunk-display');

        if (toggleChunky.checked) {
            // Rotate camera to look straight down
            visualizer.setChunkyView();

            // Show chunk display
            chunkDisplay.style.display = 'inline-block';

            // Add mouse move listener to canvas
            const canvas = visualizer.renderer.domElement;

            const onMouseMove = function(event) {
                if (!toggleChunky.checked) return;

                const chunkInfo = visualizer.getChunkFromMouse(event.clientX, event.clientY);
                if (chunkInfo) {
                    chunkDisplay.textContent = `Chunk: [${chunkInfo.chunkX}, ${chunkInfo.chunkZ}]`;
                } else {
                    chunkDisplay.textContent = 'Chunk: --';
                }
            };

            // Store the listener so we can remove it later
            canvas._chunkMouseMove = onMouseMove;
            canvas.addEventListener('mousemove', onMouseMove);

            // Add click listener to claim chunks
            const onChunkClick = function(event) {
                if (!toggleChunky.checked) return;

                const chunkInfo = visualizer.getChunkFromMouse(event.clientX, event.clientY);
                if (chunkInfo) {
                    const { chunkX, chunkZ } = chunkInfo;

                    // Check if this chunk is already claimed
                    const alreadyClaimed = currentChunks.some(
                        chunk => chunk.chunk_x === chunkX && chunk.chunk_z === chunkZ && chunk.chunk_type === 'mine'
                    );

                    if (!alreadyClaimed) {
                        // Add to in-memory array
                        currentChunks.push({
                            chunk_x: chunkX,
                            chunk_z: chunkZ,
                            chunk_type: 'mine'
                        });

                        // Append to textarea
                        appendChunkToTextarea(chunkX, chunkZ, 'mine');

                        // Re-render chunks immediately
                        visualizer.renderChunks(currentChunks);

                        // Update status
                        parseStatus.className = 'mc-status success';
                        parseStatus.textContent = `Claimed chunk [${chunkX}, ${chunkZ}]! Total: ${currentChunks.length} chunks`;
                    }
                }
            };

            // Store the click listener so we can remove it later
            canvas._chunkClick = onChunkClick;
            canvas.addEventListener('click', onChunkClick);

        } else {
            // Hide chunk display
            chunkDisplay.style.display = 'none';
            chunkDisplay.textContent = 'Chunk: --';

            // Remove mouse move listener
            const canvas = visualizer.renderer.domElement;
            if (canvas._chunkMouseMove) {
                canvas.removeEventListener('mousemove', canvas._chunkMouseMove);
                canvas._chunkMouseMove = null;
            }

            // Remove click listener
            if (canvas._chunkClick) {
                canvas.removeEventListener('click', canvas._chunkClick);
                canvas._chunkClick = null;
            }

            // Re-enable rotation
            visualizer.controls.enableRotate = true;
        }
    });

    // Save coordinates (only if elements exist - not in sample mode)
    const btnSave = document.getElementById('btn-save');
    const btnUpdate = document.getElementById('btn-update');
    const setNameInput = document.getElementById('set-name');
    const saveStatus = document.getElementById('save-status');

    if (btnSave) {
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
            })),
            chunks: result.chunks // Include chunks for saving
        };

        // Show saving status
        saveStatus.className = 'mc-status';
        saveStatus.textContent = 'Saving...';
        btnSave.disabled = true;

        try {
            const response = await fetch('/api/save-coords.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(saveData)
            });

            const responseData = await response.json();

            if (response.ok) {
                saveStatus.className = 'mc-status success';
                const chunkMsg = responseData.chunks_count > 0 ? ` + ${responseData.chunks_count} chunks` : '';
                saveStatus.textContent = `Saved "${setName}" with ${responseData.coordinates_count} coordinates${chunkMsg}!`;
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
    }

    // Update existing coordinate set (only if element exists - not in sample mode)
    if (btnUpdate) {
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
            })),
            chunks: result.chunks // Include chunks for updating
        };

        // Show updating status
        saveStatus.className = 'mc-status';
        saveStatus.textContent = 'Updating...';
        btnUpdate.disabled = true;

        try {
            const response = await fetch('/api/save-coords.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(updateData)
            });

            const responseData = await response.json();

            if (response.ok) {
                saveStatus.className = 'mc-status success';
                const chunkMsg = responseData.chunks_count > 0 ? ` + ${responseData.chunks_count} chunks` : '';
                saveStatus.textContent = `Updated "${currentLoadedSetName}" with ${responseData.coordinates_count} coordinates${chunkMsg}!`;

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
    }

    // Load functionality (only if elements exist - not in sample mode)
    const coordSetSelect = document.getElementById('coord-set-select');
    const btnLoad = document.getElementById('btn-load');
    const loadStatus = document.getElementById('load-status');
    const unsavedWarning = document.getElementById('unsaved-warning');

    let originalCoordText = coordInput.value.trim();
    let hasUnsavedChanges = false;

    // Track currently loaded coordinate set for Update functionality
    let currentLoadedSetId = null;
    let currentLoadedSetName = null;
    let currentLoadedCoordCount = 0;

    // Track changes to show unsaved warning (only if element exists)
    if (unsavedWarning) {
        coordInput.addEventListener('input', function() {
            hasUnsavedChanges = coordInput.value.trim() !== originalCoordText;
            unsavedWarning.style.display = hasUnsavedChanges ? 'inline' : 'none';
        });
    }

    // Load saved coordinate sets into dropdown
    async function loadCoordinateSets() {
        try {
            const response = await fetch('/api/list-coords.php');
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

                // Return whether user has data
                return data.sets.length > 0;
            }
            return false;
        } catch (error) {
            console.error('Failed to load coordinate sets:', error);
            return false;
        }
    }

    // Load demo coordinate set for new users
    async function loadDemoSet(setId) {
        try {
            const response = await fetch(`/api/load-demo.php?set_id=${setId}`);
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

                // Reconstruct chunks text in 2D array format
                if (data.chunks && data.chunks.length > 0) {
                    // Group chunks by type
                    const chunksByType = {};
                    data.chunks.forEach(chunk => {
                        if (!chunksByType[chunk.chunk_type]) {
                            chunksByType[chunk.chunk_type] = [];
                        }
                        chunksByType[chunk.chunk_type].push(chunk);
                    });

                    // For each chunk type, arrange in 2D grid
                    Object.keys(chunksByType).forEach(chunkType => {
                        const chunks = chunksByType[chunkType];

                        // Add chunk type header
                        if (textLines.length > 0) textLines.push(''); // Blank line
                        textLines.push(chunkType);

                        // Group by Z coordinate (rows)
                        const rowsByZ = {};
                        chunks.forEach(chunk => {
                            if (!rowsByZ[chunk.chunk_z]) {
                                rowsByZ[chunk.chunk_z] = [];
                            }
                            rowsByZ[chunk.chunk_z].push(chunk);
                        });

                        // Find the range of X values across all chunks
                        const allX = chunks.map(c => c.chunk_x);
                        const minX = Math.min(...allX);
                        const maxX = Math.max(...allX);

                        // Sort Z values in descending order (higher Z first, like Y-axis)
                        const sortedZValues = Object.keys(rowsByZ)
                            .map(z => parseInt(z))
                            .sort((a, b) => b - a);

                        // Build each row
                        sortedZValues.forEach(z => {
                            // Create a map of X positions for this row
                            const rowChunksMap = {};
                            rowsByZ[z].forEach(chunk => {
                                rowChunksMap[chunk.chunk_x] = chunk;
                            });

                            // Build the row string with gaps filled
                            const rowParts = [];
                            for (let x = minX; x <= maxX; x++) {
                                if (rowChunksMap[x]) {
                                    // Chunk exists - format with 2-digit padding
                                    const xStr = x.toString().padStart(2, ' ');
                                    const zStr = z.toString().padStart(2, ' ');
                                    rowParts.push(`[${xStr},${zStr}]`);
                                } else {
                                    // Gap - add spaces (7 chars to match "[ X, Z]" width)
                                    rowParts.push('       ');
                                }
                            }

                            textLines.push(rowParts.join(''));
                        });
                    });
                }

                // Set the textarea content
                coordInput.value = textLines.join('\n');
                originalCoordText = coordInput.value.trim();
                hasUnsavedChanges = false;
                if (unsavedWarning) {
                    unsavedWarning.style.display = 'none';
                }

                // DO NOT track as loaded set (this is demo data)
                currentLoadedSetId = null;
                currentLoadedSetName = null;
                currentLoadedCoordCount = 0;

                // DO NOT enable Update button (demo is read-only)
                if (btnUpdate) {
                    btnUpdate.disabled = true;
                    btnUpdate.style.display = 'none';
                }

                // Parse and render
                parseAndRender();

                // Show subtle hint in load status
                if (loadStatus) {
                    loadStatus.className = 'mc-status';
                    loadStatus.textContent = `Example data loaded - save it to keep your changes!`;
                }
            }
        } catch (error) {
            console.error('Failed to load demo set:', error);
        }
    }


    // Enable/disable load button based on selection (only if elements exist)
    if (coordSetSelect && btnLoad) {
        coordSetSelect.addEventListener('change', function() {
            btnLoad.disabled = !coordSetSelect.value;
            if (loadStatus) {
                loadStatus.className = 'mc-status';
                loadStatus.textContent = '';
            }
        });
    }

    // Load selected coordinate set (only if element exists)
    if (btnLoad) {
        btnLoad.addEventListener('click', async function() {
        const setId = coordSetSelect.value;
        if (!setId) return;

        // Clear overlay when loading a database set
        clearOverlay();
        if (curveOverlaySelect) {
            curveOverlaySelect.value = '';
        }
        if (overlayStatus) {
            overlayStatus.textContent = '';
            overlayStatus.className = 'mc-status';
        }

        loadStatus.className = 'mc-status';
        loadStatus.textContent = 'Loading...';
        btnLoad.disabled = true;

        try {
            const response = await fetch(`/api/load-coords.php?set_id=${setId}`);
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

                // Reconstruct chunks text in 2D array format
                if (data.chunks && data.chunks.length > 0) {
                    // Group chunks by type
                    const chunksByType = {};
                    data.chunks.forEach(chunk => {
                        if (!chunksByType[chunk.chunk_type]) {
                            chunksByType[chunk.chunk_type] = [];
                        }
                        chunksByType[chunk.chunk_type].push(chunk);
                    });

                    // For each chunk type, arrange in 2D grid
                    Object.keys(chunksByType).forEach(chunkType => {
                        const chunks = chunksByType[chunkType];

                        // Add chunk type header
                        if (textLines.length > 0) textLines.push(''); // Blank line
                        textLines.push(chunkType);

                        // Group by Z coordinate (rows)
                        const rowsByZ = {};
                        chunks.forEach(chunk => {
                            if (!rowsByZ[chunk.chunk_z]) {
                                rowsByZ[chunk.chunk_z] = [];
                            }
                            rowsByZ[chunk.chunk_z].push(chunk);
                        });

                        // Find the range of X values across all chunks
                        const allX = chunks.map(c => c.chunk_x);
                        const minX = Math.min(...allX);
                        const maxX = Math.max(...allX);

                        // Sort Z values in descending order (higher Z first, like Y-axis)
                        const sortedZValues = Object.keys(rowsByZ)
                            .map(z => parseInt(z))
                            .sort((a, b) => b - a);

                        // Build each row
                        sortedZValues.forEach(z => {
                            // Create a map of X positions for this row
                            const rowChunksMap = {};
                            rowsByZ[z].forEach(chunk => {
                                rowChunksMap[chunk.chunk_x] = chunk;
                            });

                            // Build the row string with gaps filled
                            const rowParts = [];
                            for (let x = minX; x <= maxX; x++) {
                                if (rowChunksMap[x]) {
                                    // Chunk exists - format with 2-digit padding
                                    const xStr = x.toString().padStart(2, ' ');
                                    const zStr = z.toString().padStart(2, ' ');
                                    rowParts.push(`[${xStr},${zStr}]`);
                                } else {
                                    // Gap - add spaces (7 chars to match "[ X, Z]" width)
                                    rowParts.push('       ');
                                }
                            }

                            textLines.push(rowParts.join(''));
                        });
                    });
                }

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
                btnUpdate.style.display = 'block'; // Show the button
                btnUpdate.disabled = false;
                // Truncate set name to ~17 chars to keep "Update " + name under 20 chars
                const truncatedName = currentLoadedSetName.length > 13
                    ? currentLoadedSetName.substring(0, 13) + '...'
                    : currentLoadedSetName;
                btnUpdate.textContent = `Update ${truncatedName}`;

                // Parse and render
                parseAndRender();

                loadStatus.className = 'mc-status success';
                const chunkMsg = data.chunks && data.chunks.length > 0 ? ` + ${data.chunks.length} chunks` : '';
                loadStatus.textContent = `Loaded "${data.set.name}" with ${data.coordinates.length} coordinates${chunkMsg}!`;
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
    }

    <?php if (!$is_sample_mode): ?>
        // Load coordinate sets on page load, then check if we should load demo
        loadCoordinateSets().then(hasData => {
            // Only load demo if user has no saved data AND textarea is empty
            if (!hasData && !coordInput.value.trim()) {
                // New user with no restored coords - load demo set
                loadDemoSet(12);
            }
        });
    <?php else: ?>
        // Sample mode - always load demo
        loadDemoSet(12);
    <?php endif; ?>

    // Auto-parse on load if there's content
    if (coordInput.value.trim()) {
        parseAndRender();
    }
});
</script>
