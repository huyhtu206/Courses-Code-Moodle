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
$context = context_course::instance($course->id);

// Check capability
if (!has_capability('local/courses:manage', $context) && !has_capability('moodle/course:update', $context)) {
    require_capability('local/courses:manage', $context);
}

$PAGE->set_url(new moodle_url('/local/courses/manage.php', ['id' => $course->id]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_courses', 'local_courses') . ' - ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

$courserec = local_courses_get_record($course->id);

$notification = '';
$notificationtype = 'info';

// Handle POST actions
if ($action = optional_param('action', '', PARAM_ALPHA)) {
    require_sesskey();

    if ($action === 'regenerate') {
        $courserec->studentcode = local_courses_generate_code(6);
        while ($DB->record_exists('local_courses_codes', ['studentcode' => $courserec->studentcode])) {
            $courserec->studentcode = local_courses_generate_code(6);
        }
        $courserec->timemodified = time();
        $DB->update_record('local_courses_codes', $courserec);
        $notification = get_string('codes_regenerated', 'local_courses');
        $notificationtype = 'success';
    } else if ($action === 'savesettings') {
        $requireapproval = optional_param('requireapproval', 0, PARAM_INT);
        $courserec->requireapproval = $requireapproval ? 1 : 0;
        $courserec->timemodified = time();
        $DB->update_record('local_courses_codes', $courserec);

        $notification = get_string('settings_saved', 'local_courses');
        $notificationtype = 'success';
    } else if ($action === 'approve') {
        $requestid = required_param('requestid', PARAM_INT);
        $req = $DB->get_record('local_courses_requests', ['id' => $requestid, 'courseid' => $course->id], '*', MUST_EXIST);
        $student = $DB->get_record('user', ['id' => $req->userid], '*', MUST_EXIST);

        $enrolled = local_courses_enrol_user($course->id, $req->userid, $req->roleid);
        if ($enrolled) {
            $req->status = 'approved';
            $req->actionby = $USER->id;
            $req->timemodified = time();
            $DB->update_record('local_courses_requests', $req);

            local_courses_notify_student_result($course, $student, true);

            $notification = get_string('approved_success', 'local_courses');
            $notificationtype = 'success';
        } else {
            $notification = get_string('enrol_failed_student', 'local_courses');
            $notificationtype = 'danger';
        }
    } else if ($action === 'approveall') {
        $pending = $DB->get_records('local_courses_requests', ['courseid' => $course->id, 'status' => 'pending']);
        $count = 0;
        foreach ($pending as $req) {
            $student = $DB->get_record('user', ['id' => $req->userid]);
            if ($student && local_courses_enrol_user($course->id, $req->userid, $req->roleid)) {
                $req->status = 'approved';
                $req->actionby = $USER->id;
                $req->timemodified = time();
                $DB->update_record('local_courses_requests', $req);
                local_courses_notify_student_result($course, $student, true);
                $count++;
            }
        }
        $notification = get_string('approved_success', 'local_courses') . " ({$count})";
        $notificationtype = 'success';
    } else if ($action === 'reject') {
        $requestid = required_param('requestid', PARAM_INT);
        $req = $DB->get_record('local_courses_requests', ['id' => $requestid, 'courseid' => $course->id], '*', MUST_EXIST);
        $student = $DB->get_record('user', ['id' => $req->userid]);

        $req->status = 'rejected';
        $req->actionby = $USER->id;
        $req->timemodified = time();
        $DB->update_record('local_courses_requests', $req);

        if ($student) {
            local_courses_notify_student_result($course, $student, false);
        }

        $notification = get_string('rejected_success', 'local_courses');
        $notificationtype = 'secondary';
    }
}

// Fetch pending requests
$sqlpending = "SELECT r.id AS requestid, r.courseid, r.userid, r.timecreated, r.codeentered,
                      u.id AS user_id, u.firstname, u.lastname, u.email, u.idnumber, u.username
                 FROM {local_courses_requests} r
                 JOIN {user} u ON u.id = r.userid
                WHERE r.courseid = :courseid AND r.status = 'pending'
             ORDER BY r.timecreated ASC";
$pendingrequests = $DB->get_records_sql($sqlpending, ['courseid' => $course->id]);

// Fetch history
$sqlhistory = "SELECT r.id AS requestid, r.status, r.timemodified,
                      u.firstname, u.lastname, u.email, u.idnumber, u.username
                 FROM {local_courses_requests} r
                 JOIN {user} u ON u.id = r.userid
                WHERE r.courseid = :courseid AND r.status != 'pending'
             ORDER BY r.timemodified DESC LIMIT 15";
$historyrequests = $DB->get_records_sql($sqlhistory, ['courseid' => $course->id]);

echo $OUTPUT->header();

// Helper to render initial avatar safely
function render_courses_avatar($firstname, $lastname) {
    $initials = '';
    if (!empty($lastname)) {
        $initials .= mb_substr($lastname, 0, 1, 'UTF-8');
    }
    if (!empty($firstname)) {
        $initials .= mb_substr($firstname, 0, 1, 'UTF-8');
    }
    $initials = strtoupper($initials);
    if (empty($initials)) {
        $initials = 'HV';
    }
    return '<div style="width: 38px; height: 38px; border-radius: 50%; background: #e8f0fe; color: #1a73e8; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.88rem; letter-spacing: 0.5px;">' . s($initials) . '</div>';
}
?>

<style>
.courses-container {
    max-width: 1200px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
.courses-card {
    background: #ffffff;
    border: 1px solid #dadce0;
    border-radius: 8px;
    box-shadow: 0 1px 2px 0 rgba(60,64,67,0.1);
}
.courses-code-box {
    padding: 20px 24px;
}
.courses-code-label {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #5f6368;
    margin-bottom: 4px;
}
.courses-code-val {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 2.1rem;
    font-weight: 700;
    letter-spacing: 2px;
}
.btn-google-outline {
    background-color: transparent;
    color: #5f6368 !important;
    border: 1px solid #dadce0;
    font-weight: 500;
    border-radius: 4px;
    padding: 6px 14px;
    font-size: 0.85rem;
    transition: all 0.2s;
}
.btn-google-outline:hover {
    background-color: #f1f3f4;
    color: #202124 !important;
}

/* Equal action buttons */
.btn-action-approve {
    background-color: #1a73e8;
    color: #ffffff !important;
    border: 1px solid #1a73e8;
    font-weight: 500;
    border-radius: 4px;
    height: 32px;
    width: 76px;
    padding: 0;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s, border-color 0.2s;
    box-sizing: border-box;
}
.btn-action-approve:hover {
    background-color: #1557b0;
    border-color: #1557b0;
}
.btn-action-reject {
    background-color: #ffffff;
    color: #d93025 !important;
    border: 1px solid #dadce0;
    font-weight: 500;
    border-radius: 4px;
    height: 32px;
    width: 76px;
    padding: 0;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s, border-color 0.2s;
    box-sizing: border-box;
}
.btn-action-reject:hover {
    background-color: #fce8e6;
    border-color: #d93025;
}

.courses-table th {
    font-size: 0.78rem;
    font-weight: 600;
    color: #5f6368;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #dadce0;
    background: #f8f9fa;
    padding: 12px 16px;
}
.courses-table td {
    padding: 14px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}
.courses-table tr:last-child td {
    border-bottom: none;
}
</style>

<div class="courses-container py-2">
    
    <?php if (!empty($notification)): ?>
        <div class="alert alert-<?php echo $notificationtype; ?> alert-dismissible fade show rounded-2 py-2 px-3 mb-3" role="alert">
            <?php echo $notification; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center">
                <?php echo get_string('manage_courses', 'local_courses'); ?>
            </h4>
            <div class="text-muted small mt-1">
                <?php echo get_string('course', 'local_courses'); ?>: <strong><?php echo format_string($course->fullname); ?></strong>
            </div>
        </div>
        <div>
            <a href="<?php echo new moodle_url('/course/view.php', ['id' => $course->id]); ?>" class="btn-google-outline d-inline-flex align-items-center text-decoration-none">
                <i class="fa fa-arrow-left me-2"></i> <?php echo get_string('back_to_course', 'local_courses'); ?>
            </a>
        </div>
    </div>

    <!-- BOX 1: THANH CÔNG CỤ MÃ KHÓA HỌC (Chuẩn Google Classroom: Chỉ 1 mã khóa học duy nhất) -->
    <div class="courses-card mb-4">
        <div class="row g-0 align-items-center">
            
            <!-- Mã khóa học duy nhất -->
            <div class="col-md-7 col-12 border-end border-light-subtle courses-code-box">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="courses-code-label"><?php echo get_string('course_code', 'local_courses'); ?></span>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-primary small" onclick="copyToClipboard('<?php echo s($courserec->studentcode); ?>', this)">
                        <i class="fa fa-clone me-1"></i> <?php echo get_string('copy_code', 'local_courses'); ?>
                    </button>
                </div>
                <div class="courses-code-val text-primary my-1">
                    <?php echo s($courserec->studentcode); ?>
                </div>
                <div class="small text-muted" style="font-size: 0.82rem;">
                    <?php echo get_string('course_code_desc', 'local_courses'); ?>
                </div>
            </div>

            <!-- Cài đặt duyệt & Tạo lại mã -->
            <div class="col-md-5 col-12 courses-code-box">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="courses-code-label m-0" for="requireapproval"><?php echo get_string('require_approval', 'local_courses'); ?></label>
                    <form method="post" action="<?php echo $PAGE->url; ?>" class="m-0" id="formToggleApproval">
                        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                        <input type="hidden" name="action" value="savesettings">
                        <div class="form-check form-switch m-0 p-0">
                            <input class="form-check-input ms-0" type="checkbox" id="requireapproval" name="requireapproval" value="1" onchange="this.form.submit()" <?php echo $courserec->requireapproval ? 'checked' : ''; ?>>
                        </div>
                    </form>
                </div>
                <div class="small text-muted mb-2" style="font-size: 0.82rem;">
                    <?php if ($courserec->requireapproval): ?>
                        <span class="text-success fw-bold"><?php echo get_string('enabled_status', 'local_courses'); ?>:</span> <?php echo get_string('require_approval_on', 'local_courses'); ?>
                    <?php else: ?>
                        <span class="text-secondary fw-bold"><?php echo get_string('disabled_status', 'local_courses'); ?>:</span> <?php echo get_string('require_approval_off', 'local_courses'); ?>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?php echo $PAGE->url; ?>" class="m-0">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                    <input type="hidden" name="action" value="regenerate">
                    <button type="submit" class="btn btn-sm btn-link text-secondary text-decoration-none p-0 small" onclick="return confirm('<?php echo get_string('confirm_regenerate', 'local_courses'); ?>');">
                        <i class="fa fa-refresh me-1"></i> <?php echo get_string('regenerate_codes', 'local_courses'); ?>
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- BOX 2: DANH SÁCH YÊU CẦU THAM GIA KHÓA HỌC -->
    <div class="courses-card mb-4">
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
            <div class="d-flex align-items-center">
                <h5 class="fw-bold mb-0 text-dark me-2"><?php echo get_string('pending_requests', 'local_courses'); ?></h5>
                <?php if (count($pendingrequests) > 0): ?>
                    <span class="badge rounded-pill bg-danger" style="font-size: 0.85rem; font-weight: 500;">
                        <?php echo count($pendingrequests); ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if (count($pendingrequests) > 1): ?>
                <form method="post" action="<?php echo $PAGE->url; ?>" class="m-0">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                    <input type="hidden" name="action" value="approveall">
                    <button type="submit" class="btn-action-approve" style="width: auto; padding: 0 16px;" onclick="return confirm('<?php echo get_string('confirm_approve_all', 'local_courses'); ?>');">
                        <i class="fa fa-check-double me-1"></i> <?php echo get_string('approve_all', 'local_courses'); ?> (<?php echo count($pendingrequests); ?>)
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div>
            <?php if (empty($pendingrequests)): ?>
                <div class="text-center py-5 text-muted">
                    <div style="font-size: 2.5rem; color: #dadce0;" class="mb-2">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <div class="fw-medium text-dark"><?php echo get_string('no_pending_requests', 'local_courses'); ?></div>
                    <div class="small mt-1 text-secondary"><?php echo get_string('no_pending_requests_desc', 'local_courses'); ?></div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table courses-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 28%;"><?php echo get_string('student', 'local_courses'); ?></th>
                                <th style="width: 20%;"><?php echo get_string('student_id', 'local_courses'); ?></th>
                                <th style="width: 24%;"><?php echo get_string('email', 'local_courses'); ?></th>
                                <th style="width: 14%;"><?php echo get_string('request_time', 'local_courses'); ?></th>
                                <th class="text-end" style="width: 14%;"><?php echo get_string('action', 'local_courses'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingrequests as $req): 
                                $fullname = s($req->lastname . ' ' . $req->firstname);
                                if (empty(trim($fullname))) {
                                    $fullname = s($req->username);
                                }
                                $mssv = !empty($req->idnumber) ? s($req->idnumber) : s($req->username);
                            ?>
                                <tr>
                                    <!-- Họ và tên -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php echo render_courses_avatar($req->firstname, $req->lastname); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?php echo $fullname; ?></div>
                                                <div class="text-muted" style="font-size: 0.8rem;">@<?php echo s($req->username); ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Mã số sinh viên -->
                                    <td>
                                        <span class="font-monospace fw-bold text-primary" style="font-size: 0.95rem;">
                                            <?php echo $mssv; ?>
                                        </span>
                                    </td>

                                    <!-- Email -->
                                    <td>
                                        <span class="text-dark" style="font-size: 0.9rem;">
                                            <?php echo s($req->email); ?>
                                        </span>
                                    </td>

                                    <!-- Thời gian gửi -->
                                    <td>
                                        <span class="text-muted small" style="font-size: 0.82rem;">
                                            <?php echo userdate($req->timecreated, '%d/%m/%Y %H:%M'); ?>
                                        </span>
                                    </td>

                                    <!-- Thao tác: Hai nút đều nhau tuyệt đối -->
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                            <!-- Duyệt -->
                                            <form method="post" action="<?php echo $PAGE->url; ?>" class="m-0 p-0 d-inline">
                                                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="requestid" value="<?php echo $req->requestid; ?>">
                                                <button type="submit" class="btn-action-approve">
                                                    <?php echo get_string('approve', 'local_courses'); ?>
                                                </button>
                                            </form>

                                            <!-- Từ chối -->
                                            <form method="post" action="<?php echo $PAGE->url; ?>" class="m-0 p-0 d-inline">
                                                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="requestid" value="<?php echo $req->requestid; ?>">
                                                <button type="submit" class="btn-action-reject" onclick="return confirm('<?php echo get_string('confirm_reject', 'local_courses'); ?>');">
                                                    <?php echo get_string('reject', 'local_courses'); ?>
                                                </button>
                                            </form>
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

    <!-- BOX 3: LỊCH SỬ DUYỆT GẦN ĐÂY -->
    <?php if (!empty($historyrequests)): ?>
        <div class="courses-card">
            <div class="px-4 py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-muted" style="font-size: 0.88rem;">
                    <?php echo get_string('history_requests', 'local_courses'); ?>
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table courses-table mb-0" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th><?php echo get_string('student', 'local_courses'); ?></th>
                            <th><?php echo get_string('student_id', 'local_courses'); ?></th>
                            <th><?php echo get_string('email', 'local_courses'); ?></th>
                            <th><?php echo get_string('status', 'local_courses'); ?></th>
                            <th class="text-end"><?php echo get_string('request_time', 'local_courses'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyrequests as $hist): 
                            $histname = s($hist->lastname . ' ' . $hist->firstname);
                            if (empty(trim($histname))) {
                                $histname = s($hist->username);
                            }
                            $histmssv = !empty($hist->idnumber) ? s($hist->idnumber) : s($hist->username);
                        ?>
                            <tr>
                                <td class="fw-medium text-dark"><?php echo $histname; ?></td>
                                <td class="font-monospace text-muted"><?php echo $histmssv; ?></td>
                                <td class="text-muted"><?php echo s($hist->email); ?></td>
                                <td>
                                    <?php if ($hist->status === 'approved'): ?>
                                        <span class="text-success fw-bold"><i class="fa fa-check me-1"></i> <?php echo get_string('status_approved', 'local_courses'); ?></span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold"><i class="fa fa-times me-1"></i> <?php echo get_string('status_rejected', 'local_courses'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-muted">
                                    <?php echo userdate($hist->timemodified, '%d/%m/%Y %H:%M'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
function copyToClipboard(text, btnElement) {
    navigator.clipboard.writeText(text).then(function() {
        var originalHtml = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fa fa-check text-success me-1"></i> ' + <?php echo json_encode(get_string('copied', 'local_courses')); ?>;
        setTimeout(function() {
            btnElement.innerHTML = originalHtml;
        }, 2000);
    });
}
</script>

<?php

echo $OUTPUT->footer();
