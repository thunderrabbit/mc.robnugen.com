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
    x_distance = abs(x_end - x_start)  # Should be 0 for same X
    going_north = z_end < z_start
    going_east = x_end > x_start  # Direction we'll be going AFTER the turn

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
        if going_east:
            # We're going to end up east, so curve goes west then east
            x = int(center_x - x_offset)
        else:
            # We're going to end up west, so curve goes east then west
            x = int(center_x + x_offset)

        coords.append((x, y, z))

    return coords
