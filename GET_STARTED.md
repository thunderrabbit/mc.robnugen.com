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

### 1. Fix Update Button Bug ✅ COMPLETED

**File:** `templates/index.tpl.php`

---

### 2. Create Demo Load API Endpoint ✅ COMPLETED

**New File:** `wwwroot/api/load-demo.php`

**Purpose:** Load demo coordinate sets without ownership checks, but with security whitelist.

**Security:** Only allow loading from a whitelist of demo set IDs.

---

### 3. Frontend: Auto-load Demo for New Users ✅ COMPLETED

**File:** `templates/index.tpl.php`

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
