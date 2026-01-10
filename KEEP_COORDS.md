# Keep Coordinates During Registration/Login

## Problem
When anonymous users visit `/sample/` and enter coordinates in the textarea, those coordinates are lost if they decide to register or log in. This creates a poor user experience.

## Solution: Hybrid LocalStorage + Session Approach

We'll use a combination of browser `localStorage` (for persistence) and PHP `$_SESSION` (for reliability) to preserve the textarea content across the registration/login flow.

---

## Implementation Plan

### 1. Auto-Save to LocalStorage (Sample Page)
**File:** `templates/index.tpl.php`
**Location:** In the existing `<script>` section (around line 468)
**Lines to add:** ~15 lines

```javascript
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
```

---

### 2. Pass Coordinates to Registration/Login (Sample Page)
**File:** `templates/index.tpl.php`
**Location:** In the sample mode banner (around line 7) and CTA box (around line 41)
**Lines to modify:** ~2 lines each (4 total)

**Current (line 7):**
```php
<p>Try the visualizer! <strong><a href="/login/register.php" style="color: #667eea; text-decoration: underline;">Create a free account</a></strong> to save your coordinates (no email or credit card required).</p>
```

**New (line 7):**
```php
<p>Try the visualizer! <strong><a href="/login/register.php" id="register-link-top" style="color: #667eea; text-decoration: underline;">Create a free account</a></strong> to save your coordinates (no email or credit card required).</p>
```

**Current (line 41):**
```php
<a href="/login/register.php" style="color: #ffd700; font-weight: bold; text-decoration: underline;">Create a free account</a>
```

**New (line 41):**
```php
<a href="/login/register.php" id="register-link-cta" style="color: #ffd700; font-weight: bold; text-decoration: underline;">Create a free account</a>
```

**File:** `templates/index.tpl.php`
**Location:** In the existing `<script>` section (after the auto-save code)
**Lines to add:** ~20 lines

```javascript
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
```

---

### 3. Create Temporary Coordinate Storage API
**File:** `wwwroot/api/save-temp-coords.php` (NEW)
**Lines:** ~25 lines

```php
<?php

# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$coords = $_POST['temp_coords'] ?? '';
$redirect = $_POST['redirect'] ?? '/login/register.php';

// Store in session
if (!empty($coords)) {
    $_SESSION['temp_coords'] = $coords;
    $_SESSION['temp_coords_timestamp'] = time();
}

// Redirect to registration/login
header("Location: $redirect");
exit;
```

---

### 4. Restore Coordinates After Login (Main Page)
**File:** `templates/index.tpl.php`
**Location:** In the existing `<script>` section, in `DOMContentLoaded` (around line 468)
**Lines to add:** ~30 lines

```javascript
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
```

---

### 5. Pass Session Data to Template (Main Page)
**File:** `wwwroot/index.php`
**Location:** Where the template is set up (around line 20-30)
**Lines to add:** ~3 lines

```php
// Check for temporary coordinates from registration flow
$temp_coords = $_SESSION['temp_coords'] ?? null;
$inner_page->set("temp_coords", $temp_coords);
```

---

### 6. Clear Temporary Coordinates API
**File:** `wwwroot/api/clear-temp-coords.php` (NEW)
**Lines:** ~15 lines

```php
<?php

# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Clear session data
unset($_SESSION['temp_coords']);
unset($_SESSION['temp_coords_timestamp']);

echo json_encode(['success' => true]);
```

---

## Summary of Changes

| File | Type | Lines | Description |
|------|------|-------|-------------|
| `templates/index.tpl.php` | Modify | ~70 | Add auto-save, link handlers, and restore logic |
| `wwwroot/api/save-temp-coords.php` | New | ~25 | Store coordinates in session before redirect |
| `wwwroot/api/clear-temp-coords.php` | New | ~15 | Clear session data after restore |
| `wwwroot/index.php` | Modify | ~3 | Pass session data to template |

**Total estimated lines:** ~113 lines

---

## User Flow

### Anonymous User Journey
1. User visits `/sample/` and enters coordinates
2. Coordinates auto-save to `localStorage` every 1 second (debounced)
3. User clicks "Create a free account"
4. Coordinates are saved to PHP `$_SESSION` via POST to `/api/save-temp-coords.php`
5. User is redirected to `/login/register.php`
6. User completes registration
7. User is redirected to `/login/?registered=1`
8. User logs in
9. User is redirected to `/` (main page)
10. Coordinates are restored from `$_SESSION` and displayed
11. Session data is cleared via AJAX call

### Fallback Flow (Direct Navigation)
1. User visits `/sample/` and enters coordinates
2. Coordinates auto-save to `localStorage`
3. User manually navigates to `/login/` in a new tab
4. User logs in
5. User is redirected to `/` (main page)
6. Coordinates are restored from `localStorage` (if < 24 hours old)
7. `localStorage` is cleared

---

## Edge Cases Handled

✅ **User types coordinates but navigates away:** LocalStorage persists for 24 hours
✅ **User registers but never returns:** Session expires naturally, localStorage expires after 24 hours
✅ **User has both demo data and saved coordinates:** Restored coordinates take precedence
✅ **Multiple browser tabs:** Each tab has its own localStorage, last-written wins
✅ **User clears textarea after restore:** Normal behavior, no special handling needed
✅ **Session expires before login:** LocalStorage fallback kicks in

---

## Testing Checklist

- [ ] Anonymous user enters coords → clicks register → completes registration → sees coords restored
- [ ] Anonymous user enters coords → navigates to login in new tab → logs in → sees coords restored
- [ ] Anonymous user enters coords → waits 25 hours → logs in → coords NOT restored (expired)
- [ ] Logged-in user enters coords → logs out → logs back in → coords NOT restored (only for anonymous users)
- [ ] Anonymous user enters coords → clears textarea → clicks register → empty textarea after login

---

## Future Enhancements

- Add a visual indicator when coordinates are auto-saved
- Allow users to manually trigger save before registration
- Extend expiration to 7 days instead of 24 hours
- Add server-side cleanup job for old session data
