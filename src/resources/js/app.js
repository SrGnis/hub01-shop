import './bootstrap';


// Tag checkbox handlers
window.handleMainTagChange = function(checkbox) {
    if (!checkbox.checked) {
        const tagId = checkbox.dataset.tagId;
        const subTags = document.querySelectorAll(`.sub-tag-checkbox[data-parent-id="${tagId}"]`);

        subTags.forEach(subCheckbox => {
            if (subCheckbox.checked) {
                subCheckbox.checked = false;
                subCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }
};

window.handleSubTagChange = function(checkbox) {
    if (checkbox.checked) {
        const parentId = checkbox.dataset.parentId;
        const parentCheckbox = document.querySelector(`.main-tag-checkbox[data-tag-id="${parentId}"]`);

        if (parentCheckbox && !parentCheckbox.checked) {
            parentCheckbox.checked = true;
            parentCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
};
