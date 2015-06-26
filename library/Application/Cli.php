<?php

class ZFE_Application_Cli extends Zend_Application
{
    protected $opts;

    public function __construct($environment, $options)
    {
        $this->opts = new Zend_Console_Getopt(array(
            'verbose|v' => 'Be verbose',
            'help|h' => 'Show this help text'
        ));

        parent::__construct($environment, $options);

        Zend_Registry::set('CliApplication', $this);
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
