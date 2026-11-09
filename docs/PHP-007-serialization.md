# PHP-007 — Safe Object Serialization

- **Business context:** Persist a checkpoint of a large import for later restoration in another PHP process
- **PHP focus:** `__serialize()`, `__unserialize()`, `__clone()`, `readonly`, resources, object lifecycle
- **EAV:** 20–30 minutes
- **Implementation:** one small class, three magic methods; no algorithms or external libraries

## Scenario

An import operation has a durable ID and a count of processed records. During execution it also owns a process-local stream. We need to save and restore the durable checkpoint, **not** the open stream resource.

Use the prepared `Practice\\ImportCheckpoint` in `src/ImportCheckpoint.php`. Its constructor, progress methods and stream attachment method are already provided.

```php
$checkpoint = new ImportCheckpoint('import-42', 10);
$checkpoint->advance(5);
$checkpoint->attachStream(fopen('php://temp', 'w+'));

$encoded = serialize($checkpoint);
$restored = unserialize($encoded, ['allowed_classes' => [ImportCheckpoint::class]]);

// $restored->importId === 'import-42'
// $restored->processed() === 15
// $restored->hasStream() === false
```

## Implement exactly three methods

1. **`__serialize(): array`**: return exactly `['importId' => string, 'processed' => int]`, excluding the transient stream.
2. **`__unserialize(array $data): void`**: restore ID and progress, including initialization of the `readonly` property, and leave the stream detached. PHP does not call `__construct()` automatically during unserialization.
3. **`__clone(): void`**: cloned checkpoint retains import ID and progress but does not share the original live stream. The source object must remain unchanged.

The class already has a `readonly` import ID. Keep it readonly. No versioning, schema migration, dependency injection, validation framework, or advanced algorithms required.

## PHP subtleties to notice

- `serialize()` calls `__serialize()` if provided.
- `unserialize()` restores objects without invoking their constructor and calls `__unserialize()`.
- A resource is not a persistable open file/socket handle across processes.
- A normal `clone` initially copies property values: resource handles are *not* independently duplicated.
- A `readonly` property that has not been initialized can be initialized as part of restoration from an appropriate class scope.

**Trust boundary:** Never call `unserialize()` on untrusted arbitrary input. The test passes `allowed_classes` as a defense-in-depth restriction, not as a blanket security guarantee.

## Acceptance

- Exact durable serialization payload.
- Round-trip `serialize()` → `unserialize()` preserves ID and progress.
- Runtime stream is excluded on restoration.
- Cloned object retains durable properties but does not share runtime stream.
- `readonly` initialization during restoration works.
- Existing PHP-001–006 tests remain green.

## Run

Merge the **setup** PR first (CI deliberately red), then create `drill/PHP-007` and implement the three stubs.

```bash
php tests/run.php
php -d memory_limit=16M tests/run.php
```

## Senior-level discussion

Why should an import checkpoint serialize its progress but not a live file handle? What is different between cloning an object and restoring it from serialized data?
