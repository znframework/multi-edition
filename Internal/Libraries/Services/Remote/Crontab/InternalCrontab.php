<?php namespace ZN\Services\Remote;

use Processor, SSH, Folder, File, Html;
use ZN\Services\Remote\Crontab\Exception\InvalidTimeFormatException;

class InternalCrontab extends RemoteCommon implements InternalCrontabInterface, InternalCrontabIntervalInterface
{
    //--------------------------------------------------------------------------------------------------------
    //
    // Author     : Ozan UYKUN <ozanbote@gmail.com>
    // Site       : www.znframework.com
    // License    : The MIT License
    // Copyright  : (c) 2012-2016, znframework.com
    //
    //--------------------------------------------------------------------------------------------------------

    const config = ['Services:crontab', 'Services:processor'];

    //--------------------------------------------------------------------------------------------------------
    // Crontab Interval
    //--------------------------------------------------------------------------------------------------------
    //
    // comands
    //
    //--------------------------------------------------------------------------------------------------------
    use InternalCrontabIntervalTrait;

    //--------------------------------------------------------------------------------------------------------
    // Type
    //--------------------------------------------------------------------------------------------------------
    //
    // @var string
    //
    //--------------------------------------------------------------------------------------------------------
    protected $type;

    //--------------------------------------------------------------------------------------------------------
    // Debug
    //--------------------------------------------------------------------------------------------------------
    //
    // @var boolean: false
    //
    //--------------------------------------------------------------------------------------------------------
    protected $debug = false;

    //--------------------------------------------------------------------------------------------------------
    // Driver
    //--------------------------------------------------------------------------------------------------------
    //
    // @var string
    //
    //--------------------------------------------------------------------------------------------------------
    protected $crontabDir = '';

    //--------------------------------------------------------------------------------------------------------
    // Jobs
    //--------------------------------------------------------------------------------------------------------
    //
    // @var array
    //
    //--------------------------------------------------------------------------------------------------------
    protected $jobs = [];

    //--------------------------------------------------------------------------------------------------------
    // Constructor
    //--------------------------------------------------------------------------------------------------------
    //
    // __costruct()
    //
    //--------------------------------------------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();

        $this->path   = SERVICES_PROCESSOR_CONFIG['path'];
        $this->debug  = SERVICES_CRONTAB_CONFIG['debug'];

