<?php

namespace Escavador\Vespa\Commands;

use Carbon\Carbon;
use Escavador\Vespa\Common\EnumModelStatusVespa;
use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Models\DocumentDefinition;
use Escavador\Vespa\Models\SimpleClient;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Log;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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

    protected $bulk;

    protected $time_out;

    protected $logger;

    protected $vespa_client;

    public function __construct()
    {
        parent::__construct();
        $this->vespa_status_column = config('vespa.model_columns.status', 'vespa_status');
        $this->vespa_date_column = config('vespa.model_columns.date', 'vespa_last_indexed_date');
        $this->document_definitions = DocumentDefinition::loadDefinition();

        $this->vespa_client = Utils::defaultVespaClient();

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

        $all = $this->option('all');
        $models = $this->option('model');
        $limit = $this->option('limit');
        $bulk = $this->option('bulk');
        $time_out = $this->option('time-out');

        if (!is_array($models))
        {
            $this->error('The [model] argument has to be an array.');
            exit(1);
        }
        if (!$models && !$all)
        {
            $this->error('At least one [model] is required to feed Vespa. If you want feed Vespa with all models, use the argument [all].');
            exit(1);
        }

        if ($models && $all)
        {
            $this->error('Only one argument ([all] or [model]) can be used at a time.');
            exit(1);
        }

        //Get all mapped models
        if (!$models && $all)
        {
            $models = DocumentDefinition::findAllTypes($this->document_definitions);
        }

        if (!is_numeric($limit))
        {
            $this->error('The [limit] argument has to be a number.');
            exit(1);
        }

        if (!is_numeric($bulk))
        {
            $this->error('The [bulk] argument has to be a number.');
            exit(1);
        }

        if ($limit <= 0)
        {
            $this->error('The [limit] argument has to be greater than 0.');
            exit(1);
        }

        if ($bulk <= 0)
        {
            $this->error('The [bulk] argument has to be greater than 0.');
            exit(1);
        }

        $this->limit = intval($limit);
        $this->bulk = intval($bulk);

        if ($time_out !== null && !is_numeric($time_out))
        {
            $this->error('The [time-out] argument has to be a number.');
            exit(1);
        }

        if ($time_out !== null && !is_numeric($time_out <= 0))
        {
            $this->error('The [time-out] argument has to be a number.');
            exit(1);
        }

        //TODO test it
        set_time_limit($time_out ?: 0);
        $was_fed = false;

        foreach ($models as $model)
        {
            if (!($model_definition = DocumentDefinition::findDefinition($model, null, $this->document_definitions)))
            {
                $this->error("The model [$model] is not mapped at vespa config file.");
                //go to next model
                continue;
            }

            $table_name = $model_definition->getModelTable();

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
                $this->process($model_definition);
                $was_fed = true;
            }
            catch (\Exception $e)
            {
                $this->message('error', '... fail.' . ' '. $e->getMessage() );
                exit(1);
            }

            unset($temp_model);
        }

        if($was_fed)
        {
            $total_duration = Carbon::now()->diffInSeconds($start_time);
            $this->message('info', 'The vespa was fed in '. gmdate('H:i:s:m', $total_duration). '.');
        }
    }

    protected function getLimitDefault()
    {
        return config('vespa.default.limit', 0);
    }

    protected function getBulkDefault()
    {
        return config('vespa.default.bulk', 0);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array (
            array('model', 'M', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Which models to include', array()),
            array('limit', 'L', InputOption::VALUE_OPTIONAL, 'Limit of model to feed Vespa', $this->getLimitDefault()),
            array('time-out', 'T', InputOption::VALUE_OPTIONAL, 'Defines the execution timeout', $this->time_out), //TODO testing this
            array('all', 'A', InputOption::VALUE_NONE, 'Feed all mapped models on Vespa config'),
            array('bulk', 'B', InputOption::VALUE_OPTIONAL, 'description here', $this->getBulkDefault()),

            //TODO arguments
            //array('ignore', 'I', InputOption::VALUE_OPTIONAL, 'Which models to ignore', ''),
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

    private function pepareBulk(array $documents)
    {
        $bulks = [];
        $bulk = [];
        $size =  count($documents);
        for ($i = 0; $i < $size; $i++)
        {
            $bulk[] = $documents[$i];

            if(count($bulk) == $this->bulk || $i == $size - 1)
            {
                $bulks[] =  $bulk;
                $bulk = [];
            }
        }
        return $bulks;
    }

    private function process($model_definition)
    {
        $model_class = $model_definition->getModelClass();
        $model = $model_definition->getDocumentType();
        $documents = $model_class::getVespaDocumentsToIndex($this->limit);

        $count_docs = count($documents);

        if ($documents !== null && !$count_docs)
        {
            $this->message('info', "[$model] already up-to-date.");
            return false;
        }

        $this->message('info', "Feed vespa with [$count_docs] [$model].");
        $documents = $this->pepareBulk($documents);
        $indexed = [];
        foreach ($documents as $bulk)
        {
            //Records on vespa
            $result = $this->vespa_client->sendDocuments($model_definition, $bulk);
            $indexed = array_merge($indexed, $result);
        }

        //Update model's vespa info in database
        $model_class::markAsVespaIndexed($indexed);

        $this->message('info', count($indexed)." /$count_docs [$model] was done.");
        return true;
    }
}
