#!/usr/bin/env python3
import math
from dataclasses import dataclass
from typing import List, Tuple, Optional

Vec3 = Tuple[float, float, float]

def smoothstep(t: float) -> float:
    # C1 continuous, flat derivatives at endpoints
    return t * t * (3 - 2 * t)

def dsmoothstep(t: float) -> float:
    # derivative of smoothstep
    return 6*t - 6*t*t

@dataclass
class CurveParams:
    y_end: int
    loops: int        # number of lateral circuits
    A: float          # north-bulge amplitude in Z (blocks)
    B: float          # lateral wiggle amplitude in X (blocks)
    samples: int      # number of sample points

@dataclass
class CurveReport:
    params: CurveParams
    ok: bool
    max_grade: float
    length3d: float
    length2d: float
    min_horiz_step: float
    min_chunk_z: int  # NEW: track minimum chunk Z (northernmost point)
    notes: str

def generate_curve_points(
    start: Vec3,
    end_xz: Tuple[float, float],
    y_end: float,
    loops: int,
    A: float,
    B: float,
    samples: int = 600
) -> List[Vec3]:
    """
    Generates a smooth curve that:
      - starts moving North (-Z) and ends moving South (+Z)
      - dx/dt = 0 at endpoints, so heading is purely along Z at both ends
      - includes 'loops' lateral oscillations to create circuits (adds horizontal distance)
    """

    x0, y0, z0 = start
    x1, z1 = end_xz

    pts: List[Vec3] = []

    for i in range(samples):
        t = i / (samples - 1)
        s = smoothstep(t)

        # Base x interpolation with zero endpoint derivatives:
        x_base = x0 + (x1 - x0) * s

        # Add lateral "circuits": sin(2π*loops*t) term
        # Multiply by sin^2(πt) so BOTH value and derivative are 0 at t=0 and t=1.
        gate = math.sin(math.pi * t) ** 2
        x = x_base + (B * gate * math.sin(2 * math.pi * loops * t) if loops > 0 else 0.0)

        # Z must start heading North and end heading South.
        # Base interpolation is smoothstep (flat at endpoints),
        # MINUS A*sin(πt) which gives dz/dt = -Aπ at t=0 and dz/dt = +Aπ at t=1.
        # This is the KEY CHANGE from the south-first version
        z = z0 + (z1 - z0) * s - A * math.sin(math.pi * t)

        # Y easing (you can swap to linear if you prefer)
        y = y0 + (y_end - y0) * s

        pts.append((x, y, z))

    return pts

