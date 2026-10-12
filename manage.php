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
 * Manage course codes and student approval requests.
 *
 * @package    local_courses
 * @copyright  2026 Huynh Tu
 * @author     Huynh Tu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($courseid);

// Ensure capability
if (!has_capability('local/courses:manage', $context) && !has_capability('moodle/course:update', $context)) {
    require_capability('local/courses:manage', $context);
}

$PAGE->set_url(new moodle_url('/local/courses/manage.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($course->fullname) . ': ' . get_string('manage_courses', 'local_courses'));
$PAGE->set_heading(format_string($course->fullname));

$courserec = local_courses_get_record($courseid);

// Actions
$action = optional_param('action', '', PARAM_ALPHA);
if ($action && confirm_sesskey()) {
    $now = time();

    if ($action === 'regenerate') {
        $courserec->studentcode = local_courses_generate_code(6);
        $courserec->teachercode = 'T-' . local_courses_generate_code(6);
        while ($DB->record_exists('local_courses_codes', ['studentcode' => $courserec->studentcode])) {
            $courserec->studentcode = local_courses_generate_code(6);
        }
        while ($DB->record_exists('local_courses_codes', ['teachercode' => $courserec->teachercode])) {
            $courserec->teachercode = 'T-' . local_courses_generate_code(6);
        }
        $courserec->timemodified = $now;
        $DB->update_record('local_courses_codes', $courserec);
        redirect($PAGE->url, get_string('copied', 'local_courses'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'toggle_approval') {
        $courserec->requireapproval = $courserec->requireapproval ? 0 : 1;
        $courserec->timemodified = $now;
        $DB->update_record('local_courses_codes', $courserec);
        redirect($PAGE->url);
    }

    if ($action === 'toggle_enabled') {
        $courserec->enabled = $courserec->enabled ? 0 : 1;
        $courserec->timemodified = $now;
        $DB->update_record('local_courses_codes', $courserec);
        redirect($PAGE->url);
    }

    if ($action === 'approve') {
        $reqid = required_param('reqid', PARAM_INT);
        $req = $DB->get_record('local_courses_requests', ['id' => $reqid, 'courseid' => $courseid]);
        if ($req && $req->status === 'pending') {
            local_courses_enrol_user($courseid, $req->userid, $req->roleid);
            $req->status = 'approved';
            $req->actionby = $USER->id;
            $req->timemodified = $now;
            $DB->update_record('local_courses_requests', $req);

            $student = $DB->get_record('user', ['id' => $req->userid]);
            if ($student) {
                local_courses_notify_student_result($course, $student, true);
            }
        }
        redirect($PAGE->url, get_string('status_approved', 'local_courses'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'reject') {
        $reqid = required_param('reqid', PARAM_INT);
        $req = $DB->get_record('local_courses_requests', ['id' => $reqid, 'courseid' => $courseid]);
        if ($req && $req->status === 'pending') {
            $req->status = 'rejected';
            $req->actionby = $USER->id;
            $req->timemodified = $now;
            $DB->update_record('local_courses_requests', $req);

            $student = $DB->get_record('user', ['id' => $req->userid]);
            if ($student) {
                local_courses_notify_student_result($course, $student, false);
            }
        }
        redirect($PAGE->url, get_string('status_rejected', 'local_courses'), null, \core\output\notification::NOTIFY_INFO);
    }

    if ($action === 'approveall') {
        $pending = $DB->get_records('local_courses_requests', ['courseid' => $courseid, 'status' => 'pending']);
        foreach ($pending as $req) {
            local_courses_enrol_user($courseid, $req->userid, $req->roleid);
            $req->status = 'approved';
            $req->actionby = $USER->id;
            $req->timemodified = $now;
            $DB->update_record('local_courses_requests', $req);

            $student = $DB->get_record('user', ['id' => $req->userid]);
            if ($student) {
                local_courses_notify_student_result($course, $student, true);
            }
        }
        redirect($PAGE->url, get_string('status_approved', 'local_courses'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Fetch pending and recent requests
$pendingrequests = $DB->get_records_sql("
    SELECT r.*, u.firstname, u.lastname, u.email, u.idnumber, u.username, u.picture, u.imagealt
      FROM {local_courses_requests} r
      JOIN {user} u ON u.id = r.userid
     WHERE r.courseid = :courseid AND r.status = 'pending'
  ORDER BY r.timecreated ASC", ['courseid' => $courseid]);

$historyrequests = $DB->get_records_sql("
    SELECT r.*, u.firstname, u.lastname, u.email, u.idnumber, u.username, u.picture, u.imagealt
      FROM {local_courses_requests} r
      JOIN {user} u ON u.id = r.userid
     WHERE r.courseid = :courseid AND r.status != 'pending'
  ORDER BY r.timemodified DESC", ['courseid' => $courseid], 0, 15);

echo $OUTPUT->header();
?>
<div class="local-courses-container pb-5" style="font-family: inherit;">
    <!-- Top Action Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <a href="<?php echo new moodle_url('/course/view.php', ['id' => $courseid]); ?>" class="btn btn-outline-secondary d-inline-flex align-items-center">
            <i class="fa fa-arrow-left me-2"></i> <?php echo get_string('back_to_course', 'local_courses'); ?>
        </a>
        <div class="d-flex gap-2">
            <a href="<?php echo new moodle_url($PAGE->url, ['action' => 'toggle_enabled', 'sesskey' => sesskey()]); ?>" 
               class="btn <?php echo $courserec->enabled ? 'btn-outline-danger' : 'btn-outline-success'; ?> d-inline-flex align-items-center">
                <i class="fa <?php echo $courserec->enabled ? 'fa-pause' : 'fa-play'; ?> me-2"></i>
                <?php echo $courserec->enabled ? get_string('toggle_disable_code', 'local_courses') : get_string('toggle_enable_code', 'local_courses'); ?>
            </a>
            <a href="<?php echo new moodle_url($PAGE->url, ['action' => 'regenerate', 'sesskey' => sesskey()]); ?>" 
               class="btn btn-outline-primary d-inline-flex align-items-center"
               onclick="return confirm('<?php echo get_string('confirm_regenerate', 'local_courses'); ?>');">
                <i class="fa fa-sync-alt me-2"></i> <?php echo get_string('regenerate_codes', 'local_courses'); ?>
            </a>
        </div>
    </div>

    <!-- Course Code & Settings Card -->
    <div class="row g-4 mb-4">
        <!-- Class Code Display -->
        <div class="col-lg-6">
            <div class="card h-100 border shadow-sm" style="border-radius: 12px; border-color: #e0e0e0;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted fw-bold small text-uppercase letter-spacing-1">
                                <?php echo get_string('course_code', 'local_courses'); ?>
                            </span>
                            <span class="badge <?php echo $courserec->enabled ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $courserec->enabled ? get_string('enabled_status', 'local_courses') : get_string('disabled_status', 'local_courses'); ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3 my-3" style="background: #f8fafd; border: 1px solid #d2e3fc;">
                            <div class="display-6 font-monospace fw-bold text-primary mb-0" id="displayStudentCode" style="letter-spacing: 4px;">
                                <?php echo s($courserec->studentcode); ?>
                            </div>
                            <button type="button" class="btn btn-primary d-inline-flex align-items-center px-3" onclick="copyCode('<?php echo s($courserec->studentcode); ?>', this)">
                                <i class="fa fa-copy me-2"></i> <?php echo get_string('copy_code', 'local_courses'); ?>
                            </button>
                        </div>
                        <p class="text-muted small mb-0">
                            <i class="fa fa-info-circle me-1 text-primary"></i>
                            <?php echo get_string('course_code_desc', 'local_courses'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Requirement Setting -->
        <div class="col-lg-6">
            <div class="card h-100 border shadow-sm" style="border-radius: 12px; border-color: #e0e0e0;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted fw-bold small text-uppercase letter-spacing-1">
                                <?php echo get_string('require_approval', 'local_courses'); ?>
                            </span>
                            <span class="badge <?php echo $courserec->requireapproval ? 'bg-warning text-dark' : 'bg-info text-dark'; ?>">
                                <?php echo $courserec->requireapproval ? get_string('need_approval_badge', 'local_courses') : get_string('auto_enrol_badge', 'local_courses'); ?>
                            </span>
                        </div>
                        <div class="my-3 p-3 rounded-3" style="background: #fdfaf2; border: 1px solid #feeed0;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold mb-1" style="font-size: 1.05rem;">
                                        <?php echo $courserec->requireapproval ? get_string('approval_enabled_heading', 'local_courses') : get_string('approval_disabled_heading', 'local_courses'); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo $courserec->requireapproval ? get_string('require_approval_on', 'local_courses') : get_string('require_approval_off', 'local_courses'); ?>
                                    </div>
                                </div>
                                <a href="<?php echo new moodle_url($PAGE->url, ['action' => 'toggle_approval', 'sesskey' => sesskey()]); ?>" 
                                   class="btn <?php echo $courserec->requireapproval ? 'btn-outline-secondary' : 'btn-warning'; ?> btn-sm px-3 fw-bold">
                                    <?php echo $courserec->requireapproval ? get_string('btn_disable_approval', 'local_courses') : get_string('btn_enable_approval', 'local_courses'); ?>
                                </a>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            <i class="fa fa-shield-alt me-1 text-warning"></i>
                            <?php echo get_string('approval_help_tip', 'local_courses'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Requests Table -->
    <div class="card border shadow-sm mb-4" style="border-radius: 12px; border-color: #e0e0e0; overflow: hidden;">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <div class="d-flex align-items-center">
                <h5 class="mb-0 fw-bold me-2">
                    <?php echo get_string('pending_requests', 'local_courses'); ?>
                </h5>
                <span class="badge rounded-pill bg-danger" style="font-size: 0.85rem;">
                    <?php echo count($pendingrequests); ?>
                </span>
            </div>
            <?php if (!empty($pendingrequests)): ?>
                <a href="<?php echo new moodle_url($PAGE->url, ['action' => 'approveall', 'sesskey' => sesskey()]); ?>" 
                   class="btn btn-success btn-sm d-inline-flex align-items-center px-3"
                   onclick="return confirm('<?php echo get_string('confirm_approve_all', 'local_courses'); ?>');">
                    <i class="fa fa-check-double me-2"></i> <?php echo get_string('approve_all', 'local_courses'); ?>
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($pendingrequests)): ?>
                <div class="text-center py-5">
                    <i class="fa fa-user-check text-muted fa-3x mb-3" style="opacity: 0.4;"></i>
                    <h6 class="fw-bold text-muted"><?php echo get_string('no_pending_requests', 'local_courses'); ?></h6>
                    <p class="text-muted small mb-0"><?php echo get_string('no_pending_requests_desc', 'local_courses'); ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 32%;"><?php echo get_string('student', 'local_courses'); ?></th>
                                <th style="width: 20%;"><?php echo get_string('student_id', 'local_courses'); ?></th>
                                <th style="width: 24%;"><?php echo get_string('email', 'local_courses'); ?></th>
                                <th style="width: 14%;"><?php echo get_string('request_time', 'local_courses'); ?></th>
                                <th class="text-end pe-4" style="width: 10%;"><?php echo get_string('action', 'local_courses'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingrequests as $req): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php
                                                $userobj = $DB->get_record('user', ['id' => $req->userid]);
                                                echo $OUTPUT->user_picture($userobj, ['size' => 42, 'class' => 'rounded-circle shadow-sm']);
                                                ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                                                    <?php echo s($req->lastname . ' ' . $req->firstname); ?>
                                                </div>
                                                <div class="text-muted small">@<?php echo s($req->username); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace px-2 py-1" style="font-size: 0.88rem;">
                                            <?php echo !empty($req->idnumber) ? s($req->idnumber) : (!empty($req->username) ? s($req->username) : 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo s($req->email); ?>" class="text-secondary text-decoration-none">
                                            <i class="fa fa-envelope me-1 text-muted"></i>
                                            <?php echo s($req->email); ?>
                                        </a>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo userdate($req->timecreated, '%H:%M, %d/%m/%Y'); ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex gap-2 justify-content-end">
                                            <a href="<?php echo new moodle_url($PAGE->url, ['action' => 'approve', 'reqid' => $req->id, 'sesskey' => sesskey()]); ?>" 
                                               class="btn btn-sm btn-success d-inline-flex align-items-center px-3" style="min-width: 80px; justify-content: center;"
                                               title="<?php echo get_string('approve', 'local_courses'); ?>">
                                                <i class="fa fa-check me-1"></i> <?php echo get_string('approve', 'local_courses'); ?>
                                            </a>
                                            <a href="<?php echo new moodle_url($PAGE->url, ['action' => 'reject', 'reqid' => $req->id, 'sesskey' => sesskey()]); ?>" 
                                               class="btn btn-sm btn-outline-danger d-inline-flex align-items-center px-3" style="min-width: 80px; justify-content: center;"
                                               onclick="return confirm('<?php echo get_string('confirm_reject', 'local_courses'); ?>');"
                                               title="<?php echo get_string('reject', 'local_courses'); ?>">
                                                <i class="fa fa-times me-1"></i> <?php echo get_string('reject', 'local_courses'); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- History Requests Table -->
    <?php if (!empty($historyrequests)): ?>
        <div class="card border shadow-sm" style="border-radius: 12px; border-color: #e0e0e0; overflow: hidden;">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h6 class="mb-0 fw-bold text-muted">
                    <i class="fa fa-history me-2"></i> <?php echo get_string('history_requests', 'local_courses'); ?>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-muted small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4"><?php echo get_string('student', 'local_courses'); ?></th>
                                <th><?php echo get_string('student_id', 'local_courses'); ?></th>
                                <th><?php echo get_string('email', 'local_courses'); ?></th>
                                <th><?php echo get_string('status', 'local_courses'); ?></th>
                                <th class="text-end pe-4"><?php echo get_string('processed_time', 'local_courses'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyrequests as $h): ?>
                                <tr>
                                    <td class="ps-4">
                                        <strong><?php echo s($h->lastname . ' ' . $h->firstname); ?></strong>
                                    </td>
                                    <td>
                                        <span class="font-monospace"><?php echo !empty($h->idnumber) ? s($h->idnumber) : s($h->username); ?></span>
                                    </td>
                                    <td><?php echo s($h->email); ?></td>
                                    <td>
                                        <?php if ($h->status === 'approved'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <i class="fa fa-check me-1"></i> <?php echo get_string('status_approved', 'local_courses'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                <i class="fa fa-times me-1"></i> <?php echo get_string('status_rejected', 'local_courses'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php echo userdate($h->timemodified, '%H:%M, %d/%m/%Y'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function copyCode(code, btn) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-check me-2"></i> <?php echo get_string('copied', 'local_courses'); ?>';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
            setTimeout(function() {
                btn.innerHTML = orig;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-primary');
            }, 2000);
        });
    }
}
</script>
<?php
echo $OUTPUT->footer();
