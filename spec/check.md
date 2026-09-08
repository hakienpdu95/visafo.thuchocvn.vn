Dưới đây là bản tổng hợp toàn diện về thông tin cốt lõi, lộ trình triển khai (roadmap) và kiến trúc các module/phân hệ của hệ thống Bản sao số năng lực, được tổng hợp chặt chẽ từ 4 tài liệu đã cung cấp.

### 1. Tổng hợp thông tin cốt lõi của dự án

Dự án nhằm xây dựng một hệ thống tạo lập, duy trì và cập nhật biểu diễn số có cấu trúc về trạng thái năng lực của cá nhân tại từng thời điểm (Bản sao số năng lực).

* **Nguyên tắc vận hành:** Lấy cá nhân làm chủ thể trung tâm; đánh giá phải dựa trên minh chứng truy xuất được nguồn gốc; AI chỉ đóng vai trò hỗ trợ và không tự động ra quyết định; bảo vệ dữ liệu được thiết kế ngay từ đầu (Privacy by design).


* **Kiến trúc khung năng lực (TCF):** Sử dụng thiết kế 3 lớp linh hoạt gồm: Lớp nền tảng (24 năng lực chung dùng cho mọi ngành), Lớp chuyên ngành (do chuyên gia xây dựng) và Lớp hồ sơ vị trí (cấu hình riêng cho từng doanh nghiệp).


* **Lõi công nghệ đột phá (Sáng chế):** Hệ thống không ghi đè dữ liệu lịch sử. Khi có minh chứng mới, hệ thống duyệt một "cấu trúc quan hệ phụ thuộc" (đồ thị) để tìm chính xác các nút trạng thái bị ảnh hưởng, tính toán lại cục bộ và sinh ra một bản ghi phiên bản mới (T1, T2... Tn) với dấu vân tay dữ liệu (hash) riêng biệt.


* **Trạng thái dữ liệu:** Áp dụng thang đo 6 mức (M1 đến M5), trong đó có trạng thái "Chưa đủ dữ liệu" được phân tách rõ ràng với mức năng lực, đồng thời phân biệt rạch ròi giữa "Mức năng lực" và "Mức độ tin cậy" của minh chứng.



---

### 2. Roadmap triển khai hệ thống

Lộ trình được lồng ghép giữa tiến độ phát triển phần mềm (MVP) và kế hoạch thử nghiệm thực tế 18 tháng tại địa bàn (với quy mô 100-200 hồ sơ, 5-10 vị trí).

**Giai đoạn 1: Khảo sát, Thiết kế & Sprint 0 (Tháng 1 - Tháng 3)**

* Khảo sát địa bàn, chốt danh sách tổ chức và vị trí việc làm tham gia.


* Khóa user journey, ma trận phân quyền, phân loại dữ liệu, xây dựng wireframe và mô hình dữ liệu (ERD).


* Đầu ra: Tài liệu SRS v1, cấu trúc khung năng lực mẫu, kế hoạch nghiệm thu.



**Giai đoạn 2: Phát triển MVP Core (Tháng 4 - Tháng 6)**

* Lập trình các module lõi: Quản lý tài khoản (IAM), Quản lý phiên bản khung năng lực, Quản lý hồ sơ và Quy trình duyệt minh chứng.


* Xây dựng thuật toán tạo trạng thái năng lực ban đầu (T0).


* Đầu ra: Bản nguyên mẫu (prototype) chạy trên môi trường test với dữ liệu giả lập, tài liệu quản trị dữ liệu.



**Giai đoạn 3: Thử nghiệm vòng 1 - Thiết lập T0 (Tháng 7 - Tháng 10)**

* Đưa người dùng thực vào hệ thống (onboarding), thu thập minh chứng và thực hiện đánh giá đa nguồn.


* Hệ thống chạy các luồng AI cơ bản (AI-01 OCR, AI-02 gợi ý ánh xạ).


* Đầu ra: Hình thành được trạng thái T0 (hoặc kết quả chưa đủ dữ liệu) cho các hồ sơ, xuất báo cáo phân tích khoảng cách năng lực.



**Giai đoạn 4: Can thiệp phát triển & Thử nghiệm vòng 2 (Tháng 11 - Tháng 17)**

* Lập kế hoạch phát triển (đào tạo, thực hành) dựa trên khoảng cách năng lực đã phân tích.


* Bổ sung minh chứng mới vào hệ thống sau quá trình đào tạo để hệ thống kích hoạt tính toán lại, tạo ra phiên bản trạng thái cập nhật (T1, T2).


* Tinh chỉnh kỹ thuật (Hardening): Kiểm thử bảo mật (Pentest), tối ưu hiệu năng, diễn tập phục hồi dữ liệu.



**Giai đoạn 5: Tổng kết & Hậu MVP (Tháng 18 trở đi)**

* Đánh giá sự thay đổi năng lực (trước - sau), tổng hợp báo cáo giới hạn mở rộng.


* Định hướng công nghệ tiếp theo: Mở rộng API cho đối tác, phát triển ứng dụng Mobile, tích hợp các tác nhân AI tự động hóa cao hơn.



---

### 3. Danh sách các Module và Phân hệ cần triển khai

