<?php
declare(strict_types=1);
session_start();

/* send.php — обработчик формы:
   CSRF · honeypot · time-check · math captcha · rate limit
   safe uploads · email · backup
*/

function redirect(string $url): void {
  header('Location: ' . $url);
  exit;
}
function fail(string $msg): void {
  redirect('/register.php?err=1&msg=' . urlencode($msg));
}
function clean(string $s): string {
  return preg_replace('/[\r\n]+/', ' ', trim($s));
}
function get_ip(): string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  return preg_replace('/[^0-9a-fA-F\.\:]/', '', $ip);
}

function rate_ok(string $key, int $max, int $window): bool {
  $dir = __DIR__ . '/assets/storage/ratelimit';
  if (!is_dir($dir)) @mkdir($dir, 0755, true);

  $file = $dir . '/rl_' . hash('sha256', $key) . '.json';
  $now  = time();
  $data = ['hits' => []];

  if (is_file($file)) {
    $tmp = json_decode((string)@file_get_contents($file), true);
    if (is_array($tmp) && isset($tmp['hits']) && is_array($tmp['hits'])) $data = $tmp;
  }

  $hits = [];
  foreach ($data['hits'] as $t) {
    if (is_int($t) && ($now - $t) <= $window) $hits[] = $t;
  }

  if (count($hits) >= $max) return false;

  $hits[] = $now;
  $data['hits'] = $hits;
  @file_put_contents($file, json_encode($data), LOCK_EX);
  return true;
}

function safe_upload(string $field, array $exts, array $mimes, int $maxB): string {
  if (!isset($_FILES[$field])) return '';
  $f = $_FILES[$field];

  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
  if (($f['error'] ?? 0) !== UPLOAD_ERR_OK) return '[upload error]';

  $size = (int)($f['size'] ?? 0);
  if ($size <= 0 || $size > $maxB) return '[file too large]';

  $ext = strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION));
  if (!in_array($ext, $exts, true)) return '[bad ext]';

  $tmp = (string)($f['tmp_name'] ?? '');
  if ($tmp === '' || !is_uploaded_file($tmp)) return '[invalid]';

  if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $fi ? (string)finfo_file($fi, $tmp) : '';
    if ($fi) finfo_close($fi);
    if ($mime !== '' && !in_array($mime, $mimes, true)) return '[bad mime]';
  }

  $dir = __DIR__ . '/assets/uploads';
  if (!is_dir($dir)) @mkdir($dir, 0755, true);

  $new  = 'u_' . bin2hex(random_bytes(16)) . '.' . $ext;
  $dest = $dir . '/' . $new;
  if (!move_uploaded_file($tmp, $dest)) return '[move failed]';

  $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
  return $proto . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/assets/uploads/' . $new;
}

/* MAIN */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Only POST allowed');

$ip = get_ip();
if (!rate_ok($ip, 8, 3600)) fail('Слишком много попыток. Попробуйте позже.');

// CSRF
$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
  fail('Ошибка безопасности (CSRF). Обновите страницу.');
}

// Honeypot
if (!empty($_POST['hp_website'] ?? '')) fail('Спам-фильтр сработал.');

// Time-check (>= 3 sec)
$loadedAt = (string)($_POST['form_loaded_at'] ?? '');
if ($loadedAt !== '') {
  $delta = time() - (int)floor((int)$loadedAt / 1000);
  if ($delta < 3) fail('Форма заполнена слишком быстро.');
}

// Math captcha
$ans = (string)($_POST['math_answer'] ?? '');
if ($ans === '' || !isset($_SESSION['math_sum']) || (int)$ans !== (int)$_SESSION['math_sum']) {
  fail('Неверный ответ антиспам.');
}

/* Common fields */
$type       = clean((string)($_POST['participant_type'] ?? ''));
$workLang   = clean((string)($_POST['work_lang'] ?? ''));
$lastname   = clean((string)($_POST['lastname'] ?? ''));
$firstname  = clean((string)($_POST['firstname'] ?? ''));
$nameLatin  = clean((string)($_POST['name_latin'] ?? ''));
$position   = clean((string)($_POST['position'] ?? ''));
$org        = clean((string)($_POST['organization'] ?? ''));
$country    = clean((string)($_POST['country'] ?? ''));
$city       = clean((string)($_POST['city'] ?? ''));
$email      = trim((string)($_POST['email'] ?? ''));
$phone      = clean((string)($_POST['phone'] ?? ''));
$format     = clean((string)($_POST['participation_format'] ?? ''));
$inviteNeed = clean((string)($_POST['invite_need'] ?? ''));
$inviteCom  = clean((string)($_POST['invite_comment'] ?? ''));

