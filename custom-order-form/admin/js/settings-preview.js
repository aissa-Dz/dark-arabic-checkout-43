jQuery(document).ready(function($) {
    // تهيئة منتقي الألوان مع المعاينة المباشرة
    $('.color-picker').wpColorPicker({
        change: function(event, ui) {
            updatePreview();
        }
    });

    // تحديث المعاينة المباشرة
    function updatePreview() {
        const preview = $('.form-preview');
        const settings = {
            primaryColor: $('input[name="design[primaryColor]"]').val(),
            buttonColor: $('input[name="design[buttonColor]"]').val(),
            backgroundColor: $('input[name="design[backgroundColor]"]').val(),
            textColor: $('input[name="design[textColor]"]').val(),
            borderColor: $('input[name="design[borderColor]"]').val(),
            borderRadius: $('input[name="design[borderRadius]"]').val() + 'px',
            fontFamily: $('select[name="design[fontFamily]"]').val()
        };

        // تطبيق الألوان والتنسيقات
        preview.css({
            'background-color': settings.backgroundColor,
            'color': settings.textColor,
            'font-family': settings.fontFamily
        });

        // تحديث الحقول
        $('.preview-input, .preview-select').css({
            'border-color': settings.borderColor,
            'border-radius': settings.borderRadius
        });

        // تحديث الزر
        $('.preview-button').css({
            'background-color': settings.buttonColor,
            'border-radius': settings.borderRadius
        });

        // تحديث العناوين
        $('.preview-field label').css({
            'color': settings.textColor
        });

        // تطبيق التأثيرات على العناصر التفاعلية
        $('.preview-input, .preview-select').hover(
            function() { $(this).css('border-color', settings.primaryColor); },
            function() { $(this).css('border-color', settings.borderColor); }
        );

        // تحديث متغيرات CSS
        document.documentElement.style.setProperty('--primary-color', settings.primaryColor);
        document.documentElement.style.setProperty('--button-color', settings.buttonColor);
        document.documentElement.style.setProperty('--text-color', settings.textColor);
        document.documentElement.style.setProperty('--background-color', settings.backgroundColor);
        document.documentElement.style.setProperty('--border-color', settings.borderColor);
        document.documentElement.style.setProperty('--border-radius', settings.borderRadius);
    }

    // تحديث المعاينة عند تغيير أي إعداد
    $('input[name^="design"], select[name^="design"]').on('change input', function() {
        updatePreview();
    });

    // تحديث إظهار/إخفاء الحقول في المعاينة
    $('input[name^="field_visibility"]').on('change', function() {
        const fieldName = $(this).attr('name').match(/\[(.*?)\]/)[1];
        const isChecked = $(this).prop('checked');
        
        // تحديث ظهور الحقل في المعاينة
        $(`.preview-field[data-field="${fieldName}"]`).toggle(isChecked);
    });

    // تحديث المعاينة عند تحميل الصفحة
    updatePreview();

    // تحديث المعاينة عند تغيير التبويب
    $('.settings-tab').on('click', function() {
        setTimeout(updatePreview, 100);
    });

    // إضافة تأثيرات تفاعلية للمعاينة
    $('.preview-button').hover(
        function() { $(this).css('opacity', '0.9'); },
        function() { $(this).css('opacity', '1'); }
    );

    // تحديث حجم الخط والمسافات
    $('input[name="design[fontSize]"]').on('input', function() {
        const fontSize = $(this).val() + 'px';
        $('.form-preview').css('font-size', fontSize);
    });

    // معالجة التغييرات في سعر الشحن الثابت
    $('input[name="shipping_settings[fixed_price]"]').on('input', function() {
        const price = $(this).val();
        $('.preview-shipping-price').text(price + ' د.ج');
    });
});