Hệ thống được thiết kế theo kiến trúc 5 lớp (Thu thập, Chuẩn hóa, Bản sao số, AI phân tích, Hiển thị). Dưới đây là cấu trúc các Module và Phân hệ chi tiết:

**Module 1: IAM & Tổ chức (Identity, Access & Org Management)**

* *Phân hệ Tài khoản:* Đăng ký, đăng nhập, MFA, quản lý mã định danh nội bộ (pseudonymization).
* *Phân hệ Tổ chức & Quan hệ:* Quản lý doanh nghiệp, cơ sở đào tạo, quan hệ lao động (ngày bắt đầu/kết thúc), tự động thu hồi quyền khi kết thúc quan hệ.
* *Phân hệ Chiến dịch Pilot:* Quản lý lời mời, theo dõi trạng thái tham gia (onboarding) của người dùng.

**Module 2: CFM (Quản trị Khung năng lực - Competency Framework)**

* *Phân hệ Hồ sơ vị trí (Role Profile):* Tạo và quản lý danh mục nghề, vị trí việc làm, nhiệm vụ và yêu cầu năng lực theo từng bối cảnh.
* *Phân hệ Cấu trúc năng lực:* Cấu hình 3 lớp của khung TCF, định nghĩa tiêu chí, chỉ báo cho các mức từ M1 đến M5.


* *Phân hệ Phiên bản & Quy tắc (Versioning & Rules):* Cấu hình trọng số, quy tắc tính điểm, quản lý vòng đời phiên bản khung (nháp -> chờ duyệt -> kích hoạt -> đóng băng).



**Module 3: EVM (Quản lý Minh chứng - Evidence Intake & Review)**

* *Phân hệ Thu thập (Intake):* Upload tệp, kiểm tra định dạng (MIME/Magic bytes), quét mã độc (malware scan), băm dữ liệu (checksum).
* *Phân hệ Workflow Minh chứng:* Quản lý vòng đời trạng thái file (từ mới tiếp nhận, chờ xác minh, đến đã xác minh, thu hồi hoặc hết hiệu lực).
* *Phân hệ Ánh xạ (Mapping):* Liên kết 1 minh chứng với nhiều tiêu chí năng lực, đánh giá mức độ liên quan độc lập với trạng thái xác minh của file.

**Module 4: TWE (Động cơ Bản sao số - Twin Engine)**

* *Phân hệ Đánh giá (Assessment):* Phân công người chấm (tự đánh giá, quản lý, chuyên gia), tổng hợp kết quả đa nguồn, cảnh báo thiếu dữ liệu.
* *Phân hệ Snapshot Bất biến:* Thuật toán sinh tập dữ liệu chuẩn hóa, tạo mã băm (dấu vân tay) để so sánh trạng thái ứng viên với trạng thái hiện hành, lưu vết T0-Tn không ghi đè.


* *Phân hệ Đồ thị phụ thuộc (Dependency Graph):* Quản lý chỉ mục lan truyền ảnh hưởng, tự động nhận diện các nút năng lực cần tính toán lại khi minh chứng hoặc quy tắc bị thay đổi.



**Module 5: DEV (Phân tích khoảng cách & Phát triển)**

* *Phân hệ Gap Analysis:* So sánh năng lực hiện tại với vị trí mục tiêu, bóc tách giữa việc "thiếu năng lực" và "chưa đủ dữ liệu".
* *Phân hệ Kế hoạch phát triển:* Tạo lộ trình học tập, gán khóa học/nhiệm vụ, theo dõi tiến độ (planned, in-progress, completed).

**Module 6: AIM (Điều phối AI - AI Orchestrator)**

* *Phân hệ Tác vụ AI:* Thực thi OCR trích xuất thông tin (AI-01), AI gợi ý ánh xạ minh chứng (AI-02), AI gợi ý lộ trình phát triển.
* *Phân hệ Quản trị Model & Prompt:* Quản lý danh mục nhà cung cấp, phiên bản model, các mẫu câu lệnh (prompt templates), cơ chế kill-switch.
* *Phân hệ Kiểm soát con người (Human-in-the-loop):* Giao diện bắt buộc để chuyên gia/quản lý duyệt (chấp nhận, sửa, từ chối) các đầu ra do AI gợi ý trước khi lưu chính thức.

**Module 7: PRV (Cổng hiển thị, Báo cáo & Quyền riêng tư)**

* *Phân hệ Quyền riêng tư (Privacy):* Quản lý sự đồng ý (Consent), cấp/thu hồi quyền chia sẻ dữ liệu (Share grants), tiếp nhận yêu cầu của chủ thể dữ liệu (sửa, xóa, hạn chế).
* *Phân hệ Cổng làm việc (Portals & Dashboards):* Cung cấp giao diện riêng biệt cho Cá nhân (xem hồ sơ mình), Tổ chức (xem nhóm nhân viên), và Cơ quan quản lý (xem dữ liệu ẩn danh, áp ngưỡng nhóm nhỏ).
* *Phân hệ Nhật ký & Xuất dữ liệu (Audit & Export):* Ghi log dạng append-only cho mọi thao tác quan trọng, xuất báo cáo CSV/PDF theo phân quyền.