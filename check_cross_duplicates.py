#!/usr/bin/env python3
"""
Check for duplicate coordinates between curves/ and curves_north/ directories.
"""

import os
import re
import hashlib
from pathlib import Path
from collections import defaultdict

def extract_coordinates(filepath):
    """Extract all coordinates from a curve file."""
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
    """Generate a hash from coordinate sequence."""
    coord_str = str(coords)
    return hashlib.md5(coord_str.encode()).hexdigest()

def main():
    south_dir = Path('curves')
    north_dir = Path('curves_north')

    if not south_dir.exists():
        print(f"Error: {south_dir} directory not found")
        return

    if not north_dir.exists():
        print(f"Error: {north_dir} directory not found")
        return

    # Get all files
    south_files = sorted(south_dir.glob('*.txt'))
    north_files = sorted(north_dir.glob('*.txt'))

    print(f"Analyzing curves for duplicates...")
    print(f"  South-first curves: {len(south_files)}")
    print(f"  North-first curves: {len(north_files)}")
    print("=" * 80)

    # Build hash map for south curves
    south_map = {}
    for filepath in south_files:
        coords = extract_coordinates(filepath)
        if coords:
            coord_hash = get_coord_hash(coords)
            south_map[coord_hash] = (filepath, coords)

    # Check north curves against south curves
    duplicates = []

    for north_file in north_files:
        north_coords = extract_coordinates(north_file)
        if not north_coords:
            continue

        north_hash = get_coord_hash(north_coords)

        if north_hash in south_map:
            south_file, south_coords = south_map[north_hash]
            duplicates.append((south_file, north_file))
            print(f"❌ DUPLICATE FOUND:")
            print(f"   South: {south_file.name}")
            print(f"   North: {north_file.name}")
            print()

    print("=" * 80)
    print(f"\nSummary:")
    print(f"  Total south curves: {len(south_files)}")
    print(f"  Total north curves: {len(north_files)}")
    print(f"  Duplicates found: {len(duplicates)}")

    if duplicates:
        print(f"\n⚠️  Found {len(duplicates)} curves that exist in both directories!")
        print(f"   These curves have identical coordinates despite different generation methods.")
    else:
        print(f"\n✅ No duplicates found! All curves are unique between south and north sets.")

if __name__ == "__main__":
    main()
