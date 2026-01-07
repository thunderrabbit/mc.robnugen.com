# **Minecraft-coordinate 3D scatterplot**

A web app that feels like EasyMathTools, but accepts MC formats directly and adds the MC-friendly affordances (labels, segments, Nether scaling, etc.). I’ll also give you an “agent prompt” you can hand to Claude/Gemini/etc. to generate chunks of implementation fast.

## Product goal

A single-page web tool where a player pastes Minecraft coordinates in common formats (chat logs, bracket coords, “x y z”, “x, y, z”, etc.). The app parses them into points, displays an interactive 3D scatter plot, and supports labeling + basic path visualization.

---

## Inputs (what users can paste)

### Accepted coordinate formats

Parse any of these, in any mix, across multiple lines:

* `[-278,80,487]`
* `-278 80 487`
* `-278, 80, 487`
* `x=-278 y=80 z=487`
* `X: -278, Y: 80, Z: 487`
* With comments after: `[-278,80,487] tunnel starts here`
* Multiple per line: `[-278,80,487] ... [-278,-34,272]`

### Labels & metadata

Support optional label tokens:

* Inline trailing comment becomes label:
  `[-278,80,487] base portal`
* Or explicit label syntax (optional, nice-to-have):
  `[-278,80,487] {label:"portal", color:"red"}`

### Parsing rules

* Extract integers (allow negative).
* A “point” is any triple in order X,Y,Z.
* Preserve order of appearance as the default “path order”.
* If 2+ points appear on one line, keep their order.

### Error handling

* If a line has not enough numbers for a point: ignore + warn.
* If leftover numbers not forming triple: warn.
* Show: “Parsed N points, M warnings”.

---

## Core features (MVP)

### 1) 3D scatter plot

* Points rendered in 3D with orbit controls (rotate, pan, zoom).
* Tooltips on hover: label + (x,y,z).
* Click a point → pin it (highlight + show detail panel).

### 2) Labels

* Show labels in one of three modes:

  * Off
  * Hover only
  * Always (with decluttering: only show within distance / limit max visible)

### 3) Path lines (super useful for track/tunnels)

* “Connect points in order” toggle.
* Draw thin polyline segments between consecutive points.
* Option: “break line when gap > threshold distance” (e.g. > 300 blocks).

### 4) Views

* “Top-down” button (camera looking down Y axis).
* “Reset view” button.

### 5) Export / import

* Export parsed points as:

  * JSON (points + labels)
  * CSV columns: x,y,z,label
* Import JSON (paste back in).

---

## “Minecraft niceties” (next tier, highly useful)

### Nether/Overworld scaling helper

* Toggle per dataset:

  * “Interpret as Overworld”
  * “Interpret as Nether”
* One-click transform:

  * Overworld → Nether (x/8, z/8, y unchanged)
  * Nether → Overworld (x*8, z*8)

### Flattening / slicing

* “Flatten Y” mode:

  * Option A: set all Y to 0 (pure map)
  * Option B: set all Y to median/average
* “Y range filter” slider (only show points between minY and maxY)

### Grouping / colors

* Parse prefixes:

  * `portal: [-278,80,487]`
  * `minecart: [-271,-19,266]`
* Legend that toggles visibility per group.

---

## UI layout (simple + fast to build)

Left panel (fixed width):

* Paste box (monospace)
* Buttons: Parse, Clear, Export JSON, Export CSV
* Toggles: Labels, Connect points, Break on distance, Flatten Y, Nether scale
* Warnings box
* Selected point details

Right panel:

* 3D canvas

---

## Technical architecture (recommended)

### Frontend-only (fastest)

* **Three.js** for rendering
* **OrbitControls** for camera
* Optional: **CSS2DRenderer** for text labels (works well for “always show labels”)

Data model:

```ts
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
type ParseResult = { points: Point[]; warnings: string[]; };
```

Parsing:

* Regex to find triples robustly:

  * First pass: find bracket triples `\[\s*-?\d+\s*,\s*-?\d+\s*,\s*-?\d+\s*\]`
  * Second pass: fallback: scan numbers per line and chunk into triples
