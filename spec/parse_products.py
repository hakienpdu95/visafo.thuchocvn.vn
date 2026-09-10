import pandas as pd
import json
import os
import sys

# Khởi tạo đường dẫn
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(SCRIPT_DIR) # Lùi lại 1 cấp để ra thư mục root của project
INPUT_PATH = os.path.join(SCRIPT_DIR, "Vat_tu__hang_hoa__dich_vu.xlsx")

# Helper function tương đương với Rule Engine của Laravel Import Service
def parse_excel_category(kho_code):
    kho_code = str(kho_code).strip().upper()
    if kho_code in ['KTPTS', 'KRCQ', 'KRCQSC']:
        return {'type': 'raw_material', 'category': 'fresh_food'}
    elif kho_code in ['KĐK', 'KTPĐL']:
        return {'type': 'raw_material', 'category': 'processed_food'}
    elif kho_code == 'KHLRT':
        return {'type': 'raw_material', 'category': 'additives_spices'}
    elif kho_code == 'KHQ':
        return {'type': 'trading_good', 'category': 'fresh_food'}
    elif kho_code == 'CCDC':
        return {'type': 'consumables', 'category': None}
    else:
        # Fallback an toàn cho các mã rác/trống
        return {'type': 'raw_material', 'category': 'fresh_food'}

print(f"🔄 Đang đọc dữ liệu từ: {INPUT_PATH}")

try:
    # Bỏ qua 1 dòng tiêu đề đầu tiên (header=1)
    df = pd.read_excel(INPUT_PATH, sheet_name='Vat_tu__hang_hoa__dich_vu', header=1)
except FileNotFoundError:
    print(f"❌ Lỗi: Không tìm thấy file Excel tại '{INPUT_PATH}'.")
    sys.exit(1)

parsed_products = []
skipped_count = 0

# Duyệt từng dòng trong Excel
for index, row in df.iterrows():
    sku = str(row.get('Mã', '')).strip()
    name = str(row.get('Tên', '')).strip()
    unit = str(row.get('ĐVT chính', '')).strip()
    kho = str(row.get('Kho ngầm định', '')).strip()
    
    # 1. Bỏ qua các sản phẩm không có Tên hoặc Tên là "Chưa có mã"
    if pd.isna(row.get('Tên')) or name == '' or name.lower() == 'nan' or 'chưa có mã' in name.lower():
        skipped_count += 1
        continue
        
    # 2. Xử lý thiếu Unit/Mã (Tùy chọn, hiện gán mặc định nếu trống)
    if unit == 'nan' or not unit: unit = 'Cái/Kg'
    if sku == 'nan' or not sku: sku = f"SP-AUTO-{index}"

    # 3. Ánh xạ Loại sản phẩm và Nhóm ATTP
    mapped_data = parse_excel_category(kho)
    
    parsed_products.append({
        "sku": sku,
        "name": name,
        "unit": unit,
        "product_type": mapped_data['type'],
        "category_code": mapped_data['category'],
        "original_kho": kho # Lưu lại để tham chiếu nếu cần
    })

# 4. Xuất ra file JSON vào thư mục root/datafiles (ĐÃ SỬA TẠI ĐÂY)
output_dir = os.path.join(PROJECT_ROOT, "datafiles")
os.makedirs(output_dir, exist_ok=True)
output_path = os.path.join(output_dir, "products_master_data.json")

with open(output_path, "w", encoding="utf-8") as f:
    json.dump(parsed_products, f, ensure_ascii=False, indent=2)

print(f"✅ Đã parse thành công {len(parsed_products)} sản phẩm hợp lệ.")
print(f"⚠️ Đã bỏ qua (skip) {skipped_count} dòng dữ liệu rác / 'Chưa có mã'.")
print(f"💾 File JSON được lưu tại: {output_path}")