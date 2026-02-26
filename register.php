<?php
declare(strict_types=1);

function is_https(): bool {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
  return false;
}

$secure = is_https();
session_set_cookie_params([
  'lifetime' => 0, 'path' => '/',
  'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax'
]);
session_start();

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$a = random_int(2, 9);
$b = random_int(1, 9);
$_SESSION['math_sum'] = $a + $b;

$typePref  = htmlspecialchars((string)($_GET['type']  ?? ''), ENT_QUOTES, 'UTF-8');
$levelPref = htmlspecialchars((string)($_GET['level'] ?? ''), ENT_QUOTES, 'UTF-8');

$ok  = !empty($_GET['ok']);
$err = !empty($_GET['err']);
$msg = htmlspecialchars((string)($_GET['msg'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Регистрация | International Textile Forum</title>
  <link rel="icon" href="/assets/img/logo-forum-2026.jpg">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <script defer src="/assets/js/include.js"></script>
  <script defer src="/assets/js/i18n.js"></script>
  <script defer src="/assets/js/main.js"></script>
  <script defer src="/assets/js/forms.js"></script>
</head>
<body>
  <div id="site-header"></div>

  <main class="section">
    <div class="container" style="max-width:980px">

      <div class="kicker"><span class="dot"></span><span data-i18n="register.title">Универсальная регистрация</span></div>

      <div class="card" style="padding:22px;margin-top:12px">

        <h1 style="margin:0 0 6px" data-i18n="register.title">Универсальная регистрация</h1>
        <p class="muted" data-i18n="register.subtitle"></p>
        <p class="muted small" data-i18n="required.note">* обязательное поле</p>

        <?php if ($ok): ?>
          <div class="note" style="border-color:rgba(44,224,211,.35);background:rgba(44,224,211,.08);margin-top:12px">
            Заявка отправлена. Мы свяжемся с вами по email.
          </div>
        <?php endif; ?>
        <?php if ($err): ?>
          <div class="note" style="border-color:rgba(255,61,166,.35);background:rgba(255,61,166,.10);margin-top:12px">
            <?php echo $msg ?: 'Не удалось отправить. Проверьте поля и попробуйте снова.'; ?>
          </div>
        <?php endif; ?>

        <div class="hr"></div>

        <form action="/send.php" method="post" enctype="multipart/form-data" novalidate>

          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <input type="hidden" name="form_loaded_at" value="">
          <input type="hidden" name="js_enabled" value="0">

          <!-- Honeypot -->
          <div class="hidden" aria-hidden="true">
            <label>Website</label>
            <input type="text" name="hp_website" value="" autocomplete="off" tabindex="-1">
          </div>

          <div class="field-grid">

            <div>
              <label for="participant_type" data-i18n="form.type">Тип участника*</label>
              <select id="participant_type" name="participant_type" required>
                <option value="" selected disabled>—</option>
                <option value="delegate"  <?php if($typePref==='delegate')  echo 'selected'; ?> data-i18n="type.delegate">Делегат</option>
                <option value="speaker"   <?php if($typePref==='speaker')   echo 'selected'; ?> data-i18n="type.speaker">Спикер / модератор</option>
                <option value="investrep" <?php if($typePref==='investrep') echo 'selected'; ?> data-i18n="type.investrep">Проект / стартап</option>
                <option value="investor"  <?php if($typePref==='investor')  echo 'selected'; ?> data-i18n="type.investor">Инвестор</option>
                <option value="sponsor"   <?php if($typePref==='sponsor')   echo 'selected'; ?> data-i18n="type.sponsor">Спонсор / партнер</option>
                <option value="media"     <?php if($typePref==='media')     echo 'selected'; ?> data-i18n="type.media">СМИ</option>
              </select>
            </div>

            <div>
              <label for="work_lang" data-i18n="f.workLang">Рабочий язык общения*</label>
              <select id="work_lang" name="work_lang" required>
                <option value="" selected disabled>—</option>
                <option value="RU">RU</option>
                <option value="EN">EN</option>
                <option value="TJ">TJ</option>
              </select>
            </div>

            <div>
              <label for="lastname" data-i18n="f.lastname">Фамилия*</label>
              <input id="lastname" name="lastname" type="text" required maxlength="80">
            </div>
            <div>
              <label for="firstname" data-i18n="f.firstname">Имя*</label>
              <input id="firstname" name="firstname" type="text" required maxlength="80">
            </div>
            <div>
              <label for="name_latin" data-i18n="f.latname">Фамилия и имя (латиницей)*</label>
              <input id="name_latin" name="name_latin" type="text" required maxlength="120" placeholder="John Smith">
            </div>
            <div>
              <label for="position" data-i18n="f.position">Должность*</label>
              <input id="position" name="position" type="text" required maxlength="120">
            </div>
            <div>
              <label for="organization" data-i18n="f.org">Организация*</label>
              <input id="organization" name="organization" type="text" required maxlength="180">
            </div>
            <div>
              <label for="country" data-i18n="f.country">Страна*</label>
              <input id="country" name="country" type="text" required maxlength="80">
            </div>
            <div>
              <label for="city" data-i18n="f.city">Город*</label>
              <input id="city" name="city" type="text" required maxlength="80">
            </div>
            <div>
              <label for="email" data-i18n="f.email">Email*</label>
              <input id="email" name="email" type="email" required maxlength="160">
            </div>
            <div>
              <label for="phone" data-i18n="f.phone">Телефон*</label>
              <input id="phone" name="phone" type="text" required maxlength="40" placeholder="+992 ...">
            </div>
            <div>
              <label for="participation_format" data-i18n="f.format">Формат*</label>
              <select id="participation_format" name="participation_format" required>
                <option value="" selected disabled>—</option>
                <option value="onsite">Очно</option>
                <option value="online">Онлайн</option>
              </select>
            </div>
            <div>
              <label for="invite_need" data-i18n="f.inviteNeed">Приглашение?*</label>
              <select id="invite_need" name="invite_need" required>
                <option value="" selected disabled>—</option>
                <option value="yes">Да</option>
                <option value="no">Нет</option>
              </select>
            </div>

          </div>

          <div id="invite_comment_wrap" class="hidden" style="margin-top:12px">
            <label for="invite_comment" data-i18n="f.inviteComment">Комментарий к приглашению (виза/командировка)</label>
            <textarea id="invite_comment" name="invite_comment" maxlength="1200"></textarea>
          </div>

          <div class="hr"></div>

          <label data-i18n="f.sessions">Интересующие сессии / секции форума*</label>
          <div class="grid-2 grid" style="margin-top:10px">
            <label class="checkbox"><input type="checkbox" name="sessions[]" value="open"><span data-i18n="sess.open">Открытие</span></label>
            <label class="checkbox"><input type="checkbox" name="sessions[]" value="s1"><span data-i18n="sess.s1">Сессия №1</span></label>
            <label class="checkbox"><input type="checkbox" name="sessions[]" value="s2"><span data-i18n="sess.s2">Сессия №2</span></label>
            <label class="checkbox"><input type="checkbox" name="sessions[]" value="s3"><span data-i18n="sess.s3">Сессия №3</span></label>
            <label class="checkbox"><input type="checkbox" name="sessions[]" value="s4"><span data-i18n="sess.s4">Сессия №4</span></label>
            <label class="checkbox"><input type="checkbox" name="sessions[]" value="final"><span data-i18n="sess.final">Заключительная</span></label>
            <label class="checkbox"><input type="checkbox" name="sessions[]" value="b2b"><span data-i18n="sess.b2b">B2B</span></label>
          </div>

          <div class="hr"></div>

          <div class="field-grid">
            <div>
              <label for="interests" data-i18n="f.interests">Тематика интересов (опц.)</label>
              <input id="interests" name="interests" type="text" maxlength="220">
            </div>
            <div>
              <label for="source" data-i18n="f.source">Как вы узнали о форуме? (опц.)</label>
              <input id="source" name="source" type="text" maxlength="160">
            </div>
            <div>
              <label for="b2b_need" data-i18n="f.b2bNeed">Нужны ли B2B‑встречи и с кем (опц.)</label>
              <textarea id="b2b_need" name="b2b_need" maxlength="1200"></textarea>
            </div>
            <div>
              <label for="special_requests" data-i18n="f.special">Особые запросы</label>
              <textarea id="special_requests" name="special_requests" maxlength="1200"></textarea>
            </div>
          </div>

          <!-- blocks speaker / investrep / investor / sponsor оставьте как в ваших шаблонах из части 1 -->
          <!-- (они уже совпадают с i18n-ключами и send.php) -->

          <div class="hr"></div>

          <label class="checkbox">
            <input type="checkbox" name="agree_pd" value="yes" required>
            <span><span data-i18n="legal.pd">Согласие на обработку персональных данных*</span> — <a href="/policy.html" target="_blank" rel="noopener">policy</a></span>
          </label>
          <label class="checkbox" style="margin-top:10px">
            <input type="checkbox" name="agree_rules" value="yes" required>
            <span><span data-i18n="legal.rules">Согласие с правилами участия*</span> — <a href="/rules.html" target="_blank" rel="noopener">rules</a></span>
          </label>

          <div class="hr"></div>

          <div class="note">
            <b data-i18n="anti.title">Антиспам</b>
            <div class="muted small" data-i18n="anti.tip">Решите простую задачу и отправьте форму.</div>
            <div style="display:flex;gap:12px;align-items:center;margin-top:10px">
              <div style="font-weight:900"><?php echo $a; ?> + <?php echo $b; ?> =</div>
              <input style="max-width:140px" type="number" name="math_answer" required placeholder="?">
            </div>
          </div>

          <div class="hr"></div>

          <button class="btn btn--primary" type="submit" data-i18n="submit">Отправить заявку</button>

        </form>
      </div>
    </div>
  </main>

  <div id="site-footer"></div>
</body>
</html>