* Capture trailing comment per triple if present.

Rendering:

* Points as InstancedMesh spheres for performance (even 10k points).
* Lines as LineSegments or Line2 (fat lines optional).
* Hover picking with Raycaster.

---

## Acceptance criteria (MVP)

* User pastes your sample input and gets 11 points.
* X/Y/Z negative values parse correctly.
* Labels derived from trailing text work.
* “Connect points” draws the minecart track line.
* Export CSV matches the points order and includes labels.
* No server required.

---

## Agent prompt you can use (to generate implementation)

Copy/paste this into your preferred CLI agent:

```text
You are a senior frontend engineer.

Build a single-page web app that parses Minecraft coordinates pasted as text and renders them as an interactive 3D scatter plot.

Requirements:
- Accept coordinate formats: [-278,80,487], -278 80 487, -278, 80, 487, x=-278 y=80 z=487, and multiple points per line.
- Allow trailing text after a point to become its label.
- Preserve point order.
- Display 3D plot using three.js with OrbitControls.
- Render points and (optional) a polyline connecting points in order.
- Hover tooltip shows label and (x,y,z).
- UI: left pane textarea + toggles (labels on/off, connect points, break line if distance > threshold), right pane 3D canvas.
- Add Export CSV and Export JSON buttons.

Deliver:
- Typescript + Vite setup
- Clean component structure
- A robust parser module with tests (vitest)
- Minimal styling

Return code files with paths and full content.
```

---

## Your next build step (fastest path)

If you’re building the “website frame,” I’d suggest you scaffold:

* Vite + React + TS (or vanilla TS if you prefer)
* A two-column layout
* Empty `parseCoordinates(text)` function placeholder
* A `Plot3D` component shell

Then I can drop in:

* A battle-tested parser (with your exact sample as a fixture)
* The Three.js renderer for points/labels/lines
* Export helpers


---

## 📂 Site Structure

- `classes/Template.php`: Core rendering engine with support for string-capture (`grabTheGoods()`) and layout nesting.
- `wwwroot/`: Public-facing files. Place your admin pages here (`/admin/index.php`, etc).
- `templates/`: Your site’s UI. Includes layout wrappers and specific content templates.
- `css/styles.css`: Soft blue aesthetic with clean panels and nav bar.

---

## 🚀 Features

- Lightweight custom templating (no Twig, Blade, or Smarty)
- Admin dashboard scaffold
- Built-in layout nesting (`grabTheGoods()`)
- Styled with light blues and page panels
- Easily set up first (admin) user
- Uses cookies in DB for logins

---

## 🔧 Setup (with DreamHost Deployment)

1. **Set up a DreamHost new user account:**
   - Clone [thunderrabbit/new-DH-user-account](https://github.com/thunderrabbit/new-DH-user-account)

2. **Set your domain's Web Directory in DreamHost panel:**
   - e.g. `/home/dh_user/example.com/wwwroot`

3. **Clone this repo locally** into a working directory.

4. **Configure your deploy script:**
   - Edit `scp_files_to_dh.sh` to point to your DH username and target path.

5. **Clone this repo server-side** (optional but useful):
   - Clone to `/home/dh_user/example.com`
   - ⚠️ Be aware of DreamHost system links like `.dh-diag → /dh/web/diag` — **The symlink is owned by `root`**.

6. **Deploy with `scp_files_to_dh.sh`** or manually sync files.

7. Customize the templates:
   - `/templates/layout/admin_base.tpl.php`: Main layout
   - `/templates/admin/index.tpl.php`: Admin dashboard
   - `/templates/admin/workers/index.tpl.php`: Example content page

8. Visit `/` to automagically create admin user in the freshly set up TABLEs `users` and `cookies`

---

## 📝 License

No license yet. Use it privately, tweak as needed. Attribution appreciated if it grows into something shared.

---

## ✨ Origin

Originally created during work on the **MarbleTrack3** stop-motion animation archive (June 2025). Designed for fun and minimal overhead.
