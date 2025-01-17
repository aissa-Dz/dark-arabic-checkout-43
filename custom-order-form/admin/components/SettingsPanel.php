<?php
function render_settings_panel() {
    ?>
    <div class="p-6 bg-white rounded-lg shadow-sm max-w-3xl mx-auto">
        <h2 class="text-2xl font-bold mb-6 text-right">إعدادات فورم الطلب</h2>
        
        <div class="tabs">
            <div class="tabs-list mb-6">
                <button class="tab-trigger active" data-value="fields">الحقول</button>
                <button class="tab-trigger" data-value="design">التصميم</button>
                <button class="tab-trigger" data-value="advanced">إعدادات متقدمة</button>
                <button class="tab-trigger" data-value="orders">الطلبات</button>
            </div>

            <div class="tab-content active" data-value="fields">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">إعدادات الحقول</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span>إظهار حقل الاسم</span>
                            <input type="checkbox" name="show_name" checked>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>إظهار حقل الهاتف</span>
                            <input type="checkbox" name="show_phone" checked>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>إظهار حقل البريد الإلكتروني</span>
                            <input type="checkbox" name="show_email" checked>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>إظهار حقل العنوان</span>
                            <input type="checkbox" name="show_address" checked>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>إظهار حقل المدينة</span>
                            <input type="checkbox" name="show_city" checked>
                        </div>
                    </div>
                    <button class="button add-field-button">إضافة حقل جديد</button>
                </div>
            </div>

            <div class="tab-content" data-value="design">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">إعدادات التصميم</h3>
                    <p class="text-gray-500">سيتم إضافة إعدادات التصميم قريباً</p>
                </div>
            </div>

            <div class="tab-content" data-value="advanced">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">الإعدادات المتقدمة</h3>
                    <p class="text-gray-500">سيتم إضافة الإعدادات المتقدمة قريباً</p>
                </div>
            </div>

            <div class="tab-content" data-value="orders">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">إعدادات الطلبات</h3>
                    <div class="space-y-4">
                        <h4 class="text-md font-semibold">كل الطلبات</h4>
                        <h4 class="text-md font-semibold">الطلبات المتروكة</h4>
                        <h4 class="text-md font-semibold">إدارة الحظر</h4>
                        <h4 class="text-md font-semibold">قائمة الزبائن</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
