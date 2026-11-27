
<?php
declare(strict_types=1);

define('SITE_NAME', 'EUROLINK V5');
define('SITE_URL', 'https://eurolink.valgharie.haloweb.fr');
define('SITE_VERSION', '5.0.0');

define('DATA_PATH', dirname(__DIR__) . '/data');
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads');

define('SESSION_NAME', 'EUROLINK_SESSION');
define('SESSION_LIFETIME', 7200);

define('MAX_UPLOAD_SIZE', 200 * 1024 * 1024);

date_default_timezone_set('Europe/Paris');
