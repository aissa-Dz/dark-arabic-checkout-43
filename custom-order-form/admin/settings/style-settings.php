<?php
class Custom_Order_Form_Style_Settings {
    public static function render_settings() {
        ?>
        <div class="style-settings-panel p-6 bg-white rounded-lg shadow-sm">
            <h3 class="text-xl font-semibold mb-4">إعدادات التصميم</h3>
            
            <div class="grid gap-4">
                <?php self::render_color_settings(); ?>
                <?php self::render_typography_settings(); ?>
            </div>
        </div>
        <?php
    }

    private static function render_color_settings() {
        $colors = array(
            'primary_color' => 'اللون الرئيسي',
            'secondary_color' => 'اللون الثانوي',
            'button_color' => 'لون الأزرار',
            'text_color' => 'لون النصوص',
            'background_color' => 'لون الخلفية'
        );

        foreach ($colors as $color_id => $label) {
            $value = get_option($color_id, '#8B5CF6');
            ?>
            <div class="color-picker-wrapper flex items-center justify-between p-3 bg-gray-50 rounded">
                <label class="text-gray-700"><?php echo esc_html($label); ?></label>
                <input type="color" 
                       name="<?php echo esc_attr($color_id); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       class="color-picker">
            </div>
            <?php
        }
    }

    private static function render_typography_settings() {
        ?>
        <div class="typography-settings mt-6">
            <h4 class="text-lg font-medium mb-3">إعدادات الخطوط</h4>
            <select name="font_family" class="w-full p-2 border rounded">
                <option value="IBM Plex Sans Arabic">IBM Plex Sans Arabic</option>
                <option value="Tajawal">Tajawal</option>
                <option value="Cairo">Cairo</option>
            </select>
        </div>
        <?php
    }
}