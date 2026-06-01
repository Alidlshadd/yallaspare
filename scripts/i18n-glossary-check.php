<?php
// For each common English term, show which AR/KU translations exist and flag inconsistencies.
// This lets us pick a canonical form per term and apply it everywhere.

$en = json_decode(file_get_contents(__DIR__ . '/../lang/en.json'), true);
$ar = json_decode(file_get_contents(__DIR__ . '/../lang/ar.json'), true);
$ku = json_decode(file_get_contents(__DIR__ . '/../lang/ku.json'), true);

// Common English terms to track. Each entry shows what AR/KU words I used for that term.
$terms = [
    'order'    => ['ar' => ['الطلب', 'طلب', 'الطلبية'], 'ku' => ['داواکاری', 'فەرمان']],
    'account'  => ['ar' => ['الحساب', 'حساب'],          'ku' => ['هەژمار', 'ئەکاونت']],
    'cart'     => ['ar' => ['السلة', 'سلة'],            'ku' => ['سەبەتە', 'کارت']],
    'address'  => ['ar' => ['العنوان', 'عنوان'],         'ku' => ['ناونیشان']],
    'product'  => ['ar' => ['المنتج', 'منتج', 'البضاعة'], 'ku' => ['کاڵا', 'بەرهەم']],
    'customer' => ['ar' => ['العميل', 'عميل', 'الزبون'], 'ku' => ['کڕیار']],
    'dealer'   => ['ar' => ['الوكيل', 'وكيل', 'الموزع'], 'ku' => ['فرۆشیار', 'بریکار']],
    'delivery' => ['ar' => ['التسليم', 'التوصيل', 'الشحن'], 'ku' => ['گەیاندن', 'گەیشتن']],
    'shipping' => ['ar' => ['الشحن', 'التوصيل'],         'ku' => ['گەیاندن', 'ناردن']],
    'payment'  => ['ar' => ['الدفع', 'السداد'],          'ku' => ['پارەدان']],
    'discount' => ['ar' => ['الخصم', 'تخفيض'],           'ku' => ['داشکاندن']],
    'coupon'   => ['ar' => ['القسيمة', 'الكوبون'],       'ku' => ['کوپۆن']],
    'email'    => ['ar' => ['البريد الإلكتروني', 'الإيميل'], 'ku' => ['ئیمەیڵ', 'پۆستی ئەلیکترۆنی']],
    'password' => ['ar' => ['كلمة المرور', 'كلمة السر'], 'ku' => ['وشەی نهێنی', 'پاسوۆرد']],
    'sign in'  => ['ar' => ['تسجيل الدخول', 'تسجيل دخول'], 'ku' => ['چوونەژوورەوە']],
    'support'  => ['ar' => ['الدعم', 'المساعدة'],        'ku' => ['پشتگیری', 'یارمەتی']],
];

foreach ($terms as $term => $variants) {
    echo "\n=== ENGLISH TERM: '$term' ===\n";
    $arCounts = array_fill_keys($variants['ar'], 0);
    $kuCounts = array_fill_keys($variants['ku'], 0);

    foreach ($en as $k => $v) {
        if (!is_string($k) || stripos($k, $term) === false) continue;
        $arVal = $ar[$k] ?? '';
        $kuVal = $ku[$k] ?? '';
        foreach ($variants['ar'] as $av) {
            if (str_contains($arVal, $av)) { $arCounts[$av]++; break; }
        }
        foreach ($variants['ku'] as $kv) {
            if (str_contains($kuVal, $kv)) { $kuCounts[$kv]++; break; }
        }
    }

    echo "  AR variants:\n";
    arsort($arCounts);
    foreach ($arCounts as $word => $n) {
        if ($n > 0) echo sprintf("    %4d  %s\n", $n, $word);
    }
    echo "  KU variants:\n";
    arsort($kuCounts);
    foreach ($kuCounts as $word => $n) {
        if ($n > 0) echo sprintf("    %4d  %s\n", $n, $word);
    }
}
