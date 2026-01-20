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

# Define the loop corners for smooth minecart path
CORNER_1 = (-316, 155, 300)  # Northwest corner (far west, north)
CORNER_2 = (-234, 170, 300)  # Northeast corner (back east, higher)

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

def generate_loop_path() -> List[Coord]:
    """Generate the complete path with smooth loop."""
    output: List[Coord] = []

    # Add COORDS_BEGIN as-is
    output.extend(COORDS_BEGIN)

    # Get start and end points
    start = COORDS_BEGIN[-1]  # [-226, 130, 326]
    end = COORDS_END[0]        # [-324, 214, 318]

    # Calculate distances for each segment
    # Segment 1: Start to Corner 1
    dist1_x = abs(CORNER_1[0] - start[0])
    dist1_z = abs(CORNER_1[2] - start[2])
    dist1 = int((dist1_x**2 + dist1_z**2)**0.5)

    # Segment 2: Corner 1 to Corner 2
    dist2 = abs(CORNER_2[0] - CORNER_1[0])

    # Segment 3: Corner 2 to End
    dist3_x = abs(end[0] - CORNER_2[0])
    dist3_z = abs(end[2] - CORNER_2[2])
    dist3 = int((dist3_x**2 + dist3_z**2)**0.5)

    # Generate each segment with smooth interpolation
    # Segment 1: Start → Corner 1 (west + north + climb)
    seg1 = interpolate_segment(start, CORNER_1, dist1)
    output.extend(seg1[1:])  # Skip first point (already in output)

    # Segment 2: Corner 1 → Corner 2 (east + climb, same Z)
    seg2 = interpolate_segment(CORNER_1, CORNER_2, dist2)
    output.extend(seg2[1:])  # Skip first point

    # Segment 3: Corner 2 → End (west + south + climb)
    seg3 = interpolate_segment(CORNER_2, end, dist3)
    output.extend(seg3[1:])  # Skip first point

    # Add COORDS_END as-is
    output.extend(COORDS_END[1:])  # Skip first point (already added)

    return output

# ============================================================
# 5. RUN + PRINT RESULT
# ============================================================

def main():
    new_coords = generate_loop_path()

    print("New coordinate list:\n")
    for c in new_coords:
        print(f"[{c[0]}, {c[1]}, {c[2]}]")

    print("\nSummary:")
    print(f"COORDS_BEGIN count: {len(COORDS_BEGIN)}")
    print(f"COORDS_END count:   {len(COORDS_END)}")
    print(f"Total new count:    {len(new_coords)}")

if __name__ == "__main__":
    main()
