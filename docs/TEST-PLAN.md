# Runtime test coverage and scenarios to extend

PHPUnit has checks for same-day concentration, weekly overload, weights, multi-timezone computation, DST boundaries, native assignment extensions, quiz user overrides, cross-course enrolments, hidden activities, suspended enrolments, suppression of small aggregates, lost-update concurrency, and authorized approval without an implicit module write.

Behat smoke scenarios cover the course-level calendar, privacy expectations and the absence of a simulation button without a dated supported module. Full browser scenarios must also create assignment/quiz fixtures with valid submission settings and exercise proposal creation, authorized approval and rejected updates. Those extra fixtures cannot be assumed to pass without a running Moodle Behat environment.

Manual security review is required for separate groups, group quiz overrides, timed restricted availability, individual extensions, concurrent editing, CSV formula payloads, and adaptive differencing attacks. Do not label this release production ready until these cases and the mandatory validator pass under actual Moodle 4.5 and 5.x installations.
