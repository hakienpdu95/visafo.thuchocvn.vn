import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-product-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
});

/**
 * Khu vực ảnh sản phẩm (create/edit).
 *
 * FilePond chỉ dùng làm ô kéo thả: upload xong (media nằm ở FilePondDraft) thì đẩy vào `items`
 * và gỡ khỏi pond, nên danh sách thumbnail bên dưới là nguồn duy nhất. Khi submit form gửi:
 *   gallery    — JSON mảng ID media theo thứ tự hiển thị
 *   main_image — ID ảnh chính (backend đưa lên vị trí đầu, = slide đầu tiên)
 *
 * Cần @vite('resources/js/modules/filepond.js') trên trang (window.initFilePondUpload).
 */
// Bề rộng 1 item FilePond theo đúng các breakpoint cột trong .product-gallery (product.scss).
// List của FilePond cách mép root 1em mỗi bên, mỗi item có margin 0.25em hai bên.
function previewItemWidth(root) {
    const cols = matchMedia('(min-width: 1280px)').matches ? 10
        : matchMedia('(min-width: 1024px)').matches ? 8
        : matchMedia('(min-width: 640px)').matches ? 5 : 2;
    const listWidth = (root?.clientWidth ?? 300) - 32;

    return Math.max(listWidth / cols - 8, 1);
}

document.addEventListener('alpine:init', () => {
    Alpine.data('productGallery', ({ items = [] } = {}) => {
        let pond = null; // giữ ngoài state để Alpine không bọc proxy instance FilePond

        // Ô preview vuông theo bề rộng ô thực tế (lúc khởi tạo) — khớp aspect-square của lưới thumbnail
        let previewSize = 140;

        return {
            items: items.map(i => ({ id: i.id, thumb_url: i.thumb_url, isNew: false })),
            mainId: items.find(i => i.is_main)?.id ?? items[0]?.id ?? null,

            get galleryJson() {
                return JSON.stringify(this.items.map(i => i.id));
            },

            init() {
                previewSize = Math.round(Math.max(previewItemWidth(this.$refs.pond.parentElement), 64));

                pond = window.initFilePondUpload(this.$refs.pond, {
                    collection: 'gallery',
                    maxFiles: 20,
                    // Ô preview cố định chiều cao → các item trong lưới đều nhau (xem .product-gallery trong product.scss)
                    imagePreviewHeight: previewSize,
                    itemInsertLocation: 'after',
                    credits: false,
                    // Plugin preview vẽ kiểu "contain" theo crop.aspectRatio (cao/rộng) → đặt đúng tỉ lệ ô
                    // thì ảnh lấp kín khung như object-fit: cover. Chỉ là metadata hiển thị: process tự viết
                    // trong filepond.js không gửi metadata nên file gốc lên server không bị cắt.
                    beforeAddFile: (item) => {
                        item.setMetadata('crop', {
                            center: { x: 0.5, y: 0.5 },
                            flip: { horizontal: false, vertical: false },
                            zoom: 1,
                            rotation: 0,
                            aspectRatio: previewSize / previewItemWidth(pond?.element),
                        }, true);
                        return true;
                    },
                    onUploaded: (id, url, thumbUrl) => {
                        this.items.push({ id, thumb_url: thumbUrl, isNew: true });
                        this.mainId ??= id;
                    },
                    onprocessfile: (error, file) => {
                        if (!error) pond.removeFile(file.id); // không revert — file đã nằm trong items
                    },
                });

                this.$el.closest('form')?.addEventListener('submit', (e) => {
                    if (pond?.getFiles().length) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        alert('Ảnh đang được tải lên, vui lòng đợi hoàn tất rồi lưu lại.');
                    }
                }, true);
            },

            setMain(id) {
                this.mainId = id;
            },

            move(index, step) {
                const target = index + step;
                if (target < 0 || target >= this.items.length) return;
                [this.items[index], this.items[target]] = [this.items[target], this.items[index]];
            },

            remove(item) {
                this.items = this.items.filter(i => i.id !== item.id);
                if (this.mainId === item.id) this.mainId = this.items[0]?.id ?? null;

                // Ảnh vừa upload (draft) → xóa luôn; ảnh cũ của sản phẩm chỉ bị xóa khi bấm Lưu
                if (item.isNew) {
                    fetch(`/api/v1/media/upload/${item.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            'Accept': 'application/json',
                        },
                    }).catch(() => {});
                }
            },
        };
    });
});
