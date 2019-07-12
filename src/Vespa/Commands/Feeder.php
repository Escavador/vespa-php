<?php

namespace Escavador\Vespa\Commands;

use Illuminate\Console\Command;


class Feeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vespa:feeder
                            {--buffer= : description here}
                            {--time-out= : description here}
    ';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'feeder';

    protected $BUFFER = 8000;

    protected $ITENS_BULK = 8000;

    protected $logger;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'description here.';

    public function __construct()
    {
         $hosts = explode(',', trim (config('vespa.hosts')));

         // log separado
        $this->logger = new Logger('vespa-log');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/vespa-feeder.log')), Logger::INFO);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        $models = $this->argument('models');

        $buffer = $this->argument('buffer');
        $time_out = $this->argument('time-out');
        $model = $this->argument('time-out');

        if (!ctype_digit($buffer)) {
            $this->error('The [buffer] argument has to be a number.');
            return;
        }

        if (!ctype_digit($time_out)) {
            $this->error('The [time_out] argument has to be a number.');
            return;
        }

        set_time_limit( $time_out?: 0 );

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
        return [
            ['models', InputArgument::IS_ARRAY, 'description here'],
        ];
    }
}
