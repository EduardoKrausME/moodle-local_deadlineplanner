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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * planner.js
 *
 * @package   local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Client-side validation for date selection.
 *
 * @module local_deadlineplanner/planner
 */
export const init = () => {
    const area = document.querySelector('[data-region="deadlineplanner"]');
    if (!area) {
        return;
    }
    const filter = area.querySelector('form[method="get"]');
    const start = filter.querySelector('[name="startdate"]');
    const end = filter.querySelector('[name="enddate"]');
    filter.addEventListener('submit', (event) => {
        if (!start.value || !end.value || end.value < start.value) {
            event.preventDefault();
            end.focus();
        }
    });
};
