#!/usr/bin/env python3
"""
Analyze curve files to find those that go too far south.
Rule: Curves are ineligible if they go to chunk Z >= 30 (e.g., [-12, 30])
"""

import os
import re
from pathlib import Path

def get_chunk_z(z_coord):
    """Convert Z coordinate to chunk Z coordinate."""
    return z_coord // 16

def analyze_curve_file(filepath):
    """
    Analyze a curve file and return the maximum chunk Z it reaches.
    Returns: (max_chunk_z, max_z_coord, is_eligible)
    """
    max_z = float('-inf')

    with open(filepath, 'r') as f:
        for line in f:
            line = line.strip()

            # Skip comments and empty lines
            if not line or line.startswith('#'):
                continue

            # Parse coordinate line: [x, y, z]
            match = re.match(r'\[(-?\d+),\s*(-?\d+),\s*(-?\d+)\]', line)
            if match:
                z = int(match.group(3))
                max_z = max(max_z, z)

    if max_z == float('-inf'):
        return None, None, None

    max_chunk_z = get_chunk_z(max_z)
    is_eligible = max_chunk_z < 30  # Must be less than 30 to be eligible

    return max_chunk_z, max_z, is_eligible

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

    print(f"Analyzing {len(curve_files)} curve files...")
    print(f"Rule: Curves must stay in chunk Z < 30 (Z coordinate < 480)")
    print("=" * 80)

    ineligible = []
    eligible = []

    for filepath in curve_files:
        max_chunk_z, max_z, is_eligible = analyze_curve_file(filepath)

        if max_chunk_z is None:
            print(f"⚠️  {filepath.name}: No coordinates found")
            continue

        if is_eligible:
            eligible.append((filepath.name, max_chunk_z, max_z))
        else:
            ineligible.append((filepath.name, max_chunk_z, max_z))
            print(f"❌ {filepath.name}: chunk Z={max_chunk_z} (Z={max_z}) - INELIGIBLE")

    print("\n" + "=" * 80)
    print(f"\nSummary:")
    print(f"  ✅ Eligible curves: {len(eligible)}")
    print(f"  ❌ Ineligible curves: {len(ineligible)}")

    if ineligible:
        print(f"\n📋 Ineligible files (going to chunk Z >= 30):")
        for filename, chunk_z, z_coord in sorted(ineligible, key=lambda x: x[1], reverse=True):
            print(f"  {filename} (chunk Z={chunk_z}, max Z={z_coord})")

        print(f"\n💡 To delete these files, run:")
        print(f"  cd curves")
        for filename, _, _ in ineligible:
            print(f"  mv {filename} ../curves_deleted/")

    # Show some eligible examples for verification
    if eligible:
        print(f"\n✅ Sample eligible files (staying in chunk Z < 30):")
        for filename, chunk_z, z_coord in eligible[:5]:
            print(f"  {filename} (chunk Z={chunk_z}, max Z={z_coord})")

if __name__ == "__main__":
    main()
