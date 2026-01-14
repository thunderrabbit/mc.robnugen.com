# Welcome Anonymous Users - Implementation Plan

## Overview
Create a `/sample/` page that allows anonymous users to view and interact with the Minecraft coordinate visualizer without saving functionality. This is a quick-win implementation with structure that supports future refactoring into a JavaScript module.

## Goals
1. ✅ Reuse existing visualization code from `index.tpl.php`
2. ✅ Disable save/load functionality for anonymous users
3. ✅ Remove non-functional export buttons
4. ✅ Add clear CTA for account creation (no email/CC required)
5. ✅ Use welcome layout (`welcome_base.tpl.php`)
6. ✅ Auto-load demo data
7. ✅ Structure code for future JS module extraction

---

## Implementation Steps

### Step 1: Create Sample Page Route
**File:** `/wwwroot/sample/index.php` (NEW)

```php
<?php
# Must include here because DH runs FastCGI
# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

// Sample page - no authentication required
$page = new \Template(config: $config);
$page->setTemplate("layout/welcome_base.tpl.php");
$page->set("page_title", "Try It - Minecraft Coordinate Visualizer");

// Get the inner content
$inner_page = new \Template(config: $config);
$inner_page->setTemplate("index.tpl.php");
$inner_page->set("username", "Guest");
$inner_page->set("is_sample_mode", true); // Flag to disable save/load
$inner_page->set("site_version", SENTIMENTAL_VERSION);
$page->set("page_content", $inner_page->grabTheGoods());

$page->echoToScreen();
exit;
```

**Notes:**
- Skips authentication by not checking `$is_logged_in->isLoggedIn()`
- Sets `is_sample_mode` flag for template conditionals
- Uses welcome layout for consistent anonymous user experience

---

### Step 2: Update Template with Sample Mode Support
**File:** `/templates/index.tpl.php` (MODIFY)

#### 2a. Add PHP variable initialization at top of file
```php
<?php
// Sample mode flag - defaults to false for logged-in users
$is_sample_mode = $is_sample_mode ?? false;
?>
```

#### 2b. Modify header section (lines 1-4)
```php
<div class="PagePanel">
    <h1>🎮 Minecraft Coordinate Visualizer</h1>
    <?php if ($is_sample_mode): ?>
        <p>Try the visualizer! <strong><a href="/login/register.php">Create a free account</a></strong> to save your coordinates (no email or credit card required).</p>
    <?php else: ?>
        <p>Welcome back, <?= $username ?>! Paste your coordinates below to visualize them in 3D.</p>
    <?php endif; ?>
</div>
```

#### 2c. Hide Save section (lines 59-66)
Wrap the entire Save section:
```php
<?php if (!$is_sample_mode): ?>
    <hr>
    <h3>Save</h3>
    <div class="mc-form-group">
        <input type="text" id="set-name" placeholder="Enter coordinate set name..." class="mc-input">
        <button id="btn-save" class="btn-primary">Save Coordinates</button>
    </div>
    <div id="save-status" class="mc-status"></div>
<?php endif; ?>
```

#### 2d. Hide Load section (lines 44-58)
Wrap the entire Load section:
```php
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
```

#### 2e. Remove Export section (lines 68-74)
**DELETE** these lines entirely:
```php
<hr>

<h3>Export</h3>
<div class="mc-button-group">
    <button id="btn-export-json" class="btn-secondary">Export JSON</button>
    <button id="btn-export-csv" class="btn-secondary">Export CSV</button>
</div>
```

#### 2f. Update JavaScript initialization (around line 1040-1046)
Wrap the load functionality:
```javascript
<?php if (!$is_sample_mode): ?>
    // Load coordinate sets on page load, then check if we should load demo
    loadCoordinateSets().then(hasData => {
        if (!hasData) {
            // New user - load demo set
            loadDemoSet(12);
        }
    });
<?php else: ?>
    // Sample mode - always load demo
    loadDemoSet(12);
<?php endif; ?>
```

---

### Step 3: Add CTA Messaging in Sample Mode

#### 3a. Add CTA after Parse button (optional enhancement)
After the parse status div (line 24), add:
```php
<?php if ($is_sample_mode): ?>
    <div class="mc-cta-box" style="margin-top: 15px; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; text-align: center;">
        <p style="margin: 0; color: white; font-size: 14px;">
            💾 Want to save your coordinates?
            <a href="/login/register.php" style="color: #ffd700; font-weight: bold; text-decoration: underline;">Create a free account</a>
            <br><span style="font-size: 12px; opacity: 0.9;">(No email or credit card required)</span>
        </p>
    </div>
<?php endif; ?>
```

---

### Step 4: Update Welcome Page Link
**File:** `/templates/welcome.tpl.php` (MODIFY)

Add a prominent link to the sample page in the welcome content:
```php
<a href="/sample/" class="btn-primary" style="display: inline-block; margin-top: 20px;">
    Try the Visualizer Now →
</a>
```

---

### Step 5: Testing Checklist

- [ ] Visit `/sample/` as anonymous user
- [ ] Verify demo data loads automatically
- [ ] Verify Parse & Visualize button works
- [ ] Verify Clear button works
- [ ] Verify display options work (Connect Points, Flatten)
- [ ] Verify view controls work (Top View, Reset View)
- [ ] Verify Save section is hidden
- [ ] Verify Load section is hidden
- [ ] Verify Update button is hidden
- [ ] Verify Export section is removed
- [ ] Verify CTA messaging appears
- [ ] Verify "Create account" links work
- [ ] Test coordinate parsing with various formats
- [ ] Test chunk visualization
- [ ] Verify no JavaScript errors in console

---

## Future Refactoring (Phase 2)

### Extract JavaScript Module
When ready to refactor, create `/wwwroot/js/mc-visualizer.js`:

**Structure:**
```javascript
// mc-visualizer.js
class MCVisualizerApp {
    constructor(config) {
        this.config = {
            canSave: config.canSave || false,
            canLoad: config.canLoad || false,
            autoLoadDemo: config.autoLoadDemo || false,
            demoSetId: config.demoSetId || 12
        };
        this.init();
    }

    init() {
        // Initialize visualizer
        // Set up event listeners
        // Conditionally enable/disable features based on config
    }
}

// Initialize from template
document.addEventListener('DOMContentLoaded', function() {
    const app = new MCVisualizerApp(window.MC_CONFIG);
});
```

**Template usage:**
```php
<script>
window.MC_CONFIG = {
    canSave: <?= $is_sample_mode ? 'false' : 'true' ?>,
    canLoad: <?= $is_sample_mode ? 'false' : 'true' ?>,
    autoLoadDemo: <?= $is_sample_mode ? 'true' : 'false' ?>
};
</script>
<script src="/js/mc-visualizer.js"></script>
```

**Benefits:**
- Single source of truth for visualization logic
- Easier testing
- Better separation of concerns
- Reduced duplication

---

## Notes

- **No authentication bypass needed** - sample page simply doesn't include `prepend.php` auth check
- **Demo data** (set ID 12) is already available via `/api/load-demo.php`
- **Welcome layout** provides consistent branding for anonymous users
- **Future-proof structure** - conditionals make it easy to extract to JS module later
- **No breaking changes** - existing logged-in functionality remains unchanged

---

## Files Modified
1. `/wwwroot/sample/index.php` - NEW
2. `/templates/index.tpl.php` - MODIFIED (add conditionals)
3. `/templates/welcome.tpl.php` - MODIFIED (add link to sample)

## Files to Delete (in Step 2e)
- Export button HTML and event listeners (non-functional)
