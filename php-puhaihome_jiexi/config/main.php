<?php

defined('ENV_DEV') or define('ENV_DEV', 0);
date_default_timezone_set('PRC');
if (ENV_DEV) {
    defined('LISTEN_HOST') or define('LISTEN_HOST', '0.0.0.0');
    defined('MONGO_HOST') or define('MONGO_HOST', '192.168.100.233:27017');
    defined('DAEMONIZE') or define('DAEMONIZE', 0);
} else {
    defined('LISTEN_HOST') or define('LISTEN_HOST', '0.0.0.0');
//    defined('MONGO_HOST') or define('MONGO_HOST', '10.10.11.15:27017');
    defined('MONGO_HOST') or define('MONGO_HOST', '127.0.0.1:27017');
    defined('DAEMONIZE') or define('DAEMONIZE', 1);
}
