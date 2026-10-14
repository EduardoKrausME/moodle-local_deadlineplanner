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
 * Deterministic threshold evaluator.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\local;

/**
 * Policy evaluator implementation.
 */
final class policy_evaluator {
    /**
     * Determine overload days/week buckets from an activity list.
     *
     * @param array $activities each row has timestamp and weight
     * @param \DateTimeZone $zone calendar zone of learner
     * @param int $dailycount delivery count threshold
     * @param int $weeklycount delivery count threshold
     * @param float $dailyweight indicative weight threshold
     * @return array breakdown and flagged days
     */
    public static function evaluate(array $activities, \DateTimeZone $zone,
            int $dailycount, int $weeklycount, float $dailyweight): array {
        $days = [];
        $weeks = [];
        foreach ($activities as $activity) {
            if (empty($activity['timestamp'])) {
                continue;
            }
            $day = (new \DateTimeImmutable('@' . $activity['timestamp']))->setTimezone($zone);
            $datekey = $day->format('Y-m-d');
            $weekkey = $day->format('o-\WW');
            if (!isset($days[$datekey])) {
                $days[$datekey] = ['count' => 0, 'weight' => 0.0];
            }
            $days[$datekey]['count']++;
            $days[$datekey]['weight'] += (float) $activity['weight'];
            $weeks[$weekkey] = ($weeks[$weekkey] ?? 0) + 1;
        }
        ksort($days);
        ksort($weeks);
        $overload = [];
        foreach ($days as $day => $stats) {
            $week = (new \DateTimeImmutable($day, $zone))->format('o-\WW');
            if ($stats['count'] > $dailycount || $stats['weight'] > $dailyweight ||
                    $weeks[$week] > $weeklycount) {
                $overload[] = $day;
            }
        }
        return ['days' => $days, 'weeks' => $weeks, 'overload' => $overload];
    }

    /** Get effective administrator thresholds with hard safety minimum for anonymity. */
    /**
     * Policy.
     */
    public static function policy(int $courseid = 0): array {
        global $DB;
        $defaults = [
            'dailycount' => max(1, (int) get_config('local_deadlineplanner', 'dailycount') ?: 3),
            'weeklycount' => max(1, (int) get_config('local_deadlineplanner', 'weeklycount') ?: 8),
            'dailyweight' => max(0.1, (float) get_config('local_deadlineplanner', 'dailyweight') ?: 4.0),
            'privacymin' => max(5, (int) get_config('local_deadlineplanner', 'privacymin')),
        ];
        if ($courseid <= 1) {
            return $defaults;
        }
        $context = \context_course::instance($courseid);
        $row = $DB->get_record('local_deadlineplanner_policy', ['contextid' => $context->id]);
        if (!$row) {
            return $defaults;
        }
        return [
            'dailycount' => max(1, (int) $row->dailycount),
            'weeklycount' => max(1, (int) $row->weeklycount),
            'dailyweight' => max(0.1, (float) $row->dailyweight),
            'privacymin' => max(5, (int) $row->privacymin),
        ];
    }

    /** Weights are indicative, not estimates of hours or real workload. */
    /**
     * Weight.
     */
    public static function weight(string $modname): float {
        if ($modname === 'quiz') {
            return max(0.1, (float) get_config('local_deadlineplanner', 'weightquiz') ?: 1.5);
        }
        return max(0.1, (float) get_config('local_deadlineplanner', 'weightassign') ?: 1.0);
    }
}
