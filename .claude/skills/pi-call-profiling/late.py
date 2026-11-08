import sys
for d in sys.argv[1:]:
    rows = [l.split() for l in open(f"{d}/late.log") if l.strip()]
    t0 = float(rows[0][0])
    by = {}
    for t, kind, track, late in rows:
        if float(t) - t0 < 180:
            continue  # every call joined
        by.setdefault(kind, []).append(float(late))
    for kind, v in sorted(by.items()):
        v.sort(); n = len(v)
        over = lambda ms: 100 * sum(1 for x in v if x > ms) / n
        print(f"{d} {kind}: {n} samples; lateness ms p50={v[n//2]:.1f} p90={v[int(n*.9)]:.1f} p99={v[int(n*.99)]:.1f} max={v[-1]:.1f}; >20ms {over(20):.1f}%, >100ms {over(100):.1f}%, >1s {over(1000):.1f}%")
