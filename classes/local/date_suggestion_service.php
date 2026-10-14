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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * Deterministic draft scheduling suggestions based on own-course deadlines.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_deadlineplanner\local;

/**
 * These suggestions are cheap candidates, not learner-aware promises.
 * An actual proposed move must be evaluated by the cross-course simulator.
 */
final class date_suggestion_service {
    public function suggest(int $courseid, int $cmid): array {
        $context = \context_course::instance($courseid);
        require_capability('local/deadlineplanner:simulate', $context);
        $dataservice = new calendar_data_service();
        $rows = $dataservice->get_course($courseid);
        $selected = null;
        $counts = [];
        $zone = \core_date::get_user_timezone_object();
        foreach ($rows as $row) {
            if ($row->cmid === $cmid) {
                $selected = $row;
            }
            if (!$row->duetime || $row->cmid === $cmid) {
                continue;
            }
            $day = (new \DateTimeImmutable('@' . $row->duetime))->setTimezone($zone)->format('Y-m-d');
            $counts[$day] = ($counts[$day] ?? 0) + 1;
        }
        if (!$selected || !$selected->duetime) {
            return [];
        }
        $reference = max(time() + DAYSECS, (int) $selected->duetime);
        $start = (new \DateTimeImmutable('@' . $reference))->setTimezone($zone);
        $candidates = [];
        for ($i = 1; $i <= 14; $i++) {
            $candidate = $start->modify('+' . $i . ' days');
            $epoch = $candidate->getTimestamp();
            if ($selected->cutofftime && $epoch > $selected->cutofftime) {
                continue;
            }
            $day = $candidate->format('Y-m-d');
            $candidates[] = ['timestamp' => $epoch, 'date' => $day,
                'count' => $counts[$day] ?? 0, 'inputdate' => $candidate->format('Y-m-d\TH:i')];
        }
        usort($candidates, static fn($a, $b) =>
            ($a['count'] <=> $b['count']) ?: ($a['timestamp'] <=> $b['timestamp']));
        return array_slice($candidates, 0, 3);
    }
}
