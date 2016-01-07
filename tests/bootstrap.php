<?php

define('TEST_PATH', dirname(realpath(__FILE__)));
define('LIBRARY_PATH', realpath(TEST_PATH . '/../library'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    TEST_PATH,
    LIBRARY_PATH,
    get_include_path(),
)));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('ZFE_');
$autoloader->registerNamespace('Test_');

// Set mb encoding
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
