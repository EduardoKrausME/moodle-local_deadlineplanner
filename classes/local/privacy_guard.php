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
 * Disclosure-limited cross-course impact summaries.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_deadlineplanner\local;

final class privacy_guard {
    /**
     * Suppress small cohorts and cells; publish only coarse size bands.
     * This is indicative planning intelligence, not a roster or a statistical export.
     */
    public static function public_summary(array $raw, int $courseid = 0): array {
        if (!empty($raw['unavailable'])) {
            return ['available' => false, 'reason' => $raw['reason']];
        }
        $min = policy_evaluator::policy($courseid)['privacymin'];
        $total = (int) $raw['analysed'];
        if ($total < $min * 2) {
            return ['available' => false,
                'reason' => get_string('privacyrestricted', 'local_deadlineplanner')];
        }
        foreach (['before', 'after', 'improved', 'worsened'] as $key) {
            if ($raw[$key] > 0 && ($raw[$key] < $min || $total - $raw[$key] < $min)) {
                return ['available' => false,
                    'reason' => get_string('privacyrestricted', 'local_deadlineplanner')];
            }
        }
        return [
            'available' => true,
            'before' => self::band($raw['before'], $min),
            'after' => self::band($raw['after'], $min),
            'improved' => self::band($raw['improved'], $min),
            'worsened' => self::band($raw['worsened'], $min),
        ];
    }

    private static function band(int $count, int $min): string {
        if ($count === 0) {
            return get_string('none', 'local_deadlineplanner');
        }
        $bucket = max($min, 10);
        $lo = (int) floor($count / $bucket) * $bucket;
        $lo = max($min, $lo);
        return $lo . '–' . ($lo + $bucket - 1);
    }
}
