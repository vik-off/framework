<?php
session_start();

// обозначение корня ресурса
$_url = dirname($_SERVER['SCRIPT_NAME']);
define('WWW_ROOT', 'http://'.$_SERVER['SERVER_NAME'].(strlen($_url) > 1 ? $_url : '').'/');
define('WWW_URI', 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
define('FS_ROOT', realpath('.').DIRECTORY_SEPARATOR);

/** определение ajax-запроса */
define('AJAX_MODE', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

/** индекс файл */
define('INDEX_FILE', basename(__FILE__));

// отправка Content-type заголовка
header('Content-Type: text/html; charset=utf-8');

// подключение файлов CMF
require_once(FS_ROOT.'setup.php');

// инициализация класса Request, чтобы отсеять ненужные запросы (favicon)
Request::get();

// сохранение статистики
UserStatistics_Model::get()->savePrimaryStatistics();

/** контроллер отображения по умолчанию */
define('DEFAULT_CONTROLLER', 'page');

// выполнение приложения
if(AJAX_MODE)
	App::get()->ajax();
else
	App::get()->run();

?>