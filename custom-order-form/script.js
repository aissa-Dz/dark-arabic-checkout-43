// تطبيق التنسيق المخصص
function applyCustomStyles() {
    const settings = window.formSettings || {};
    const design = settings.design || {};
    
    const formContainer = document.querySelector('.custom-order-form-container');
    if (formContainer) {
        Object.keys(design).forEach(key => {
            if (design[key]) {
                switch (key) {
                    case 'backgroundColor':
                        formContainer.style.backgroundColor = design[key];
                        break;
                    case 'textColor':
                        formContainer.style.color = design[key];
                        break;
                    case 'borderColor':
                        const inputs = formContainer.querySelectorAll('input, select');
                        inputs.forEach(input => {
                            input.style.borderColor = design[key];
                        });
                        break;
                    case 'borderRadius':
                        const elements = formContainer.querySelectorAll('input, select, button, .order-summary');
                        elements.forEach(el => {
                            el.style.borderRadius = design[key] + 'px';
                        });
                        break;
                    default:
                        break;
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // تحميل إعدادات منع السبام
    const spamSettings = window.woocommerce_params?.form_settings?.spam_settings || {};
    
    // التحقق من العملاء المحظورين وحفظ الطلبات المتروكة
    const fullNameInput = document.getElementById('fullName');
    const phoneInput = document.getElementById('phone');
    let saveTimeout;

    async function checkBlockedCustomer(phone) {
        const formData = new FormData();
        formData.append('action', 'check_customer_block');
        formData.append('phone', phone);

        try {
            const response = await fetch(window.woocommerce_params.ajax_url, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            return data.success && data.data.blocked;
        } catch (error) {
            console.error('Error checking blocked status:', error);
            return false;
        }
    }

    function saveAbandonedOrder() {
        if (fullNameInput.value && phoneInput.value) {
            const abandonedOrder = {
                fullName: fullNameInput.value,
                phone: phoneInput.value,
                address: document.getElementById('address')?.value || '',
                country: document.getElementById('country')?.value || '',
                city: document.getElementById('city')?.value || '',
                productName: document.getElementById('productName').value,
                quantity: document.getElementById('quantity').value,
                date: new Date().toLocaleString('ar')
            };

            const formData = new FormData();
            formData.append('action', 'save_abandoned_order');
            formData.append('order_data', JSON.stringify(abandonedOrder));

            navigator.sendBeacon(window.woocommerce_params.ajax_url, formData);
        }
    }

    // التحقق من الحظر وحفظ الطلب المتروك
    phoneInput.addEventListener('blur', async function() {
        if (this.value) {
            const isBlocked = await checkBlockedCustomer(this.value);
            if (isBlocked) {
                showError(this, 'عذراً، لا يمكنك إرسال طلبات في الوقت الحالي');
                this.value = '';
            } else if (fullNameInput.value) {
                saveAbandonedOrder();
            }
        }
    });

    fullNameInput.addEventListener('blur', function() {
        if (this.value && phoneInput.value) {
            saveAbandonedOrder();
        }
    });

    // حفظ الطلب المتروك عند تغيير أي حقل آخر
    const otherInputs = document.querySelectorAll('#address, #country, #city, #quantity');
    otherInputs.forEach(input => {
        input.addEventListener('change', () => {
            if (fullNameInput.value && phoneInput.value) {
                saveAbandonedOrder();
            }
        });
    });
    
    // منع النسخ واللصق إذا كان مفعلاً
    if (spamSettings.disable_copy_paste) {
        const formInputs = document.querySelectorAll('.custom-order-form input, .custom-order-form select');
        formInputs.forEach(input => {
            input.addEventListener('copy', e => e.preventDefault());
            input.addEventListener('paste', e => e.preventDefault());
            input.addEventListener('cut', e => e.preventDefault());
        });
    }

    // حفظ الطلبات المتروكة
    if (spamSettings.save_abandoned) {
        const formInputs = document.querySelectorAll('.custom-order-form input, .custom-order-form select');
        let formData = {};
        
        // استرجاع البيانات المحفوظة
        const savedData = localStorage.getItem('abandoned_order');
        if (savedData) {
            try {
                formData = JSON.parse(savedData);
                // ملء النموذج بالبيانات المحفوظة
                Object.keys(formData).forEach(key => {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = formData[key];
                    }
                });
            } catch (e) {
                console.error('Error loading saved data:', e);
            }
        }

        // حفظ البيانات عند الكتابة
        formInputs.forEach(input => {
            input.addEventListener('change', () => {
                formData[input.name] = input.value;
                localStorage.setItem('abandoned_order', JSON.stringify(formData));
            });
        });

        // مسح البيانات المحفوظة عند نجاح الطلب
        const orderForm = document.getElementById('orderForm');
        if (orderForm) {
            orderForm.addEventListener('submit', () => {
                localStorage.removeItem('abandoned_order');
            });
        }
    }

    // تهيئة المتغيرات الأساسية
    const countrySelect = document.getElementById('country');
    const citySelect = document.getElementById('city');
    const shippingPriceElement = document.getElementById('shippingPrice');
    const totalPriceElement = document.getElementById('totalPrice');
    const deliveryOptions = document.querySelectorAll('input[name="delivery_type"]');
    const quantityInput = document.getElementById('quantity');
    const increaseButton = document.getElementById('increaseQuantity');
    const decreaseButton = document.getElementById('decreaseQuantity');
    const basePrice = parseFloat(document.getElementById('basePrice').value) || 0;
    const hasVariations = document.getElementById('hasVariations').value === '1';
    const variationsContainer = document.getElementById('variationsContainer');

    let selectedDeliveryType = 'home';
    let currentProductPrice = basePrice;

    // تحديث سعر الشحن
    function updateShippingPrice() {
        const settings = window.woocommerce_params.form_settings || {};
        let shippingPrice = 0;
        
        // استخدام السعر الثابت إذا كان حقل الولاية مخفي
        if (!settings.fieldVisibility?.show_state) {
            shippingPrice = parseFloat(settings.shipping?.fixed_price || 0);
        } else {
            const selectedState = countrySelect?.value;
            if (selectedState && window.shippingPrices && window.shippingPrices[selectedState]) {
                shippingPrice = window.shippingPrices[selectedState][selectedDeliveryType];
            }
        }
        
        shippingPriceElement.textContent = shippingPrice.toFixed(2) + ' د.ج';
        updateTotalPrice();
    }

    // تحديث السعر الإجمالي
    function updateTotalPrice() {
        const shippingPrice = parseFloat(shippingPriceElement.textContent) || 0;
        const quantity = parseInt(quantityInput.value) || 1;
        const total = (currentProductPrice * quantity) + shippingPrice;
        totalPriceElement.textContent = total.toFixed(2) + ' د.ج';
    }

    // معالجة أزرار الكمية
    if (increaseButton) {
        increaseButton.addEventListener('click', function() {
            let currentQuantity = parseInt(quantityInput.value) || 1;
            quantityInput.value = currentQuantity + 1;
            updateTotalPrice();
        });
    }

    if (decreaseButton) {
        decreaseButton.addEventListener('click', function() {
            let currentQuantity = parseInt(quantityInput.value) || 1;
            if (currentQuantity > 1) {
                quantityInput.value = currentQuantity - 1;
                updateTotalPrice();
            }
        });
    }

    // معالجة تغيير الولاية
    if (countrySelect) {
        countrySelect.addEventListener('change', function() {
            updateShippingPrice();
            
            // تحديث قائمة البلديات
            citySelect.innerHTML = '<option value="">اختر البلدية</option>';
            const selectedState = this.value;
            if (selectedState && window.algeriaStates && window.algeriaStates[selectedState]) {
                window.algeriaStates[selectedState].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        });
    }

    // معالجة تغيير نوع التوصيل
    const addressGroup = document.getElementById('addressGroup');
    const addressInput = document.getElementById('address');
    
    function updateAddressVisibility() {
        const isHomeDelivery = document.getElementById('home_delivery').checked;
        const settings = window.woocommerce_params.form_settings || {};
        const showAddress = settings.fieldVisibility?.show_address;
        
        if (isHomeDelivery && showAddress) {
            addressGroup.style.display = 'block';
            addressInput.required = true;
        } else {
            addressGroup.style.display = 'none';
            addressInput.required = false;
            addressInput.value = ''; // مسح القيمة عند الإخفاء
            // مسح أي رسائل خطأ
            clearError(addressInput);
        }
    }
    
    deliveryOptions.forEach(option => {
        option.addEventListener('change', function() {
            selectedDeliveryType = this.value;
            updateShippingPrice();
            updateAddressVisibility();
        });
    });

    // تحديد الحالة الأولية لحقل العنوان
    updateAddressVisibility();

    // معالجة المتغيرات إذا كانت موجودة
    if (hasVariations) {
        const variationSelects = document.querySelectorAll('.variation-select');
        const swatchOptions = document.querySelectorAll('.swatch-option');
        
        function updateVariationPrice() {
            const formData = new FormData();
            formData.append('action', 'get_variation_price');
            formData.append('product_id', window.woocommerce_params.product_id);
            
            // إضافة قيم المتغيرات المحددة
            variationSelects.forEach(select => {
                formData.append(select.name, select.value);
            });

            // إظهار حالة التحميل
            const productPriceElement = document.querySelector('#summaryContent p:nth-child(2) span:last-child');
            if (productPriceElement) {
                productPriceElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }

            // تحديث السعر
            fetch(window.woocommerce_params.ajax_url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.price) {
                    currentProductPrice = parseFloat(data.data.price);
                    // تحديث سعر المنتج في ملخص الطلب
                    if (productPriceElement) {
                        productPriceElement.textContent = currentProductPrice.toFixed(2) + ' د.ج';
                    }
                    updateTotalPrice();
                }
            })
            .catch(error => {
                console.error('Error fetching variation price:', error);
                // إعادة السعر السابق في حالة الخطأ
                if (productPriceElement) {
                    productPriceElement.textContent = currentProductPrice.toFixed(2) + ' د.ج';
                }
            });
        }

        // معالجة النقر على الخيارات
        swatchOptions.forEach(swatch => {
            swatch.addEventListener('click', () => {
                const attributeName = swatch.dataset.attribute;
                const value = swatch.dataset.value;
                const hiddenInput = document.querySelector(`input[name="${attributeName}"]`);
                
                // إزالة التحديد من باقي الخيارات في نفس المجموعة
                const siblings = document.querySelectorAll(`.swatch-option[data-attribute="${attributeName}"]`);
                siblings.forEach(sibling => sibling.classList.remove('selected'));
                
                // تحديد الخيار المختار
                swatch.classList.add('selected');
                
                // تحديث قيمة الحقل المخفي
                if (hiddenInput) {
                    hiddenInput.value = value;
                    // تشغيل حدث change لتحديث السعر
                    const event = new Event('change', { bubbles: true });
                    hiddenInput.dispatchEvent(event);
                }
            });
        });

        // تحديث السعر عند تغيير أي متغير
        let debounceTimer;
        variationSelects.forEach(select => {
            select.addEventListener('change', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(updateVariationPrice, 300);
            });
        });
    }

    // دوال التحقق من الحقول
    function showError(input, message) {
        const inputGroup = input.closest('.input-group');
        let errorDiv = input.closest('.form-group').querySelector('.error-message');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            input.closest('.form-group').appendChild(errorDiv);
        }
        
        inputGroup.classList.add('error');
        errorDiv.textContent = message;
    }

    function clearError(input) {
        const inputGroup = input.closest('.input-group');
        const errorDiv = input.closest('.form-group').querySelector('.error-message');
        
        if (inputGroup) {
            inputGroup.classList.remove('error');
        }
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    function validateField(input) {
        // تجاهل التحقق إذا كان الحقل مخفياً
        if (input.closest('.form-group').style.display === 'none') {
            return true;
        }

        clearError(input);

        if (!input.value.trim()) {
            showError(input, 'هذا الحقل مطلوب');
            return false;
        }

        if (input.id === 'phone') {
            const phoneRegex = /^[0-9]{9,}$/;
            if (!phoneRegex.test(input.value.trim())) {
                showError(input, 'يرجى إدخال رقم هاتف صحيح');
                return false;
            }
        }

        return true;
    }

    // معالجة تقديم النموذج
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        // التحقق من الحقول عند الكتابة
        const inputs = orderForm.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => {
                if (input.closest('.input-group').classList.contains('error')) {
                    validateField(input);
                }
            });
        });

        orderForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // التحقق من الحقول المطلوبة الظاهرة فقط
            let isValid = true;
            const requiredFields = this.querySelectorAll('input[required], select[required]');
            
            requiredFields.forEach(field => {
                const formGroup = field.closest('.form-group');
                // تجاهل الحقول المخفية
                if (formGroup && formGroup.style.display !== 'none') {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                }
            });

            if (!isValid) {
                return;
            }
            
            // تجميع بيانات النموذج
            const formData = new FormData(this);
            formData.append('action', 'place_custom_order');
            formData.append('product_id', window.woocommerce_params.product_id);
            
            // إضافة سعر الشحن
            const shippingText = shippingPriceElement.textContent.replace('د.ج', '').trim();
            const shippingPrice = parseFloat(shippingText) || 0;
            formData.append('shipping_cost', shippingPrice);

            // حفظ الطلب المتروك إذا كان مفعل في الإعدادات
            const spamSettings = window.woocommerce_params?.form_settings?.spam_settings || {};
            if (spamSettings.save_abandoned) {
                const abandonedOrder = {
                    fullName: document.getElementById('fullName').value,
                    phone: document.getElementById('phone').value,
                    address: document.getElementById('address')?.value || '',
                    country: document.getElementById('country')?.value || '',
                    city: document.getElementById('city')?.value || '',
                    productName: document.getElementById('productName').value,
                    quantity: document.getElementById('quantity').value
                };

                // إرسال الطلب المتروك للخادم
                const abandonedFormData = new FormData();
                abandonedFormData.append('action', 'save_abandoned_order');
                abandonedFormData.append('order_data', JSON.stringify(abandonedOrder));

                navigator.sendBeacon(window.woocommerce_params.ajax_url, abandonedFormData);
            }
            
            const submitButton = document.getElementById('confirmOrder');
            const submitText = document.getElementById('confirmOrderText');
            const submitLoading = document.getElementById('confirmOrderLoading');
            
            // تغيير حالة الزر
            if (submitButton && submitText && submitLoading) {
                submitButton.disabled = true;
                submitText.style.display = 'none';
                submitLoading.classList.add('show');
            }

            fetch(window.woocommerce_params.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.redirect_url) {
                    window.location.href = data.data.redirect_url;
                } else {
                    alert('حدث خطأ أثناء إنشاء الطلب. يرجى المحاولة مرة أخرى.');
                    // إعادة الزر لحالته الأصلية
                    if (submitButton && submitText && submitLoading) {
                        submitButton.disabled = false;
                        submitText.style.display = 'block';
                        submitLoading.classList.remove('show');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إنشاء الطلب. يرجى المحاولة مرة أخرى.');
                // إعادة الزر لحالته الأصلية
                    if (submitButton && submitText && submitLoading) {
                        submitButton.disabled = false;
                        submitText.style.display = 'block';
                        submitLoading.classList.remove('show');
                    }
            });
        });
    }

    // معالجة زر الواتساب
    window.orderViaWhatsApp = function() {
        const settings = window.woocommerce_params.form_settings || {};
        const whatsappNumber = settings.whatsapp_number;
        
        if (!whatsappNumber) {
            alert('عذراً، رقم الواتساب غير متوفر حالياً');
            return;
        }

        // التحقق من الحقول الظاهرة فقط قبل فتح الواتساب
        let isValid = true;
        const requiredFields = orderForm.querySelectorAll('input[required], select[required]');
        
        requiredFields.forEach(field => {
            const formGroup = field.closest('.form-group');
            // تجاهل الحقول المخفية
            if (formGroup && formGroup.style.display !== 'none') {
                if (!validateField(field)) {
                    isValid = false;
                }
            }
        });

        if (!isValid) {
            return;
        }

        const fullName = document.getElementById('fullName').value;
        const phone = document.getElementById('phone').value;
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
        
        if (country) message += `الولاية: ${country}%0a`;
        if (city) message += `البلدية: ${city}%0a`;
        if (address) message += `العنوان: ${address}%0a`;
        
        message += `نوع التوصيل: ${deliveryType === 'home' ? 'للمنزل' : 'للمكتب'}%0a`;
        message += `السعر الإجمالي: ${total}%0a`;

        window.open(`https://wa.me/${whatsappNumber}?text=${message}`, '_blank');
    };

    // تحديث الأسعار وتطبيق التنسيق عند تحميل الصفحة
    updateShippingPrice();
    updateTotalPrice();
    applyCustomStyles();

    // معالجة ملخص الطلب
    const toggleSummary = document.getElementById('toggleSummary');
    if (toggleSummary) {
        toggleSummary.addEventListener('click', function() {
            const content = document.getElementById('summaryContent');
            const icon = this.querySelector('i');
            if (content && icon) {
                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    icon.classList.add('rotated');
                } else {
                    content.style.display = 'none';
                    icon.classList.remove('rotated');
                }
            }
        });
    }
});