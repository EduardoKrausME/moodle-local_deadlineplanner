# local_deadlineplanner (1.0.0)

Academic planning across Moodle course enrolments, emphasizing safe aggregation over instructor access to individual external-course activity. Moodle **4.5 LTS+** and 5.x target, with PHP-supported prerequisites inherited from the Moodle version.

## Installation

1. Copy this directory to `local/deadlineplanner` under the Moodle source tree.
2. Visit Site administration > Notifications to install the XMLDB tables.
3. Configure policies under Site administration > Plugins > Local plugins > Academic deadline planner.
4. Grant `local/deadlineplanner:view` and `local/deadlineplanner:simulate` only to roles permitted in each course, and `local/deadlineplanner:apply` to coordinators. Reserve `viewall` and `configure` for explicitly trusted staff.
5. Ensure Moodle cron runs. A scheduled task refreshes course snapshots every 15 minutes, with adhoc invalidation on module changes.
6. Open Course navigation > Academic deadline planner, or `local/deadlineplanner/index.php?courseid=123`.

## Operation

Supports assignments (due dates and learner extensions) and quizzes (close dates and user/group override by official quiz API). Opening dates are never treated as due dates; cutoff and opening times are separately retained. Some Moodle modules have other date fields but are not included unless an audited provider is added.

The per-course dashboard and CSV list only the viewer's permitted activities. Each simulation recalculates the authorized course's cohort by looking at active enrolments and each student's actual view of their enrolled courses. Results are aggregated, suppressed for small groups, and binned into indicative count ranges. No other-course module names, student names, or student-by-student timetables are stored or displayed. Weighted thresholds are merely policy heuristics, not workload measurements.

A simulation is **read-only** toward course modules, and saved as a proposal with a date fingerprint and audit trail. A reviewer with explicit apply capability and normal Moodle module-edit capability may approve while rechecking concurrency. On approval, a safe link to the Moodle editor is provided. **The 1.0.0 release intentionally does not programmatically change dates:** module update functions require complete form state and were not validated in the available environment, so writing is disabled rather than risking corrupted grading/availability settings.

## Known restrictions

- Deadline collection is native assign/quiz only; other activity types need contracts and tests.
- Simulations are bounded at the configured maximum enrolments and return *unavailable* rather than estimates when exceeded. Simulations may be CPU intensive for many cross-enrolled learners; schedule outside heavy traffic.
- Availability uses Moodle's per-user `get_fast_modinfo` and submission permissions. Conditional restrictions dependent on time can change between calculation and actual student access; refresh and re-simulate close to deployment.
- Group extensions in `assign` that are implemented through mechanisms other than individual `assign_user_flags` may not be resolved. A strict integration adapter should be added before claiming full coverage.
- Completion/attempt state is not used to remove already-submitted items from potential workload counts.
- Cross-course privacy thresholds reduce disclosure but cannot guarantee protection from inference by repeated adaptive scenario queries. Limit simulate capability accordingly. The plugin caps scenario creation at 30 proposals per author per rolling 24 hours; additional operational controls are still recommended for sensitive cohorts.
- Form dates use the user's timezone; SQL stores epoch seconds. Ambiguous DST fall-back wall times require explicit institutional scheduling policy.
- Approval is a workflow permission, not a claim that the underlying activity was updated.

## Validation

Before committing, run the full validator repository's `.chatgpt/run.py` normalizers and `--check`, plus PHP lint, Moodle Plugin CI validate, PHPCS, PHPUnit, and Behat in a complete Moodle installation. Do not interpret source-file presence as a passing test.

## License

GPL-3.0-or-later. Copyright 2026 Eduardo Kraus.
