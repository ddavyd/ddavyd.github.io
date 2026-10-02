<?php

// /index.php — SPA + серверный роутер для админки через ?r=...

declare(strict_types=1);

$ver = trim(@file_get_contents(__DIR__ . '/version.txt')) ?: (string)time();

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}



// --- SERVER ROUTES (админка) ---
$r = isset($_GET['r']) ? (string)$_GET['r'] : '';

if ($r !== '') {
    // админские страницы должны быть защищены
    // IMPORTANT: require_same_origin() нельзя для HTML-страниц (иначе будет forbidden)
    require_once __DIR__ . '/api/security.php';

    try {
        $ctx  = require __DIR__ . '/api/guard.php';
    } catch (Throwable $e) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'guard_failed';
        exit;
    }

    $role = strtolower(trim((string)($ctx['role'] ?? '')));

    // owner может переключать tenant через ?tid=
    // ВАЖНО: пишем owner_tid, а НЕ tenant_id (tenant_id = базовый tenant пользователя для входа)
    if ($role === 'owner' && isset($_GET['tid'])) {
        if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

        $newTid = (int)($_GET['tid'] ?? 0);
        $_SESSION['owner_tid'] = ($newTid > 0) ? $newTid : 0;

        // гарантированно записать сессию
        session_write_close();

        header('Location: /?r=' . rawurlencode($r));
        exit;
    }

    // viewer не пускаем в админку
    if ($role === 'viewer' || $role === '') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'no_permission';
        exit;
    }

    $adminDir = __DIR__ . '/api/admin';
    if (!is_dir($adminDir)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'admin_dir_not_found';
        exit;
    }

    $routes = [
        'admin' => $adminDir . '/index.php',
        'admin_employees' => $adminDir . '/employees.php',
        'admin_employee_edit' => $adminDir . '/employee_edit.php',
        'admin_employees_import' => $adminDir . '/employees_import.php',
        'admin_groups' => $adminDir . '/groups.php',
        'admin_tenants' => $adminDir . '/tenants.php',
        'admin_tenant_join_code' => $adminDir . '/tenant_join_code.php',
        'admin_audit' => $adminDir . '/audit.php',
    ];

    $file = $routes[$r] ?? null;

    if (!$file || !is_file($file)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'route_not_found';
        exit;
    }

    $oldCwd = getcwd();
    @chdir($adminDir);

    require $file;

    if ($oldCwd) {
        @chdir($oldCwd);
    }
    exit;
}

?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Planning</title>
  <link rel="stylesheet" href="/frontend/styles.css?v=<?= htmlspecialchars($ver, ENT_QUOTES) ?>">

</head>
<body>

  <!-- ===== LOGIN ===== -->
  <div id="loginPanel" class="app-container narrow">
    <h2>Вход</h2>
    <form id="loginForm">
      <label>Логин<br>
        <input type="text" id="loginUsername" required>
      </label><br>
      <label>Пароль<br>
        <input type="password" id="loginPassword" required>
      </label><br>

      <!-- кнопки: Войти + Регистрация -->
      <div style="display:flex; gap:10px; justify-content:center; align-items:center; flex-wrap:wrap; margin-top:8px;">
        <button type="submit">Войти</button>

        <a href="/register.html" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; padding:8px 12px; border:1px solid rgba(0,0,0,.15); border-radius:6px; background:#fff; cursor:pointer;">
          Регистрация
        </a>
      </div>

      <!-- забыл пароль — отдельной строкой по центру -->
      <div style="margin-top:10px; text-align:center;">
        <a href="/forgot_password.html" style="font-size:14px; text-decoration:underline;">
          Забыли пароль?
        </a>
      </div>

      <div id="loginError" class="error"></div>
    </form>
  </div>

  <!-- ===== MAIN ===== -->
  <div id="mainPanel" style="display:none;">
    <div class="app-container">

      <div class="topbar">
        <div id="userInfo"></div>
        <div class="topbar-actions">
          <button id="logoutBtn">Выйти</button>
          <a id="adminLink" href="/?r=admin" style="display:none;">Админка</a>
        </div>
      </div>

      <div class="tabs">
        <button class="tab-button active" data-tab="tabSchedule">Расписание</button>
        <button class="tab-button" data-tab="tabTabel">Табель</button>
       <!-- ===== <button class="tab-button" data-tab="tabProfile">Профиль</button>  ===== -->
      </div>

      <!-- ===== SCHEDULE ===== -->
      <div id="tabSchedule" class="tab-content active">
        <div class="filters">
          <select id="scheduleGroup"></select>
          <select id="scheduleYear"></select>
          <select id="scheduleMonth"></select>
          <select id="scheduleWeek"></select>
          <button id="scheduleLoadBtn">Загрузить</button>
          <button id="copyWeekBtn">Копировать предыдущую неделю</button>
          <button id="saveScheduleBtn">Сохранить</button>
          <button id="exportAllGroupsBtn">Экспорт всех групп</button>
          <button type="button" class="btn" id="btnDaySchedule">Расписание на день</button>
        </div>

        <div class="content-scroll">
          <div id="scheduleContainer"></div>
        </div>
      </div>

      <!-- ===== TABEL ===== -->
      <div id="tabTabel" class="tab-content">
        <div class="filters">
          <select id="tabelGroup"></select>
          <select id="tabelYear"></select>
          <select id="tabelMonth"></select>
          <button id="tabelLoadBtn">Загрузить</button>
          <button id="tabelExportBtn">Экспорт</button>
          <button id="tabelExport2Btn">Экспорт 2</button>
        </div>

        <div class="content-scroll">
          <div id="tabelContainer"></div>
        </div>
      </div>

      <!-- ===== PROFILE ===== -->
      <div id="tabProfile" class="tab-content">
        <div class="filters">
          <select id="profileYear"></select>
          <select id="profileMonth"></select>
        </div>

        <div id="profileAdminPanel" style="display:none;">
          <select id="profileEmployee"></select>
          <input type="file" id="profileFile">
          <button id="profileUploadBtn">Загрузить</button>
          <button id="profileDeleteBtn">Удалить</button>
          <div id="profileUploadStatus" class="small-info"></div>
        </div>

        <div id="profileImageContainer"></div>
      </div>

    </div>
  </div>

<!-- ===== SCHEDULE DAY GET ===== -->
<div id="dayScheduleModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:9999;">
  <div class="daymodal-card">

    <div class="daymodal-head">
      <div class="daymodal-title">Расписание на день</div>
      <button type="button" class="btn btn--ghost" id="dayScheduleClose">×</button>
    </div>

    <div class="daymodal-controls">
      <div>Дата:</div>
      <input type="date" id="dayScheduleDate" />
      <button type="button" class="btn" id="dayScheduleLoad">Показать</button>
    </div>

    <div id="dayScheduleBox"></div>
  </div>
</div>



  

  <script src="/frontend/app.js?v=<?= htmlspecialchars($ver, ENT_QUOTES) ?>"></script>

</body>
</html>
