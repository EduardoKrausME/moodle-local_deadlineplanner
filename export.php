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
 * Safe UTF-8 CSV export restricted to courses viewers may access.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
$courseid = required_param('courseid', PARAM_INT);
$start = optional_param('start', strtotime('today'), PARAM_INT);
$end = optional_param('end', $start + 28 * DAYSECS, PARAM_INT);
$course = get_course($courseid);
require_login($course);
require_capability('local/deadlineplanner:view', context_course::instance($courseid));
if ($start < 0 || $end <= $start || $end - $start > 366 * DAYSECS) {
    throw new moodle_exception('invaliddate', 'local_deadlineplanner');
}
$rows = (new \local_deadlineplanner\local\calendar_data_service())->calendar(
    $courseid, $USER->id, $start, $end);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="deadlineplanner.csv"');
header('Cache-Control: private, no-store');
$stream = fopen('php://output', 'w');
fputcsv($stream, ['Activity', 'Type', 'Due at', 'URL']);
foreach ($rows as $row) {
    $fields = [$row['name'], $row['type'], $row['date'], $row['url']];
    $fields = array_map(static function($value): string {
        $value = (string) $value;
        return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'" . $value : $value;
    }, $fields);
    fputcsv($stream, $fields);
}
fclose($stream);
exit;
