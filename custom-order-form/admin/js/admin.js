document.addEventListener('DOMContentLoaded', function() {
    initializeColorPickers();
    initializeFieldToggles();
    initializeCustomFields();
    setupLivePreview();
});

function initializeColorPickers() {
    const colorPickers = document.querySelectorAll('.color-picker');
    colorPickers.forEach(picker => {
        picker.addEventListener('change', function() {
            updatePreview();
        });
    });
}

function initializeFieldToggles() {
    const toggles = document.querySelectorAll('.field-toggle-input');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            updatePreview();
        });
    });
}

function initializeCustomFields() {
    let customFieldCount = 0;
    
    document.getElementById('add-custom-field')?.addEventListener('click', function() {
        customFieldCount++;
        const fieldHtml = `
            <div class="custom-field-row flex items-center gap-2 mt-2">
                <input type="text" 
                       placeholder="اسم الحقل" 
                       class="flex-1 p-2 border rounded"
                       name="custom_fields[${customFieldCount}][label]">
                <select name="custom_fields[${customFieldCount}][type]"
                        class="p-2 border rounded">
                    <option value="text">نص</option>
                    <option value="number">رقم</option>
                    <option value="select">قائمة منسدلة</option>
                </select>
                <button type="button" 
                        class="remove-field px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                    حذف
                </button>
            </div>
        `;
        
        document.getElementById('custom-fields-container').insertAdjacentHTML('beforeend', fieldHtml);
    });

    document.addEventListener('click', function(e) {
        if (e.target.matches('.remove-field')) {
            e.target.closest('.custom-field-row').remove();
            updatePreview();
        }
    });
}

function setupLivePreview() {
    const previewFrame = document.getElementById('form-preview-frame');
    if (!previewFrame) return;

    const updatePreview = _.debounce(function() {
        const settings = collectCurrentSettings();
        
        // تحديث المعاينة عبر postMessage
        previewFrame.contentWindow.postMessage({
            type: 'update-form-settings',
            settings: settings
        }, '*');
    }, 300);

    // إضافة مستمعي الأحداث لجميع عناصر التحكم
    document.querySelectorAll('input, select, textarea').forEach(element => {
        element.addEventListener('change', updatePreview);
        element.addEventListener('input', updatePreview);
    });
}

function collectCurrentSettings() {
    return {
        fields: getFieldSettings(),
        styles: getStyleSettings(),
        customFields: getCustomFieldSettings()
    };
}

function getFieldSettings() {
    const fields = {};
    document.querySelectorAll('.field-toggle-input').forEach(toggle => {
        fields[toggle.name] = toggle.checked;
    });
    return fields;
}

function getStyleSettings() {
    const styles = {};
    document.querySelectorAll('.color-picker').forEach(picker => {
        styles[picker.name] = picker.value;
    });
    styles.fontFamily = document.querySelector('select[name="font_family"]').value;
    return styles;
}

function getCustomFieldSettings() {
    const customFields = [];
    document.querySelectorAll('.custom-field-row').forEach(row => {
        customFields.push({
            label: row.querySelector('input[type="text"]').value,
            type: row.querySelector('select').value
        });
    });
    return customFields;
}

// Add color settings handlers
jQuery(document).ready(function($) {
    // Add new color
    $('.add-color').on('click', function() {
        const name = $('#new-color-name').val();
        const value = $('#new-color-value').val();
        
        if (!name || !value) {
            alert('الرجاء إدخال اسم اللون وقيمته');
            return;
        }

        const row = `
            <tr>
                <td>${name}</td>
                <td>
                    <input type="color" value="${value}" 
                           data-color-name="${name}" 
                           class="color-picker">
                </td>
                <td>
                    <button type="button" class="button delete-color" 
                            data-color-name="${name}">
                        حذف
                    </button>
                </td>
            </tr>
        `;
        
        $('#custom-colors').append(row);
        $('#new-color-name').val('');
        $('#new-color-value').val('#000000');
        
        saveColors();
    });

    // Delete color
    $(document).on('click', '.delete-color', function() {
        $(this).closest('tr').remove();
        saveColors();
    });

    // Save colors when changed
    $(document).on('change', '#custom-colors .color-picker', function() {
        saveColors();
    });

    function saveColors() {
        const colors = {};
        $('#custom-colors tr').each(function() {
            const name = $(this).find('.color-picker').data('color-name');
            const value = $(this).find('.color-picker').val();
            colors[name] = value;
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_color_settings',
                nonce: customOrderFormAdmin.nonce,
                colors: colors
            },
            success: function(response) {
                if (response.success) {
                    // Optional: Show success message
                }
            }
        });
    }
});
