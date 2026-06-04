<?php
/**
 * Плагин для SEO-редиректов SeoRedirector by Vectorserver:
 * 1. Редирект с /index.php на корень/чистый URL
 * 2. Удаление конечного слэша
 * 3. Перевод URL в нижний регистр (Lower Case)
 *
 * Событие: OnHandleRequest
 */

// 1. Проверяем имя события
if ($modx->event->name !== 'OnHandleRequest') {
    return;
}

// 2. Проверяем контекст — исключаем админку (mgr)
if ($modx->context->get('key') === 'mgr') {
    return;
}

// Получаем текущий URI запроса
$uri = $_SERVER['REQUEST_URI'];

// Разделяем путь и GET-параметры (например, /Index.php?id=5)
$url_parts = explode('?', $uri);
$path = $url_parts[0];
$query_string = !empty($url_parts[1]) ? '?' . $url_parts[1] : '';

$need_redirect = false;
$new_path = $path;

// --- ИСКЛЮЧЕНИЕ ДЛЯ ФАЙЛОВ ---
// Если запрос идет к существующему физическому файлу (кроме index.php), ничего не делаем
if ($path !== '/index.php' && is_file(MODX_BASE_PATH . ltrim($path, '/'))) {
    return;
}

//Удаление index.php ---
// Проверяем, заканчивается ли путь на index.php или равен ему
if ($new_path === '/index.php' || substr($new_path, -10) === '/index.php') {
    // Отрезаем index.php из конца пути
    $new_path = substr($new_path, 0, -10);
    // Если после отрезания путь стал пустым, делаем его корнем
    if ($new_path === '') {
        $new_path = '/';
    }
    $need_redirect = true;
}

//Удаление конечного слэша ---
// Если в конце есть слэш и это не главная страница сайта
if (substr($new_path, -1) === '/' && $new_path !== '/') {
    $new_path = rtrim($new_path, '/');
    $need_redirect = true;
}

//Перевод в нижний регистр (Lower Case) ---
// Используем mb_strtolower для корректной работы с любыми символами
$lowercase_path = mb_strtolower($new_path, 'UTF-8');

if ($new_path !== $lowercase_path) {
    $new_path = $lowercase_path;
    $need_redirect = true;
}

// --- ВЫПОЛНЕНИЕ РЕДИРЕКТА ---
if ($need_redirect) {
    // Получаем базовый URL сайта (учитывает http/https и текущий домен)
    $site_url = $modx->getOption('site_url');

    // Формируем итоговый очищенный URL
    $clean_path = ltrim($new_path, '/');
    $redirect_url = rtrim($site_url, '/') . '/' . $clean_path . $query_string;

    // Делаем правильный 301-й редирект
    $modx->sendRedirect($redirect_url, array('responseCode' => 'HTTP/1.1 301 Moved Permanently'));
    exit();
}