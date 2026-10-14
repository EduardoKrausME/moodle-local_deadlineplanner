// This file is part of Moodle - http://moodle.org/
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