$sessions   = $_POST['sessions'] ?? [];
$interests  = clean((string)($_POST['interests'] ?? ''));
$b2b        = clean((string)($_POST['b2b_need'] ?? ''));
$special    = clean((string)($_POST['special_requests'] ?? ''));
$source     = clean((string)($_POST['source'] ?? ''));

$agreePd    = (string)($_POST['agree_pd'] ?? '');
$agreeRules = (string)($_POST['agree_rules'] ?? '');

/* Validate common */
$allowedTypes = ['delegate','speaker','investrep','investor','sponsor','media'];
if (!in_array($type, $allowedTypes, true)) fail('Выберите тип участника.');
if (!in_array($workLang, ['RU','EN','TJ'], true)) fail('Выберите рабочий язык.');
if ($lastname === '' || $firstname === '' || $nameLatin === '' || $position === '' || $org === '' || $country === '' || $city === '') {
  fail('Заполните все обязательные поля.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Некорректный email.');
if ($phone === '') fail('Укажите телефон.');
if (!in_array($format, ['onsite','online'], true)) fail('Выберите формат участия.');
if (!in_array($inviteNeed, ['yes','no'], true)) fail('Укажите, нужно ли приглашение.');
if (!is_array($sessions) || count($sessions) === 0) fail('Выберите минимум одну секцию.');
if ($agreePd !== 'yes' || $agreeRules !== 'yes') fail('Нужно согласие на обработку ПД и правила.');

/* Conditional blocks */
$extraLines = [];

/* Speaker */
if ($type === 'speaker') {
  $spkRole    = clean((string)($_POST['speaker_role'] ?? ''));
  $spkLang    = clean((string)($_POST['speaker_talk_lang'] ?? ''));
  $spkTopic   = clean((string)($_POST['speaker_topic'] ?? ''));
  $spkFormat  = clean((string)($_POST['speaker_format'] ?? ''));
  $spkTrack   = clean((string)($_POST['speaker_track'] ?? ''));
  $spkInterp  = clean((string)($_POST['speaker_interpret'] ?? ''));
  $spkAbstr   = clean((string)($_POST['speaker_abstract'] ?? ''));
  $spkBio     = clean((string)($_POST['speaker_bio'] ?? ''));
  $spkSite    = clean((string)($_POST['speaker_site'] ?? ''));
  $spkConsent = (string)($_POST['speaker_publish_consent'] ?? '');

  $spkPhoto = safe_upload('speaker_photo',
    ['jpg','jpeg','png','webp'],
    ['image/jpeg','image/png','image/webp'],
    5 * 1024 * 1024
  );
  $spkMat = safe_upload('speaker_materials',
    ['pdf','ppt','pptx','doc','docx'],
    [
      'application/pdf',
      'application/vnd.ms-powerpoint',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ],
    10 * 1024 * 1024
  );

  $extraLines[] = "--- SPEAKER ---";
  $extraLines[] = "Role: $spkRole";
  $extraLines[] = "Talk lang: $spkLang";
  $extraLines[] = "Topic: $spkTopic";
  $extraLines[] = "Format: $spkFormat";
  $extraLines[] = "Track: $spkTrack";
  $extraLines[] = "Interpret: $spkInterp";
  $extraLines[] = "Abstract: $spkAbstr";
  $extraLines[] = "Bio: $spkBio";
  $extraLines[] = "Site: $spkSite";
  $extraLines[] = "Photo: $spkPhoto";
  $extraLines[] = "Materials: $spkMat";
  $extraLines[] = "Publish consent: $spkConsent";
}

/* Project / Startup */
if ($type === 'investrep') {
  $prjName    = clean((string)($_POST['project_name'] ?? ''));
  $prjSegment = clean((string)($_POST['project_segment'] ?? ''));
  $prjDesc    = clean((string)($_POST['project_desc'] ?? ''));
  $prjStage   = clean((string)($_POST['project_stage'] ?? ''));
  $prjNeed    = clean((string)($_POST['project_need'] ?? ''));
  $prjPref    = clean((string)($_POST['project_pref'] ?? ''));
  $prjSite    = clean((string)($_POST['project_site'] ?? ''));

  $prjFile = safe_upload('project_file',
    ['pdf','ppt','pptx','doc','docx'],
    [
      'application/pdf',
      'application/vnd.ms-powerpoint',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ],
    10 * 1024 * 1024
  );

  $extraLines[] = "--- PROJECT ---";
  $extraLines[] = "Name: $prjName";
  $extraLines[] = "Segment: $prjSegment";
  $extraLines[] = "Desc: $prjDesc";
  $extraLines[] = "Stage: $prjStage";
  $extraLines[] = "Need: $prjNeed";
  $extraLines[] = "Pref: $prjPref";
  $extraLines[] = "Site: $prjSite";
  $extraLines[] = "File: $prjFile";
}

/* Investor */
if ($type === 'investor') {
  $invType     = clean((string)($_POST['investor_type'] ?? ''));
  $invRange    = clean((string)($_POST['investor_range'] ?? ''));
  $invFocus    = clean((string)($_POST['investor_focus'] ?? ''));
  $invProjects = clean((string)($_POST['investor_projects'] ?? ''));

  $extraLines[] = "--- INVESTOR ---";
  $extraLines[] = "Type: $invType";
  $extraLines[] = "Range: $invRange";
  $extraLines[] = "Focus: $invFocus";
  $extraLines[] = "Projects: $invProjects";
}

/* Sponsor */
if ($type === 'sponsor') {
  $sLegal   = clean((string)($_POST['sponsor_legal'] ?? ''));
  $sWeb     = clean((string)($_POST['sponsor_web'] ?? ''));
  $sProfile = clean((string)($_POST['sponsor_profile'] ?? ''));
  $sLevel   = clean((string)($_POST['sponsor_level'] ?? ''));
  $sCustom  = (string)($_POST['sponsor_custom'] ?? '');
  $sGoals   = clean((string)($_POST['sponsor_goals'] ?? ''));
  $sAct     = clean((string)($_POST['sponsor_activations'] ?? ''));
  $sCName   = clean((string)($_POST['sponsor_contact_name'] ?? ''));
  $sCEmail  = clean((string)($_POST['sponsor_contact_email'] ?? ''));
  $sCPhone  = clean((string)($_POST['sponsor_contact_phone'] ?? ''));

  $extraLines[] = "--- SPONSOR ---";
  $extraLines[] = "Legal: $sLegal";
  $extraLines[] = "Web: $sWeb";
  $extraLines[] = "Profile: $sProfile";
  $extraLines[] = "Level: $sLevel";
  $extraLines[] = "Custom: $sCustom";
  $extraLines[] = "Goals: $sGoals";
  $extraLines[] = "Activations: $sAct";
  $extraLines[] = "Contact name: $sCName";
  $extraLines[] = "Contact email: $sCEmail";
  $extraLines[] = "Contact phone: $sCPhone";
}

/* Email */
$sessStr  = is_array($sessions) ? implode(', ', $sessions) : '';
$extraStr = !empty($extraLines) ? "\n" . implode("\n", $extraLines) : '';
$ts       = date('Y-m-d H:i:s');
$ua       = $_SERVER['HTTP_USER_AGENT'] ?? '';

$body = <<<MAIL
=== НОВАЯ ЗАЯВКА НА ФОРУМ ===

Тип: $type
Рабочий язык: $workLang
Формат: $format

Фамилия: $lastname
Имя: $firstname
Латиница: $nameLatin
Должность: $position
Организация: $org
Страна: $country
Город: $city
Email: $email
Телефон: $phone

Приглашение: $inviteNeed
Комментарий: $inviteCom

Сессии: $sessStr

Интересы: $interests
B2B: $b2b
Особые запросы: $special
Источник: $source

Согласие ПД: $agreePd
Согласие правила: $agreeRules
{$extraStr}

IP: {$ip}
UA: {$ua}
Дата: {$ts}
MAIL;

$to      = 'reg@textileforum.tj';
$subject = "ITF-2026 Registration: $type — $lastname $firstname ($org)";

$headers  = "From: noreply@textileforum.tj\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

@mail($to, $subject, $body, $headers);

/* Backup */
$logDir = __DIR__ . '/assets/storage';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

$logFile  = $logDir . '/submissions_' . date('Y-m') . '.txt';
$logEntry = "\n\n" . str_repeat('=', 60) . "\n" . $ts . "\n" . $body;
@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

/* Redirect */
unset($_SESSION['csrf_token']);
redirect('/register.php?ok=1');
