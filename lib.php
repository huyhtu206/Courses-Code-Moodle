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
 * Library functions for local_courses plugin.
 *
 * @package    local_courses
 * @copyright  2026 Huynh Tu
 * @author     Huynh Tu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Generate a random alphanumeric code of specified length.
 * Excludes ambiguous characters (0, O, 1, I, L) to avoid user confusion.
 *
 * @param int $length
 * @return string
 */
function local_courses_generate_code(int $length = 6): string {
    $chars = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    return $code;
}

/**
 * Get or create course enrollment record with student and teacher codes.
 *
 * @param int $courseid
 * @return stdClass
 */
function local_courses_get_record(int $courseid): stdClass {
    global $DB;

    $rec = $DB->get_record('local_courses_codes', ['courseid' => $courseid]);
    if (!$rec) {
        $rec = new stdClass();
        $rec->courseid = $courseid;
        $rec->studentcode = local_courses_generate_code(6);
        $rec->teachercode = 'T-' . local_courses_generate_code(6);
        $rec->requireapproval = 1;
        $rec->enabled = 1;
        $rec->timecreated = time();
        $rec->timemodified = time();

        // Check for collisions
        while ($DB->record_exists('local_courses_codes', ['studentcode' => $rec->studentcode])) {
            $rec->studentcode = local_courses_generate_code(6);
        }
        while ($DB->record_exists('local_courses_codes', ['teachercode' => $rec->teachercode])) {
            $rec->teachercode = 'T-' . local_courses_generate_code(6);
        }

        $rec->id = $DB->insert_record('local_courses_codes', $rec);
    }
    return $rec;
}

/**
 * Enrol a user into a course using manual enrolment plugin.
 *
 * @param int $courseid
 * @param int $userid
 * @param int $roleid 5 = student, 3 = editingteacher
 * @return bool
 */
function local_courses_enrol_user(int $courseid, int $userid, int $roleid = 5): bool {
    global $DB;

    $instances = enrol_get_instances($courseid, true);
    $manualinstance = null;
    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            $manualinstance = $instance;
            break;
        }
    }

    if (!$manualinstance) {
        $enrolplugin = enrol_get_plugin('manual');
        if ($enrolplugin) {
            $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
            $instanceid = $enrolplugin->add_instance($course);
            $manualinstance = $DB->get_record('enrol', ['id' => $instanceid]);
        }
    }

    if ($manualinstance) {
        $plugin = enrol_get_plugin('manual');
        $plugin->enrol_user($manualinstance, $userid, $roleid, time(), 0);
        return true;
    }

    return false;
}

/**
 * Send notification to teachers when a student submits a join request.
 *
 * @param stdClass $course
 * @param stdClass $student
 */
