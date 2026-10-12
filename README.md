# Courses Code Plugin (`local_courses`)

Plugin ghi danh khóa học bằng mã (**Courses Code**) cho hệ thống **Moodle LMS** (tương thích Moodle 4.4, 4.5, 5.0+).
- **Tác giả:** Huynh Tu
- **Tên Plugin:** Courses Code (`local_courses`)

---

## 🌟 Tính năng chính

1. **Mã khóa học tự động (Course Code):**
   - Mỗi khóa học được định nghĩa một mã khóa học duy nhất gồm 6 ký tự (loại trừ các ký tự dễ nhầm lẫn như `0`, `O`, `1`, `I`, `L`).
   - Hỗ trợ đổi mã mới bất kỳ lúc nào hoặc tạm tắt tính năng nhập mã cho khóa học.

2. **Chế độ kiểm soát và Phê duyệt học sinh (Approval Workflow):**
   - **Bật duyệt:** Học sinh nhập đúng mã sẽ gửi yêu cầu phê duyệt tới giáo viên.
   - **Tắt duyệt:** Học sinh nhập đúng mã sẽ được ghi danh ngay lập tức vào khóa học.

3. **Giao diện hiện đại & Đồng bộ với Theme (Moove Theme / Boost Theme):**
   - Nút **"Tham gia khóa học"** xuất hiện trên trang *My courses* (`/my/courses.php`), thẳng hàng sát lề phải tiêu đề trang.
   - Popup Modal mở nhanh, thiết kế thanh lịch, tự động đồng bộ màu sắc và hiệu ứng của Theme LMS.
   - Không gây rối mắt khi học sinh đã vào bên trong bài học (`/course/view.php`).

4. **Khu vực quản lý dành riêng cho Giáo viên:**
   - Menu **"Mã khóa học & Duyệt học viên"** hiển thị trực tiếp trên thanh điều hướng ngang của khóa học.
   - Huy hiệu thông báo đỏ hiển thị số lượng học sinh đang chờ duyệt.
   - Banner thông báo nhắc nhở giáo viên nổi bật ở đầu trang khóa học khi có yêu cầu mới.
   - Hiển thị đầy đủ thông tin học sinh: Họ và tên, Avatar, Mã số sinh viên (MSSV / Username), Email, Thời gian gửi.
   - Các nút thao tác đồng đều: Duyệt từng học sinh, Từ chối, hoặc Duyệt tất cả chỉ với 1 click.

5. **Hệ thống thông báo tức thời (Moodle Bell Notification & Email):**
   - Tự động gửi thông báo chuông và email cho giáo viên khi có học sinh xin vào lớp.
   - Tự động gửi thông báo kết quả (Đã duyệt / Từ chối) kèm đường dẫn trực tiếp vào học cho học sinh.

---

## 📋 Tuân thủ Quy chuẩn Moodle (Moodle Guidelines & Standards)

Plugin được xây dựng theo đầy đủ các quy định và chuẩn mực của **Moodle Plugin Contribution Guidelines**:

- **Bản quyền & Giấy phép:** Mã nguồn mở chuẩn GNU General Public License v3 (GPL v3).
- **Ngăn chặn truy cập trái phép:** Kiểm tra nghiêm ngặt `defined('MOODLE_INTERNAL') || die();` trên tất cả các tệp hệ thống.
- **Bảo mật CSRF & Xác thực:** Sử dụng `require_login()`, `require_sesskey()`, `confirm_sesskey()`.
- **Phân quyền Moodle (Capabilities API):**
  - `local/courses:manage`: Dành cho Giáo viên (`editingteacher`), Quản trị viên (`manager`).
  - `local/courses:join`: Dành cho Học viên đã đăng nhập (`user`).
- **Chuẩn cấu trúc cơ sở dữ liệu XMLDB:**
  - Định nghĩa chuẩn qua `db/install.xml` với Foreign Keys và Indexes tối ưu hiệu năng.
  - Tích hợp `db/upgrade.php` hỗ trợ nâng cấp phiên bản và chuyển đổi dữ liệu mượt mà.
- **Hook & Navigation API:** Tích hợp `\core\hook\navigation\secondary_extend` chuẩn Moodle 4.x/5.x.
- **Đa ngôn ngữ (Localization):** Hỗ trợ đầy đủ tiếng Việt (`lang/vi/local_courses.php`) và tiếng Anh (`lang/en/local_courses.php`).
- **Quyền riêng tư & GDPR (Privacy API):** Tích hợp đầy đủ `classes/privacy/provider.php` theo chuẩn Moodle Privacy Subsystem (mô tả metadata, xuất dữ liệu cá nhân, xóa dữ liệu theo yêu cầu).
- **Yêu cầu kỹ thuật (Prerequisites):** Moodle 4.4+, PHP 8.1 / 8.2 / 8.3+, cơ sở dữ liệu MariaDB 10.6+ hoặc MySQL 8.0+ / PostgreSQL.
- **Phong cách mã nguồn (Coding Style):** Tuân thủ Moodle CodeSniffer (moodle-cs), PSR-12, thụt lề 4 khoảng trắng, chuẩn kết thúc dòng LF.
- **Chuẩn dữ liệu an toàn:** Làm sạch dữ liệu đầu vào qua `PARAM_*` và escape đầu ra qua hàm `s()`, `format_string()`.

---

## 🚀 Hướng dẫn Cài đặt & Triển khai

### Cách 1: Cài đặt trực tiếp qua giao diện Quản trị Moodle (Khuyên dùng)
1. Tải tệp zip **`local_courses.zip`**.
2. Đăng nhập tài khoản Quản trị viên (Admin) trên website Moodle.
3. Truy cập: **Site administration (Quản trị khu đất)** ➔ **Plugins (Phần bổ trợ)** ➔ **Install plugins (Cài đặt các phần bổ trợ)**.
4. Kéo thả file `local_courses.zip` vào ô tải lên và bấm **Install plugin from the ZIP file**.
5. Làm theo các bước xác nhận để hoàn tất cài đặt.

### Cách 2: Triển khai thủ công lên máy chủ
1. Giải nén thư mục `courses` từ file zip.
2. Sao chép toàn bộ thư mục `courses` vào thư mục `moodle/local/courses/`.
3. Phân quyền tệp (trên Linux/Docker):
   ```bash
   chown -R www-data:www-data /path/to/moodle/local/courses
   chmod -R 755 /path/to/moodle/local/courses
   ```
4. Truy cập trang Quản trị Moodle hoặc chạy lệnh CLI:
   ```bash
   php admin/cli/upgrade.php
   ```

---

## 📄 Bản quyền & Tác giả
- **Tên Plugin:** Courses Code
- **Thành phần:** `local_courses`
- **Tác giả:** Huynh Tu
- **Bản quyền:** &copy; 2026 Huynh Tu
- **Phiên bản:** `1.0.0` (Build 2026101201)
- **Giấy phép:** GNU GPL v3 or later
