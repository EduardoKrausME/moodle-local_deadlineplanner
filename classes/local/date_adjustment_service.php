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
 * Approval and concurrency revalidation for safe manual changes.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_deadlineplanner\local;

final class date_adjustment_service {
    /** Approve after checking live permissions and the complete date fingerprint. */
    public function approve(int $scenarioid, int $actorid): \stdClass {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $scenario = $DB->get_record('local_dlp_scenario', ['id' => $scenarioid], '*', MUST_EXIST);
        if ($scenario->status !== 'draft') {
            throw new \moodle_exception('invalidstate', 'local_deadlineplanner');
        }
        $context = \context_course::instance($scenario->courseid);
        require_capability('local/deadlineplanner:apply', $context);
        $modulecontext = \context_module::instance($scenario->cmid);
        require_capability('moodle/course:manageactivities', $modulecontext);
        $cm = get_coursemodule_from_id('', $scenario->cmid, $scenario->courseid, false, MUST_EXIST);
        $provider = provider_registry::get($cm->modname);
        $base = $provider ? $provider->base((int) $cm->instance) : null;
        if (!$base || calendar_data_service::fingerprint($base) !== $scenario->fingerprint ||
                (int) $base['due'] !== (int) $scenario->olddue) {
            throw new \moodle_exception('staleproposal', 'local_deadlineplanner');
        }
        $scenario->status = 'approved';
        $scenario->reviewerid = $actorid;
        $scenario->timemodified = time();
        $DB->update_record('local_dlp_scenario', $scenario);
        audit_history::add($scenarioid, $actorid, 'approved');
        $transaction->allow_commit();
        notification_service::status_changed($scenario, $actorid);
        return $scenario;
    }

    /**
     * This release intentionally does not automatically write to mod_assign/mod_quiz.
     * Module form update APIs may reset settings without complete form state.
     * Return a live, capability-checked link for controlled manual application.
     */
    public function manual_edit_url(int $scenarioid): \moodle_url {
        global $DB;
        $scenario = $DB->get_record('local_dlp_scenario', ['id' => $scenarioid], '*', MUST_EXIST);
        require_capability('local/deadlineplanner:apply', \context_course::instance($scenario->courseid));
        require_capability('moodle/course:manageactivities', \context_module::instance($scenario->cmid));
        if ($scenario->status !== 'approved') {
            throw new \moodle_exception('invalidstate', 'local_deadlineplanner');
        }
        $cm = get_coursemodule_from_id('', $scenario->cmid, $scenario->courseid, false, MUST_EXIST);
        $provider = provider_registry::get($cm->modname);
        $base = $provider ? $provider->base((int) $cm->instance) : null;
        if (!$base || calendar_data_service::fingerprint($base) !== $scenario->fingerprint) {
            throw new \moodle_exception('staleproposal', 'local_deadlineplanner');
        }
        return new \moodle_url('/course/modedit.php', ['update' => $scenario->cmid, 'return' => 0]);
    }
}
