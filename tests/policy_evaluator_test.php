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
 * Deterministic calendar and DST unit tests.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_deadlineplanner;

use local_deadlineplanner\local\policy_evaluator;

final class policy_evaluator_test extends \advanced_testcase {
    public function test_multiple_due_dates_raise_daily_conflict(): void {
        $this->resetAfterTest();
        $zone = new \DateTimeZone('America/Sao_Paulo');
        $stamp = (new \DateTimeImmutable('2026-10-14 14:00', $zone))->getTimestamp();
        $activities = array_fill(0, 4, ['timestamp' => $stamp, 'weight' => 1]);
        $result = policy_evaluator::evaluate($activities, $zone, 3, 8, 4.0);
        $this->assertSame(4, $result['days']['2026-10-14']['count']);
        $this->assertSame(['2026-10-14'], $result['overload']);
    }

    public function test_weekly_load_detects_conflict_across_days(): void {
        $zone = new \DateTimeZone('UTC');
        $activities = [];
        for ($day = 12; $day <= 16; $day++) {
            $timestamp = (new \DateTimeImmutable("2026-10-$day 13:00", $zone))->getTimestamp();
            $activities[] = ['timestamp' => $timestamp, 'weight' => 0.5];
        }
        $result = policy_evaluator::evaluate($activities, $zone, 3, 4, 4.0);
        $this->assertCount(5, $result['overload']);
    }

    public function test_weighted_quiz_not_assumed_equal_to_assignment(): void {
        $zone = new \DateTimeZone('UTC');
        $timestamp = strtotime('2026-10-14 12:00 UTC');
        $result = policy_evaluator::evaluate([
            ['timestamp' => $timestamp, 'weight' => 1.0],
            ['timestamp' => $timestamp, 'weight' => 2.0],
        ], $zone, 3, 8, 2.5);
        $this->assertSame(3.0, $result['days']['2026-10-14']['weight']);
        $this->assertSame(['2026-10-14'], $result['overload']);
    }

    public function test_timezone_local_day_can_differ_from_utc(): void {
        $timestamp = strtotime('2026-10-14 01:30 UTC');
        $event = [['timestamp' => $timestamp, 'weight' => 1]];
        $utc = policy_evaluator::evaluate($event, new \DateTimeZone('UTC'), 1, 8, 4.0);
        $brazil = policy_evaluator::evaluate($event, new \DateTimeZone('America/Sao_Paulo'), 1, 8, 4.0);
        $this->assertArrayHasKey('2026-10-14', $utc['days']);
        $this->assertArrayHasKey('2026-10-13', $brazil['days']);
    }

    public function test_daylight_saving_transition_is_not_a_fixed_24h_offset(): void {
        $zone = new \DateTimeZone('America/New_York');
        $before = (new \DateTimeImmutable('2026-11-01 00:30', $zone))->getTimestamp();
        $after = (new \DateTimeImmutable('2026-11-01 23:30', $zone))->getTimestamp();
        $this->assertSame(24 * HOURSECS, $after - $before);
        $result = policy_evaluator::evaluate([
            ['timestamp' => $before, 'weight' => 1],
            ['timestamp' => $after, 'weight' => 1],
        ], $zone, 1, 8, 4.0);
        $this->assertSame(2, $result['days']['2026-11-01']['count']);
    }
}
