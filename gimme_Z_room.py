#!/usr/bin/env python3
from typing import List, Tuple

Coord = Tuple[int, int, int]

# ============================================================
# 1. HARD-CODE YOUR COORDINATES HERE
# ============================================================
# NOTE: Borrowed north debt is cleared by coordinate [-324, 214, 318]
#       All coordinates after that point are okay.

COORDS_BEGIN: List[Coord] = [
[-199, 98, 410],
[-199, 98, 409],
[-199, 98, 409],
[-199, 98, 408],
[-199, 98, 407],
[-199, 98, 406],
[-199, 98, 406],
[-199, 98, 405],
[-199, 98, 404],
[-199, 98, 403],
[-199, 98, 403],
[-199, 98, 402],
[-199, 98, 401],
[-200, 99, 400],
[-200, 99, 400],
[-200, 99, 399],
[-200, 99, 398],
[-200, 99, 397],
[-200, 99, 397],
[-200, 99, 396],
[-200, 99, 395],
[-200, 99, 394],
[-200, 99, 394],
[-200, 100, 393],
[-201, 100, 392],
[-201, 100, 391],
[-201, 100, 391],
[-201, 100, 390],
[-201, 100, 389],
[-201, 100, 388],
[-201, 101, 387],
[-202, 101, 387],
[-202, 101, 386],
[-202, 101, 385],
[-202, 101, 384],
[-202, 101, 384],
[-202, 102, 383],
[-202, 102, 382],
[-203, 102, 381],
[-203, 102, 380],
[-203, 103, 380],
[-203, 103, 379],
[-203, 103, 378],
[-203, 103, 377],
[-204, 103, 377],
[-204, 104, 376],
[-204, 104, 375],
[-204, 104, 374],
[-204, 104, 374],
[-205, 105, 373],
[-205, 105, 372],
[-205, 105, 371],
[-205, 105, 370],
[-205, 106, 370],
[-206, 106, 369],
[-206, 106, 368],
[-206, 107, 367],
[-206, 107, 367],
[-207, 107, 366],
[-207, 107, 365],
[-207, 108, 364],
[-207, 108, 364],
[-207, 108, 363],
[-208, 109, 362],
[-208, 109, 361],
[-208, 109, 361],
[-208, 110, 360],
[-209, 110, 359],
[-209, 110, 358],
[-209, 111, 358],
[-209, 111, 357],
[-210, 111, 356],
[-210, 112, 355],
[-210, 112, 355],
[-210, 112, 354],
[-211, 113, 353],
[-211, 113, 352],
[-211, 113, 352],
[-212, 114, 351],
[-212, 114, 350],
[-212, 115, 349],
[-212, 115, 349],
[-213, 115, 348],
[-213, 116, 347],
[-213, 116, 347],
[-214, 116, 346],
[-214, 117, 345],
[-214, 117, 345],
[-215, 118, 344],
[-215, 118, 343],
[-215, 118, 342],
[-216, 119, 342],
[-216, 119, 341],
[-216, 120, 340],
[-217, 120, 340],
[-217, 121, 339],
[-218, 121, 338],
[-218, 121, 338],
[-218, 122, 337],
[-219, 122, 336],
[-219, 123, 336],
[-220, 123, 335],
[-220, 124, 334],
[-221, 124, 333],
[-221, 125, 333],
[-222, 125, 332],
[-222, 126, 331],
[-223, 126, 331],
[-223, 127, 330],
[-223, 127, 329],
[-224, 128, 329],
[-224, 128, 328],
[-225, 129, 328],
[-225, 129, 327],
[-226, 130, 326]
]

COORDS_END: List[Coord] = [
[-324, 214, 318],
[-324, 215, 319],
[-324, 215, 320],
[-324, 215, 321],
[-325, 216, 321],
[-325, 216, 322],
[-325, 216, 323],
[-325, 217, 324],
[-326, 217, 324],
[-326, 217, 325],
[-326, 217, 326],
[-326, 218, 326],
[-326, 218, 327],
[-327, 218, 328],
[-327, 219, 329],
[-327, 219, 330],
[-327, 219, 331],
[-328, 219, 332],
[-328, 220, 332],
[-328, 220, 333],
[-328, 220, 334],
[-328, 220, 335],
[-328, 220, 336],
[-329, 220, 336],
[-329, 221, 337],
[-329, 221, 338],
[-329, 221, 339],
[-329, 221, 340],
[-329, 221, 341],
[-329, 221, 342],
[-329, 221, 343],
[-330, 222, 344],
[-330, 222, 345],
[-330, 222, 346],
[-330, 222, 347],
[-330, 222, 348],
[-330, 222, 349],
[-330, 222, 350],
[-330, 222, 351],
[-330, 222, 352]
]

# ============================================================
# 2. CONFIGURATION - Loop Corner Points
# ============================================================

