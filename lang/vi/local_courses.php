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
 * Vietnamese language strings for local_courses.
 *
 * @package    local_courses
 * @copyright  2026 Huynh Tu
 * @author     Huynh Tu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Courses Code';
$string['courses:manage'] = 'Quản lý mã khóa học và phê duyệt học viên';
$string['courses:join'] = 'Tham gia khóa học bằng mã';

$string['course_code'] = 'Mã khóa học';
$string['course_code_desc'] = 'Cung cấp mã này cho học sinh để tham gia khóa học.';
$string['copy_code'] = 'Sao chép';
$string['copied'] = 'Đã sao chép';
$string['require_approval'] = 'Duyệt học sinh';
$string['enabled_status'] = 'Đang bật';
$string['disabled_status'] = 'Đang tắt';
$string['require_approval_on'] = 'Cần giáo viên duyệt trước khi vào khóa học.';
$string['require_approval_off'] = 'Học sinh nhập mã sẽ vào khóa học ngay.';
$string['confirm_regenerate'] = 'Bạn có chắc muốn tạo lại mã khóa học mới?';
$string['regenerate_codes'] = 'Đổi mã khóa học mới';
$string['pending_requests'] = 'Yêu cầu tham gia khóa học';
$string['no_pending_requests'] = 'Hiện không có học sinh nào chờ duyệt';
$string['no_pending_requests_desc'] = 'Khi học sinh nhập mã khóa học, yêu cầu sẽ xuất hiện tại đây.';
$string['student'] = 'Học sinh';
$string['student_id'] = 'Mã số sinh viên (MSSV)';
$string['email'] = 'Email';
$string['request_time'] = 'Thời gian gửi';
$string['action'] = 'Thao tác';
$string['approve'] = 'Duyệt';
$string['reject'] = 'Từ chối';
$string['approve_all'] = 'Duyệt tất cả';
$string['confirm_approve_all'] = 'Bạn có chắc muốn duyệt tất cả học sinh đang chờ?';
$string['confirm_reject'] = 'Bạn có chắc muốn từ chối yêu cầu này?';
$string['history_requests'] = 'Lịch sử xử lý gần đây';
$string['status_approved'] = 'Đã duyệt';
$string['status_rejected'] = 'Đã từ chối';
$string['status_pending'] = 'Chờ duyệt';
$string['status'] = 'Trạng thái';
$string['back_to_course'] = 'Quay lại khóa học';
$string['manage_courses'] = 'Mã khóa học & Duyệt học viên';
$string['course'] = 'Khóa học';
$string['settings_saved'] = 'Đã lưu cài đặt thành công.';
$string['approved_success'] = 'Đã duyệt học sinh thành công.';
$string['rejected_success'] = 'Đã từ chối yêu cầu tham gia.';
$string['codes_regenerated'] = 'Đã tạo lại mã khóa học mới thành công.';

// Join page & modal
$string['joincourse'] = 'Tham gia khóa học';
$string['joincourse_title'] = 'Tham gia khóa học';
$string['joincourse_desc'] = 'Nhập mã khóa học do giáo viên của bạn cung cấp.';
$string['enter_code'] = 'Mã khóa học';
$string['code_placeholder'] = 'Ví dụ: K9N4P2';
$string['quick_join_placeholder'] = 'Nhập mã khóa học...';
$string['btn_join'] = 'Tham gia';
$string['cancel'] = 'Hủy';
$string['logged_in_as'] = 'Bạn đang đăng nhập với tài khoản';
$string['ask_teacher_hint'] = 'Hỏi giáo viên mã khóa học của bạn rồi nhập mã vào đây.';
$string['guidelines_title'] = 'Để đăng nhập bằng mã khóa học';
$string['guidelines_item1'] = 'Sử dụng tài khoản được cấp quyền';
$string['guidelines_item2'] = 'Sử dụng mã khóa học gồm 5–8 chữ cái hoặc số, không có khoảng cách hoặc ký hiệu';
$string['guidelines_item3'] = 'Nếu bạn gặp sự cố khi tham gia khóa học, hãy liên hệ giáo viên phụ trách môn.';