        $this->crontabDir = File::originpath(STORAGE_DIR.'Crontab'.DS);
    }

    //--------------------------------------------------------------------------------------------------------
    // Driver
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  string $driver: empty
    // @return object
    //
    //--------------------------------------------------------------------------------------------------------
    public function driver(String $driver) : InternalCrontab
    {
        Processor::driver($driver);

        return $this;
    }

    //--------------------------------------------------------------------------------------------------------
    // Path
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  string $path: empty
    // @return object
    //
    //--------------------------------------------------------------------------------------------------------
    public function path(String $path = NULL) : InternalCrontab
    {
        $this->path = $path;

        return $this;
    }

    //--------------------------------------------------------------------------------------------------------
    // List
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  void
    // @return string
    //
    //--------------------------------------------------------------------------------------------------------
    public function list() : Bool
    {
        return Processor::exec('crontab -l');
    }

    //--------------------------------------------------------------------------------------------------------
    // Create File
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  string $name: crontab.txt
    // @return object
    //
    //--------------------------------------------------------------------------------------------------------
    public function createFile(String $name = 'crontab.txt') : Bool
    {
        if( ! Folder::exists($this->crontabDir) )
        {
            return Folder::create($this->crontabDir);
        }
        else
        {
            $cronFile = $this->crontabDir.$name;

            if( ! is_file($cronFile) )
            {
                $command = 'crontab -l > '.$cronFile.' && [ -f '.$cronFile.' ] || > '.$cronFile;

                return Processor::exec($command);
            }
        }
    }

    //--------------------------------------------------------------------------------------------------------
    // Delete File
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  string $name: crontab.txt
    // @return object
    //
    //--------------------------------------------------------------------------------------------------------
    public function deleteFile(String $name = 'crontab.txt') : Bool
    {
        $cronFile = $this->crontabDir.$name;

        if( is_file($cronFile) )
        {
            $command = 'rm '.$cronFile;

            return Processor::exec($command);
        }

        return false;
    }

    //--------------------------------------------------------------------------------------------------------
    // Remove
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  string $name: crontab.txt
    // @return object
    //
    //--------------------------------------------------------------------------------------------------------
    public function remove(String $name = 'crontab.txt') : Bool
    {
        $this->deleteFile($name);

        return Processor::exec('crontab -r');
    }

    //--------------------------------------------------------------------------------------------------------
    // Add
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  void
    // @return object
    //
    //--------------------------------------------------------------------------------------------------------
    public function add() : InternalCrontab
    {
        $command = $this->_command();

        $this->_defaultVariables();

        $this->jobs[] = $command;

        return $this;
    }

    //--------------------------------------------------------------------------------------------------------
    // Run
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  string $cmd: empty
    // @return string
    //
    //--------------------------------------------------------------------------------------------------------
    public function run(String $cmd = NULL) : Bool
    {
        $command = '';

        if( empty($this->jobs) )
        {
            $command = $this->_command();

            if( ! empty($cmd) )
            {
                $command = $cmd;
            }

            $this->stringCommand = $command;

            return Processor::exec($command);
        }
        else
        {
            $jobs = $this->jobs;

            $this->jobs = [];

            foreach( $jobs as $job )
            {
                Processor::exec($job);

                $this->stringCommand .= $job.Html::br();
            }

            return true;
        }
    }

    //--------------------------------------------------------------------------------------------------------
    // Protected Command Fix
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  string $command: empty
    // @return string
    //
    //--------------------------------------------------------------------------------------------------------
    protected function _commandFix($command)
    {
        if( strlen($command) === 1 )
        {
            return prefix($command, '-');
        }

        return $command;
    }

    //--------------------------------------------------------------------------------------------------------
    // Debug
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  bool   $status: true
    // @return object
    //
    //--------------------------------------------------------------------------------------------------------
    public function debug(Bool $status = true) : InternalCrontab
    {
        $this->debug = $status;

        return $this;
    }

    //--------------------------------------------------------------------------------------------------------
    // Command
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  string $command: empty
    // @return object
    //
    //--------------------------------------------------------------------------------------------------------
    public function command(String $command) : InternalCrontab
    {
        $fix = '';

        $command = str_replace('-', '', $command);
        $command = preg_replace('/\s+/', ' ', $command);

        if( strstr($command, ' ') )
        {
            $commands = explode(' ', $command);

            $commandJoin = '';

            foreach( $commands as $cmd )
            {
                $commandJoin .= $this->_commandFix($cmd).' ';
            }

            $this->command = rtrim($commandJoin, ' ');
        }
        else
        {
            $this->command = $this->_commandFix($command);
        }

        return $this;
    }

    //--------------------------------------------------------------------------------------------------------
    // File
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  string $file: empty
    // @return string
    //
    //--------------------------------------------------------------------------------------------------------
    public function file(String $file) : InternalCrontab
    {
        $this->type = REAL_BASE_DIR.$file;

        return $this;
    }

    //--------------------------------------------------------------------------------------------------------
    // Url
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  string $file: empty
    // @param  bool   $type: wget, get, curl
    // @return string
    //
    //--------------------------------------------------------------------------------------------------------
    public function url(String $url) : InternalCrontab
    {
        if( ! isUrl($url) )
        {
            $url = siteUrl($url);
        }

        $this->type = $url;

        return $this;
    }

    //--------------------------------------------------------------------------------------------------------
    // Protected Date Time
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  void
    // @return string
    //
    //--------------------------------------------------------------------------------------------------------
    protected function _datetime()
    {
        if( $this->interval !== '* * * * *' )
        {
            $interval = $this->interval.' ';
        }
        else
        {
            $interval = ( $this->minute    ?? '*' ) . ' '.
                        ( $this->hour      ?? '*' ) . ' '.
                        ( $this->dayNumber ?? '*' ) . ' '.
                        ( $this->month     ?? '*' ) . ' '.
                        ( $this->day       ?? '*' ) . ' ';
        }

        $this->_intervalDefaultVariables();

        return $interval;
    }

    //--------------------------------------------------------------------------------------------------------
    // Protected Date Time
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  void
    // @return string
    //
    //--------------------------------------------------------------------------------------------------------
    protected function _command()
    {
        $datetimeFormat = $this->_datetime();
        $type           = $this->type;
        $path           = $this->path;
        $command        = $this->command;
        $debug          = $this->debug;

        $match = '(\*|[0-9]{1,2}|\*\/[0-9]{1,2}|[0-9]{1,2}\s*\-\s*[0-9]{1,2}|(([0-9]{1,2})*\s*\,\s*[0-9]{1,2})+)\s+';

        if( ! preg_match('/^'.$match.$match.$match.$match.$match.'$/', $datetimeFormat) )
        {
            throw new InvalidTimeFormatException('Services', 'crontab:timeFormatError');
        }
        else
        {
            return $datetimeFormat.
                   ( ! empty($path)    ? $path    . ' ' : '' ).
                   ( ! empty($command) ? $command . ' ' : '' ).
                   ( ! empty($type)    ? $type    . ' ' : '' ).
                   ( $debug === true   ? '>> '    . $this->crontabDir . 'debug.log 2>&1' : '' );
        }
    }

    //--------------------------------------------------------------------------------------------------------
    // Protected Date Time
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  void
    // @return void
    //
    //--------------------------------------------------------------------------------------------------------
    protected function _defaultVariables()
    {
        $this->type     = NULL;
        $this->path     = NULL;
        $this->command  = NULL;
        $this->debug    = false;
    }

    //--------------------------------------------------------------------------------------------------------
    // Protected Date Time
    //--------------------------------------------------------------------------------------------------------
    //
    // @param  void
    // @return void
    //
    //--------------------------------------------------------------------------------------------------------
    protected function _intervalDefaultVariables()
    {
        $this->interval  = '* * * * *';
        $this->minute    = '*';
        $this->hour      = '*';
        $this->dayNumber = '*';
        $this->month     = '*';
        $this->day       = '*';
    }
}
