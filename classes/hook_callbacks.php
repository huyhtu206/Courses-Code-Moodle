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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Hook callbacks for local_courses.
 *
 * @package    local_courses
 * @copyright  2026 Huynh Tu
 * @author     Huynh Tu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courses;

defined('MOODLE_INTERNAL') || die();

class hook_callbacks {
    /**
     * Extend course secondary navigation to place "Mã khóa học & Duyệt học viên" directly on the main horizontal menu.
     *
     * @param \core\hook\navigation\secondary_extend $hook
     */
    public static function secondary_extend(\core\hook\navigation\secondary_extend $hook): void {
        global $DB, $PAGE;

        $course = $PAGE->course;
        if (!$course || $course->id <= SITEID) {
            return;
        }

        $context = \context_course::instance($course->id);
        if (!has_capability('moodle/course:update', $context)) {
            return;
        }

        $secondary = $hook->get_secondaryview();

        // Remove old node if exists
        if ($old = $secondary->get('local_courses_manage')) {
            $old->remove();
        }

        $pendingcount = $DB->count_records('local_courses_requests', ['courseid' => $course->id, 'status' => 'pending']);
        $label = get_string('course_code', 'local_courses');
        if ($pendingcount > 0) {
            $nodename = $label . ' <span class="badge rounded-pill bg-danger ms-1" style="font-size: 0.75rem; vertical-align: middle; padding: 0.25em 0.55em;">' . $pendingcount . '</span>';
        } else {
            $nodename = $label;
        }

        $node = \navigation_node::create(
            $nodename,
            new \moodle_url('/local/courses/manage.php', ['id' => $course->id]),
            \navigation_node::TYPE_CUSTOM,
            null,
            'local_courses_manage',
            new \pix_icon('i/settings', '')
        );
        $node->set_force_into_more_menu(false);
        $node->showinflatnavigation = true;

        // Insert right before 'participants' so it appears prominently on the main top menu
        if ($secondary->get('participants')) {
            $secondary->add_node($node, 'participants');
        } else {
            $secondary->add_node($node);
        }
    }
}
