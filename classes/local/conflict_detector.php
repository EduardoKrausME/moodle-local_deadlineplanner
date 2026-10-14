<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Compare before/after cohort workload without disclosing user-level rows.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\local;

/**
 * Conflict detector implementation.
 */
final class conflict_detector {
    /**
     * Evaluate impact for a course cohort, with a strict hard cap rather than silently sampling.
     *
     * @param int $courseid owned course
     * @param int $cmid target activity
     * @param int $old timestamp
     * @param int $proposed timestamp
     * @return array nonidentifying counts
     */
    public function compare(int $courseid, int $cmid, int $old, int $proposed): array {
        $context = \context_course::instance($courseid);
        $limit = max(100, (int) get_config('local_deadlineplanner', 'maxlearners') ?: 1500);
        $policy = policy_evaluator::policy($courseid);
        // Moodle filters suspended enrolments when onlyactive=true.
        $learners = get_enrolled_users($context, '', 0, 'u.id', 'u.id ASC', 0, $limit + 1, true);
        if (count($learners) > $limit) {
            return ['unavailable' => true, 'reason' => get_string('limitexceeded', 'local_deadlineplanner')];
        }
        $before = 0;
        $after = 0;
        $improved = 0;
        $worsened = 0;
        $analysed = 0;
        $calculator = new learner_workload_calculator();
        $start = min($old, $proposed) - 8 * DAYSECS;
        $end = max($old, $proposed) + 8 * DAYSECS;
        foreach ($learners as $learner) {
            $userid = (int) $learner->id;
            $activities = $calculator->collect($userid, $start, $end);
            $proposedactivities = $calculator->collect($userid, $start, $end,
                ['cmid' => $cmid, 'due' => $proposed]);
            $zone = learner_workload_calculator::zone($userid);
            $beforestats = policy_evaluator::evaluate($activities, $zone,
                $policy['dailycount'], $policy['weeklycount'], $policy['dailyweight']);
            $afterstats = policy_evaluator::evaluate($proposedactivities, $zone,
                $policy['dailycount'], $policy['weeklycount'], $policy['dailyweight']);
            // Do not include instructors or staff without any relevant assessment capability.
            if (!$activities && !$proposedactivities) {
                continue;
            }
            $analysed++;
            $b = count($beforestats['overload']) > 0;
            $a = count($afterstats['overload']) > 0;
            $before += (int) $b;
            $after += (int) $a;
            $improved += (int) ($b && !$a);
            $worsened += (int) (!$b && $a);
        }
        return [
            'unavailable' => false,
            'analysed' => $analysed,
            'before' => $before,
            'after' => $after,
            'improved' => $improved,
            'worsened' => $worsened,
        ];
    }
}
