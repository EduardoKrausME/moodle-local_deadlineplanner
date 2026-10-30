# Course backup and restore

The local_deadlineplanner backup hooks use two Moodle connection points.

- Course: the course-context planning policy is included even when the backup excludes user information. The policy is attached to the target course context. Any existing target-course policy is updated.
- Module: scenarios and their audit histories are included only when the activity's user information is included. Moodle's user mappings are applied to authors, reviewers and actors; missing required mappings cause those records to be skipped rather than leaving dangling references.

The original proposal dates, fingerprints, states and audit details remain historical snapshots. Restoration does not approve a date change or modify native Moodle activities. The existing approval service checks the restored activity against its live fingerprint, and a changed activity must be re-simulated.

The derived deadline table and synchronization watermarks are not backed up. Restoring an activity invalidates the target course's synchronization watermark and deadline cache, so the next planner read rebuilds these values from its assignments and quizzes.

## Validation

Run the backup/restore PHPUnit tests on supported Moodle 4.5 and 5.x installations, and verify activity-only restore, userless backup, and restore into an existing course in the Moodle UI. A static review cannot replace runtime validation.
