import pandas as pd
import json
import re
import os
import sys

# Khởi tạo đường dẫn
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(os.path.dirname(SCRIPT_DIR)) # Lùi lại 2 cấp để ra thẳng root của project
INPUT_PATH = os.path.join(SCRIPT_DIR, "thuocbaovethucvat.html")

print(f"🔄 Đang đọc dữ liệu từ: {INPUT_PATH}")

try:
    # Đọc HTML, pandas tự động xử lý các ô gộp (rowspan/colspan)
    dfs = pd.read_html(INPUT_PATH, encoding='utf-8')
    df = dfs[0]
except Exception as e:
    print(f"❌ Lỗi đọc file HTML: {e}")
    sys.exit(1)

parsed_pesticides = []
current_group = ""

# Duyệt từng dòng trong bảng
for index, row in df.iterrows():
    if index == 0: continue # Bỏ qua dòng tiêu đề "TT | Hoạt chất..."
    
    col0, col1, col2, col3, col4 = row[0], row[1], row[2], row[3], row[4]
    
    # Nhận diện dòng Category (VD: "1. Thuốc trừ sâu")
    # Do colspan, các cột sẽ chứa giá trị giống hệt nhau
    if col0 == col1 and col0 == col2 and str(col0).startswith(str(col0).split('.')[0] + '.'):
        # Bỏ đi số thứ tự "1. ", "2. " để lấy tên nhóm sạch
        current_group = re.sub(r'^\d+\.\s*', '', str(col0)).strip()
        continue
        
    if pd.isna(col0) and pd.isna(col1):
        continue
        
    parsed_pesticides.append({
        "category": current_group,                          # VD: Thuốc trừ sâu
        "active_ingredients": str(col1).strip(),            # VD: Abamectin 0.7% + Cyromazine 30.3%
        "trade_name": str(col2).strip(),                    # VD: Cyromaz 31SC
        "target_pest": str(col3).strip() if not pd.isna(col3) else "",
        "applicant": str(col4).strip() if not pd.isna(col4) else "",
        "quarantine_days": None, # Để trống, QC sẽ cập nhật trên phần mềm để cấu hình Rule Engine
        "is_banned": False
    })

# Xuất ra file JSON
output_dir = os.path.join(PROJECT_ROOT, "datafiles")
os.makedirs(output_dir, exist_ok=True)
output_path = os.path.join(output_dir, "agri_pesticides_master_data.json")

with open(output_path, "w", encoding="utf-8") as f:
    json.dump(parsed_pesticides, f, ensure_ascii=False, indent=2)

print(f"✅ Đã parse thành công {len(parsed_pesticides)} loại thuốc BVTV.")
print(f"💾 File JSON được lưu tại: {output_path}")