# Changelog

This changelog documents **only the two most recent published GitHub releases** of `local_deadlineplanner`, based on each published tag compared with its immediately preceding **published release** tag. No commits outside these tag comparisons are included.

## [1.0.3] - 2026-10-24

Published on GitHub at 2026-10-25 01:30:21 UTC (2026-10-24 22:30:21 America/Sao_Paulo).

Comparison: [1.0.2...1.0.3](https://github.com/EduardoKrausME/moodle-local_deadlineplanner/compare/1.0.2...1.0.3)

### Changed: JavaScript module loading

- Converted `amd/src/planner.js` from an ES module with `export const init` to a Moodle-compatible AMD `define([], function() { ... return {init}; })` module.
- Updated `amd/build/planner.min.js` to use a matching anonymous AMD `define` module rather than the previous named `define("local_deadlineplanner/planner", ...)` declaration.
- Preserved client-side validation that prevents submitting the date filter when either date is missing or the end date precedes the start date. The changes concern module format/initialization, not a newly introduced planning algorithm.

### Changed: release workflow

- Added a release-specific `concurrency` configuration to `.github/workflows/release.yml`, using `group: release-${{ github.ref }}`, `cancel-in-progress: false`, and `queue: max`.

### Documentation and maintenance

- Added GNU GPL boilerplate/license headers to three Mustache templates (`dashboard.mustache`, `policies.mustache`, and `scenario.mustache`).
- Standardized the GPL license URL from `https://www.gnu.org/licenses/` to `http://www.gnu.org/licenses/` in PHP source, configuration, language and test file headers. These are comment-only changes, not runtime behavior changes.
- Updated `version.php` from plugin version `2026101500` / release `1.0.2` to version `2026102400` / release `1.0.3`. The minimum Moodle build remained `2024100700`.

### Scope

The published-tag comparison contains five commits. Apart from the AMD source and build, the release workflow, version metadata and documentation/license comments, it does not establish changes to the scheduling engine, privacy behavior, persistence, or deadline-provider interfaces.

## [1.0.2] - 2026-10-15

Published on GitHub at 2026-10-15 20:29:44 UTC.

Comparison: [1.0.1...1.0.2](https://github.com/EduardoKrausME/moodle-local_deadlineplanner/compare/1.0.1...1.0.2)

### Changed

- Added the plugin icon asset at `pix/icon.png`.
- Updated `version.php` from plugin version `2026101400` / release `1.0.1` to version `2026101500` / release `1.0.2`.

### Scope

The comparison contains two commits (`1c4c16f83ca3efe6e32462be5e868bee7f852a4e` and `3e69ae78cfab42ca74ddacb9811a451586f2fd47`) and changes only `pix/icon.png` and `version.php`. No functional source, test files or workflows changed between these published tags. The minimum Moodle build remained `2024100700`.

---

These release notes reflect verified Git tag differences only. No tests or deployments were executed during their preparation.
