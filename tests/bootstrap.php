<?php

define('TEST_PATH', dirname(realpath(__FILE__)));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    TEST_PATH . '/library',
    get_include_path(),
)));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('ZFE_');
