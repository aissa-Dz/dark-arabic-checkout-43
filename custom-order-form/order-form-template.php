<?php
$field_visibility = get_option('custom_order_form_field_visibility', array(
    'show_address' => true,
    'show_state' => true,
    'show_municipality' => true,
    'show_country' => false
));

$whatsapp_settings = get_option('custom_order_form_whatsapp_settings', array(
    'number' => '',
    'enabled' => true
));

$shipping_settings = get_option('custom_order_form_shipping_settings', array(
    'fixed_price' => 0,
    'use_fixed_price' => false
));

$form_settings = array(
    'fieldVisibility' => $field_visibility,
    'whatsapp_number' => $whatsapp_settings['number'],
    'whatsapp_enabled' => $whatsapp_settings['enabled'],
    'shipping' => $shipping_settings
);
?>
<?php
$spam_settings = get_option('custom_order_form_spam_settings', array(
    'disable_autocomplete' => false,
    'disable_copy_paste' => false,
    'limit_orders' => false,
    'save_abandoned' => true
));

// التحقق من حد الطلبات اليومي
$can_order = true;
$error_message = '';
if ($spam_settings['limit_orders']) {
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $last_order = get_transient('last_order_' . $user_ip);
    if ($last_order) {
        $can_order = false;
        $time_left = human_time_diff(time(), $last_order + (24 * HOUR_IN_SECONDS));
        $error_message = sprintf('عذراً، يمكنك إرسال طلب جديد بعد %s', $time_left);
    }
}
?>
<div class="custom-order-form-container">
    <?php if (!$can_order): ?>
    <div class="order-limit-message">
        <?php echo esc_html($error_message); ?>
    </div>
    <?php endif; ?>
    
    <form id="orderForm" class="custom-order-form needs-validation" 
          <?php if ($spam_settings['disable_autocomplete']): ?>
          autocomplete="off" 
          autocorrect="off" 
          autocapitalize="off" 
          spellcheck="false"
          <?php endif; ?>
          novalidate>
        <?php if ($spam_settings['disable_autocomplete']): ?>
        <!-- Trick browsers into disabling autofill -->
        <input type="text" style="display:none" name="fakeusernameremembered"/>
        <input type="password" style="display:none" name="fakepasswordremembered"/>
        <?php endif; ?>
        <h2>
            <i class="fas fa-shopping-cart"></i>
            <?php echo esc_html(get_option('custom_order_form_title', 'أضف معلوماتك في الأسفل لطلب هذا المنتج')); ?>
        </h2>

        <?php if ($has_variations): ?>
        <div class="variations-group">
            <label>خيارات المنتج:</label>
            <?php foreach ($variations as $attribute_name => $options): 
                $attribute_id = 'attribute_' . sanitize_title($attribute_name);
                $is_color = (strpos(strtolower($attribute_name), 'color') !== false || 
                           strpos(strtolower($attribute_name), 'colour') !== false || 
                           strpos(strtolower($attribute_name), 'لون') !== false);
            ?>
            <div class="form-group variation-group">
                <label><?php echo wc_attribute_label($attribute_name); ?></label>
                <div class="variation-options">
                    <input type="hidden" name="<?php echo esc_attr($attribute_id); ?>" class="variation-select" required>
                    <?php foreach ($options as $option): 
                        $option_id = $attribute_id . '_' . sanitize_title($option);
                        if ($is_color):
                            $color_settings = get_option('custom_order_form_color_settings', array());
                            $color = $option;
                            $color_map = array_merge([
                                'أحمر' => '#ff0000',
                                'أخضر' => '#00ff00',
                                'أزرق' => '#0000ff',
                                'أسود' => '#000000',
                                'أبيض' => '#ffffff',
                                'أصفر' => '#ffff00',
                                'برتقالي' => '#ffa500',
                                'بني' => '#a52a2a',
                                'رمادي' => '#808080',
                                'ذهبي' => '#ffd700',
                                'فضي' => '#c0c0c0',
                                'وردي' => '#ffc0cb',
                                'بنفسجي' => '#800080'
                            ], $color_settings);
                            
                            if (isset($color_map[$option])) {
                                $color = $color_map[$option];
                            }
                    ?>
                        <div class="swatch-option color-swatch" 
                             data-value="<?php echo esc_attr($option); ?>"
                             data-attribute="<?php echo esc_attr($attribute_id); ?>"
                             style="background-color: <?php echo esc_attr($color); ?>">
                            <span class="swatch-label"><?php echo esc_html($option); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="swatch-option text-swatch" 
                             data-value="<?php echo esc_attr($option); ?>"
                             data-attribute="<?php echo esc_attr($attribute_id); ?>">
                            <?php echo esc_html($option); ?>
                        </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <div class="input-group">
                <input type="text" 
                       id="fullName" 
                       name="fullName" 
                       class="form-control" 
                       placeholder="الاسم بالكامل"
                       <?php if ($spam_settings['disable_autocomplete']): ?>
                       autocomplete="off"
                       autocorrect="off"
                       autocapitalize="off"
                       data-form-type="other"
                       data-lpignore="true"
                       readonly
                       onfocus="this.removeAttribute('readonly');"
                       <?php endif; ?>
                       required>
                <span class="input-group-text">
                    <i class="fas fa-user"></i>
                </span>
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       class="form-control" 
                       placeholder="رقم الهاتف"
                       <?php if ($spam_settings['disable_autocomplete']): ?>
                       autocomplete="off"
                       autocorrect="off"
                       autocapitalize="off"
                       data-form-type="other"
                       data-lpignore="true"
                       readonly
                       onfocus="this.removeAttribute('readonly');"
                       <?php endif; ?>
                       required>
                <span class="input-group-text">
                    <i class="fas fa-phone-alt"></i>
                </span>
            </div>
        </div>

        <?php if ($field_visibility['show_country']): ?>
        <div class="form-group">
            <div class="input-group">
                <input type="text" 
                       id="country" 
                       name="country" 
                       class="form-control" 
                       placeholder="الدولة"
                       required>
                <span class="input-group-text">
                    <i class="fas fa-globe"></i>
                </span>
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <input type="text" 
                       id="city" 
                       name="city" 
                       class="form-control" 
                       placeholder="المدينة"
                       required>
                <span class="input-group-text">
                    <i class="fas fa-city"></i>
                </span>
            </div>
        </div>
        <?php else: ?>
        <?php if (isset($field_visibility['show_state']) && $field_visibility['show_state']): ?>
        <div class="form-group">
            <div class="input-group">
                <select id="country" 
                        name="country" 
                        class="form-select"
                        <?php if ($spam_settings['disable_autocomplete']): ?>
                        autocomplete="off"
                        data-form-type="other"
                        data-lpignore="true"
                        <?php endif; ?>
                        required>
                    <option value="">اختر الولاية</option>
                </select>
                <span class="input-group-text">
                    <i class="fas fa-map-marker-alt"></i>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($field_visibility['show_municipality']) && $field_visibility['show_municipality']): ?>
        <div class="form-group">
            <div class="input-group">
                <select id="city" 
                        name="city" 
                        class="form-select"
                        <?php if ($spam_settings['disable_autocomplete']): ?>
                        autocomplete="off"
                        data-form-type="other"
                        data-lpignore="true"
                        <?php endif; ?>
                        required>
                    <option value="">اختر البلدية</option>
                </select>
                <span class="input-group-text">
                    <i class="fas fa-city"></i>
                </span>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="form-group" id="addressGroup" style="<?php echo (!isset($field_visibility['show_address']) || !$field_visibility['show_address']) ? 'display: none;' : ''; ?>">
            <div class="input-group">
                <input type="text" 
                       id="address" 
                       name="address" 
                       class="form-control" 
                       placeholder="العنوان بالتفصيل"
                       <?php if ($spam_settings['disable_autocomplete']): ?>
                       autocomplete="off"
                       autocorrect="off"
                       autocapitalize="off"
                       data-form-type="other"
                       data-lpignore="true"
                       readonly
                       onfocus="this.removeAttribute('readonly');"
                       <?php endif; ?>
                       required>
                <span class="input-group-text">
                    <i class="fas fa-home"></i>
                </span>
            </div>
        </div>

        <div class="delivery-type-group">
            <label>نوع التوصيل:</label>
            <div class="btn-group">
                <input type="radio" class="btn-check" name="delivery_type" id="home_delivery" value="home" checked required>
                <label class="btn" for="home_delivery">
                    <i class="fas fa-home"></i>
                    التوصيل للمنزل
                </label>

                <input type="radio" class="btn-check" name="delivery_type" id="office_delivery" value="office" required>
                <label class="btn" for="office_delivery">
                    <i class="fas fa-building"></i>
                    التوصيل للمكتب
                </label>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="custom-quantity-control">
                <button type="button" id="decreaseQuantity">
                    <i class="fas fa-minus"></i>
                </button>
                <input type="number" id="quantity" name="quantity" value="1" min="1">
                <button type="button" id="increaseQuantity">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <button id="confirmOrder" type="submit" class="btn btn-primary flex-grow-1">
                <span id="confirmOrderText">تأكيد الطلب</span>
                <span id="confirmOrderLoading" class="d-none">
                    <i class="fas fa-spinner fa-spin"></i>
                    جاري التأكيد...
                </span>
            </button>
        </div>

        <?php if ($form_settings['whatsapp_enabled']): ?>
            <button type="button" class="btn btn-success w-100" onclick="orderViaWhatsApp()">
                <i class="fab fa-whatsapp"></i>
                طلب عبر الواتساب
            </button>
        <?php endif; ?>

        <div class="order-summary">
            <h3 id="toggleSummary">
                ملخص الطلب
                <i class="fas fa-chevron-down"></i>
            </h3>
            <div id="summaryContent">
                <p>
                    <span><i class="fas fa-box"></i> <?php echo get_the_title(); ?></span>
                </p>
                <p>
                    <span><i class="fas fa-tag"></i> سعر المنتج: </span>
                    <span><?php echo $product->is_type('variable') ? $product->get_variation_regular_price('min') : $product->get_price(); ?> د.ج</span>
                </p>
                <p>
                    <span><i class="fas fa-truck"></i> سعر التوصيل: </span>
                    <span id="shippingPrice">0 د.ج</span>
                </p>
                <p>
                    <span><i class="fas fa-calculator"></i> السعر الإجمالي: </span>
                    <span id="totalPrice">0 د.ج</span>
                </p>
            </div>
        </div>

        <input type="hidden" id="basePrice" value="<?php echo $product->is_type('variable') ? $product->get_variation_regular_price('min') : $product->get_price(); ?>">
        <input type="hidden" id="variableProduct" value="<?php echo $product->is_type('variable') ? '1' : '0'; ?>">
        <input type="hidden" id="hasVariations" value="<?php echo $has_variations ? '1' : '0'; ?>">
        <input type="hidden" id="productName" value="<?php echo esc_attr(get_the_title()); ?>">
        <div id="paypal-button-container"></div>
    </form>
</div>

<script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD"></script>
<script>
paypal.Buttons({
    createOrder: function(data, actions) {
        // جمع بيانات النموذج
        const formData = new FormData(document.getElementById('orderForm'));
        const total = document.getElementById('totalPrice').textContent.replace(/[^\d.]/g, '');
        
        return actions.order.create({
            purchase_units: [{
                amount: {
                    value: total
                }
            }]
        });
    },
    onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
            // إرسال الطلب إلى ووكومرس
            const formData = new FormData(document.getElementById('orderForm'));
            formData.append('action', 'place_custom_order');
            formData.append('payment_method', 'paypal');
            formData.append('payment_id', details.id);
            
            fetch(woocommerce_params.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.redirect_url) {
                    window.location.href = data.data.redirect_url;
                }
            });
        });
    }
}).render('#paypal-button-container');
</script>
