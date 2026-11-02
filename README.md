# PHP Practice Lab

Native PHP engineering drills: compact, production-shaped tasks around engineering problems rather than showcasing a language feature for its own sake.

## Goals

- **One PR = one compact drill** (target 15–25 minutes).
- Every drill has a **business scenario**, **engineering problem ID**, **constraints**, and **acceptance criteria**.
- Prefer the simplest adequate implementation; advanced features must justify their complexity.
- Use **native PHP**: no Laravel, Symfony, or framework bootstrap.
- Rotate concepts: generators/iterators, SPL, WeakMap, closures, Reflection/attributes, memory, files, types.

## Requirements

PHP **8.3+** and optionally Composer for PSR-4 autoloading. No external runtime dependencies.

## Quick start

```bash
php -v
php tests/run.php
php bin/demo.php
```

Composer can generate PSR-4 autoloading:

```bash
composer install
composer test
```

## Repository layout

- `src/` — small, reusable native-PHP components.
- `bin/` — CLI examples and drill entrypoints.
- `tests/` — minimal tests runnable without third-party packages.
- `fixtures/` — tiny representative input files.
- `.github/workflows/` — CI on PHP 8.3 and 8.4.

## Planned engineering problems

| Engineering problem | Candidate approaches | Possible follow-up variants |
| --- | --- | --- |
| ENG-01: Bound memory when processing large data | `Generator`, `SplFileObject`, `IteratorAggregate` | smaller files (simple array), huge files (streaming), repeated traversal |
| ENG-02: Compose transformations without materialization | `yield`, `yield from`, iterators | reusable pipelines and exception handling |
| ENG-05: Process higher-priority tasks first | `SplPriorityQueue` | equal priorities, stable ordering, dynamic updates |
| ENG-23: Avoid retaining objects unintentionally | `WeakMap`, `WeakReference`, compare `SplObjectStorage` | object life-cycle and GC |
| ENG-41/43: Metadata-driven dispatch and testability | Reflection, attributes, closures | cache reflection results, validate signatures |

**Note:** These are candidate mechanisms, not mandatory answers. A basic `foreach` or array can be the better engineering choice when constraints allow.

## Workflow

1. Create a branch: `drill/PHP-001`.
2. Implement the task with tests, run `php tests/run.php`.
3. Open a PR: `PHP-001: <short problem-oriented title>`.
4. Review covers correctness, clarity, complexity justified by constraints, and testing.
5. Squash and merge.

The practical task log should map each drill to one or more **Engineering Problem IDs**, without numeric interview scores.
