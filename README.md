# Minecraft Coordinate 3D Visualizer

Enter your Minecraft coordinates to visualize them in 3D!
A web application for visualizing Minecraft coordinates
in an interactive 3D space with support for paths, chunk claims, and more.

**Live at:** `mc.robnugen.com`

---

## 🎮 Features

### Coordinate Parsing
- **Multiple formats supported:** `[-278,80,487]`, `-278 80 487`, `-278, 80, 487`, `x=-278 y=80 z=487`
- **Color coding:** Group coordinates by color (red, blue, green, etc.)
- **Path segments:** Connect points with commas to create paths

### 3D Visualization
- **Interactive controls:** Rotate, pan, and zoom with your mouse
- **Path rendering:** Visualize tunnels and tracks
- **Top-down view:** Switch between perspectives
- **Flatten mode:** View coordinates in 2D map view

### Chunk Visualization
- **Chunk claims:** Mark chunks as `mine` or `unavailable`
- **Visual overlays:** Semi-transparent colored planes show chunk ownership
- **Easy input:** Simple text format: `mine` followed by `[X, Z]` coordinates

### Data Management
- **Save coordinate sets:** Store your work with custom names
- **Load saved sets:** Quick access to all your coordinate collections
- **Update existing sets:** Modify and save changes
- **User accounts:** Keep your data private and organized

---

## � Getting Started

1. **Visit** [mc.robnugen.com](https://mc.robnugen.com)
2. **Create an account** (free, no email required)
3. **Paste your coordinates** in the text area
4. **Click "Parse & Visualize"** to see your 3D map
5. **Save your work** for later

### Example Input

```
red
[-278, 80, 487] base portal
[-150, 70, 250] tower,

blue
[-200, 65, 300] mine entrance

mine
[-10, 15]
[-10, 16]

unavailable
[-5, 20]
```

---

## � How to Use

### Adding Coordinates
- Paste coordinates in any common format
- Use colors to group related locations
- End lines with commas to connect points as paths

### Chunk Claims
- Type `mine` or `unavailable` on its own line
- Follow with chunk coordinates in `[X, Z]` format
- Chunks appear as colored overlays (green for mine, red for unavailable)

### Display Options
- **Connect Points:** Show/hide path lines
- **Flatten to Y=80:** View map in 2D

### Saving & Loading
- Enter a name for your coordinate set
- Click "Save Coordinates" to store
- Use the dropdown to load saved sets
- Click "Update" to save changes to existing sets

---

## 🎯 Use Cases

- **Base planning:** Visualize your base layout before building
- **Tunnel networks:** Plan efficient minecart systems
- **Resource mapping:** Track ore locations and mining areas
- **Chunk claiming:** Manage territory on multiplayer servers
- **Exploration tracking:** Record interesting locations
- **Build collaboration:** Share coordinate sets with teammates

---

## 🛠️ Technology

Built with:
- **Three.js** for 3D rendering
- **PHP** backend with MySQL database
- **Responsive design** for desktop and mobile

---

## 📝 License

Free to use. No formal license yet. Attribution appreciated if shared publicly.

---

## ✨ Credits

- **Developer:** Rob Nugen (thunderrabbit)
- **Design:** AI-assisted (January 2026)

---

**Questions or feedback?** Visit the site and create an account to get started!