// Messages
$string['invalid_code'] = 'Mã khóa học không hợp lệ hoặc tính năng đã bị vô hiệu hóa!';
$string['already_enrolled'] = 'Bạn đã là thành viên của khóa học này rồi!';
$string['request_submitted'] = 'Mã hợp lệ! Yêu cầu tham gia của bạn đã được gửi tới giáo viên và đang chờ duyệt.';
$string['request_pending'] = 'Bạn đã gửi yêu cầu tham gia khóa học này rồi, vui lòng đợi giáo viên duyệt!';
$string['enrolled_success_student'] = 'Chúc mừng bạn đã tham gia khóa học thành công!';
$string['enrolled_success_teacher'] = 'Chào mừng bạn! Bạn đã được thêm vào vai trò Giáo viên của khóa học.';

// Banner
$string['pending_banner_title'] = 'Yêu cầu vào khóa học: Có {$a} học sinh đang chờ bạn duyệt';
$string['pending_banner_desc'] = 'Học sinh đã nhập mã khóa học của bạn và đang đợi xác nhận để vào học.';
$string['pending_banner_btn'] = 'Duyệt học sinh ngay';
$string['messageprovider:joinrequest'] = 'Thông báo khi có học sinh yêu cầu tham gia khóa học';
$string['messageprovider:joinresult'] = 'Thông báo cho học sinh khi yêu cầu được duyệt hoặc từ chối';

// Privacy API
$string['privacy:metadata:local_courses_requests'] = 'Lưu trữ thông tin yêu cầu tham gia khóa học và lịch sử phê duyệt.';
$string['privacy:metadata:local_courses_requests:courseid'] = 'ID của khóa học được yêu cầu.';
$string['privacy:metadata:local_courses_requests:userid'] = 'ID của học viên gửi yêu cầu tham gia.';
$string['privacy:metadata:local_courses_requests:roleid'] = 'Vai trò được gán khi tham gia khóa học.';
$string['privacy:metadata:local_courses_requests:status'] = 'Trạng thái của yêu cầu (chờ duyệt, đã duyệt, đã từ chối).';
$string['privacy:metadata:local_courses_requests:codeentered'] = 'Mã khóa học được học viên nhập vào.';
$string['privacy:metadata:local_courses_requests:actionby'] = 'ID của giáo viên đã xử lý yêu cầu.';
$string['privacy:metadata:local_courses_requests:timecreated'] = 'Thời điểm gửi yêu cầu.';
$string['privacy:metadata:local_courses_requests:timemodified'] = 'Thời điểm cập nhật yêu cầu gần nhất.';

// Additional UI strings
$string['enrol_failed_teacher'] = 'Không thể ghi danh với vai trò giáo viên. Vui lòng liên hệ quản trị viên.';
$string['enrol_failed_student'] = 'Không thể ghi danh vào khóa học. Vui lòng liên hệ quản trị viên.';
$string['toggle_disable_code'] = 'Tắt mã khóa học';
$string['toggle_enable_code'] = 'Bật mã khóa học';
$string['need_approval_badge'] = 'Cần duyệt trước khi vào';
$string['auto_enrol_badge'] = 'Vào tự động';
$string['approval_enabled_heading'] = 'Đang bật duyệt học sinh';
$string['approval_disabled_heading'] = 'Vào lớp ngay khi nhập mã';
$string['btn_disable_approval'] = 'Tắt duyệt';
$string['btn_enable_approval'] = 'Bật duyệt';
$string['approval_help_tip'] = 'Bật tính năng này giúp giáo viên kiểm soát chặt chẽ danh sách học sinh tham gia khóa học.';
$string['processed_time'] = 'Thời gian xử lý';