# Define the serpentine path anchor points
# Format: (end of ramp, after curve) pairs
ANCHORS = [
    (-226, 130, 326),  # Start
    (-317, 146, 326),  # End of Ramp 1
    (-317, 146, 302),  # After Curve 1 (24 blocks north)
    (-234, 163, 302),  # End of Ramp 2
    (-234, 163, 322),  # After Curve 2 (20 blocks south)
    (-317, 180, 322),  # End of Ramp 3
    (-317, 180, 342),  # After Curve 3 (20 blocks south)
    (-234, 197, 342),  # End of Ramp 4
    (-234, 197, 318),  # After Curve 4 (24 blocks north)
    (-324, 214, 318),  # End (Ramp 5)
]

CURVE_RADIUS = 8  # Radius for flat curves

# ============================================================
# 3. UTILITY FUNCTIONS
# ============================================================

def step_dir(a: Coord, b: Coord) -> Tuple[int, int]:
    """Return (dx, dz) horizontal direction."""
    return (b[0] - a[0], b[2] - a[2])

def is_west(dx: int, dz: int) -> bool:
    return dx == -1 and dz == 0

def is_north(dx: int, dz: int) -> bool:
    return dx == 0 and dz == -1

def is_south(dx: int, dz: int) -> bool:
    return dx == 0 and dz == 1

# ============================================================
# 4. LOOP PATH GENERATION
# ============================================================

CURVE_RADIUS = 3  # 3-block radius for smooth corners

def interpolate_segment(start: Coord, end: Coord, num_steps: int) -> List[Coord]:
    """Generate smooth interpolated coordinates between two points."""
    coords = []
    for i in range(num_steps + 1):
        t = i / num_steps
        x = int(start[0] + (end[0] - start[0]) * t)
        y = int(start[1] + (end[1] - start[1]) * t)
        z = int(start[2] + (end[2] - start[2]) * t)
        coords.append((x, y, z))
    return coords

def create_curve(before: Coord, corner: Coord, after: Coord, radius: int, y_start: int, y_end: int) -> List[Coord]:
    """Create a smooth curve around a corner point.

    Args:
        before: Point before the corner (direction we're coming from)
        corner: The corner point itself
        after: Point after the corner (direction we're going to)
        radius: Curve radius in blocks
        y_start: Y coordinate at curve start
        y_end: Y coordinate at curve end
    """
    coords = []

    # Determine directions
    dx_in = 1 if corner[0] > before[0] else -1 if corner[0] < before[0] else 0
    dz_in = 1 if corner[2] > before[2] else -1 if corner[2] < before[2] else 0

    dx_out = 1 if after[0] > corner[0] else -1 if after[0] < corner[0] else 0
    dz_out = 1 if after[2] > corner[2] else -1 if after[2] < corner[2] else 0

    # Create approach point (radius blocks before corner)
    approach_x = corner[0] - dx_in * radius
    approach_z = corner[2] - dz_in * radius

    # Create exit point (radius blocks after corner)
    exit_x = corner[0] + dx_out * radius
    exit_z = corner[2] + dz_out * radius

    # Generate curve points (simple arc approximation)
    # IMPORTANT: Y stays constant during the curve - minecarts can't curve upward!
    num_curve_points = radius * 2 + 1
    for i in range(num_curve_points):
        t = i / (num_curve_points - 1)

        # Y stays constant during curve
        y = y_start

        # Create smooth curve using quadratic interpolation through corner
        if t < 0.5:
            # First half: approach to corner
            t2 = t * 2
            x = int(approach_x + (corner[0] - approach_x) * t2)
            z = int(approach_z + (corner[2] - approach_z) * t2)
        else:
            # Second half: corner to exit
            t2 = (t - 0.5) * 2
            x = int(corner[0] + (exit_x - corner[0]) * t2)
            z = int(corner[2] + (exit_z - corner[2]) * t2)

        coords.append((x, y, z))

    return coords


