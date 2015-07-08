<?php

class ZFE_Application_Cli extends Zend_Application
{
    protected $opts;

    public function __construct($environment, $options)
    {
        $this->opts = new Zend_Console_Getopt(array(
            'module|m=w' => 'Select module',
            'verbose|v' => 'Be verbose',
            'help|h' => 'Show this help text'
        ));

        $module = $this->getModule();
        if ($module !== 'default') {
            $options['config'][] = APPLICATION_PATH . '/modules/' . $module . '/configs/module.ini';
        }

        parent::__construct($environment, $options);

        Zend_Registry::set('CliApplication', $this);
    }


    public function getModule()
    {
        return $this->opts->module ? $this->opts->module : 'default';
    }

    public function run()
    {
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
    }

    public function error($message)
    {
        fwrite(STDERR, $message . "\n");
    }

    public function usage()
    {
        echo $this->opts->getUsageMessage();
    }
}
