<?php

namespace Escavador\Vespa\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Log;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Feeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'vespa:feeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'description here.';

    protected $buffer;

    protected $time_out;

    protected $logger;


    public function __construct()
    {
        parent::__construct();
        $hosts = explode(',', trim(config('vespa.hosts')));

        //$this->logger = new Logger('vespa-log');
        //$this->logger->pushHandler(new StreamHandler(storage_path('logs/vespa-feeder.log')), Logger::INFO);
        //yaml_parse($yaml);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $model = $this->argument('model');
        $buffer = $this->option('buffer');
        $time_out = $this->option('time-out');

        if (!is_array($model))
        {
            $this->error('The [model] argument has to be an array.');
            return;
        }

        if ($buffer !== null && !ctype_digit($buffer))
        {
            $this->error('The [buffer] argument has to be a number.');
            return;
        }

        if ($time_out !== null && !ctype_digit($time_out))
        {
            $this->error('The [time-out] argument has to be a number.');
            return;
        }

        $mapped_models = config('vespa.mapped_models');

        foreach ($model as $item)
        {
            if (!array_key_exists($item, $mapped_models))
            {
                $this->error("The model [$item] is not mapped at vespa config file.");
                return;
            }
        }

        set_time_limit($time_out ?: 0);

        $this->message('info', '....started.');
        $start_time = Carbon::now();


        $this->message('info', '... finished.');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array (
            array('model', InputArgument::IS_ARRAY, 'Which models to include', array()),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('buffer', 'B', InputOption::VALUE_OPTIONAL, 'description here', $this->buffer),
            array('time-out', 'T', InputOption::VALUE_OPTIONAL, 'description here', $this->time_out),
            array('dir', 'D', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The model dir', array()),
            array('ignore', 'I', InputOption::VALUE_OPTIONAL, 'Which models to ignore', ''),
        );
    }

    protected function getDefaultBuffer()
    {
        return config('vespa.default.buffer', 0);
    }

    protected function getDefaultBulk()
    {
        return config('vespa.default.bulk', 0);
    }

    protected function message($type, $message)
    {
        if ($type == 'error')
        {
            Log::error($message);
        }

        if (!app()->environment('production'))
        {
            $this->error($message);
        }
    }
}
