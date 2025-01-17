<?php
class Custom_Order_Form_Field_Settings {
    public static function render_settings() {
        ?>
        <div class="field-settings-panel p-6 bg-white rounded-lg shadow-sm">
            <h3 class="text-xl font-semibold mb-4">إعدادات الحقول</h3>
            
            <div class="grid gap-4">
                <?php self::render_field_toggles(); ?>
                <?php self::render_custom_fields(); ?>
            </div>
        </div>
        <?php
    }

    private static function render_field_toggles() {
        $fields = array(
            'show_name' => 'إظهار حقل الاسم',
            'show_phone' => 'إظهار حقل الهاتف',
            'show_email' => 'إظهار حقل البريد الإلكتروني',
            'show_address' => 'إظهار حقل العنوان',
            'show_city' => 'إظهار حقل المدينة'
        );

        foreach ($fields as $field_id => $label) {
            $value = get_option($field_id, '1');
            ?>
            <div class="field-toggle flex items-center justify-between p-3 bg-gray-50 rounded">
                <label class="text-gray-700"><?php echo esc_html($label); ?></label>
                <label class="switch">
                    <input type="checkbox" 
                           name="<?php echo esc_attr($field_id); ?>" 
                           <?php checked($value, '1'); ?> 
                           class="field-toggle-input">
                    <span class="slider round"></span>
                </label>
            </div>
            <?php
        }
    }

    private static function render_custom_fields() {
        ?>
        <div class="custom-fields-section mt-6">
            <h4 class="text-lg font-medium mb-3">الحقول المخصصة</h4>
            <div id="custom-fields-container"></div>
            <button type="button" id="add-custom-field" 
                    class="mt-3 px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark transition">
                إضافة حقل جديد
            </button>
        </div>
        <?php
    }
}