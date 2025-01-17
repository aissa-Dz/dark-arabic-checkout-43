// تطبيق إعدادات الحقول والتنسيق
function applyFormSettings() {
    const settings = window.woocommerce_params?.form_settings || {};
    const fieldVisibility = settings.fieldVisibility || {
        show_address: true,
        show_state: true,
        show_municipality: true
    };

    // تطبيق إظهار/إخفاء الحقول
    const addressField = document.querySelector('.form-group:has(#address)');
    const stateField = document.querySelector('.form-group:has(#country)');
    const municipalityField = document.querySelector('.form-group:has(#city)');

    if (addressField) {
        addressField.style.display = fieldVisibility.show_address ? 'block' : 'none';
    }
    if (stateField) {
        stateField.style.display = fieldVisibility.show_state ? 'block' : 'none';
    }
    if (municipalityField) {
        municipalityField.style.display = fieldVisibility.show_municipality ? 'block' : 'none';
    }

    // تحديث سعر الشحن الثابت إذا كان حقل الولاية مخفياً
    if (!fieldVisibility.show_state && settings.shipping?.fixed_price) {
        const shippingPriceElement = document.getElementById('shippingPrice');
        if (shippingPriceElement) {
            shippingPriceElement.textContent = parseFloat(settings.shipping.fixed_price).toFixed(2) + ' د.ج';
            updateTotalPrice();
        }
    }
}

// تطبيق إعدادات الواتساب
function applyWhatsAppSettings() {
    const settings = window.woocommerce_params?.form_settings || {};
    const whatsappButton = document.querySelector('.whatsapp-order-btn');
    if (whatsappButton) {
        whatsappButton.style.display = settings.whatsapp_enabled ? 'block' : 'none';
    }
}

// إرسال الطلب عبر واتساب
function orderViaWhatsApp() {
    const settings = window.woocommerce_params?.form_settings || {};
    const whatsappNumber = settings.whatsapp_number;
    
    if (!whatsappNumber) {
        alert('عذراً، رقم الواتساب غير متوفر حالياً');
        return;
    }

    const fullName = document.getElementById('fullName').value;
    if (!fullName) {
        alert('الرجاء إدخال الاسم');
        return;
    }

    const phone = document.getElementById('phone').value;
    if (!phone) {
        alert('الرجاء إدخال رقم الهاتف');
        return;
    }

    const address = document.getElementById('address')?.value || '';
    const country = document.getElementById('country')?.value || '';
    const city = document.getElementById('city')?.value || '';
    const quantity = document.getElementById('quantity').value;
    const productName = document.getElementById('productName').value;
    const deliveryType = document.querySelector('input[name="delivery_type"]:checked').value;
    const total = document.getElementById('totalPrice').textContent;

    let message = `*طلب جديد*%0a`;
    message += `المنتج: ${productName}%0a`;
    message += `الكمية: ${quantity}%0a`;
    message += `الاسم: ${fullName}%0a`;
    message += `الهاتف: ${phone}%0a`;
    
    const fieldVisibility = settings.fieldVisibility || {};

    if (fieldVisibility.show_state && country) {
        message += `الولاية: ${country}%0a`;
    }
    if (fieldVisibility.show_municipality && city) {
        message += `البلدية: ${city}%0a`;
    }
    if (fieldVisibility.show_address && address) {
        message += `العنوان: ${address}%0a`;
    }

    message += `نوع التوصيل: ${deliveryType === 'home' ? 'للمنزل' : 'للمكتب'}%0a`;
    message += `السعر الإجمالي: ${total}%0a`;

    window.open(`https://wa.me/${whatsappNumber}?text=${message}`, '_blank');
}

// تهيئة النموذج عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    if (!window.woocommerce_params?.form_settings) {
        console.error('Form settings not found');
        return;
    }

    applyFormSettings();
    applyWhatsAppSettings();
    
    // إضافة معالج النقر لزر الواتساب
    const whatsappButton = document.querySelector('.whatsapp-order-btn');
    if (whatsappButton) {
        whatsappButton.onclick = orderViaWhatsApp;
    }
});