def generate_serpentine_path() -> List[Coord]:
    """Generate the complete serpentine path with 5 ramps and 4 flat curves."""
    output: List[Coord] = []

    # Add COORDS_BEGIN as-is
    output.extend(COORDS_BEGIN)

    # The pattern is: Ramp1, Curve1, Ramp2, Curve2, Ramp3, Curve3, Ramp4, Curve4, Ramp5
    # Anchors: [0]=start, [1]=end_ramp1, [2]=after_curve1, [3]=end_ramp2, etc.

    # Ramp 1: Start (anchor[0]) → End of Ramp 1 (anchor[1])
    ramp1_dist = int(((ANCHORS[1][0] - ANCHORS[0][0])**2 + (ANCHORS[1][2] - ANCHORS[0][2])**2)**0.5)
    ramp1 = interpolate_segment(ANCHORS[0], ANCHORS[1], ramp1_dist)
    output.extend(ramp1[1:])  # Skip first point (already in output)

    # Curve 1: 180° turn from west to east, moving north (curve opens EAST)
    # The curve goes from anchor[1] to anchor[2]
    curve1 = create_180_degree_curve(ANCHORS[1], ANCHORS[2], CURVE_RADIUS, ANCHORS[1][1], 'east')
    output.extend(curve1[1:])

    # Ramp 2: After Curve 1 (anchor[2]) → End of Ramp 2 (anchor[3])
    ramp2_dist = abs(ANCHORS[3][0] - ANCHORS[2][0])  # Eastward movement
    ramp2 = interpolate_segment(ANCHORS[2], ANCHORS[3], ramp2_dist)
    output.extend(ramp2[1:])

    # Curve 2: 180° turn from east to west, moving south (curve opens WEST)
    curve2 = create_180_degree_curve(ANCHORS[3], ANCHORS[4], CURVE_RADIUS, ANCHORS[3][1], 'west')
    output.extend(curve2[1:])

    # Ramp 3: After Curve 2 (anchor[4]) → End of Ramp 3 (anchor[5])
    ramp3_dist = abs(ANCHORS[5][0] - ANCHORS[4][0])  # Westward movement
    ramp3 = interpolate_segment(ANCHORS[4], ANCHORS[5], ramp3_dist)
    output.extend(ramp3[1:])

    # Curve 3: 180° turn from west to east, moving south (curve opens EAST)
    curve3 = create_180_degree_curve(ANCHORS[5], ANCHORS[6], CURVE_RADIUS, ANCHORS[5][1], 'east')
    output.extend(curve3[1:])

    # Ramp 4: After Curve 3 (anchor[6]) → End of Ramp 4 (anchor[7])
    ramp4_dist = abs(ANCHORS[7][0] - ANCHORS[6][0])  # Eastward movement
    ramp4 = interpolate_segment(ANCHORS[6], ANCHORS[7], ramp4_dist)
    output.extend(ramp4[1:])

    # Curve 4: 180° turn from east to west, moving north (curve opens WEST)
    curve4 = create_180_degree_curve(ANCHORS[7], ANCHORS[8], CURVE_RADIUS, ANCHORS[7][1], 'west')
    output.extend(curve4[1:])

    # Ramp 5: After Curve 4 (anchor[8]) → End (anchor[9])
    ramp5_dist = int(((ANCHORS[9][0] - ANCHORS[8][0])**2 + (ANCHORS[9][2] - ANCHORS[8][2])**2)**0.5)
    ramp5 = interpolate_segment(ANCHORS[8], ANCHORS[9], ramp5_dist)
    output.extend(ramp5[1:])

    # Add COORDS_END as-is
    output.extend(COORDS_END[1:])

    return output

def create_180_degree_curve(start: Coord, end: Coord, radius: int, y: int, curve_direction: str) -> List[Coord]:
    """Create a flat 180-degree U-turn curve for minecart rails.

    Creates a semicircular path that:
    - Reverses the X direction (west→east or east→west)
    - Moves in the Z direction (north or south)
    - Keeps Y constant (flat curve)

    Args:
        start: Starting point (end of previous ramp)
        end: Ending point (start of next ramp)
        radius: Curve radius
        y: Y coordinate (constant)
        curve_direction: 'east' or 'west' - which way the curve opens
    """
    import math
    coords = []

    # Analyze the turn
    x_start = start[0]
    x_end = end[0]
    z_start = start[2]
    z_end = end[2]

    # Determine the turn parameters
    z_distance = abs(z_end - z_start)  # How far north/south we move
    going_north = z_end < z_start

    # For a U-turn, we create a semicircle
    # The semicircle curves in the X direction (perpendicular to travel)
    # while progressing in the Z direction (direction of travel)

    # The radius of the turn in X direction
    turn_radius = z_distance / 2

    # Center of the semicircle
    center_x = (x_start + x_end) / 2
    center_z = (z_start + z_end) / 2

    # Generate points along the semicircle
    num_points = max(int(z_distance * 1.5), 24)  # Smooth curve

    for i in range(num_points + 1):
        t = i / num_points

        # Angle from 0 to π (180 degrees)
        angle = t * math.pi

        # Z progresses linearly from start to end (direction of travel)
        if going_north:
            z = int(z_start - t * z_distance)
        else:
            z = int(z_start + t * z_distance)

        # X follows a semicircular path (perpendicular to travel)
        # This creates the U-turn
        x_offset = turn_radius * math.sin(angle)

        if curve_direction == 'east':
            # Curve opens to the east (goes west then back east)
            x = int(center_x - x_offset)
        else:  # 'west'
            # Curve opens to the west (goes east then back west)
            x = int(center_x + x_offset)

        coords.append((x, y, z))

    return coords

# ============================================================
# 5. RUN + PRINT RESULT
# ============================================================

def main():
    new_coords = generate_serpentine_path()

    print("New coordinate list:\n")
    for c in new_coords:
        print(f"[{c[0]}, {c[1]}, {c[2]}]")

    print("\nSummary:")
    print(f"COORDS_BEGIN count: {len(COORDS_BEGIN)}")
    print(f"COORDS_END count:   {len(COORDS_END)}")
    print(f"Total new count:    {len(new_coords)}")

if __name__ == "__main__":
    main()
