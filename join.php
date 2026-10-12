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
 * Join course by code page.
 *
 * @package    local_courses
 * @copyright  2026 Huynh Tu
 * @author     Huynh Tu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$PAGE->set_url(new moodle_url('/local/courses/join.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('joincourse_title', 'local_courses'));
$PAGE->set_heading(get_string('joincourse_title', 'local_courses'));

$notification = '';
$notificationtype = 'info';

// Handle form submission
if ($data = data_submitted()) {
    require_sesskey();

    $code = optional_param('coursecode', '', PARAM_ALPHANUMEXT);
    if (empty($code)) {
        $code = optional_param('classcode', '', PARAM_ALPHANUMEXT);
    }
    $code = trim(core_text::strtoupper($code));

    if (empty($code)) {
        $notification = get_string('invalid_code', 'local_courses');
        $notificationtype = 'danger';
    } else {
        // Query matching course code
        $sql = "SELECT c.*, lc.studentcode, lc.teachercode, lc.requireapproval, lc.enabled, lc.id as codeid
                  FROM {local_courses_codes} lc
                  JOIN {course} c ON c.id = lc.courseid
                 WHERE (UPPER(lc.studentcode) = :code1 OR UPPER(lc.teachercode) = :code2)
                   AND lc.enabled = 1";
        $courseinfo = $DB->get_record_sql($sql, ['code1' => $code, 'code2' => $code]);

        if (!$courseinfo) {
            $notification = get_string('invalid_code', 'local_courses');
            $notificationtype = 'danger';
        } else {
            $coursecontext = context_course::instance($courseinfo->id);

            // Check if user is already enrolled
            if (is_enrolled($coursecontext, $USER->id)) {
                redirect(new moodle_url('/course/view.php', ['id' => $courseinfo->id]),
                    get_string('already_enrolled', 'local_courses'), null, \core\output\notification::NOTIFY_INFO);
            }

            // Check if teacher code was entered
            if (strtoupper($courseinfo->teachercode) === $code) {
                // Enrol immediately as Teacher (editingteacher: 3)
                $enrolled = local_courses_enrol_user($courseinfo->id, $USER->id, 3);
                if ($enrolled) {
                    redirect(new moodle_url('/course/view.php', ['id' => $courseinfo->id]),
                        get_string('enrolled_success_teacher', 'local_courses'), null, \core\output\notification::NOTIFY_SUCCESS);
                } else {
                    $notification = get_string('enrol_failed_teacher', 'local_courses');
                    $notificationtype = 'danger';
                }
            } else {
                // Student code entered
                if ($courseinfo->requireapproval == 0) {
                    // Instant enrolment (no approval required)
                    $enrolled = local_courses_enrol_user($courseinfo->id, $USER->id, 5);
                    if ($enrolled) {
                        redirect(new moodle_url('/course/view.php', ['id' => $courseinfo->id]),
                            get_string('enrolled_success_student', 'local_courses'), null, \core\output\notification::NOTIFY_SUCCESS);
                    } else {
                        $notification = get_string('enrol_failed_student', 'local_courses');
                        $notificationtype = 'danger';
                    }
                } else {
                    // Approval is required
                    $existing = $DB->get_record('local_courses_requests', [
                        'courseid' => $courseinfo->id,
                        'userid' => $USER->id,
                        'status' => 'pending'
                    ]);

                    if ($existing) {
                        $notification = get_string('request_pending', 'local_courses');
                        $notificationtype = 'warning';
                    } else {
                        $request = new stdClass();
                        $request->courseid = $courseinfo->id;
                        $request->userid = $USER->id;
                        $request->roleid = 5; // Student
                        $request->status = 'pending';
                        $request->codeentered = $code;
                        $request->timecreated = time();
                        $request->timemodified = time();

                        $DB->insert_record('local_courses_requests', $request);

                        // Send notification to course teachers (Moodle bell notification)
                        local_courses_notify_teachers($courseinfo, $USER);

                        $notification = get_string('request_submitted', 'local_courses');
                        $notificationtype = 'success';
                    }
                }
            }
        }
    }
}

echo $OUTPUT->header();
?>
<div class="container py-4" style="max-width: 580px;">
    <div class="card shadow border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex align-items-center">
            <i class="fa fa-graduation-cap fa-lg me-3"></i>
            <h4 class="mb-0 text-white font-weight-bold" style="font-size: 1.25rem;">
                <?php echo get_string('joincourse_title', 'local_courses'); ?>
            </h4>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($notification)): ?>
                <div class="alert alert-<?php echo $notificationtype; ?> d-flex align-items-center mb-4" role="alert">
                    <i class="fa <?php echo ($notificationtype === 'success' ? 'fa-check-circle' : ($notificationtype === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle')); ?> fa-lg me-2"></i>
                    <div><?php echo $notification; ?></div>
                </div>
            <?php endif; ?>

            <p class="text-muted mb-4">
                <?php echo get_string('joincourse_desc', 'local_courses'); ?>
            </p>

            <form method="post" action="<?php echo $PAGE->url; ?>" class="needs-validation">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                <div class="mb-4">
                    <label for="coursecode" class="form-label fw-bold text-dark">
                        <?php echo get_string('enter_code', 'local_courses'); ?>
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-key text-muted"></i>
                        </span>
                        <input type="text"
                               class="form-control border-start-0 text-uppercase font-monospace fw-bold"
                               id="coursecode"
                               name="coursecode"
                               placeholder="<?php echo get_string('code_placeholder', 'local_courses'); ?>"
                               maxlength="12"
                               required
                               autocomplete="off"
                               autofocus
                               style="letter-spacing: 2px;">
                    </div>
                    <div class="form-text text-muted mt-2">
                        <i class="fa fa-info-circle me-1"></i>
                        <?php echo get_string('ask_teacher_hint', 'local_courses'); ?>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">
                        <i class="fa fa-sign-in-alt me-2"></i>
                        <?php echo get_string('btn_join', 'local_courses'); ?>
                    </button>
                    <a href="<?php echo new moodle_url('/my/courses.php'); ?>" class="btn btn-outline-secondary">
                        <?php echo get_string('cancel', 'local_courses'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
echo $OUTPUT->footer();
