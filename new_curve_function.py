def create_180_degree_curve(start: Coord, end: Coord, radius: int, y: int) -> List[Coord]:
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
    going_east = x_end > x_start  # Direction we'll be going AFTER the turn

    # For a U-turn, we create a semicircle
    # The semicircle has its "opening" in the Z direction
    # and its "width" in the X direction

    # The radius of the turn is determined by the Z distance
    turn_radius = z_distance / 2

    # Center of the semicircle
    center_x = (x_start + x_end) / 2
    center_z = (z_start + z_end) / 2

    # Generate points along the semicircle
    num_points = max(int(z_distance * 1.5), 24)  # Smooth curve

    for i in range(num_points + 1):
        t = i / num_points

        # Angle from 0 to π (180 degrees)
        # We start at one side and end at the other
        if going_east:
            # Starting west, ending east: angle goes from π to 0
            angle = math.pi * (1 - t)
        else:
            # Starting east, ending west: angle goes from 0 to π
            angle = math.pi * t

        # Calculate position on semicircle
        # X follows the semicircular arc
        x_offset = turn_radius * math.cos(angle)
        x = int(center_x + x_offset)

        # Z follows the semicircular arc
        z_offset = turn_radius * math.sin(angle)
        if going_north:
            z = int(center_z - z_offset)
        else:
            z = int(center_z + z_offset)

        coords.append((x, y, z))

    return coords