function local_courses_notify_teachers(stdClass $course, stdClass $student): void {
    global $DB;

    $context = context_course::instance($course->id);
    $teachers = get_enrolled_users($context, 'moodle/course:update', 0, 'u.*');

    if (empty($teachers)) {
        return;
    }

    $studentname = fullname($student);
    $mssv = !empty($student->idnumber) ? $student->idnumber : (!empty($student->username) ? $student->username : 'N/A');
    $manageurl = new moodle_url('/local/courses/manage.php', ['id' => $course->id]);

    foreach ($teachers as $teacher) {
        if ($teacher->id == $student->id) {
            continue;
        }

        $eventdata = new \core\message\message();
        $eventdata->component         = 'local_courses';
        $eventdata->name              = 'joinrequest';
        $eventdata->userfrom          = $student;
        $eventdata->userto            = $teacher;
        $eventdata->subject           = "🔔 Yêu cầu tham gia khóa học: {$studentname} - " . format_string($course->fullname);
        $eventdata->fullmessage       = "Học sinh {$studentname} (MSSV: {$mssv}, Email: {$student->email}) vừa gửi yêu cầu tham gia khóa học \"{$course->fullname}\". Vui lòng truy cập liên kết sau để duyệt học sinh: " . $manageurl->out(false);
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <p>Xin chào <strong>" . fullname($teacher) . "</strong>,</p>
                <p>Học sinh <strong>{$studentname}</strong> vừa nhập mã khóa học và gửi yêu cầu tham gia khóa học của bạn:</p>
                <div style='background: #f8f9fa; border-left: 4px solid #0f47ad; padding: 12px 16px; margin: 12px 0;'>
                    <p style='margin: 0;'><strong>Khóa học:</strong> " . format_string($course->fullname) . "</p>
                    <p style='margin: 0;'><strong>Học sinh:</strong> {$studentname}</p>
                    <p style='margin: 0;'><strong>MSSV / Tài khoản:</strong> {$mssv}</p>
                    <p style='margin: 0;'><strong>Email:</strong> {$student->email}</p>
                </div>
                <p><a href='" . $manageurl->out(false) . "' style='display: inline-block; background: #0f47ad; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;'>👉 Bấm vào đây để Duyệt học viên</a></p>
            </div>";
        $eventdata->smallmessage      = "Học sinh {$studentname} (MSSV: {$mssv}) xin vào khóa học " . format_string($course->fullname);
        $eventdata->contexturl        = $manageurl->out(false);
        $eventdata->contexturlname    = "Duyệt yêu cầu tham gia";
        $eventdata->courseid          = $course->id;

        try {
            message_send($eventdata);
        } catch (\Throwable $e) {
            // Notification dispatch should not break flow
        }
    }
}

/**
 * Send notification to student when their request is approved or rejected.
 *
 * @param stdClass $course
 * @param stdClass $student
 * @param bool $approved
 */
function local_courses_notify_student_result(stdClass $course, stdClass $student, bool $approved): void {
    global $USER;

    $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
    $coursename = format_string($course->fullname);

    $eventdata = new \core\message\message();
    $eventdata->component         = 'local_courses';
    $eventdata->name              = 'joinresult';
    $eventdata->userfrom          = $USER;
    $eventdata->userto            = $student;

    if ($approved) {
        $eventdata->subject        = "🎉 Yêu cầu tham gia khóa học \"{$coursename}\" đã được DUYỆT!";
        $eventdata->fullmessage    = "Chúc mừng bạn! Giáo viên đã phê duyệt yêu cầu tham gia khóa học \"{$coursename}\". Bạn có thể truy cập khóa học ngay bây giờ: " . $courseurl->out(false);
        $eventdata->fullmessagehtml= "<p>Chúc mừng bạn! Yêu cầu tham gia khóa học <strong>{$coursename}</strong> đã được giáo viên duyệt. <a href='" . $courseurl->out(false) . "'>Bấm vào đây để vào học</a>.</p>";
        $eventdata->smallmessage   = "Bạn đã được duyệt vào khóa học {$coursename}";
        $eventdata->contexturl     = $courseurl->out(false);
        $eventdata->contexturlname = "Vào khóa học";
    } else {
        $eventdata->subject        = "Thông báo: Yêu cầu tham gia khóa học \"{$coursename}\"";
        $eventdata->fullmessage    = "Yêu cầu tham gia khóa học \"{$coursename}\" của bạn đã bị từ chối. Vui lòng liên hệ giáo viên bộ môn nếu có thắc mắc.";
        $eventdata->fullmessagehtml= "<p>Yêu cầu tham gia khóa học <strong>{$coursename}</strong> của bạn đã bị từ chối.</p>";
        $eventdata->smallmessage   = "Yêu cầu vào khóa học {$coursename} bị từ chối";
    }
    $eventdata->courseid = $course->id;

    try {
        message_send($eventdata);
    } catch (\Throwable $e) {
    }
}

/**
 * Extend global navigation for all users to have quick access to "Tham gia khóa học bằng mã".
 *
 * @param global_navigation $nav
 */
