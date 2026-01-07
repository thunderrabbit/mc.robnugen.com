<div class="PagePanel">
    <h1>🎮 Minecraft Coordinate Visualizer</h1>
    <p>Welcome back, <?= $username ?>! Paste your coordinates below to visualize them in 3D.</p>
</div>

<div class="mc-visualizer-container">
    <!-- Left Panel: Input & Controls -->
    <div class="mc-input-panel">
        <h2>Coordinates</h2>

        <textarea id="coord-input" cols="50" rows="10">[-278,  80, 487]
[-278, -34, 272]
[-423, -59, 410]
[-271, -19, 266]
[-263, -14, 266]
[-254, -10, 287]
[-238, -10, 287]
[-226,   3, 219]
[-206,   3, 218]
[-226,  -5, 270]
[-206,   3, 247]</textarea>

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
                <input type="checkbox" id="toggle-connect">
                Connect Points (Path)
            </label>
            <label>
                <input type="checkbox" id="toggle-nether">
                Nether Scale (÷8)
            </label>
        </div>

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
// Coordinate Parser
function parseCoordinates(text) {
    const points = [];
    const warnings = [];
    const lines = text.split('\n');

    // Regex for bracket format: [-278, 80, 487]
    const bracketRegex = /\[(-?\d+)\s*,\s*(-?\d+)\s*,\s*(-?\d+)\]/g;

    lines.forEach((line, lineNum) => {
        let match;
        while ((match = bracketRegex.exec(line)) !== null) {
            points.push({
                x: parseInt(match[1]),
                y: parseInt(match[2]),
                z: parseInt(match[3]),
                id: `point-${points.length}`,
                sourceLine: lineNum + 1
            });
        }
    });

    return { points, warnings };
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
        this.pathLine = null;

        // Origin offset: makes (-281, 80, 487) the center (0, 0, 0)
        this.originOffset = { x: -281, y: 80, z: 487 };

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

        // Grid and Axes - positioned at the origin offset
        const gridSize = 1000;
        const gridDivisions = 20;
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

    renderPoints(points, showPath = false) {
        // Clear existing points
        this.pointMeshes.forEach(mesh => this.scene.remove(mesh));
        this.pointMeshes = [];

        if (this.pathLine) {
            this.scene.remove(this.pathLine);
            this.pathLine = null;
        }

        this.points = points;

        if (points.length === 0) return;

        // Create point meshes
        const geometry = new THREE.SphereGeometry(5, 16, 16);
        const material = new THREE.MeshPhongMaterial({
            color: 0x00aaff,
            emissive: 0x0055aa,
            shininess: 30
        });

        points.forEach(point => {
            const mesh = new THREE.Mesh(geometry, material);
            // Use actual Minecraft coordinates
            mesh.position.set(point.x, point.y, point.z);
            mesh.userData = point;
            this.scene.add(mesh);
            this.pointMeshes.push(mesh);
        });

        // Create path line if requested
        if (showPath && points.length > 1) {
            // Use actual Minecraft coordinates
            const pathPoints = points.map(p => new THREE.Vector3(p.x, p.y, p.z));
            const lineGeometry = new THREE.BufferGeometry().setFromPoints(pathPoints);
            const lineMaterial = new THREE.LineBasicMaterial({
                color: 0xffaa00,
                linewidth: 2
            });
            this.pathLine = new THREE.Line(lineGeometry, lineMaterial);
            this.scene.add(this.pathLine);
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
        visualizer.renderPoints(result.points, showPath);

        parseStatus.className = 'mc-status success';
        parseStatus.textContent = `Parsed ${result.points.length} point${result.points.length !== 1 ? 's' : ''} successfully!`;
    }

    // Event listeners
    btnParse.addEventListener('click', parseAndRender);

    btnClear.addEventListener('click', function() {
        coordInput.value = '';
        parseStatus.className = 'mc-status';
        parseStatus.textContent = '';
        visualizer.renderPoints([]);
    });

    btnTopView.addEventListener('click', () => visualizer.setTopView());
    btnResetView.addEventListener('click', () => visualizer.resetView());

    toggleConnect.addEventListener('change', parseAndRender);

    // Auto-parse on load if there's content
    if (coordInput.value.trim()) {
        parseAndRender();
    }
});
</script>

