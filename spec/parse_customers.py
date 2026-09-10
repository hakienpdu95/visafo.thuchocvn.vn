import pandas as pd
import re
import json
import os
import sys

# Khởi tạo đường dẫn
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(SCRIPT_DIR) # Lùi lại 1 cấp để ra thư mục root của project
INPUT_PATH = os.path.join(SCRIPT_DIR, "Khach_hang.xlsx")

def remove_vietnamese_accents(s):
    """Bộ lọc loại bỏ dấu Tiếng Việt"""
    s = re.sub(r'[àáạảãâầấậẩẫăằắặẳẵ]', 'a', s)
    s = re.sub(r'[ÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴ]', 'A', s)
    s = re.sub(r'[èéẹẻẽêềếệểễ]', 'e', s)
    s = re.sub(r'[ÈÉẸẺẼÊỀẾỆỂỄ]', 'E', s)
    s = re.sub(r'[òóọỏõôồốộổỗơờớợởỡ]', 'o', s)
    s = re.sub(r'[ÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠ]', 'O', s)
    s = re.sub(r'[ìíịỉĩ]', 'i', s)
    s = re.sub(r'[ÌÍỊỈĨ]', 'I', s)
    s = re.sub(r'[ùúụủũưừứựửữ]', 'u', s)
    s = re.sub(r'[ÙÚỤỦŨƯỪỨỰỬỮ]', 'U', s)
    s = re.sub(r'[ỳýỵỷỹ]', 'y', s)
    s = re.sub(r'[ỲÝỴỶỸ]', 'Y', s)
    s = re.sub(r'[đ]', 'd', s)
    s = re.sub(r'[Đ]', 'D', s)
    return s

def clean_code(text):
    if pd.isna(text) or not str(text).strip():
        return ""
    text = str(text).strip()
    text = remove_vietnamese_accents(text)
    text = re.sub(r'\s+', '_', text)
    text = re.sub(r'[^a-zA-Z0-9_]', '', text)
    return text.upper()

print(f"🔄 Đang đọc dữ liệu từ: {INPUT_PATH}")

try:
    df = pd.read_excel(INPUT_PATH, sheet_name='Khach_hang', header=1)
except FileNotFoundError:
    print(f"❌ Lỗi: Không tìm thấy file Excel tại '{INPUT_PATH}'.")
    sys.exit(1)

# Lấy 4 cột (Mã để tham chiếu/chuẩn hóa, còn lại 3 cột theo yêu cầu)
df_subset = df[['Mã khách hàng', 'Tên khách hàng', 'Địa chỉ', 'Mã số thuế']].copy()
df_subset = df_subset.fillna('')

parsed_customers = []
skipped_count = 0

for index, row in df_subset.iterrows():
    original_code = str(row['Mã khách hàng']).strip()
    name = str(row['Tên khách hàng']).strip()
    address = str(row['Địa chỉ']).strip()
    tax_code = str(row['Mã số thuế']).strip()
    
    if not name:
        skipped_count += 1
        continue
        
    c_code = clean_code(original_code)
    if not c_code:
        c_code = None 

    parsed_customers.append({
        "customer_code": c_code,
        "name": name,            
        "address": address,             
        "tax_code": tax_code
    })

# Xuất ra file JSON
output_dir = os.path.join(PROJECT_ROOT, "datafiles")
os.makedirs(output_dir, exist_ok=True)
output_path = os.path.join(output_dir, "customers_master_data.json")

with open(output_path, "w", encoding="utf-8") as f:
    json.dump(parsed_customers, f, ensure_ascii=False, indent=2)

print(f"✅ Đã parse thành công {len(parsed_customers)} Khách hàng.")
print(f"⚠️ Đã bỏ qua {skipped_count} dòng trống.")
print(f"💾 File JSON được lưu tại: {output_path}")