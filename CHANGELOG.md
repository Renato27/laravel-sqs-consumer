# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Fixed
- A message the handler released (`$job->release($delay)`) is no longer deleted by the after-job listener; it comes back after its delay. Already-deleted jobs are not deleted twice.

### Added

- Per-connection consumer options, all optional and backwards compatible:
  - `wait_time`: long polling (`WaitTimeSeconds`) instead of short polling.
  - `visibility_timeout`: how long a received message stays hidden (`VisibilityTimeout`), for handlers slower
    than the queue default.
  - `queues`: overrides the global `sqs-consumer.queues` list, so each queue can get its own connection and
    `queue:work` process.

## [2.0.0] - 2026-07-21

### Changed

- **Breaking:** raised minimum PHP version to `^8.1` (was `>=7.1.3`).
- **Breaking:** dropped support for Laravel/Lumen 7, 8 and 9. This package now
  requires Laravel/Lumen 10, 11, 12 or 13.
- Bumped dev dependencies to match: PHPUnit `^9.6|^10.5|^11.5|^12.0`,
  Orchestra Testbench `^8.0|^9.0|^10.0|^11.0`, added Mockery `^1.6`.

### Fixed

- Service providers now register the `sqs-consumer` connector via
  `Container::resolving('queue', ...)` instead of resolving `queue` eagerly
  (Lumen provider) or waiting for `booted()` (Laravel provider). This fixes a
  `Target class [queue] does not exist` error on recent Laravel versions,
  where the queue service provider is deferred.
- `QueueSqs::executeQueue()` no longer requires a non-nullable queue name,
  fixing a `TypeError` that occurred whenever `sqs-consumer.queues` was empty
  and the connection's default queue was polled directly.

### Removed

- Dead code path in `QueueSqs::executeQueue()` that branched on the host
  framework's version but returned identical behavior on both sides.

### Testing

- Replaced PHPUnit's `getMockBuilder()->addMethods()` (removed in PHPUnit 12)
  with Mockery for mocking the AWS SQS client.
- Added coverage for multi-queue iteration, handler-to-queue mapping,
  default-handler fallback, and empty-queue/no-message scenarios.
- Added a GitHub Actions workflow running the suite across PHP 8.1-8.4 and
  Laravel 10-13.

## [1.0.1] - previous release

- See git history prior to this changelog.
