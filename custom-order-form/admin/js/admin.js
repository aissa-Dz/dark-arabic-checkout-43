jQuery(document).ready(function($) {
    // تهيئة منتقي الألوان
    $('.color-picker').wpColorPicker();

    // تحديث إعدادات الشحن
    $('input[name="field_visibility[show_state]"]').on('change', function() {
        const fixedShippingField = $('.fixed-shipping-price');
        if (!$(this).is(':checked')) {
            fixedShippingField.slideDown();
        } else {
            fixedShippingField.slideUp();
        }
    });

    // تحديث الإعدادات
    $('#custom-order-form-settings').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'save_form_settings');
        formData.append('nonce', customOrderFormAdmin.nonce);

        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true);

        $.ajax({
            url: customOrderFormAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                }
            },
            error: function() {
                showMessage('error', 'حدث خطأ أثناء حفظ الإعدادات');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });

    // حذف الطلب المتروك
    $('.delete-abandoned-order').on('click', function() {
        const button = $(this);
        const orderId = button.data('id');
        
        if (confirm('هل أنت متأكد من حذف هذا الطلب؟')) {
            $.ajax({
                url: customOrderFormAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_abandoned_order',
                    nonce: customOrderFormAdmin.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut(function() {
                            $(this).remove();
                            if ($('.abandoned-orders tbody tr').length === 0) {
                                $('.abandoned-orders table').replaceWith('<p>لا توجد طلبات متروكة حالياً.</p>');
                            }
                        });
                        showMessage('success', 'تم حذف الطلب بنجاح');
                    }
                },
                error: function() {
                    showMessage('error', 'حدث خطأ أثناء حذف الطلب');
                }
            });
        }
    });

    // إضافة حظر جديد
    $('.add-block-button').on('click', function() {
        const type = $('#blockType').val();
        const value = $('#blockValue').val().trim();
        const reason = $('#blockReason').val().trim();

        if (!value) {
            showMessage('error', 'يرجى إدخال قيمة للحظر');
            return;
        }

        // التحقق من صحة القيمة
        if (type === 'ip' && !isValidIP(value)) {
            showMessage('error', 'عنوان IP غير صالح');
            return;
        } else if (type === 'phone' && !isValidPhone(value)) {
            showMessage('error', 'رقم الهاتف غير صالح');
            return;
        }

        $.ajax({
            url: customOrderFormAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'add_block_item',
                nonce: customOrderFormAdmin.nonce,
                type: type,
                value: value,
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // تحديث الصفحة لعرض العنصر المحظور الجديد
                }
            },
            error: function() {
                showMessage('error', 'حدث خطأ أثناء إضافة الحظر');
            }
        });
    });

    // إلغاء الحظر
    $('.remove-block-button').on('click', function() {
        const button = $(this);
        const type = button.data('type');
        const value = button.data('value');

        if (confirm('هل أنت متأكد من إلغاء هذا الحظر؟')) {
            $.ajax({
                url: customOrderFormAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'remove_block_item',
                    nonce: customOrderFormAdmin.nonce,
                    type: type,
                    value: value
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut(function() {
                            $(this).remove();
                            if ($('.blocked-list tbody tr').length === 0) {
                                $('.blocked-list table').replaceWith('<p>لا توجد عناصر محظورة حالياً.</p>');
                            }
                        });
                        showMessage('success', 'تم إلغاء الحظر بنجاح');
                    }
                },
                error: function() {
                    showMessage('error', 'حدث خطأ أثناء إلغاء الحظر');
                }
            });
        }
    });

    // التحقق من صحة عنوان IP
    function isValidIP(ip) {
        const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
        if (!ipRegex.test(ip)) return false;
        const parts = ip.split('.');
        return parts.every(part => parseInt(part) >= 0 && parseInt(part) <= 255);
    }

    // التحقق من صحة رقم الهاتف
    function isValidPhone(phone) {
        // يمكن تعديل هذا النمط حسب تنسيق أرقام الهواتف المطلوب
        return /^\d{8,15}$/.test(phone.replace(/[\s\-\+]/g, ''));
    }

    // تبديل علامات التبويب
    $('.settings-tab').on('click', function() {
        const tabId = $(this).data('tab');
        
        $('.settings-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.settings-panel').removeClass('active');
        $('#' + tabId + '-panel').addClass('active');

        // تحديث URL مع التبويب النشط
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('tab', tabId);
        window.history.replaceState({}, '', `${window.location.pathname}?${urlParams}`);
    });

    // تحريك زر الحفظ إلى الأسفل عند التمرير
    const saveButton = $('.button.button-primary');
    const originalPosition = saveButton.offset()?.top;

    if (originalPosition) {
        $(window).on('scroll', function() {
            const scrollPosition = $(window).scrollTop();
            if (scrollPosition > originalPosition) {
                saveButton.addClass('sticky-save');
            } else {
                saveButton.removeClass('sticky-save');
            }
        });
    }

    // عرض رسائل النجاح والخطأ
    function showMessage(type, message) {
        const notice = $(`<div class="orders-message ${type}"><p>${message}</p></div>`);
        $('.wrap.custom-order-form-settings h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    // تحديد التبويب النشط من URL
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab) {
        $(`.settings-tab[data-tab="${activeTab}"]`).click();
    }
});
