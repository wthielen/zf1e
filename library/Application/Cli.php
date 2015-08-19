<?php

class ZFE_Application_Cli extends Zend_Application
{
    protected static $optConfig = array(
        'module|m=w' => 'Select module',
        'verbose|v' => 'Be verbose',
        'help|h' => 'Show this help text'
    );

    protected $opts;

    public function __construct($environment, $options)
    {
        $optConfig = static::$optConfig;

        if (isset($options['optrules'])) {
            $optConfig = array_merge($optConfig, $options['optrules']);
            unset($options['optrules']);
        }

        $this->opts = new Zend_Console_Getopt($optConfig);

        $module = $this->getModule();
        if ($module !== 'default') {
            $options['config'][] = APPLICATION_PATH . '/modules/' . $module . '/configs/module.ini';
        }

        parent::__construct($environment, $options);

        try {
            $this->opts->parse();
        } catch (Zend_Console_Getopt_Exception $e) {
            $this->error("Error parsing command line options: {$e->getMessage()}");
            exit(1);
        }

        if ($this->opts->h) {
            $this->usage();
            exit(0);
        }
        Zend_Registry::set('CliApplication', $this);
    }

    public function getModule()
    {
        return $this->opts->module ? $this->opts->module : 'default';
    }

    public function run() {}

    public function error($message)
    {
        $ts = new DateTime();
        $str = sprintf("%s [ERROR] %s" . PHP_EOL, $ts->format(DateTime::ISO8601), $message);
        fwrite(STDERR, $str);
    }

    public function warn($message)
    {
        $ts = new DateTime();
        $str = sprintf("%s [WARNING] %s" . PHP_EOL, $ts->format(DateTime::ISO8601), $message);
        fwrite(STDERR, $str);
    }

    public function notice($message)
    {
        $ts = new DateTime();
        $str = sprintf("%s [NOTICE] %s" . PHP_EOL, $ts->format(DateTime::ISO8601), $message);
        fwrite(STDOUT, $str);
    }

    public function usage()
    {
        echo $this->opts->getUsageMessage();
    }
}
