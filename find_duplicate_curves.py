#!/usr/bin/env python3
"""
Find and delete duplicate curve files.
Two files are duplicates if they have identical coordinate sequences.
"""

import os
import re
import hashlib
from pathlib import Path
from collections import defaultdict

def extract_coordinates(filepath):
    """
    Extract all coordinates from a curve file.
    Returns a tuple of (x, y, z) tuples, or None if no coordinates found.
    """
    coords = []

    with open(filepath, 'r') as f:
        for line in f:
            line = line.strip()

            # Skip comments and empty lines
            if not line or line.startswith('#'):
                continue

            # Parse coordinate line: [x, y, z]
            match = re.match(r'\[(-?\d+),\s*(-?\d+),\s*(-?\d+)\]', line)
            if match:
                x = int(match.group(1))
                y = int(match.group(2))
                z = int(match.group(3))
                coords.append((x, y, z))

    if not coords:
        return None

    return tuple(coords)

def get_coord_hash(coords):
    """Generate a hash from coordinate sequence for fast comparison."""
    coord_str = str(coords)
    return hashlib.md5(coord_str.encode()).hexdigest()

def main():
    curves_dir = Path('curves')

    if not curves_dir.exists():
        print(f"Error: {curves_dir} directory not found")
        return

    # Get all .txt files
    curve_files = sorted(curves_dir.glob('*.txt'))

    if not curve_files:
        print(f"No curve files found in {curves_dir}")
        return

    print(f"Analyzing {len(curve_files)} curve files for duplicates...")
    print("=" * 80)

    # Map: coord_hash -> list of (filepath, coords)
    coord_map = defaultdict(list)

    # First pass: extract coordinates and group by hash
    for filepath in curve_files:
        coords = extract_coordinates(filepath)
        if coords is None:
            print(f"⚠️  {filepath.name}: No coordinates found")
            continue

        coord_hash = get_coord_hash(coords)
        coord_map[coord_hash].append((filepath, coords))

    # Find duplicates
    duplicates = []
    unique_groups = 0

    for coord_hash, files_with_coords in coord_map.items():
        if len(files_with_coords) > 1:
            # Verify they're actually identical (hash collision check)
            first_coords = files_with_coords[0][1]
            group = []

            for filepath, coords in files_with_coords:
                if coords == first_coords:
                    group.append(filepath)

            if len(group) > 1:
                duplicates.append(group)
                unique_groups += 1

    if not duplicates:
        print("✅ No duplicate files found! All curves are unique.")
        return

    print(f"Found {unique_groups} groups of duplicate files:\n")

    files_to_delete = []

    for i, group in enumerate(duplicates, 1):
        print(f"Group {i}: {len(group)} identical files")

        # Keep the first file (alphabetically), delete the rest
        group_sorted = sorted(group, key=lambda p: p.name)
        keeper = group_sorted[0]
        to_delete = group_sorted[1:]

        print(f"  ✅ KEEP: {keeper.name}")
        for filepath in to_delete:
            print(f"  ❌ DELETE: {filepath.name}")
            files_to_delete.append(filepath)
        print()

    print("=" * 80)
    print(f"\nSummary:")
    print(f"  Total files: {len(curve_files)}")
    print(f"  Duplicate groups: {unique_groups}")
    print(f"  Files to delete: {len(files_to_delete)}")
    print(f"  Files to keep: {len(curve_files) - len(files_to_delete)}")

    if files_to_delete:
        print(f"\n🗑️  Deleting {len(files_to_delete)} duplicate files...")

        deleted_dir = Path('curves_deleted')
        deleted_dir.mkdir(exist_ok=True)

        for filepath in files_to_delete:
            dest = deleted_dir / filepath.name
            filepath.rename(dest)
            print(f"  Moved: {filepath.name} -> curves_deleted/")

        print(f"\n✅ Done! Deleted {len(files_to_delete)} duplicate files.")
        print(f"   Remaining unique curves: {len(curve_files) - len(files_to_delete)}")

if __name__ == "__main__":
    main()
