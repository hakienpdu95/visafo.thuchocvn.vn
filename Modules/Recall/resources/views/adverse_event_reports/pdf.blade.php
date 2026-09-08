<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Phụ lục 18-MP</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11pt; margin: 0; padding: 15mm; color: #000; }
        .header-line { text-align: center; font-weight: bold; }
        .addressee { margin: 6mm 0; }
        h1 { text-align: center; font-size: 13pt; margin: 8mm 0; letter-spacing: 1px; }
        h2 { font-size: 11.5pt; margin: 6mm 0 2mm; }
        table.form { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
        table.form td { vertical-align: top; padding: 1.2mm 0; }
        table.form td.label { width: 62%; }
        table.form td.value { border-bottom: 1px dotted #000; font-weight: bold; }
        .checkbox { margin-right: 6mm; }
        .signature { margin-top: 12mm; text-align: right; }
        .signature .box { display: inline-block; text-align: center; width: 60mm; }
    </style>
</head>
<body>
    <p class="header-line">Phụ lục số 18-MP<br>MỸ PHẨM</p>

    <p class="addressee">
        Kính gửi: &nbsp; Cục Quản lý dược - Bộ Y tế<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;138A Giảng Võ, Ba Đình, Hà Nội
    </p>

    <h1>THÔNG BÁO TÁC DỤNG BẤT LỢI</h1>

    <h2>I. Thông tin về công ty</h2>
    <table class="form">
        <tr><td class="label">Tên và địa chỉ của công ty</td><td class="value">{{ $report->company_name }}{{ $report->company_address ? ' — ' . $report->company_address : '' }}</td></tr>
        <tr><td class="label">Tên và chức danh người thông báo</td><td class="value">{{ $report->reporter_name }}{{ $report->reporter_title ? ' — ' . $report->reporter_title : '' }}</td></tr>
        <tr><td class="label">Số điện thoại</td><td class="value">{{ $report->reporter_phone ?? '—' }}</td></tr>
        <tr><td class="label">Fax</td><td class="value">{{ $report->reporter_fax ?? '—' }}</td></tr>
        <tr><td class="label">Email</td><td class="value">{{ $report->reporter_email ?? '—' }}</td></tr>
    </table>

    <h2>II. Thông tin sản phẩm</h2>
    <table class="form">
        <tr><td class="label">Tên sản phẩm (giống như tên trên đơn công bố)</td><td class="value">{{ $report->product->name }}</td></tr>
        <tr><td class="label">Danh sách thành phần, dạng đóng gói</td><td class="value">{{ $report->ingredients_packaging ?? '—' }}</td></tr>
        <tr><td class="label">Dạng sản phẩm/mục đích sử dụng</td><td class="value">{{ $report->product_form_purpose ?? '—' }}</td></tr>
        <tr><td class="label">Tên công ty sản xuất/xuất xứ</td><td class="value">{{ $report->manufacturer_origin ?? '—' }}</td></tr>
        <tr><td class="label">Ngày sản xuất hoặc hạn dùng</td><td class="value">{{ $report->mfgOrExpDateLabel() ?? '—' }}</td></tr>
        <tr><td class="label">Số lô</td><td class="value">{{ $report->lotNumber() ?? '—' }}</td></tr>
    </table>

    <h2>III. Báo cáo tác dụng bất lợi chi tiết</h2>
    <table class="form">
        <tr><td class="label">Tên người sử dụng</td><td class="value">{{ $report->consumer_name }}</td></tr>
        <tr><td class="label">Số chứng minh nhân dân hoặc hộ chiếu</td><td class="value">{{ $report->consumer_id_number ?? '—' }}</td></tr>
        <tr><td class="label">Tuổi</td><td class="value">{{ $report->consumer_age ?? '—' }}</td></tr>
        <tr><td class="label">Giới tính</td><td class="value">{{ $report->consumer_gender?->label() ?? '—' }}</td></tr>
        <tr><td class="label">Tôn giáo/Quốc tịch</td><td class="value">{{ $report->consumer_nationality ?? '—' }}</td></tr>
        <tr><td class="label">Thời gian xuất hiện tác dụng bất lợi</td><td class="value">{{ $report->onset_at?->format('H:i d/m/Y') ?? '—' }}</td></tr>
    </table>

    <p><strong>Mô tả tác dụng bất lợi</strong> (đính kèm bản mô tả tác dụng bất lợi nếu cần thiết):</p>
    <p>{{ $report->reaction_description }}</p>

    <table class="form">
        <tr><td class="label">Thời gian giữa lần dùng sản phẩm cuối cùng và thời điểm xuất hiện tác dụng bất lợi</td><td class="value">{{ $report->time_since_last_use ?? '—' }}</td></tr>
        <tr><td class="label">Sản phẩm đã được sử dụng như thế nào</td><td class="value">{{ $report->usage_description ?? '—' }}</td></tr>
    </table>

    <p>
        Người sử dụng có phải nhập viện vì tác dụng bất lợi không?
        <span class="checkbox">{{ $report->was_hospitalized ? '☒' : '☐' }} Có</span>
        <span class="checkbox">{{ !$report->was_hospitalized ? '☒' : '☐' }} Không</span>
    </p>

    <p>
        Người sử dụng có phải điều trị y tế không?
        <span class="checkbox">{{ $report->required_medical_treatment ? '☒' : '☐' }} Có</span>
        <span class="checkbox">{{ !$report->required_medical_treatment ? '☒' : '☐' }} Không</span>
    </p>

    <p>
        Kết quả:
        <span class="checkbox">{{ $report->outcome?->value === 'recovered' ? '☒' : '☐' }} Đã hồi phục ({{ $report->outcome?->value === 'recovered' ? ('Ngày: ' . $report->outcome_date?->format('d/m/Y')) : 'Ngày: _______' }})</span>
        <span class="checkbox">{{ $report->outcome?->value === 'fatal' ? '☒' : '☐' }} Tử vong ({{ $report->outcome?->value === 'fatal' ? ('Ngày: ' . $report->outcome_date?->format('d/m/Y')) : 'Ngày: _______' }})</span><br>
        <span class="checkbox">{{ $report->outcome?->value === 'not_recovered' ? '☒' : '☐' }} Vẫn chưa hồi phục</span>
        <span class="checkbox">{{ $report->outcome === null || $report->outcome?->value === 'unknown' ? '☒' : '☐' }} Không biết</span>
    </p>

    <p>
        Nguồn cung cấp báo cáo:
        <span class="checkbox">{{ $report->report_source?->value === 'healthcare_professional' ? '☒' : '☐' }} Chuyên gia y tế{{ $report->report_source?->value === 'healthcare_professional' && $report->report_source_detail ? ' (' . $report->report_source_detail . ')' : '' }}</span>
        <span class="checkbox">{{ $report->report_source?->value === 'customer' ? '☒' : '☐' }} Khách hàng</span>
        <span class="checkbox">{{ $report->report_source?->value === 'other' ? '☒' : '☐' }} Nguồn khác{{ $report->report_source?->value === 'other' && $report->report_source_detail ? ' (' . $report->report_source_detail . ')' : '' }}</span>
    </p>

    <div class="signature">
        <div class="box">
            [Chữ ký của người báo cáo tác dụng bất lợi]<br><br><br>
            Ngày: {{ now()->format('d/m/Y') }}
        </div>
    </div>
</body>
</html>
