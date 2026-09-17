window.salesPackageEditItem = function (url, currentName) {
    const form = document.getElementById('editCustomItemForm');
    const nameInput = document.getElementById('editCustomItemName');
    if (!form || !nameInput) return;

    form.action = url;
    nameInput.value = currentName ?? '';
    document.getElementById('editCustomItemModal')?.showModal();
};
