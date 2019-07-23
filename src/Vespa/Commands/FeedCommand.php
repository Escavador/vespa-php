<?php

namespace Escavador\Vespa\Commands;

use Carbon\Carbon;
use Escavador\Vespa\Common\EnumModelStatusVespa;
use Escavador\Vespa\Models\SimpleClient;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Log;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use GuzzleHttp\Client;

class FeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'vespa:feed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Feed the vespa with models.';

    protected $limit;

    protected $host;

    protected $time_out;

    protected $logger;

    protected $vespa_client;

    public function __construct()
    {
        parent::__construct();
        $this->host = trim(config('vespa.host'));
        $this->vespa_status_column = config('vespa.model_columns.status', 'vespa_status');
        $this->vespa_date_column = config('vespa.model_columns.date', 'vespa_last_indexed_date');
        $this->mapped_models = config('vespa.mapped_models');
        $this->limit = $this->getLimitDefault();

        $this->vespa_client = new SimpleClient($this->host);

        //$this->logger = new Logger('vespa-log');
        //$this->logger->pushHandler(new StreamHandler(storage_path('logs/vespa-feeder.log')), Logger::INFO);
    }

    /**
     * Execute the console command.
     *
     * @return void
    */
    public function handle()
    {
        $this->message('info', 'Feed was started');
        $start_time = Carbon::now();

        $models = $this->argument('model');
        $limit = $this->option('limit');
        $time_out = $this->option('time-out');

        if (!is_array($models))
        {
            $this->error('The [model] argument has to be an array.');
            return;
        }

        if (!is_numeric($limit))
        {
            $this->error('The [limit] argument has to be a number.');
            return;
        }

        if ($limit <= 0)
        {
            $this->error('The [limit] argument has to be greater than 0.');
            return;
        }

        $this->limit = $limit;

        if ($time_out !== null && !is_numeric($time_out))
        {
            $this->error('The [time-out] argument has to be a number.');
            return;
        }

        if ($time_out !== null && !is_numeric($time_out <= 0))
        {
            $this->error('The [time-out] argument has to be a number.');
            return;
        }

        set_time_limit($time_out ?: 0);

        foreach ($models as $model)
        {
            if (!array_key_exists($model, $this->mapped_models))
            {
                $this->error("The model [$model] is not mapped at vespa config file.");
                //go to next model
                continue;
            }

            $table_name = ($this->mapped_models[$model])::getVespaDocumentTable();

            if (!Schema::hasColumn($table_name, $this->vespa_status_column))
            {
                exit($this->message('error', "The model [$this->vespa_status_column] does not have status information on the vespa."));
            }

            if (!Schema::hasColumn($table_name, $this->vespa_date_column))
            {
                exit($this->message('error', "The model [$this->vespa_date_column] does not have date information on the vespa."));
            }

            //TODO: make this async
            try
            {
                $this->process($model);
                $this->message('info', 'The vespa was fed.');
            }
            catch (\Exception $e)
            {
                $this->message('error', '... fail.' . ' '. $e->getMessage() );
            }

            unset($temp_model);
        }
    }

    protected function getLimitDefault()
    {
        return config('vespa.default.limit', 1);
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
        return array (
            array('limit', 'L', InputOption::VALUE_OPTIONAL, 'description here', $this->getLimitDefault()),
            array('time-out', 'T', InputOption::VALUE_OPTIONAL, 'description here', $this->time_out),

            //TODO arguments
            //array('dir', 'D', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The model dir', array()),
            //array('ignore', 'I', InputOption::VALUE_OPTIONAL, 'Which models to ignore', ''),
            //array('bulk', 'B', InputOption::VALUE_OPTIONAL, 'description here', 0),
        );
    }

    protected function message($type, $message)
    {
        if ($type == 'error') {
            Log::error($message);
        }

        if (!app()->environment('production')) {
            if ($type == 'error') {
                $this->error($message);
            } else if ($type == 'info') {
                $this->info($message);
            }
        }
    }

    private function process($model)
    {
        $model_class = $this->mapped_models[$model];
        $documents = $model_class::getVespaDocumentsToIndex($this->limit);

        $count_docs = count($documents);

        if ($documents !== null && !$count_docs)
        {
            $this->message('info', "[$model] already up-to-date.");
            return false;
        }

        $this->message('info', "Feed vespa with [$count_docs] [$model].");
        //Records on vespa
        $indexed = $this->vespa_client->sendDocuments($documents);

        //Update model's vespa info in database
        $model_class::markAsIndexed($indexed);

        $this->message('info', " $count_docs/". count($indexed)." [$model] was done.");
        return true;
    }
}
