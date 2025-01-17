<div class="custom-order-form-container">
    <form id="orderForm" class="custom-order-form needs-validation" novalidate>
        <div class="step-number">
            <span>01</span>
            <span>للطلب، يرجى ادخال معلوماتك هنا:</span>
        </div>

        <div class="input-group">
            <input type="text" id="fullName" name="fullName" placeholder="الاسم الكامل" required>
            <i class="fas fa-user"></i>
        </div>

        <div class="input-group">
            <input type="tel" id="phone" name="phone" placeholder="رقم الهاتف" required>
            <i class="fas fa-phone"></i>
        </div>

        <div class="input-group">
            <select id="country" name="country" required>
                <option value="">اختر الولاية</option>
            </select>
            <i class="fas fa-map-marker-alt"></i>
        </div>

        <div class="input-group">
            <select id="city" name="city" required>
                <option value="">اختر البلدية</option>
            </select>
            <i class="fas fa-city"></i>
        </div>

        <div class="quantity-section">
            <span class="quantity-label">الكمية</span>
            <div class="quantity-controls">
                <button type="button" id="decreaseQuantity">-</button>
                <input type="number" id="quantity" name="quantity" value="1" min="1">
                <button type="button" id="increaseQuantity">+</button>
            </div>
        </div>

        <div class="delivery-section">
            <h3>
                <i class="fas fa-truck"></i>
                طريقة التوصيل
            </h3>
            <p class="delivery-note">قم باختيار ولايتك لتظهر لك تكاليف التوصيل (تختلف حسب الولاية)</p>
            
            <div class="delivery-options">
                <label class="delivery-option">
                    <span>
                        <input type="radio" name="delivery_type" value="home" checked>
                        التوصيل للمنزل
                    </span>
                    <span class="delivery-option-price">DZD550.00</span>
                </label>

                <label class="delivery-option">
                    <span>
                        <input type="radio" name="delivery_type" value="office">
                        التوصيل لمكتب شركة التوصيل
                    </span>
                    <span class="delivery-option-price">DZD250.00</span>
                </label>
            </div>
        </div>

        <div class="total-section">
            <span class="total-label">
                <i class="fas fa-calculator"></i>
                إجمالي المبلغ:
            </span>
            <span class="total-amount">DZD2,750.00</span>
        </div>

        <button type="submit" class="submit-button">
            اشتري الآن - الدفع عند الاستلام
        </button>

        <?php if ($form_settings['whatsapp_enabled']): ?>
        <button type="button" class="whatsapp-button" onclick="orderViaWhatsApp()">
            <i class="fab fa-whatsapp"></i>
            سارع بالطلب الآن
        </button>
        <?php endif; ?>
    </form>
</div>