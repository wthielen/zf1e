<?php

// // Define path to application directory
// defined('APPLICATION_PATH')
//     || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// // Define application environment
// defined('APPLICATION_ENV')
//     || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

require_once __DIR__ . '/../vendor/autoload.php';

// REMOVED 2016APR
// define('TEST_PATH', dirname(realpath(__FILE__)));
// define('LIBRARY_PATH', realpath(TEST_PATH . '/../library'));
//
// // Ensure library/ is on include_path
// set_include_path(implode(PATH_SEPARATOR, array(
//     TEST_PATH,
//     LIBRARY_PATH,
//     get_include_path(),
// )));
//
// require_once 'Zend/Loader/Autoloader.php';
// $autoloader = Zend_Loader_Autoloader::getInstance();
// $autoloader->registerNamespace('ZFE_');
// $autoloader->registerNamespace('Test_');
//
// // Set mb encoding
// mb_internal_encoding('UTF-8');
// mb_regex_encoding('UTF-8');
