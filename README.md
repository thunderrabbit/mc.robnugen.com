# Minecraft Coordinate 3D Visualizer

A web application that feels like EasyMathTools for visualizing Minecraft coordinates in an interactive 3D scatter plot. Adds the MC-friendly affordances (labels, segments, Nether scaling, etc.).
Built on a lightweight PHP backend with user authentication and coordinate data persistence.

**Live at:** `mc.robnugen.com`

---

## 🎮 Product Overview

A web tool where Minecraft players can:
- Paste coordinates in common formats (chat logs, bracket coords, space/comma-separated)
- View them as an interactive 3D scatter plot with Three.js
- Label points, connect them as paths, and visualize tunnels/tracks
- Save coordinate sets to their account (login required)
- Export/import data as JSON or CSV

### Key Features

**Coordinate Parsing:**
- Accepts multiple formats: `[-278,80,487]`, `-278 80 487`, `-278, 80, 487`, `x=-278 y=80 z=487`
- Inline labels: `[-278,80,487] base portal`
- Multiple points per line with preserved order

**3D Visualization:**
- Interactive orbit controls (rotate, pan, zoom)
- Hover tooltips showing coordinates and labels
- Click to pin/highlight points
- Path lines connecting points in order
- Top-down and reset view buttons

**Minecraft-Specific Tools:**
- Nether/Overworld coordinate scaling (x8 or ÷8)
- Y-axis flattening for map view
- Y-range filtering
- Group/color coding by prefix (e.g., `portal: [-278,80,487]`)

**Data Management:**
- User authentication via PHP backend
- Save coordinate sets to database (per-user, no sharing yet)
- Export as JSON or CSV
- Import saved coordinate sets

---

## 🏗️ Architecture

### Backend (PHP + MySQL)
- **Framework:** Custom lightweight PHP templating system
- **Authentication:** Cookie-based login with database storage
- **Database:** MySQL via PDO with migration system
- **Deployment:** DreamHost-optimized with auto-deploy script

### Frontend (JavaScript + Three.js)
- **Rendering:** Three.js with OrbitControls
- **UI:** Integrated into PHP template system
- **Parsing:** Regex-based coordinate extraction
- **State:** Client-side with AJAX for save/load

### Data Model

```typescript
type Point = {
  id: string;
  x: number;
  y: number;
  z: number;
  label?: string;
  group?: string;
  color?: string;
  sourceLine?: number;
};

type CoordinateSet = {
  id: number;
  user_id: number;
  name: string;
  points: Point[];
  created_at: string;
  updated_at: string;
};
```

---

## 📂 Project Structure

```
mc.robnugen.com/
├── classes/
│   ├── Template.php          # Core templating engine
│   ├── Database/             # PDO abstraction + migrations
│   ├── Auth/                 # Cookie-based authentication
│   └── Config.php            # DB credentials (create from ConfigSample.php)
├── db_schemas/               # Database migration files
├── templates/
│   ├── layout/               # Base layouts with auth checks
│   └── mc/                   # Minecraft visualizer templates
├── wwwroot/
│   ├── index.php             # Main entry point
│   ├── mc/                   # Visualizer frontend
│   │   ├── index.php         # Main visualizer page
│   │   ├── visualizer.js     # Three.js rendering
│   │   └── parser.js         # Coordinate parsing
│   └── css/styles.css        # Site-wide styling
└── prepend.php               # Bootstrap (autoloader, DB checks, auth)
```

---

## 🔧 Setup Instructions

### 1. DreamHost Account Setup
- Clone [thunderrabbit/new-DH-user-account](https://github.com/thunderrabbit/new-DH-user-account)
- Set domain web directory to `/home/dh_user/mc.robnugen.com/wwwroot`

### 2. Local Development
```bash
# Clone repository
git clone <repo-url> mc.robnugen.com
cd mc.robnugen.com

# Configure database
cp classes/ConfigSample.php classes/Config.php
# Edit Config.php with your MySQL credentials

# Configure deployment
cp scp_files_to_dh.sh.example scp_files_to_dh.sh
# Edit with your DH username and path
```

### 3. Database Setup
- Create MySQL database via DreamHost panel
- First visit to site auto-creates tables and admin user
- Or manually apply migrations via `/admin/migrate_tables.php`

### 4. Deployment
```bash
# Auto-deploy on file changes
./scp_files_to_dh.sh

# Or manual sync
scp -r classes templates wwwroot user@host:/home/user/mc.robnugen.com/
```

### 5. First Login
- Visit `mc.robnugen.com`
- Register admin account (auto-redirects on first visit)
- Login and access visualizer at `/mc/`

---

## � Development Roadmap

### Phase 1: MVP (Current)
- [x] PHP backend framework with auth
- [ ] Basic coordinate parser (regex-based)
- [ ] Three.js 3D scatter plot
- [ ] Database schema for coordinate sets
- [ ] Save/load functionality
- [ ] CSV/JSON export

### Phase 2: Enhanced Features
- [ ] Nether/Overworld scaling
- [ ] Path line rendering with gap detection
- [ ] Y-axis filtering and flattening
- [ ] Group/color coding by prefix
- [ ] Label visibility modes (off/hover/always)

### Phase 3: Advanced
- [ ] Coordinate set sharing between users
- [ ] Collaborative editing
- [ ] Screenshot/video export
- [ ] Mobile-responsive 3D controls

---

## 🛠️ Technology Stack

**Backend:**
- PHP 7.4+ (DreamHost default)
- MySQL 5.7+ via PDO
- Custom autoloader (Mlaphp\Autoloader)
- No Composer dependencies

**Frontend:**
- Three.js (3D rendering)
- OrbitControls (camera manipulation)
- CSS2DRenderer (optional, for labels)
- Vanilla JavaScript (no framework required)

**Development:**
- File-watch deployment via `scp_files_to_dh.sh`
- Debug mode: `?debug=1` URL parameter
- `print_rob()` for formatted debugging

---

## 📝 License

No formal license yet. Private use and modification encouraged. Attribution appreciated if shared publicly.

---

## ✨ Credits

- **Backend Framework:** Originally created for MarbleTrack3 archive (June 2025)
- **MC Visualizer Spec:** AI-assisted design (January 2026)
- **Developer:** Rob Nugen (thunderrabbit)
