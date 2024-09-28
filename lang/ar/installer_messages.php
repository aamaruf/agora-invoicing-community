<?php

return [

    'title' => 'مثبت فواتير أغيرا',
    'probe' => 'أجهزة استشعار أغيرا للفواتير',
    'magic_phrase' => 'ما هي العبارة السحرية',
    'server_requirements' => 'متطلبات الخادم',
    'database_setup'=> 'إعداد قاعدة البيانات',
    'getting_started' => 'البدء',
    'final' => 'نهائي',
    'directory' => 'الدليل',
    'permissions' => 'الأذونات',
    'requisites' => 'المتطلبات',
    'status' => 'الحالة',
    'php_extensions' => 'امتدادات PHP',
    'not_enabled' => 'غير مفعل',
    'extension_not_enabled' => 'غير مفعل: لتفعيل ذلك، يرجى تثبيت الامتداد على خادمك وتحديث :php_ini_file لتفعيل :extensionName. <a href=":url" target="_blank">كيف تثبت امتدادات PHP على خادمي؟</a>',
    'mod_rewrite' => 'إعادة كتابة الوحدة',
    'off_apache' => 'مغلق (إذا كنت تستخدم apache، تأكد من أن <var><strong>AllowOverride</strong></var> تم تعيينه على <var><strong>All</strong></var> في إعدادات apache)',
    'rewrite_engine' => 'محرك إعادة الكتابة',
    'user_url' => 'رابط صديق للمستخدم',

    'host' => 'المضيف',
    'host_tooltip' => 'إذا كانت MySQL مثبتة على نفس الخادم كـ Agora Invoicing، دعها تكون localhost',
    'database_name_label' => 'اسم قاعدة البيانات',
    'mysql_port_label' => 'رقم منفذ MySQL',
    'mysql_port_tooltip' => 'رقم المنفذ الذي يستمع عليه خادم MySQL الخاص بك. بشكل افتراضي، هو 3306',
    'username' => 'اسم المستخدم',
    'password_label' => 'كلمة المرور',
    'test_prerequisites_message' => 'هذا الاختبار سيتحقق من المتطلبات اللازمة لتثبيت Agora Invoicing',
    'previous' => 'السابق',

    'sign_up_as_admin' => 'التسجيل كمدير',
    'first_name' => 'الاسم الأول',
    'first_name_required' => 'الاسم الأول مطلوب',
    'last_name' => 'اسم العائلة',
    'last_name_required' => 'اسم العائلة مطلوب',
    'username_info' => 'يمكن أن يحتوي اسم المستخدم على أحرف أبجدية رقمية، والمسافات، والشرطات السفلية، والشرطات، والنقاط، ورمز @ فقط.',
    'email' => 'البريد الإلكتروني',
    'email_required' => 'البريد الإلكتروني للمستخدم مطلوب',
    'password_required' => 'كلمة المرور مطلوبة',
    'confirm_password' => 'تأكيد كلمة المرور',
    'confirm_password_required' => 'تأكيد كلمة المرور مطلوب',
    'password_requirements' => 'يجب أن تحتوي كلمة المرور الخاصة بك على:',
    'password_requirements_list' => [
        'بين 8-16 حرفًا',
        'حروف كبيرة (A-Z)',
        'حروف صغيرة (a-z)',
        'أرقام (0-9)',
        'رموز خاصة (~*!@$#%_+.?:,{ })',
    ],

    'system_information' => 'معلومات النظام',
    'environment' => 'البيئة',
    'environment_required' => 'البيئة مطلوبة',
    'production' => 'الإنتاج',
    'development' => 'التطوير',
    'testing' => 'الاختبار',
    'cache_driver' => 'محرك التخزين المؤقت',
    'cache_driver_required' => 'محرك التخزين المؤقت مطلوب',
    'file' => 'ملف',
    'redis' => 'ريديس',
    'password' => 'كلمة المرور',

    'redis_setup' => 'إعداد ريديس',
    'redis_host' => 'مضيف ريديس',
    'redis_port' => 'منفذ ريديس',
    'redis_password' => 'كلمة مرور ريديس',


    'continue' => 'متابعة',


    'final_setup' => 'تطبيق فواتير أغيرا جاهز!',
    'installation_complete' => 'رائع، لقد اجتزت عملية التثبيت.',


    'learn_more' => 'تعلم المزيد',
    'knowledge_base' => 'قاعدة المعرفة',
    'email_support' => 'دعم البريد الإلكتروني',


    'next_step' => 'الخطوة التالية',
    'login_button' => 'تسجيل الدخول إلى الفواتير',

    'pre_migration_success' => 'تم اختبار ما قبل الهجرة بنجاح',
    'migrating_tables' => 'ترحيل الجداول في قاعدة البيانات',
    'db_connection_error' => 'لم يتم تحديث اتصال قاعدة البيانات.',
    'database_setup_success' => 'تم إعداد قاعدة البيانات بنجاح.',
    'env_file_created' => 'تم إنشاء ملف إعداد البيئة بنجاح',
    'pre_migration_test' => 'تشغيل اختبار ما قبل الهجرة',

    'redis_host_required' => 'مضيف ريديس مطلوب.',
    'redis_password_required' => 'كلمة مرور ريديس مطلوبة.',
    'redis_port_required' => 'منفذ ريديس مطلوب.',
    'password_regex' => 'يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل، وحرف كبير واحد، وحرف صغير واحد، ورقم واحد، ورمز خاص واحد.',
    'setup_completed' => 'تم الانتهاء من الإعداد بنجاح!',

    'database' => 'قاعدة البيانات',
    'selected' => 'المحدد',
    'mysql_version_is' => 'إصدار MySQL هو',
    'database_empty' => 'قاعدة البيانات فارغة',
    'database_not_empty' => 'تثبيت فواتير أغيرا يتطلب قاعدة بيانات فارغة، قاعدة البيانات الخاصة بك تحتوي بالفعل على جداول وبيانات.',
    'mysql_version_required' => 'نوصي بالترقية إلى MySQL 5.6 على الأقل أو MariaDB 10.3!',
    'database_connection_unsuccessful' => 'فشل اتصال قاعدة البيانات.',
    'connected_as' => 'متصل بقاعدة البيانات كـ',
    'failed_connection' => 'فشل الاتصال بقاعدة البيانات.',


];
