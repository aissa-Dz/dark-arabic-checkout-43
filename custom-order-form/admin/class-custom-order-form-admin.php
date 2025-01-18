<?php
class Custom_Order_Form_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_save_form_settings', array($this, 'save_form_settings'));
        add_action('wp_ajax_delete_abandoned_order', array($this, 'delete_abandoned_order'));
        add_action('wp_ajax_add_block_item', array($this, 'add_block_item'));
        add_action('wp_ajax_remove_block_item', array($this, 'remove_block_item'));
        add_action('wp_ajax_nopriv_check_customer_block', array($this, 'check_customer_block'));
    }

    public function add_block_item() {
        check_ajax_referer('custom_order_form_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بتنفيذ هذا الإجراء');
            return;
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';

        if (!in_array($type, array('phone', 'ip')) || empty($value)) {
            wp_send_json_error('بيانات غير صالحة');
            return;
        }

        // التحقق من صحة القيمة
        if ($type === 'ip' && !filter_var($value, FILTER_VALIDATE_IP)) {
            wp_send_json_error('عنوان IP غير صالح');
            return;
        }

        $blocked_items = get_option('custom_order_form_blocked_items', array());

        // التحقق من وجود العنصر مسبقاً
        foreach ($blocked_items as $item) {
            if ($item['type'] === $type && $item['value'] === $value) {
                wp_send_json_error('هذا العنصر محظور مسبقاً');
                return;
            }
        }

        // إضافة العنصر الجديد
        $blocked_items[] = array(
            'type' => $type,
            'value' => $value,
            'reason' => $reason,
            'date' => current_time('mysql')
        );

        update_option('custom_order_form_blocked_items', $blocked_items);
        wp_send_json_success('تم إضافة الحظر بنجاح');
    }

    public function remove_block_item() {
        check_ajax_referer('custom_order_form_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بتنفيذ هذا الإجراء');
            return;
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';

        if (empty($type) || empty($value)) {
            wp_send_json_error('بيانات غير صالحة');
            return;
        }

        $blocked_items = get_option('custom_order_form_blocked_items', array());
        
        // البحث عن العنصر وحذفه
        foreach ($blocked_items as $key => $item) {
            if ($item['type'] === $type && $item['value'] === $value) {
                unset($blocked_items[$key]);
                update_option('custom_order_form_blocked_items', array_values($blocked_items));
                wp_send_json_success('تم إلغاء الحظر بنجاح');
                return;
            }
        }

        wp_send_json_error('لم يتم العثور على العنصر المحظور');
    }

    public function toggle_customer_block() {
        check_ajax_referer('custom_order_form_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بتنفيذ هذا الإجراء');
            return;
        }

        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $block = isset($_POST['block']) ? (bool)$_POST['block'] : false;

        if (!$phone) {
            wp_send_json_error('رقم الهاتف غير صالح');
            return;
        }

        $blocked_customers = get_option('custom_order_form_blocked_customers', array());

        if ($block) {
            if (!in_array($phone, $blocked_customers)) {
                $blocked_customers[] = $phone;
            }
        } else {
            $blocked_customers = array_diff($blocked_customers, array($phone));
        }

        update_option('custom_order_form_blocked_customers', array_values($blocked_customers));
        wp_send_json_success();
    }

    public function check_customer_block() {
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $ip = $_SERVER['REMOTE_ADDR'];

        if (!$phone && !$ip) {
            wp_send_json_error('بيانات غير صالحة');
            return;
        }

        $blocked_customers = get_option('custom_order_form_blocked_customers', array());
        $is_blocked = in_array($phone, $blocked_customers);

        if (!$is_blocked) {
            // التحقق من IP
            global $wpdb;
            $blocked_ips = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT pm_ip.meta_value
                FROM {$wpdb->postmeta} pm_ip
                JOIN {$wpdb->postmeta} pm_phone ON pm_phone.post_id = pm_ip.post_id
                WHERE pm_ip.meta_key = '_customer_ip_address'
                AND pm_phone.meta_key = '_billing_phone'
                AND pm_phone.meta_value IN ('" . implode("','", $blocked_customers) . "')
            "));

            $is_blocked = in_array($ip, $blocked_ips);
        }

        wp_send_json_success(array('blocked' => $is_blocked));
    }

    public function delete_abandoned_order() {
        check_ajax_referer('custom_order_form_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بتنفيذ هذا الإجراء');
            return;
        }

        $order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';
        if (!$order_id) {
            wp_send_json_error('معرف الطلب غير صالح');
            return;
        }

        $abandoned_orders = get_option('custom_order_form_abandoned_orders', array());
        
        // البحث عن الطلب وحذفه
        foreach ($abandoned_orders as $key => $order) {
            if ($order['id'] === $order_id) {
                unset($abandoned_orders[$key]);
                update_option('custom_order_form_abandoned_orders', array_values($abandoned_orders));
                wp_send_json_success('تم حذف الطلب بنجاح');
                return;
            }
        }

        wp_send_json_error('لم يتم العثور على الطلب');
    }

    public function add_admin_menu() {
        add_menu_page(
            'إعدادات فورم الطلب',
            'فورم الطلب',
            'manage_options',
            'custom-order-form',
            array($this, 'display_admin_page'),
            'dashicons-format-aside',
            30
        );
    }

    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_custom-order-form' !== $hook) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('custom-order-form-admin', plugin_dir_url(__FILE__) . 'css/admin.css', array(), $this->version);
        
        // Enqueue Google Fonts
        wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600&family=Tajawal:wght@400;500;700&family=Cairo:wght@400;600;700&display=swap', array());

        // Enqueue scripts
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('custom-order-form-admin', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery', 'wp-color-picker'), $this->version, true);
        wp_enqueue_script('settings-preview', plugin_dir_url(__FILE__) . 'js/settings-preview.js', array('jquery', 'wp-color-picker', 'custom-order-form-admin'), $this->version, true);
        
        // Localize scripts
        $settings = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('custom_order_form_admin_nonce'),
            'currentSettings' => array(
                'design' => get_option('custom_order_form_design', array(
                    'primaryColor' => '#2563eb',
                    'buttonColor' => '#2563eb',
                    'backgroundColor' => '#ffffff',
                    'textColor' => '#1f2937',
                    'borderColor' => '#e2e8f0',
                    'borderRadius' => '12',
                    'fontFamily' => 'IBM Plex Sans Arabic'
                )),
                'fieldVisibility' => get_option('custom_order_form_field_visibility', array(
                    'show_address' => true,
                    'show_state' => true,
                    'show_municipality' => true,
                    'show_country' => false // إضافة خيار حقل الدولة
                )),
                'shipping' => get_option('custom_order_form_shipping_settings', array(
                    'fixed_price' => 0,
                    'use_fixed_price' => false
                ))
            )
        );
        
        wp_localize_script('custom-order-form-admin', 'customOrderFormAdmin', $settings);
        wp_localize_script('settings-preview', 'formPreviewSettings', $settings);
    }

    public function display_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $field_labels = get_option('custom_order_form_field_labels', array(
            'fullName' => 'الاسم بالكامل',
            'phone' => 'رقم الهاتف',
            'address' => 'العنوان بالتفصيل',
            'state' => 'الولاية',
            'municipality' => 'البلدية'
        ));

        $form_title = get_option('custom_order_form_title', 'أضف معلوماتك في الأسفل لطلب هذا المنتج');

        $field_visibility = get_option('custom_order_form_field_visibility', array(
            'show_address' => true,
            'show_state' => true,
            'show_municipality' => true,
            'show_country' => false // إضافة خيار حقل الدولة
        ));

        $shipping_settings = get_option('custom_order_form_shipping_settings', array(
            'fixed_price' => 0,
            'use_fixed_price' => false
        ));

        $whatsapp_settings = get_option('custom_order_form_whatsapp_settings', array(
            'number' => '',
            'enabled' => true
        ));

        $button_settings = get_option('custom_order_form_button_settings', array(
            'show_sticky_button' => true,
            'button_text' => 'اشتري الآن'
        ));

        $spam_settings = get_option('custom_order_form_spam_settings', array(
            'disable_autocomplete' => false,
            'disable_copy_paste' => false,
            'limit_orders' => false,
            'save_abandoned' => true
        ));
        
        $design_settings = get_option('custom_order_form_design', array(
            'primaryColor' => '#2563eb',
            'buttonColor' => '#2563eb',
            'fontFamily' => 'IBM Plex Sans Arabic'
        ));
        ?>
        <div class="wrap custom-order-form-settings">
            <h1>إعدادات فورم الطلب</h1>
            
            <form id="custom-order-form-settings" method="post">
                <?php wp_nonce_field('custom_order_form_settings', 'custom_order_form_nonce'); ?>
                
                <div class="settings-tabs">
                    <button type="button" class="settings-tab active" data-tab="fields">الحقول</button>
                    <button type="button" class="settings-tab" data-tab="design">التصميم</button>
                    <button type="button" class="settings-tab" data-tab="orders">الطلبات</button>
                </div>

                <div class="settings-panel" id="orders-panel">
                    <h2>الطلبات المتروكة</h2>
                    <div class="abandoned-orders">
                        <?php
                        global $wpdb;
                        $abandoned_orders = get_option('custom_order_form_abandoned_orders', array());
                        if (!empty($abandoned_orders)): ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th>رقم الهاتف</th>
                                        <th>العنوان</th>
                                        <th>المنتج</th>
                                        <th>التاريخ</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($abandoned_orders as $order): ?>
                                    <tr>
                                        <td><?php echo esc_html($order['fullName'] ?? ''); ?></td>
                                        <td><?php echo esc_html($order['phone'] ?? ''); ?></td>
                                        <td><?php echo esc_html($order['address'] ?? ''); ?></td>
                                        <td><?php echo esc_html($order['productName'] ?? ''); ?></td>
                                        <td><?php echo esc_html($order['date'] ?? ''); ?></td>
                                        <td>
                                            <button class="button delete-abandoned-order" 
                                                    data-id="<?php echo esc_attr($order['id']); ?>">
                                                حذف
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>لا توجد طلبات متروكة حالياً.</p>
                        <?php endif; ?>
                    </div>

                    <h2>إدارة الحظر</h2>
                    <div class="block-management">
                        <div class="add-block-form">
                            <h3>إضافة حظر جديد</h3>
                            <div class="form-group">
                                <label>نوع الحظر</label>
                                <select id="blockType" class="form-select">
                                    <option value="phone">رقم الهاتف</option>
                                    <option value="ip">عنوان IP</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>القيمة</label>
                                <input type="text" id="blockValue" class="form-input" placeholder="أدخل رقم الهاتف أو عنوان IP">
                            </div>
                            <div class="form-group">
                                <label>سبب الحظر (اختياري)</label>
                                <input type="text" id="blockReason" class="form-input" placeholder="سبب الحظر">
                            </div>
                            <button type="button" class="button add-block-button">إضافة حظر</button>
                        </div>

                        <div class="blocked-list">
                            <h3>القائمة السوداء</h3>
                            <?php
                            $blocked_items = get_option('custom_order_form_blocked_items', array());
                            if (!empty($blocked_items)): ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>النوع</th>
                                            <th>القيمة</th>
                                            <th>تاريخ الحظر</th>
                                            <th>السبب</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($blocked_items as $item): ?>
                                        <tr>
                                            <td><?php echo $item['type'] === 'phone' ? 'رقم الهاتف' : 'عنوان IP'; ?></td>
                                            <td><?php echo esc_html($item['value']); ?></td>
                                            <td><?php echo esc_html($item['date']); ?></td>
                                            <td><?php echo esc_html($item['reason'] ?: '-'); ?></td>
                                            <td>
                                                <button class="button remove-block-button" 
                                                        data-type="<?php echo esc_attr($item['type']); ?>"
                                                        data-value="<?php echo esc_attr($item['value']); ?>">
                                                    إلغاء الحظر
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>لا توجد عناصر محظورة حالياً.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h2>قائمة الزبائن</h2>
                    <div class="customers-list">
                        <?php
                        $customers = $wpdb->get_results("
                            SELECT 
                                pm_name.meta_value as customer_name,
                                pm_phone.meta_value as phone,
                                COUNT(DISTINCT p.ID) as order_count,
                                MAX(p.post_date) as last_order,
                                GROUP_CONCAT(DISTINCT pm_ip.meta_value) as ip_addresses
                            FROM {$wpdb->postmeta} pm_name
                            JOIN {$wpdb->posts} p ON p.ID = pm_name.post_id
                            LEFT JOIN {$wpdb->postmeta} pm_phone ON pm_phone.post_id = p.ID AND pm_phone.meta_key = '_billing_phone'
                            LEFT JOIN {$wpdb->postmeta} pm_ip ON pm_ip.post_id = p.ID AND pm_ip.meta_key = '_customer_ip_address'
                            WHERE pm_name.meta_key = '_customer_full_name'
                            AND p.post_type = 'shop_order'
                            GROUP BY pm_name.meta_value, pm_phone.meta_value
                            ORDER BY last_order DESC
                        ");
                        
                        // الحصول على قائمة العملاء المحظورين
                        $blocked_customers = get_option('custom_order_form_blocked_customers', array());
                        
                        if (!empty($customers)): ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>اسم الزبون</th>
                                        <th>رقم الهاتف</th>
                                        <th>عدد الطلبات</th>
                                        <th>آخر طلب</th>
                                        <th>عناوين IP</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $customer): 
                                        $is_blocked = in_array($customer->phone, $blocked_customers);
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($customer->customer_name); ?></td>
                                        <td><?php echo esc_html($customer->phone); ?></td>
                                        <td><?php echo esc_html($customer->order_count); ?></td>
                                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($customer->last_order))); ?></td>
                                        <td><?php echo esc_html($customer->ip_addresses); ?></td>
                                        <td>
                                            <button class="button <?php echo $is_blocked ? 'unblock-customer' : 'block-customer'; ?>"
                                                    data-phone="<?php echo esc_attr($customer->phone); ?>"
                                                    data-action="<?php echo $is_blocked ? 'unblock' : 'block'; ?>">
                                                <?php echo $is_blocked ? 'إلغاء الحظر' : 'حظر'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>لا يوجد زبائن حالياً.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="settings-panel active" id="fields-panel">
                    <h2>إعدادات النص</h2>
                    <div class="form-group">
                        <label>عنوان النموذج</label>
                        <input type="text" name="form_title" 
                               value="<?php echo esc_attr($form_title); ?>" 
                               class="regular-text">
                        <p class="description">النص الذي يظهر في أعلى النموذج</p>
                    </div>

                    <h2>إعدادات التوصيل</h2>
                    <div class="form-group">
                        <label>سعر التوصيل الثابت</label>
                        <input type="number" name="shipping_settings[fixed_price]" 
                               value="<?php echo esc_attr($shipping_settings['fixed_price']); ?>" 
                               class="regular-text"
                               min="0"
                               step="0.01">
                        <p class="description">سيتم استخدام هذا السعر إذا كان حقل الولاية مخفياً</p>
                    </div>
                    <h2>إعدادات الواتساب</h2>
                    <div class="form-group">
                        <label>رقم الواتساب</label>
                        <input type="text" name="whatsapp_settings[number]" 
                               value="<?php echo esc_attr($whatsapp_settings['number']); ?>" 
                               class="regular-text"
                               placeholder="مثال: 213123456789">
                        <p class="description">أدخل رقم الواتساب مع رمز البلد (مثال: 213 للجزائر)</p>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="whatsapp_settings[enabled]" 
                                   <?php checked($whatsapp_settings['enabled'], true); ?>>
                            تفعيل زر الطلب عبر الواتساب
                        </label>
                    </div>

                    <h2>إعدادات منع السبام</h2>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="spam_settings[disable_autocomplete]" 
                                   <?php checked($spam_settings['disable_autocomplete'], true); ?>>
                            منع الإكمال التلقائي في خانات الفورم
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="spam_settings[disable_copy_paste]" 
                                   <?php checked($spam_settings['disable_copy_paste'], true); ?>>
                            منع نسخ ولصق النص في خانات الفورم
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="spam_settings[limit_orders]" 
                                   <?php checked($spam_settings['limit_orders'], true); ?>>
                            تفعيل خاصية ارسال طلبية واحدة فقط خلال 24 ساعة
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="spam_settings[save_abandoned]" 
                                   <?php checked($spam_settings['save_abandoned'], true); ?>>
                            تفعيل خاصية حفظ الطلبات المتروكة
                        </label>
                    </div>

                    <h2>إعدادات زر الشراء</h2>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="button_settings[show_sticky_button]" 
                                   <?php checked($button_settings['show_sticky_button'], true); ?>>
                            إظهار زر الشراء المثبت أسفل الصفحة
                        </label>
                    </div>
                    <div class="form-group">
                        <label>نص زر الشراء</label>
                        <input type="text" name="button_settings[button_text]" 
                               value="<?php echo esc_attr($button_settings['button_text']); ?>" 
                               class="regular-text"
                               placeholder="اشتري الآن">
                    </div>

                    <h2>إظهار/إخفاء الحقول</h2>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="field_visibility[show_country]" 
                                   <?php checked($field_visibility['show_country'], true); ?>>
                            تفعيل حقل الدولة
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="field_visibility[show_address]" 
                                   <?php checked($field_visibility['show_address'], true); ?>>
                            إظهار حقل العنوان
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="field_visibility[show_state]" 
                                   <?php checked($field_visibility['show_state'], true); ?>>
                            إظهار حقل الولاية
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="field_visibility[show_municipality]" 
                                   <?php checked($field_visibility['show_municipality'], true); ?>>
                            إظهار حقل البلدية
                        </label>
                    </div>

                    <h2>تسميات الحقول</h2>
                    <div class="form-group">
                        <label>الاسم الكامل</label>
                        <input type="text" name="field_labels[fullName]" 
                               value="<?php echo esc_attr($field_labels['fullName']); ?>" class="regular-text">
                    </div>
                    <div class="form-group">
                        <label>رقم الهاتف</label>
                        <input type="text" name="field_labels[phone]" 
                               value="<?php echo esc_attr($field_labels['phone']); ?>" class="regular-text">
                    </div>
                    <div class="form-group">
                        <label>العنوان</label>
                        <input type="text" name="field_labels[address]" 
                               value="<?php echo esc_attr($field_labels['address']); ?>" class="regular-text">
                    </div>
                    <div class="form-group">
                        <label>الولاية</label>
                        <input type="text" name="field_labels[state]" 
                               value="<?php echo esc_attr($field_labels['state']); ?>" class="regular-text">
                    </div>
                    <div class="form-group">
                        <label>البلدية</label>
                        <input type="text" name="field_labels[municipality]" 
                               value="<?php echo esc_attr($field_labels['municipality']); ?>" class="regular-text">
                    </div>
                </div>

                <div class="settings-panel" id="design-panel">
                    <h2>إعدادات التصميم</h2>
                    <div class="form-group">
                        <label>اللون الرئيسي</label>
                        <input type="color" name="design[primaryColor]" 
                               value="<?php echo esc_attr($design_settings['primaryColor']); ?>" class="color-picker">
                    </div>
                    <div class="form-group">
                        <label>لون الأزرار</label>
                        <input type="color" name="design[buttonColor]" 
                               value="<?php echo esc_attr($design_settings['buttonColor']); ?>" class="color-picker">
                    </div>
                    <div class="form-group">
                        <label>نوع الخط</label>
                        <select name="design[fontFamily]">
                            <option value="IBM Plex Sans Arabic" <?php selected($design_settings['fontFamily'], 'IBM Plex Sans Arabic'); ?>>IBM Plex Sans Arabic</option>
                            <option value="Tajawal" <?php selected($design_settings['fontFamily'], 'Tajawal'); ?>>Tajawal</option>
                            <option value="Cairo" <?php selected($design_settings['fontFamily'], 'Cairo'); ?>>Cairo</option>
                        </select>
                    </div>
                    
                    <h2>تنسيق النموذج</h2>
                    <div class="form-group">
                        <label>لون الخلفية</label>
                        <input type="color" name="design[backgroundColor]" 
                               value="<?php echo esc_attr($design_settings['backgroundColor'] ?? '#ffffff'); ?>" 
                               class="color-picker">
                    </div>
                    <div class="form-group">
                        <label>لون النص</label>
                        <input type="color" name="design[textColor]" 
                               value="<?php echo esc_attr($design_settings['textColor'] ?? '#1f2937'); ?>" 
                               class="color-picker">
                    </div>
                    <div class="form-group">
                        <label>لون الحدود</label>
                        <input type="color" name="design[borderColor]" 
                               value="<?php echo esc_attr($design_settings['borderColor'] ?? '#e2e8f0'); ?>" 
                               class="color-picker">
                    </div>
                    <div class="form-group">
                        <label>نصف قطر الحواف (بالبكسل)</label>
                        <input type="number" name="design[borderRadius]" 
                               value="<?php echo esc_attr($design_settings['borderRadius'] ?? '12'); ?>" 
                               class="small-text"
                               min="0"
                               max="50">
                    </div>
                </div>

                <button type="submit" class="button button-primary">حفظ الإعدادات</button>
            </form>
        </div>
        <?php
    }

    public function save_form_settings() {
        check_ajax_referer('custom_order_form_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بتنفيذ هذا الإجراء');
        }

        $form_title = isset($_POST['form_title']) ? sanitize_text_field($_POST['form_title']) : '';
        $field_labels = isset($_POST['field_labels']) ? $_POST['field_labels'] : array();
        $field_visibility = isset($_POST['field_visibility']) ? array(
            'show_address' => isset($_POST['field_visibility']['show_address']),
            'show_state' => isset($_POST['field_visibility']['show_state']),
            'show_municipality' => isset($_POST['field_visibility']['show_municipality']),
            'show_country' => isset($_POST['field_visibility']['show_country']) // إضافة خيار حقل الدولة
        ) : array();
        
        $whatsapp_settings = isset($_POST['whatsapp_settings']) ? array(
            'number' => sanitize_text_field($_POST['whatsapp_settings']['number']),
            'enabled' => isset($_POST['whatsapp_settings']['enabled'])
        ) : array();

        $button_settings = isset($_POST['button_settings']) ? array(
            'show_sticky_button' => isset($_POST['button_settings']['show_sticky_button']),
            'button_text' => sanitize_text_field($_POST['button_settings']['button_text'])
        ) : array();

        $spam_settings = isset($_POST['spam_settings']) ? array(
            'disable_autocomplete' => isset($_POST['spam_settings']['disable_autocomplete']),
            'disable_copy_paste' => isset($_POST['spam_settings']['disable_copy_paste']),
            'limit_orders' => isset($_POST['spam_settings']['limit_orders']),
            'save_abandoned' => isset($_POST['spam_settings']['save_abandoned'])
        ) : array();
        $design_settings = isset($_POST['design']) ? $_POST['design'] : array();
        $shipping_settings = isset($_POST['shipping_settings']) ? array(
            'fixed_price' => floatval($_POST['shipping_settings']['fixed_price']),
            'use_fixed_price' => !isset($_POST['field_visibility']['show_state'])
        ) : array();

        update_option('custom_order_form_title', $form_title);
        update_option('custom_order_form_field_labels', $field_labels);
        update_option('custom_order_form_field_visibility', $field_visibility);
        update_option('custom_order_form_whatsapp_settings', $whatsapp_settings);
        update_option('custom_order_form_button_settings', $button_settings);
        update_option('custom_order_form_spam_settings', $spam_settings);
        update_option('custom_order_form_design', $design_settings);
        update_option('custom_order_form_shipping_settings', $shipping_settings);

        wp_send_json_success(array(
            'message' => 'تم حفظ الإعدادات بنجاح',
            'settings' => array(
                'fieldLabels' => $field_labels,
                'fieldVisibility' => $field_visibility,
                'whatsapp_number' => $whatsapp_settings['number'],
                'whatsapp_enabled' => $whatsapp_settings['enabled'],
                'design' => $design_settings
            )
        ));
    }
}
