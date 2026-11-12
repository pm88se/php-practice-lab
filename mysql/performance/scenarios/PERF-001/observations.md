# PERF-001 — Experiment notes (fill in during implementation)

Dataset profile: `small / medium / large`

Rows: ...

## Baseline

- Task A plan: ...
- Task B plan: ...
- Measured baseline timings (repeat runs): ...

## Hypothesis

Which work is the optimizer doing unnecessarily, and why?

## Experiment

- SQL changes: see `solution.sql`
- Task A plan after: ...
- Task B plan after: ...
- Timings after (repeat runs): ...
- Result correctness: unchanged / explain differences

## Trade-offs

- Additional index size and write overhead:
- Why your chosen index/order (or query rewrite) works:
- When your solution may not help:

Do not treat noisy CI or single-run timings as stable performance measurements.
