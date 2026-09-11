import pandas as pd
import json
import os
import sys

# Cấu hình đường dẫn
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
# Lùi lại 2 cấp nếu file script đang nằm trong spec/tailieuchuanhoa/
PROJECT_ROOT = os.path.dirname(os.path.dirname(SCRIPT_DIR)) 
INPUT_PATH = os.path.join(SCRIPT_DIR, "phanbon.html")

print(f"🔄 Đang đọc dữ liệu từ: {INPUT_PATH}")

try:
    dfs = pd.read_html(INPUT_PATH, encoding='utf-8')
except Exception as e:
    print(f"❌ Lỗi đọc file HTML: {e}")
    sys.exit(1)

parsed_fertilizers = []
# Các nhóm phân bón lấy từ tiêu đề trong HTML
categories = [
    "Phân hữu cơ", "Phân hữu cơ khoáng", "Phân hữu cơ sinh học", 
    "Phân hữu cơ vi sinh", "Phân vi sinh vật", "Phân bón lá", "Chất giữ ẩm, cải tạo đất"
]

# Chỉ xử lý 7 bảng tương ứng với 7 danh mục
for i in range(min(7, len(dfs))):
    df = dfs[i]
    category = categories[i] if i < len(categories) else f"Nhóm {i+1}"
    
    current_name = None
    current_applicant = None
    current_ingredients = []

    # Pandas tự động điền khuyết (forward-fill) các ô bị gộp (rowspan)
    for index, row in df.iterrows():
        if index == 0: continue # Bỏ qua header
        
        # Cấu trúc bảng: 0: TT Cũ, 1: TT Mới, 2: Tên, 3: Đơn vị, 4: Thành phần, 5: Tổ chức
        if len(row) >= 6:
            name = str(row[2]).strip()
            unit = str(row[3]).strip()
            ingredients = str(row[4]).strip()
            applicant = str(row[5]).strip()
            
            if pd.isna(row[2]) or name == "nan" or "Tên phân bón" in name: 
                continue
            
            # Nếu gặp phân bón mới, đóng gói cái cũ lại
            if name != current_name and current_name is not None:
                parsed_fertilizers.append({
                    "category": category,
                    "name": current_name,
                    "ingredients": " | ".join(current_ingredients),
                    "applicant": current_applicant,
                    "is_banned": False
                })
                current_ingredients = []
            
            current_name = name
            current_applicant = applicant
            if ingredients != "nan" and unit != "nan":
                # Gom đơn vị và thành phần lại cho trực quan
                current_ingredients.append(f"{unit}: {ingredients}")
            elif ingredients != "nan":
                current_ingredients.append(ingredients)

    # Đóng gói sản phẩm cuối cùng của bảng
    if current_name is not None:
        parsed_fertilizers.append({
            "category": category,
            "name": current_name,
            "ingredients": " | ".join(current_ingredients),
            "applicant": current_applicant,
            "is_banned": False
        })

output_dir = os.path.join(PROJECT_ROOT, "datafiles")
os.makedirs(output_dir, exist_ok=True)
output_path = os.path.join(output_dir, "agri_fertilizers_master_data.json")

with open(output_path, "w", encoding="utf-8") as f:
    json.dump(parsed_fertilizers, f, ensure_ascii=False, indent=2)

print(f"✅ Đã parse thành công {len(parsed_fertilizers)} loại Phân bón.")
print(f"💾 File JSON được lưu tại: {output_path}")