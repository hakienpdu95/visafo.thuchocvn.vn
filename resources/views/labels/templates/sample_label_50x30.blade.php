{{--
    Partial tem nhiệt LƯU MẪU THỨC ĂN (Mẫu số 4, QĐ 1246/QĐ-BYT) — 50x30 mm. Được nhúng bởi foodinspection::food-samples.labels
    (không có <html>/<body>). Bảng info-table căn dấu ":" thẳng hàng như các tem khác.
    Biến: $sample (FoodSampleDetail: ->meal_time, ->dish_name, ->sampled_at, ->sampler_name, ->destroyableFrom()).
--}}
@once
<style>
    .tpl-sample .label { width: 50mm; height: 30mm; padding: 1.5mm 2mm; overflow: hidden; background: #fff; }
    .tpl-sample .title { text-align: center; font-size: 6.5pt; font-weight: 700; letter-spacing: .1mm; line-height: 1; padding-bottom: .6mm; border-bottom: .75pt solid #000; }
    .tpl-sample .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 6.5pt; line-height: 1.15; margin-top: .4mm; }
    .tpl-sample .info-table th, .tpl-sample .info-table td { padding: .3mm 0; vertical-align: top; border-bottom: .5px dotted #666; }
    .tpl-sample .info-table th { position: relative; width: 30%; padding-right: 1.4mm; text-align: left; font-weight: 700; white-space: nowrap; overflow: hidden; }
    .tpl-sample .info-table th::after { content: ':'; position: absolute; right: .3mm; top: .3mm; }
    .tpl-sample .info-table td { padding-left: 1mm; word-break: break-word; }
    .tpl-sample .info-table .dish td { font-size: 7.5pt; font-weight: 700; }
    .tpl-sample .clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .tpl-sample .foot { margin-top: .5mm; text-align: center; font-size: 5.5pt; font-weight: 700; line-height: 1.1; }

    @media screen { .tpl-sample .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
</style>
@endonce
<div class="label-wrapper tpl-sample">
    <div class="label">
        <div class="title">MẪU LƯU THỨC ĂN</div>
        <table class="info-table">
            <tr>
                <th>Bữa ăn</th>
                <td>{{ $sample->meal_time->label() }}</td>
            </tr>
            <tr class="dish">
                <th>Tên món</th>
                <td><div class="clamp-2">{{ $sample->dish_name }}</div></td>
            </tr>
            <tr>
                <th>Thời gian lấy</th>
                <td>{{ $sample->sampled_at?->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Người lấy</th>
                <td>{{ $sample->sampler_name }}</td>
            </tr>
        </table>
        <div class="foot">Bảo quản 2–8°C • Hủy sau: {{ $sample->destroyableFrom()->format('d/m H:i') }}</div>
    </div>
</div>
