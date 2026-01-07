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



<script>
// Placeholder for Three.js integration
document.addEventListener('DOMContentLoaded', function() {
    const coordInput = document.getElementById('coord-input');
    const btnParse = document.getElementById('btn-parse');
    const btnClear = document.getElementById('btn-clear');
    const parseStatus = document.getElementById('parse-status');

    btnParse.addEventListener('click', function() {
        const text = coordInput.value.trim();
        if (!text) {
            parseStatus.className = 'mc-status error';
            parseStatus.textContent = 'Please enter some coordinates first.';
            return;
        }

        // TODO: Implement coordinate parsing
        parseStatus.className = 'mc-status success';
        parseStatus.textContent = 'Ready to parse! Three.js integration coming next...';
    });

    btnClear.addEventListener('click', function() {
        coordInput.value = '';
        parseStatus.className = 'mc-status';
        parseStatus.textContent = '';
    });

    // TODO: Implement Three.js scene
    const container = document.getElementById('canvas-container');
    container.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #888; font-size: 1.2em;">3D Canvas (Three.js coming soon)</div>';
});
</script>