function local_courses_extend_navigation(global_navigation $nav): void {
    if (isloggedin() && !isguestuser()) {
        $url = new moodle_url('/local/courses/join.php');
        $node = $nav->add(
            get_string('joincourse', 'local_courses'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'local_courses_join',
            new pix_icon('i/cohort', '')
        );
        $node->showinflatnavigation = true;
    }
}

/**
 * Hook before footer:
 * 1. Shows "Tham gia khóa học" button and modal on "My courses" page.
 * 2. Injects prominent Teacher Alert Banner on course view page if there are pending students.
 *
 * @return string HTML output
 */
function local_courses_before_footer(): string {
    global $PAGE, $USER, $DB, $COURSE;

    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $joinurl = new moodle_url('/local/courses/join.php');
    $sesskey = sesskey();
    $currentpath = $PAGE->url ? $PAGE->url->get_path() : '';
    $pagelayout = $PAGE->pagelayout ?? '';
    $pagetype = $PAGE->pagetype ?? '';

    // 1. "Tham gia khóa học" Button on "My courses" / Dashboard & Popup modal
    $headerquickjoinscript = '';
    $ismycourses = (strpos($currentpath, '/my/courses.php') !== false ||
                    strpos($currentpath, '/my/') !== false ||
                    strpos($currentpath, '/my/index.php') !== false ||
                    $pagelayout === 'mycourses' ||
                    $pagetype === 'my-index');

    // Do NOT show if already inside a specific course
    $incourse = (!empty($COURSE) && $COURSE->id > SITEID && strpos($currentpath, '/course/view.php') !== false);

    if ($ismycourses && !$incourse) {
        $btnjoinlabel = get_string('joincourse', 'local_courses');

        // Header button HTML (uses Moove theme btn-primary styling and transitions)
        $buttonhtml = '<button type="button" class="btn btn-primary d-inline-flex align-items-center shadow-sm" id="btn-open-courses-modal">'
                    . '<i class="fa fa-plus me-2"></i> ' . s($btnjoinlabel)
                    . '</button>';

        // Modal dialog HTML (harmonized with Moove theme, no account card, clean input)
        $modalhtml = '<div class="modal fade" id="coursesJoinModal" tabindex="-1" aria-labelledby="coursesJoinModalLabel" aria-hidden="true" style="z-index: 1060;">'
                   . '<div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">'
                   . '<div class="modal-content border-0 shadow" style="border-radius: 10px; overflow: hidden;">'
                   . '<div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">'
                   . '<h5 class="modal-title fw-bold text-dark mb-0" id="coursesJoinModalLabel">' . s(get_string('joincourse_title', 'local_courses')) . '</h5>'
                   . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
                   . '</div>'
                   . '<form method="post" action="' . $joinurl->out(false) . '" id="courses-modal-join-form" class="m-0">'
                   . '<input type="hidden" name="sesskey" value="' . $sesskey . '">'
                   . '<div class="modal-body px-4 pt-4 pb-2">'
                   // Course code container styled with theme-harmonized card
                   . '<div class="card bg-light border-0 mb-3 shadow-none" style="border-radius: 8px;">'
                   . '<div class="card-body p-3">'
                   . '<label for="modalCourseCodeInput" class="form-label fw-bold text-dark mb-1" style="font-size: 0.95rem;">' . s(get_string('course_code', 'local_courses')) . '</label>'
                   . '<div class="text-muted small mb-3">' . s(get_string('ask_teacher_hint', 'local_courses')) . '</div>'
                   . '<div style="max-width: 280px;">'
                   . '<input type="text" class="form-control form-control-lg text-uppercase font-monospace fw-bold bg-white" id="modalCourseCodeInput" name="coursecode" required maxlength="12" placeholder="' . s(get_string('code_placeholder', 'local_courses')) . '" autocomplete="off" style="letter-spacing: 2px; font-size: 1.15rem; border-radius: 6px;">'
                   . '</div>'
                   . '</div>'
                   . '</div>'
                   // Guidelines text
                   . '<div class="text-dark small mb-3 ps-1" style="line-height: 1.55;">'
                   . '<div class="fw-bold text-secondary mb-1" style="font-size: 0.88rem;">' . s(get_string('guidelines_title', 'local_courses')) . '</div>'
                   . '<ul class="ps-3 mb-2 text-muted" style="font-size: 0.84rem;">'
                   . '<li>' . s(get_string('guidelines_item1', 'local_courses')) . '</li>'
                   . '<li>' . s(get_string('guidelines_item2', 'local_courses')) . '</li>'
                   . '</ul>'
                   . '<div class="text-muted small">' . s(get_string('guidelines_item3', 'local_courses')) . '</div>'
                   . '</div>'
                   . '</div>'
                   // Footer with Theme Moove Buttons
                   . '<div class="modal-footer border-0 px-4 pt-1 pb-3 d-flex justify-content-end align-items-center gap-2">'
                   . '<button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" id="btn-cancel-modal">' . s(get_string('cancel', 'local_courses')) . '</button>'
                   . '<button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" id="btn-submit-modal" disabled>' . s(get_string('btn_join', 'local_courses')) . '</button>'
                   . '</div>'
                   . '</form>'
                   . '</div>'
                   . '</div>'
                   . '</div>';

        $jsonbuttonhtml = json_encode($buttonhtml);
        $jsonmodalhtml = json_encode($modalhtml);

        $headerquickjoinscript = "
        <script>
        (function() {
            function initCoursesModal() {
                // 1. Inject Header Button
                if (!document.getElementById('btn-open-courses-modal')) {
                    var container = document.querySelector('#page-header .header-actions-container') 
                                 || document.querySelector('.header-actions-container');
                    var headerRow = document.querySelector('#page-header .d-flex.align-items-center') 
                                 || document.querySelector('#page-header .d-flex')
                                 || document.querySelector('.page-context-header');

                    if (container || headerRow) {
                        var wrapper = document.createElement('div');
                        wrapper.className = 'header-action ms-auto d-flex align-items-center my-1';
                        wrapper.id = 'courses-header-action-wrapper';
                        wrapper.innerHTML = {$jsonbuttonhtml};

                        if (container) {
                            container.appendChild(wrapper);
                        } else if (headerRow) {
                            headerRow.appendChild(wrapper);
                        }
                    }
                }

                // 2. Inject Modal if not present
                if (!document.getElementById('coursesJoinModal')) {
                    document.body.insertAdjacentHTML('beforeend', {$jsonmodalhtml});
                }

                // 3. Bind Open click handler
                var openBtn = document.getElementById('btn-open-courses-modal');
                if (openBtn && !openBtn.dataset.bound) {
                    openBtn.dataset.bound = 'true';
                    openBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var modalEl = document.getElementById('coursesJoinModal');
                        if (modalEl) {
                            if (window.bootstrap && window.bootstrap.Modal) {
                                var modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                                modalInstance.show();
                            } else {
                                modalEl.classList.add('show');
                                modalEl.style.display = 'block';
                                document.body.classList.add('modal-open');
                                if (!document.getElementById('courses-backdrop')) {
                                    var backdrop = document.createElement('div');
                                    backdrop.className = 'modal-backdrop fade show';
                                    backdrop.id = 'courses-backdrop';
                                    document.body.appendChild(backdrop);
                                }
                            }
                            setTimeout(function() {
                                var inp = document.getElementById('modalCourseCodeInput');
                                if (inp) { inp.focus(); }
                            }, 300);
                        }
                    });
                }

                // 4. Bind Dismiss handlers
                document.querySelectorAll('#coursesJoinModal [data-bs-dismiss=\"modal\"]').forEach(function(el) {
                    if (!el.dataset.bound) {
                        el.dataset.bound = 'true';
                        el.addEventListener('click', function() {
                            var modalEl = document.getElementById('coursesJoinModal');
                            if (modalEl) {
                                if (window.bootstrap && window.bootstrap.Modal) {
                                    var modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                                    modalInstance.hide();
                                } else {
                                    modalEl.classList.remove('show');
                                    modalEl.style.display = 'none';
                                    document.body.classList.remove('modal-open');
                                    var backdrop = document.getElementById('courses-backdrop');
                                    if (backdrop) backdrop.remove();
                                }
                            }
                        });
                    }
                });

                // 5. Bind Input validation for Join button state
                var codeInp = document.getElementById('modalCourseCodeInput');
                var submitBtn = document.getElementById('btn-submit-modal');
                if (codeInp && submitBtn && !codeInp.dataset.bound) {
                    codeInp.dataset.bound = 'true';
                    codeInp.addEventListener('input', function() {
                        this.value = this.value.toUpperCase();
                        var len = this.value.trim().length;
                        submitBtn.disabled = (len < 3);
                    });
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initCoursesModal);
            } else {
                initCoursesModal();
            }
            setTimeout(initCoursesModal, 200);
            setTimeout(initCoursesModal, 800);
        })();
        </script>";
    }

    // 2. Teacher alert banner inside course page when students are pending approval
    $coursealertscript = '';
    $targetcourse = (!empty($PAGE->course) && $PAGE->course->id > SITEID) ? $PAGE->course : null;
    if (!$targetcourse && !empty($COURSE) && $COURSE->id > SITEID) {
        $targetcourse = $COURSE;
    }

    if ($targetcourse && strpos($currentpath, '/course/view.php') !== false) {
        $context = context_course::instance($targetcourse->id);
        if (has_capability('moodle/course:update', $context)) {
            $manageurl = new moodle_url('/local/courses/manage.php', ['id' => $targetcourse->id]);
            $pendingcount = $DB->count_records('local_courses_requests', ['courseid' => $targetcourse->id, 'status' => 'pending']);
            
            if ($pendingcount > 0) {
                $bannertitle = get_string('pending_banner_title', 'local_courses', $pendingcount);
                $bannerdesc = get_string('pending_banner_desc', 'local_courses');
                $bannerbtn = get_string('pending_banner_btn', 'local_courses');

                $alertbannerhtml = '<div id="local-courses-pending-banner" class="alert border mb-4 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between" style="background: #f8fafd; border: 1px solid #c2e7ff !important; border-radius: 8px; color: #041e49; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">'
                                 . '<div class="d-flex align-items-center me-3">'
                                 . '<div style="width: 38px; height: 38px; min-width: 38px; border-radius: 50%; background: #c2e7ff; color: #004a77; display: flex; align-items: center; justify-content: center; font-size: 1rem; margin-right: 14px;">'
                                 . '<i class="fa fa-user-clock"></i>'
                                 . '</div>'
                                 . '<div>'
                                 . '<div style="font-size: 0.95rem; font-weight: 600;">' . s($bannertitle) . '</div>'
                                 . '<div style="font-size: 0.82rem; color: #444746;">' . s($bannerdesc) . '</div>'
                                 . '</div>'
                                 . '</div>'
                                 . '<div class="mt-2 mt-sm-0">'
                                 . '<a href="' . $manageurl->out(false) . '" class="btn btn-sm btn-primary text-decoration-none shadow-sm" style="font-weight: 500; padding: 6px 16px; font-size: 0.88rem;">'
                                 . s($bannerbtn)
                                 . '</a>'
                                 . '</div>'
                                 . '</div>';

                $jsonbannerhtml = json_encode($alertbannerhtml);

                $coursealertscript = "
                <script>
                (function() {
                    function injectBanner() {
                        var target = document.querySelector('#region-main') || document.querySelector('.course-content') || document.querySelector('#page-content');
                        if (target && !document.getElementById('local-courses-pending-banner')) {
                            var div = document.createElement('div');
                            div.innerHTML = {$jsonbannerhtml};
                            target.insertBefore(div.firstElementChild, target.firstChild);
                        }
                    }
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', injectBanner);
                    } else {
                        injectBanner();
                    }
                })();
                </script>";
            }
        }
    }

    return $headerquickjoinscript . $coursealertscript;
}