def analyze_curve(points: List[Vec3]) -> Tuple[bool, float, float, float, float, int, str]:
    """
    Checks the 45° grade constraint segment-by-segment:
        |dy| <= horizontal_distance
    Returns:
        ok, max_grade, length3d, length2d, min_horiz_step, min_chunk_z, notes
    """
    max_grade = 0.0
    length3d = 0.0
    length2d = 0.0
    min_h = float("inf")
    min_z = float("inf")

    for (x0, y0, z0), (x1, y1, z1) in zip(points, points[1:]):
        dx = x1 - x0
        dy = y1 - y0
        dz = z1 - z0

        horiz = math.hypot(dx, dz)
        seg3 = math.hypot(horiz, dy)

        length2d += horiz
        length3d += seg3
        min_h = min(min_h, horiz)
        min_z = min(min_z, z0, z1)

        if horiz == 0:
            # vertical step => fails immediately
            return (False, float("inf"), length3d, length2d, min_h, int(min_z // 16), "Found zero horizontal step (vertical move).")

        grade = abs(dy) / horiz
        max_grade = max(max_grade, grade)

        if abs(dy) > horiz + 1e-9:
            return (False, max_grade, length3d, length2d, min_h, int(min_z // 16), "Exceeded 45° grade on at least one segment.")

    min_chunk_z = int(min_z // 16)
    return (True, max_grade, length3d, length2d, min_h, min_chunk_z, "OK")

def round_points(points: List[Vec3]) -> List[Tuple[int,int,int]]:
    return [(int(round(x)), int(round(y)), int(round(z))) for (x,y,z) in points]

def search(
    start: Vec3,
    end_xz: Tuple[float, float],
    y_end_min: int,
    y_end_max: int,
    loops_range: range,
    A_values: List[float],
    B_values: List[float],
    samples: int = 800,
    max_chunk_z_north: int = 18  # NEW: northernmost allowed chunk
) -> List[CurveReport]:
    reports: List[CurveReport] = []

    for y_end in range(y_end_min, y_end_max + 1, 2):
        for loops in loops_range:
            for A in A_values:
                for B in B_values:
                    pts = generate_curve_points(
                        start=start,
                        end_xz=end_xz,
                        y_end=y_end,
                        loops=loops,
                        A=A,
                        B=B,
                        samples=samples
                    )
                    ok, max_grade, L3, L2, min_h, min_chunk_z, notes = analyze_curve(pts)

                    # Check if curve goes too far north
                    if ok and min_chunk_z < max_chunk_z_north:
                        ok = False
                        notes = f"Goes too far north: chunk Z={min_chunk_z} (limit: >={max_chunk_z_north})"

                    reports.append(CurveReport(
                        params=CurveParams(y_end=y_end, loops=loops, A=A, B=B, samples=samples),
                        ok=ok,
                        max_grade=max_grade,
                        length3d=L3,
                        length2d=L2,
                        min_horiz_step=min_h,
                        min_chunk_z=min_chunk_z,
                        notes=notes
                    ))

    # Keep only OK curves, sort by smoothness proxy: lower max_grade, then shorter length (or tweak)
    ok_reports = [r for r in reports if r.ok]
    ok_reports.sort(key=lambda r: (r.max_grade, r.length3d))
    return ok_reports

def main():
    start = (-199.0, 98.0, 410.0)
    end_xz = (-330.0, 352.0)  # X and Z fixed

    # Search space knobs:
    loops_range = range(0, 9)  # allow up to 8 circuits
    A_values = [40, 60, 80, 100, 120, 140]   # how far north it bulges
    B_values = [0, 10, 20, 30, 40, 60, 80]   # how wide the circuits are (0 works when loops=0)

    all_curves = search(
        start=start,
        end_xz=end_xz,
        y_end_min=222,
        y_end_max=270,
        loops_range=loops_range,
        A_values=A_values,
        B_values=B_values,
        samples=900,
        max_chunk_z_north=18  # Can go as far north as chunk 18
    )

    if not all_curves:
        print("No feasible curves found in this search space. Try increasing loops/A/B or samples.")
        return

    print(f"Found {len(all_curves)} valid curves. Saving to files...\n")

    # Save all curves to files in curves_north directory
    import os
    output_dir = "curves_north"
    os.makedirs(output_dir, exist_ok=True)

    for i, curve in enumerate(all_curves, 1):
        p = curve.params
        filename = f"curve_y{p.y_end}_loops{p.loops}_A{int(p.A)}_B{int(p.B)}.txt"
        filepath = os.path.join(output_dir, filename)

        # Generate the curve points
        pts = generate_curve_points(start, end_xz, p.y_end, p.loops, p.A, p.B, samples=350)
        rounded = round_points(pts)

        with open(filepath, 'w') as f:
            # Write metadata header
            f.write(f"# Curve Parameters (NORTH-FIRST)\n")
            f.write(f"# y_end: {p.y_end}\n")
            f.write(f"# loops: {p.loops}\n")
            f.write(f"# A (north bulge): {p.A}\n")
            f.write(f"# B (lateral wiggle): {p.B}\n")
            f.write(f"# samples: 350\n")
            f.write(f"#\n")
            f.write(f"# Analysis Results\n")
            f.write(f"# max_grade: {curve.max_grade:.6f}\n")
            f.write(f"# length_3d: {curve.length3d:.2f}\n")
            f.write(f"# length_2d: {curve.length2d:.2f}\n")
            f.write(f"# min_horiz_step: {curve.min_horiz_step:.6f}\n")
            f.write(f"# min_chunk_z: {curve.min_chunk_z}\n")
            f.write(f"# status: {curve.notes}\n")
            f.write(f"#\n")
            f.write(f"# Coordinates (x, y, z)\n")
            f.write(f"#" + "="*50 + "\n\n")

            # Write coordinates
            for pt in rounded:
                f.write(f"{list(pt)}\n")

        if i % 50 == 0:
            print(f"Saved {i}/{len(all_curves)} curves...")

    print(f"\nAll {len(all_curves)} curves saved to '{output_dir}/' directory")

    # Print summary of top 20 curves
    print("\nTop 20 curves (sorted by lowest max grade, then shortest length):\n")
    for i, r in enumerate(all_curves[:20], 1):
        p = r.params
        print(
            f"{i:2d}) y_end={p.y_end} loops={p.loops} A={p.A} B={p.B} | "
            f"max_grade={r.max_grade:.3f} | L2={r.length2d:.1f} L3={r.length3d:.1f} | "
            f"min_chunk_z={r.min_chunk_z}"
        )

    # Show the best curve filename
    if all_curves:
        best = all_curves[0]
        bp = best.params
        best_filename = f"curve_y{bp.y_end}_loops{bp.loops}_A{int(bp.A)}_B{int(bp.B)}.txt"
        print(f"\nBest curve saved as: {output_dir}/{best_filename}")

if __name__ == "__main__":
    main()
