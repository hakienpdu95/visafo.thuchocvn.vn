from PIL import Image
import numpy as np

def process_thermal_logo(input_path, output_path):
    # 1. Đọc ảnh PNG và xử lý nền trong suốt (tránh việc nền trong bị biến thành màu đen)
    img = Image.open(input_path).convert("RGBA")
    background = Image.new("RGBA", img.size, (255, 255, 255))
    alpha_composite = Image.alpha_composite(background, img)
    
    # Chuyển sang ảnh xám
    gray_img = alpha_composite.convert('L')
    img_array = np.array(gray_img)

    # 2. Trim khoảng trắng: Tìm các pixel tối (giá trị < 240, bỏ qua nền trắng)
    logo_pixels = img_array < 240
    coords = np.argwhere(logo_pixels)

    if coords.size > 0:
        # Lấy tọa độ viền sát nhất của logo
        y0, x0 = coords.min(axis=0)
        y1, x1 = coords.max(axis=0)

        # Thêm padding 10px để logo không bị sát mép
        padding = 10
        y0 = max(0, y0 - padding)
        x0 = max(0, x0 - padding)
        y1 = min(img_array.shape[0], y1 + padding)
        x1 = min(img_array.shape[1], x1 + padding)

        # Cắt ảnh theo khung vừa tìm được
        cropped_img = gray_img.crop((x0, y0, x1, y1))

        # 3. Ép thành Đen & Trắng tuyệt đối (Pure B&W) cho máy in nhiệt
        bw_img = cropped_img.point(lambda x: 0 if x < 200 else 255, '1')

        # 4. Lưu kết quả
        bw_img.save(output_path)
        print(f"Đã xử lý và lưu logo thành công tại: {output_path}")
    else:
        print("Không tìm thấy nội dung logo.")

# Chạy hàm với file logo.png đang có sẵn trong thư mục
process_thermal_logo('logo.png', 'logo_visafo_bw_trimmed.png')
