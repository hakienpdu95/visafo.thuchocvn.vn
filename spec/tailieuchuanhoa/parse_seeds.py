import pandas as pd
import json
import os
import sys

# Cấu hình đường dẫn
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(os.path.dirname(SCRIPT_DIR)) 
INPUT_PATH = os.path.join(SCRIPT_DIR, "danhmucgiongcaytrong.html")

print(f"🔄 Đang đọc dữ liệu từ: {INPUT_PATH}")

try:
    dfs = pd.read_html(INPUT_PATH, encoding='utf-8')
    df = dfs[0]
except Exception as e:
    print(f"❌ Lỗi đọc file HTML: {e}")
    sys.exit(1)

parsed_hs_codes = []
seen_codes = set() # Dùng set để loại bỏ các mã HS bị lặp lại trong file

for index, row in df.iterrows():
    # Sử dụng iloc[] để lấy giá trị theo cột, khắc phục lỗi FutureWarning
    hs_code = str(row.iloc[0]).strip()
    name = str(row.iloc[1]).strip()
    description = str(row.iloc[2]).strip()
    
    # Bỏ qua các dòng rác hoặc dòng tiêu đề
    if pd.isna(row.iloc[0]) or hs_code == 'nan' or hs_code == "Mã hàng" or not hs_code:
        continue
        
    # Loại bỏ các ký tự thừa trong mã HS (VD: 0602.20.00 -> 06022000)
    hs_code = hs_code.replace('.', '')
    
    # Chỉ thêm vào danh sách nếu mã HS này chưa tồn tại
    if hs_code not in seen_codes:
        parsed_hs_codes.append({
            "hs_code": hs_code,
            "name": name,
            "description": description
        })
        seen_codes.add(hs_code)

# Lưu file JSON
output_dir = os.path.join(PROJECT_ROOT, "datafiles")
os.makedirs(output_dir, exist_ok=True)
output_path = os.path.join(output_dir, "agri_hs_codes_master_data.json")

with open(output_path, "w", encoding="utf-8") as f:
    json.dump(parsed_hs_codes, f, ensure_ascii=False, indent=2)

print(f"✅ Đã parse thành công {len(parsed_hs_codes)} Mã HS Nông sản.")
print(f"💾 File JSON được lưu tại: {output_path}")