#!/usr/bin/env python3
"""
Calculate anchor points for serpentine minecart path.
Anchor points are where ramps transition to curves.
"""

# Start and end points
start = (-226, 130, 326)
end = (-324, 214, 318)

# Total distances
total_x = end[0] - start[0]  # -98 (west)
total_y = end[1] - start[1]  # 84 (up)
total_z = end[2] - start[2]  # -8 (north)

print(f"Start: {start}")
print(f"End: {end}")
print(f"Total distance: X={total_x}, Y={total_y}, Z={total_z}")
print()

# Constants
CURVE_RADIUS = 8
CURVE_Z_SHIFT = 16  # How much Z changes during a 180° turn
Y_INCREMENT = total_y / 5  # Each ramp climbs 1/5 of total

# For X: we go west, east, west, east, west
# Net movement should be total_x (-98 west)
# If each west segment is W and each east segment is E:
# -W + E - W + E - W = -98
# -3W + 2E = -98
# We want W = E for symmetry, so: -3W + 2W = -W = -98, thus W = 98
# But we said 2/3 of total, so let's use that: W = 65.33

LONG_RAMP_DISTANCE = abs(total_x) * 3 / 4  # 2/3 of total X distance per ramp
SHORT_RAMP_DISTANCE = abs(total_x) * 2 / 3  # 1/3 of total X distance per ramp

print(f"Y increment per ramp: {Y_INCREMENT}")
print(f"Long ramp distance per ramp: {LONG_RAMP_DISTANCE}")
print(f"Short ramp distance per ramp: {SHORT_RAMP_DISTANCE}")
print()

# Calculate anchor points
anchors = []

# Starting position
current_x = start[0]
current_y = start[1]
current_z = start[2]
direction = -1  # -1 = west, +1 = east

print("ANCHOR POINTS (end of ramp, start of curve):")
print("=" * 60)

# Ramp 1 -> Curve 1 (going WEST)
current_x += direction * LONG_RAMP_DISTANCE
current_y += Y_INCREMENT
anchor1 = (int(current_x), int(current_y), current_z)
anchors.append(("After Ramp 1", anchor1))
print(f"Anchor 1 (after Ramp 1, before Curve 1): {anchor1}")

# After Curve 1 (turn north, now facing EAST)
current_z -= CURVE_Z_SHIFT+8  # 24 blocks north
direction *= -1  # Now going east
after_curve1 = (int(current_x), int(current_y), current_z)
print(f"  After Curve 1: {after_curve1}")
print()

# Ramp 2 -> Curve 2 (going EAST)
current_x += direction * SHORT_RAMP_DISTANCE
current_y += Y_INCREMENT
# Z stays the same (0 north/south change)
anchor2 = (int(current_x), int(current_y), current_z)
anchors.append(("After Ramp 2", anchor2))
print(f"Anchor 2 (after Ramp 2, before Curve 2): {anchor2}")

# After Curve 2 (turn south, now facing WEST)
current_z += CURVE_Z_SHIFT+4  # 20 blocks south
direction *= -1  # Now going west
after_curve2 = (int(current_x), int(current_y), current_z)
print(f"  After Curve 2: {after_curve2}")
print()

# Ramp 3 -> Curve 3 (going WEST)
current_x += direction * SHORT_RAMP_DISTANCE
current_y += Y_INCREMENT
# Z stays the same (0 north/south change)
anchor3 = (int(current_x), int(current_y), current_z)
anchors.append(("After Ramp 3", anchor3))
print(f"Anchor 3 (after Ramp 3, before Curve 3): {anchor3}")

# After Curve 3 (turn south again, now facing EAST)
current_z += CURVE_Z_SHIFT+4  # 20 blocks south
direction *= -1  # Now going east
after_curve3 = (int(current_x), int(current_y), current_z)
print(f"  After Curve 3: {after_curve3}")
print()

# Ramp 4 -> Curve 4 (going EAST)
current_x += direction * SHORT_RAMP_DISTANCE
current_y += Y_INCREMENT
# Z stays the same (0 north/south change)
anchor4 = (int(current_x), int(current_y), current_z)
anchors.append(("After Ramp 4", anchor4))
print(f"Anchor 4 (after Ramp 4, before Curve 4): {anchor4}")

# After Curve 4 (turn north, now facing WEST)
current_z -= CURVE_Z_SHIFT+8  # 24 blocks north
direction *= -1  # Now going west
after_curve4 = (int(current_x), int(current_y), current_z)
print(f"  After Curve 4: {after_curve4}")
print()

# Ramp 5 -> End (going WEST to end point)
# Calculate how far west we need to go
remaining_x = end[0] - current_x
current_x = end[0]
current_y += Y_INCREMENT
# Z should already be at target from the curves (0 change on this ramp)
final_z = current_z
anchor5 = (int(current_x), int(current_y), int(final_z))
print(f"Anchor 5 (End point): {anchor5}")
print(f"Expected end: {end}")
print(f"Remaining X for ramp 5: {remaining_x}")
print()

# Check Z alignment
print(f"Current Z after curve 4: {after_curve4[2]}")
print(f"Final Z after ramp 5: {final_z}")
print(f"Target Z: {end[2]}")
print(f"Z difference from target: {end[2] - final_z}")
print()

# Summary for visualization
print("=" * 60)
print("COPY THESE COORDINATES FOR VISUALIZATION:")
print("=" * 60)
print(f"{start}")
print(f"{anchor1}")
print(f"{after_curve1}")
print(f"{anchor2}")
print(f"{after_curve2}")
print(f"{anchor3}")
print(f"{after_curve3}")
print(f"{anchor4}")
print(f"{after_curve4}")
print(f"{end}")
