<?php
/**
 * Plugin Name: Custom Order Form for WooCommerce
 * Description: Adds a custom order form to WooCommerce product pages.
 * Version: 1.0
 * Author: pexlat
 * Text Domain: custom-order-form
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include admin class
require_once plugin_dir_path(__FILE__) . 'admin/class-custom-order-form-admin.php';

function custom_order_form_init() {
    $plugin = new Custom_Order_Form_Admin('custom-order-form', '1.0');
}
add_action('plugins_loaded', 'custom_order_form_init');

function custom_order_form_shortcode($atts = array()) {
    // تحديد القيم الافتراضية للـ shortcode
    $atts = shortcode_atts(array(
        'product_id' => get_the_ID() // استخدام معرف المنتج الحالي كقيمة افتراضية
    ), $atts);

    // التحقق من وجود المنتج
    $product = wc_get_product($atts['product_id']);
    if (!$product) {
        return '<p>المنتج غير موجود</p>';
    }

    // حفظ الحالة الحالية
    $original_post = $GLOBALS['post'];
    $original_product = $GLOBALS['product'];
    
    // تعيين المنتج المطلوب
    $GLOBALS['post'] = get_post($atts['product_id']);
    $GLOBALS['product'] = $product;
    setup_postdata($GLOBALS['post']);

    // تهيئة المتغيرات اللازمة لعرض النموذج
    $has_variations = $product->is_type('variable');
    $variations = array();
    if ($has_variations) {
        $attributes = $product->get_variation_attributes();
        foreach ($attributes as $attribute => $values) {
            $variations[$attribute] = $values;
        }
    }

    ob_start();
    custom_order_form_assets();
    
    // إضافة div لمحاكاة بيئة صفحة المنتج
    echo '<div class="woocommerce single-product">';
    echo '<div id="product-' . $atts['product_id'] . '" class="' . implode(' ', wc_get_product_class('', $product)) . '">';
    echo '<div class="summary entry-summary">';
    
    include 'order-form-template.php';
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // استعادة الحالة الأصلية
    $GLOBALS['post'] = $original_post;
    $GLOBALS['product'] = $original_product;
    if ($original_post) {
        setup_postdata($original_post);
    }
    
    return ob_get_clean();
}
add_shortcode('custom-order-form', 'custom_order_form_shortcode');

function check_customer_block() {
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $ip = $_SERVER['REMOTE_ADDR'];

    if (!$phone && !$ip) {
        wp_send_json_error('بيانات غير صالحة');
        return;
    }

    $blocked_items = get_option('custom_order_form_blocked_items', array());
    $is_blocked = false;

    foreach ($blocked_items as $item) {
        if (($item['type'] === 'phone' && $item['value'] === $phone) ||
            ($item['type'] === 'ip' && $item['value'] === $ip)) {
            $is_blocked = true;
            break;
        }
    }

    wp_send_json_success(array('blocked' => $is_blocked));
}

function is_customer_blocked($phone) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $blocked_items = get_option('custom_order_form_blocked_items', array());
    
    foreach ($blocked_items as $item) {
        if (($item['type'] === 'phone' && $item['value'] === $phone) ||
            ($item['type'] === 'ip' && $item['value'] === $ip)) {
            return true;
        }
    }
    
    return false;
}

function place_custom_order() {
    // التحقق من حد الطلبات اليومي
    $spam_settings = get_option('custom_order_form_spam_settings', array(
        'limit_orders' => false
    ));

    if ($spam_settings['limit_orders']) {
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $last_order = get_transient('last_order_' . $user_ip);
        if ($last_order) {
            $time_left = human_time_diff(time(), $last_order + (24 * HOUR_IN_SECONDS));
            wp_send_json_error(sprintf('عذراً، يمكنك إرسال طلب جديد بعد %s', $time_left));
            return;
        }
    }

    // التحقق من العملاء المحظورين
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    if (is_customer_blocked($phone)) {
        wp_send_json_error('عذراً، لا يمكنك إرسال طلبات في الوقت الحالي');
        return;
    }
   
    if (isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);
        $price = wc_get_product($product_id)->get_price(); 
        $full_name = sanitize_text_field($_POST['fullName']);
        $phone = sanitize_text_field($_POST['phone']);
        $country = sanitize_text_field($_POST['country']);
        $city = sanitize_text_field($_POST['city']);
        $address = sanitize_text_field($_POST['address']);
        $delivery_type = sanitize_text_field($_POST['delivery_type']);
        $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

        // التحقق من البيانات المطلوبة
        if (empty($full_name) || empty($phone)) {
            wp_send_json_error('يرجى ملء جميع الحقول المطلوبة');
            return;
        }

        // تجميع المتغيرات
    
        $variations = array();
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'attribute_') !== false) {
               $variations[$key] = sanitize_text_field($value);
            }
        }

        $order = wc_create_order();
        if (!$order) {
            wp_send_json_error('Failed to create order');
            return;
        }

        // إضافة المنتج مع المتغيرات إلى الطلب
        $order->add_product($product, $quantity, array('variation' => $variations));

        // تجميع العنوان الكامل مع نوع التوصيل
        $full_address = $address . ' (' . ($delivery_type == 'home' ? 'توصيل للمنزل' : 'توصيل للمكتب') . ')';

        // تقسيم الاسم الكامل إلى اسم أول واسم أخير
        $name_parts = explode(' ', $full_name);
        $first_name = array_shift($name_parts);
        $last_name = implode(' ', $name_parts);

        // إعداد عناوين الدفع والشحن
        $address_data = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'country' => $country,
            'state' => $city,
            'address_1' => $full_address,
        );

        $order->set_address($address_data, 'billing');
        $order->set_address($address_data, 'shipping');

        // حفظ بيانات إضافية
        $order->update_meta_data('_customer_full_name', $full_name);

        // حذف الطلب المتروك بعد نجاح إنشاء الطلب
        $abandoned_orders = get_option('custom_order_form_abandoned_orders', array());
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $abandoned_orders = array_filter($abandoned_orders, function($abandoned_order) use ($user_ip) {
            return !isset($abandoned_order['ip']) || $abandoned_order['ip'] !== $user_ip;
        });
        update_option('custom_order_form_abandoned_orders', array_values($abandoned_orders));

        // إضافة رسوم الشحن
        $shipping_cost = isset($_POST['shipping_cost']) ? floatval($_POST['shipping_cost']) : 0;
        if ($shipping_cost > 0) {
            $item = new WC_Order_Item_Shipping();
            $item->set_method_title('رسوم التوصيل');
            $item->set_total($shipping_cost);
            $order->add_item($item);
        }

        // تحديث بيانات الطلب وحساب الإجمالي
        $order->update_meta_data('delivery_type', $delivery_type);
        $order->calculate_totals();
        $order->update_status('processing');
        $order->save();

        // حفظ وقت آخر طلب
        if ($spam_settings['limit_orders']) {
            $user_ip = $_SERVER['REMOTE_ADDR'];
            set_transient('last_order_' . $user_ip, time(), 24 * HOUR_IN_SECONDS);
        }

        // إعادة توجيه المستخدم بعد نجاح الطلب
        wp_send_json_success(array(
            'redirect_url' => $order->get_checkout_order_received_url()
        ));
    } else {
        wp_send_json_error('Product ID not set');
    }
}

// معالجة طلب سعر المتغير
function get_variation_price() {
    if (!isset($_POST['product_id'])) {
        wp_send_json_error('Product ID not set');
        return;
    }

    $product_id = intval($_POST['product_id']);
    $product = wc_get_product($product_id);

    if (!$product || !$product->is_type('variable')) {
        wp_send_json_error('Invalid product');
        return;
    }

    $variation_attributes = array();
    $all_attributes_set = true;
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'attribute_') !== false) {
            if (empty($value)) {
                $all_attributes_set = false;
            }
            $variation_attributes[$key] = sanitize_text_field($value);
        }
    }

    // إذا لم يتم تحديد جميع المتغيرات، نرجع السعر الأدنى
    if (!$all_attributes_set) {
        wp_send_json_success(array(
            'price' => $product->get_variation_price('min')
        ));
        return;
    }

    $data_store = WC_Data_Store::load('product');
    $variation_id = $data_store->find_matching_product_variation($product, $variation_attributes);

    if ($variation_id) {
        $variation = wc_get_product($variation_id);
        wp_send_json_success(array(
            'price' => $variation->get_price(),
            'is_variation' => true
        ));
    } else {
        // إذا لم يتم العثور على تطابق، نرجع السعر الأدنى
        wp_send_json_success(array(
            'price' => $product->get_variation_price('min')
        ));
    }
}

add_action('wp_ajax_get_variation_price', 'get_variation_price');
add_action('wp_ajax_nopriv_get_variation_price', 'get_variation_price');

// حفظ الطلب المتروك
function save_abandoned_order() {
    if (!isset($_POST['order_data'])) {
        return;
    }

    $order_data = json_decode(stripslashes($_POST['order_data']), true);
    if (!$order_data || !isset($order_data['fullName'])) {
        return;
    }

    // إضافة معرف فريد وتاريخ للطلب
    $order_data['id'] = uniqid('abandoned_');
    $order_data['date'] = current_time('mysql');
    $order_data['ip'] = $_SERVER['REMOTE_ADDR'];

    // الحصول على الطلبات المتروكة الحالية
    $abandoned_orders = get_option('custom_order_form_abandoned_orders', array());
    
    // حذف الطلبات القديمة لنفس عنوان IP
    $abandoned_orders = array_filter($abandoned_orders, function($order) use ($order_data) {
        return !isset($order['ip']) || $order['ip'] !== $order_data['ip'];
    });
    
    // إضافة الطلب الجديد في البداية
    array_unshift($abandoned_orders, $order_data);
    
    // الاحتفاظ فقط بآخر 50 طلب
    $abandoned_orders = array_slice($abandoned_orders, 0, 50);
    
    update_option('custom_order_form_abandoned_orders', array_values($abandoned_orders));
}

add_action('wp_ajax_save_abandoned_order', 'save_abandoned_order');
add_action('wp_ajax_nopriv_save_abandoned_order', 'save_abandoned_order');

add_action('wp_ajax_check_customer_block', 'check_customer_block');
add_action('wp_ajax_nopriv_check_customer_block', 'check_customer_block');

add_action('wp_ajax_place_custom_order', 'place_custom_order');
add_action('wp_ajax_nopriv_place_custom_order', 'place_custom_order');

function add_custom_order_form() {
    if (is_product()) {
        global $product;
        
        $has_variations = $product->is_type('variable');
        $variations = [];

        if ($has_variations) {
            $attributes = $product->get_variation_attributes();
            foreach ($attributes as $attribute => $values) {
                $variations[$attribute] = $values;
            }
        }

        echo '<div class="custom-order-form-container">';
        include 'order-form-template.php';
        echo '</div>';

        // إضافة زر الشراء المثبت
        $button_settings = get_option('custom_order_form_button_settings', array(
            'show_sticky_button' => true,
            'button_text' => 'اشتري الآن'
        ));

        if ($button_settings['show_sticky_button']) {
            echo '<div class="sticky-buy-button">';
            echo '<button onclick="scrollToOrderForm()">';
            echo esc_html($button_settings['button_text']);
            echo '</button>';
            echo '</div>';
            
            // إضافة JavaScript للتمرير
            echo '<script>
                function scrollToOrderForm() {
                    const form = document.querySelector(".custom-order-form-container");
                    if (form) {
                        form.scrollIntoView({ behavior: "smooth" });
                    }
                }
                
                // إخفاء/إظهار الزر عند التمرير
                window.addEventListener("scroll", function() {
                    const button = document.querySelector(".sticky-buy-button");
                    const form = document.querySelector(".custom-order-form-container");
                    if (button && form) {
                        const formTop = form.getBoundingClientRect().top;
                        const formBottom = form.getBoundingClientRect().bottom;
                        
                        if (formTop > window.innerHeight || formBottom < 0) {
                            button.classList.add("visible");
                        } else {
                            button.classList.remove("visible");
                        }
                    }
                });
            </script>';
        }
    }
}

// تغيير موضع ظهور النموذج إلى مكان الوصف القصير
remove_action('woocommerce_before_single_product_summary', 'add_custom_order_form', 10);
add_action('woocommerce_single_product_summary', 'add_custom_order_form', 20);

function custom_order_form_assets() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
    wp_enqueue_style('custom-order-form-styles', plugin_dir_url(__FILE__) . 'styles.css');
    wp_enqueue_script('custom-order-form-scripts', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);
    wp_enqueue_script('algeria-cities', plugin_dir_url(__FILE__) . 'algeria-cities.js', array(), null, true);

    // Get form settings
    $design_settings = get_option('custom_order_form_design', array(
        'primaryColor' => '#2563eb',
        'buttonColor' => '#2563eb',
        'backgroundColor' => '#ffffff',
        'textColor' => '#1f2937',
        'borderColor' => '#e2e8f0',
        'borderRadius' => '12',
        'fontFamily' => 'IBM Plex Sans Arabic'
    ));

    $field_visibility = get_option('custom_order_form_field_visibility', array(
        'show_address' => true,
        'show_state' => true,
        'show_municipality' => true
    ));

    $shipping_settings = get_option('custom_order_form_shipping_settings', array(
        'fixed_price' => 0,
        'use_fixed_price' => false
    ));

    // Add Google Fonts
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600&family=Tajawal:wght@400;500;700&family=Cairo:wght@400;600;700&display=swap', array());

    // Pass settings to JavaScript
    // Get WhatsApp settings
    $whatsapp_settings = get_option('custom_order_form_whatsapp_settings', array(
        'number' => '',
        'enabled' => true
    ));

    // Get spam settings
    $spam_settings = get_option('custom_order_form_spam_settings', array(
        'disable_autocomplete' => false,
        'disable_copy_paste' => false,
        'limit_orders' => false,
        'save_abandoned' => true
    ));

    wp_localize_script('custom-order-form-scripts', 'woocommerce_params', array(
        'product_id' => get_the_ID(),
        'ajax_url' => admin_url('admin-ajax.php'),
        'form_settings' => array(
            'design' => $design_settings,
            'fieldVisibility' => $field_visibility,
            'shipping' => $shipping_settings,
            'whatsapp_number' => $whatsapp_settings['number'],
            'whatsapp_enabled' => $whatsapp_settings['enabled'],
            'spam_settings' => $spam_settings
        )
    ));

    // Add inline CSS for custom styling
    $custom_css = "
        :root {
            --primary-color: {$design_settings['primaryColor']};
            --button-color: {$design_settings['buttonColor']};
            --background-color: {$design_settings['backgroundColor']};
            --text-color: {$design_settings['textColor']};
            --border-color: {$design_settings['borderColor']};
            --border-radius: {$design_settings['borderRadius']}px;
            --font-family: {$design_settings['fontFamily']}, sans-serif;
        }
    ";
    wp_add_inline_style('custom-order-form-styles', $custom_css);
}
add_action('wp_enqueue_scripts', 'custom_order_form_assets');
