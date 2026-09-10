import pandas as pd
import re
import json
import os
import sys

# Khởi tạo đường dẫn
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(SCRIPT_DIR) # Lùi lại 1 cấp để ra thư mục root của project
INPUT_PATH = os.path.join(SCRIPT_DIR, "Nha_cung_cap.xlsx")

def remove_vietnamese_accents(s):
    """Bộ lọc loại bỏ dấu Tiếng Việt 100% tuyệt đối"""
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

def clean_vendor_code(text):
    """Hàm chuẩn hóa mã NCC ERP"""
    if pd.isna(text) or not str(text).strip():
        return ""
    text = str(text).strip()
    
    # 1. Xóa dấu Tiếng Việt
    text = remove_vietnamese_accents(text)
    
    # 2. Thay thế khoảng trắng (hoặc nhiều khoảng trắng liền nhau) bằng 1 dấu gạch dưới
    text = re.sub(r'\s+', '_', text)
    
    # 3. Loại bỏ mọi ký tự đặc biệt (chỉ giữ lại a-z, A-Z, 0-9 và dấu _)
    text = re.sub(r'[^a-zA-Z0-9_]', '', text)
    
    # 4. Ép thành chữ in hoa chuẩn ERP
    return text.upper()

print(f"🔄 Đang đọc dữ liệu từ: {INPUT_PATH}")

try:
    df = pd.read_excel(INPUT_PATH, sheet_name='Nha_cung_cap', header=1)
except FileNotFoundError:
    print(f"❌ Lỗi: Không tìm thấy file Excel tại '{INPUT_PATH}'.")
    sys.exit(1)

df_subset = df[['Mã nhà cung cấp', 'Tên nhà cung cấp', 'Địa chỉ', 'Mã số thuế']].copy()
df_subset = df_subset.fillna('')

parsed_vendors = []
skipped_count = 0

for index, row in df_subset.iterrows():
    original_code = str(row['Mã nhà cung cấp']).strip()
    vendor_name = str(row['Tên nhà cung cấp']).strip()
    address = str(row['Địa chỉ']).strip()
    tax_code = str(row['Mã số thuế']).strip()
    
    # Bỏ qua nếu không có tên nhà cung cấp
    if not vendor_name:
        skipped_count += 1
        continue
        
    # Chuẩn hóa Mã
    clean_code = clean_vendor_code(original_code)
    if not clean_code:
        clean_code = None 

    parsed_vendors.append({
        "vendor_code": clean_code,      
        "name": vendor_name,            
        "address": address,             
        "tax_code": tax_code,           
        "original_code": original_code  
    })

# Xuất ra file JSON
output_dir = os.path.join(PROJECT_ROOT, "datafiles")
os.makedirs(output_dir, exist_ok=True)
output_path = os.path.join(output_dir, "vendors_master_data.json")


with open(output_path, "w", encoding="utf-8") as f:
    json.dump(parsed_vendors, f, ensure_ascii=False, indent=2)

print(f"✅ Đã parse thành công {len(parsed_vendors)} Nhà cung cấp.")
print(f"⚠️ Đã bỏ qua (skip) {skipped_count} dòng trống.")
print(f"💾 File JSON được lưu tại: {output_path}")