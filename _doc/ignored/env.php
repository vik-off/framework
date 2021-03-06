<?php

/**
 * Этот файл (env.php) спецефичен для каждого хоста, на котором работает сайт.
 * Его стандартное расположение: config/env.php
 * Рекомендуется помещать его в .gitignore
 * Файл хранит две директивы:
 *     - host - имя хоста для правильного подключения к бд и прочих случаев
 *     - mode - режим работы определяет какой конфигурационный файл будет подключен
 */

return array(
	'host' => 'default',
	'mode' => 'production',
);