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
 * English language strings for local_courses.
 *
 * @package    local_courses
 * @copyright  2026 Huynh Tu
 * @author     Huynh Tu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Courses Code';
$string['courses:manage'] = 'Manage course enrollment codes and approve students';
$string['courses:join'] = 'Join a course using an enrollment code';

$string['course_code'] = 'Course code';
$string['course_code_desc'] = 'Share this code with your students to join the course.';
$string['copy_code'] = 'Copy';
$string['copied'] = 'Copied';
$string['require_approval'] = 'Student approval';
$string['enabled_status'] = 'Enabled';
$string['disabled_status'] = 'Disabled';
$string['require_approval_on'] = 'Teacher must approve students before joining.';
$string['require_approval_off'] = 'Students join immediately upon entering code.';
$string['confirm_regenerate'] = 'Are you sure you want to generate a new course code?';
$string['regenerate_codes'] = 'Reset course code';
$string['pending_requests'] = 'Join requests';
$string['no_pending_requests'] = 'No pending join requests';
$string['no_pending_requests_desc'] = 'When students enter the course code, requests will appear here.';
$string['student'] = 'Student';
$string['student_id'] = 'Student ID';
$string['email'] = 'Email';
$string['request_time'] = 'Time submitted';
$string['action'] = 'Action';
$string['approve'] = 'Approve';
$string['reject'] = 'Reject';
$string['approve_all'] = 'Approve all';
$string['confirm_approve_all'] = 'Are you sure you want to approve all pending students?';
$string['confirm_reject'] = 'Are you sure you want to reject this request?';
$string['history_requests'] = 'Recent history';
$string['status_approved'] = 'Approved';
$string['status_rejected'] = 'Rejected';
$string['status_pending'] = 'Pending';
$string['status'] = 'Status';
$string['back_to_course'] = 'Back to course';
$string['manage_courses'] = 'Course code & Student approval';
$string['course'] = 'Course';
$string['settings_saved'] = 'Settings saved successfully.';
$string['approved_success'] = 'Student request approved successfully.';
$string['rejected_success'] = 'Student request rejected.';
$string['codes_regenerated'] = 'Course code regenerated successfully.';

// Join page & modal
$string['joincourse'] = 'Join course';
$string['joincourse_title'] = 'Join course';
$string['joincourse_desc'] = 'Enter the course code provided by your teacher.';
$string['enter_code'] = 'Course code';
$string['code_placeholder'] = 'Course code';
$string['quick_join_placeholder'] = 'Course code';
$string['btn_join'] = 'Join';
$string['cancel'] = 'Cancel';
$string['logged_in_as'] = 'You are currently signed in as';
$string['ask_teacher_hint'] = 'Ask your teacher for the course code, then enter it here.';
$string['guidelines_title'] = 'To sign in with a course code';
$string['guidelines_item1'] = 'Use an authorized account';
$string['guidelines_item2'] = 'Use a course code with 5-8 letters or numbers, and no spaces or symbols';
$string['guidelines_item3'] = 'If you have trouble joining the course, contact your teacher.';

// Messages and Notifications
$string['invalid_code'] = 'Invalid course code or feature is disabled!';
$string['already_enrolled'] = 'You are already enrolled in this course!';
$string['request_submitted'] = 'Code accepted! Your request has been sent to the teacher and is pending approval.';
$string['request_pending'] = 'You already have a pending join request for this course!';
$string['enrolled_success_student'] = 'Congratulations! You have successfully enrolled in the course.';
$string['enrolled_success_teacher'] = 'Welcome! You have been enrolled as an Instructor for this course.';

// Banner
$string['pending_banner_title'] = 'Course join requests: {$a} student(s) pending approval';
$string['pending_banner_desc'] = 'Students entered your course code and are waiting for your confirmation to enter the course.';
$string['pending_banner_btn'] = 'Review students';
$string['messageprovider:joinrequest'] = 'Notification when a student requests to join course';
$string['messageprovider:joinresult'] = 'Notification to student on join request approval or rejection';

// Privacy API
$string['privacy:metadata:local_courses_requests'] = 'Stores course join requests and approval history.';
$string['privacy:metadata:local_courses_requests:courseid'] = 'The ID of the course requested.';
$string['privacy:metadata:local_courses_requests:userid'] = 'The ID of the user who submitted the join request.';
$string['privacy:metadata:local_courses_requests:roleid'] = 'The role assigned to the user upon joining.';
$string['privacy:metadata:local_courses_requests:status'] = 'The current status of the request (pending, approved, rejected).';
$string['privacy:metadata:local_courses_requests:codeentered'] = 'The course enrollment code entered by the user.';
$string['privacy:metadata:local_courses_requests:actionby'] = 'The ID of the teacher who reviewed the request.';
$string['privacy:metadata:local_courses_requests:timecreated'] = 'The timestamp when the request was submitted.';
$string['privacy:metadata:local_courses_requests:timemodified'] = 'The timestamp when the request was last updated.';

// Additional UI strings
$string['enrol_failed_teacher'] = 'Cannot enrol as teacher. Please contact your administrator.';
$string['enrol_failed_student'] = 'Cannot enrol in course. Please contact your administrator.';
$string['toggle_disable_code'] = 'Disable course code';
$string['toggle_enable_code'] = 'Enable course code';
$string['need_approval_badge'] = 'Approval required';
$string['auto_enrol_badge'] = 'Direct join';
$string['approval_enabled_heading'] = 'Student approval enabled';
$string['approval_disabled_heading'] = 'Direct join upon entering code';
$string['btn_disable_approval'] = 'Disable approval';
$string['btn_enable_approval'] = 'Enable approval';
$string['approval_help_tip'] = 'Enabling this allows instructors to carefully manage student enrollment.';
$string['processed_time'] = 'Processed time';

