<?php

namespace Escavador\Vespa\Commands;

use Carbon\Carbon;
use Escavador\Vespa\Common\LogManager;
use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Common\VespaExceptionSubject;
use Escavador\Vespa\Exception\VespaException;
use Escavador\Vespa\Jobs\FeedDocumentJob;
use Escavador\Vespa\Models\DocumentDefinition;
use Escavador\Vespa\Models\SimpleClient;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
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
    protected $description = 'Feed the Vespa with documents.';

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

        $this->logger =  new LogManager();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->message('debug', 'Feed was started');

        $start_time = Carbon::now();

        $all = $this->option('all');
        $async = $this->option('async');
        $models = $this->option('model');
        $limit = $this->option('limit');
        $bulk = $this->option('bulk');
        $time_out = $this->option('time-out');
        $queue = $this->option('queue');

        if (!is_array($models))
        {
            $this->message('error', 'The [model] argument has to be an array.');
            exit(1);
        }
        if (!$models && !$all)
        {
            $this->message('error', 'At least one [model] is required to feed Vespa. If you want feed Vespa with all models, use the argument [all].');
            exit(1);
        }

        if ($models && $all)
        {
            $this->message('error', 'Only one argument ([all] or [model]) can be used at a time.');
            exit(1);
        }

        //Get all mapped models
        if (!$models && $all)
        {
            $models = DocumentDefinition::findAllTypes($this->document_definitions);
        }

        if (!is_numeric($limit))
        {
            $this->message('error', '['.implode(',', $models) . ']: The [limit] argument has to be a number.');
            exit(1);
        }

        if (!is_numeric($bulk))
        {
            $this->message('error', '['.implode(',', $models) . ']: The [bulk] argument has to be a number.');
            exit(1);
        }

        if ($limit <= 0)
        {
            $this->message('error', '['.implode(',', $models) . ']: The [limit] argument has to be greater than 0.');
            exit(1);
        }

        if ($bulk <= 0)
        {
            $this->message('error', '['.implode(',', $models) . ']: The [bulk] argument has to be greater than 0.');
            exit(1);
        }

        if ($bulk > $limit)
        {
            $this->message('warn', '['.implode(',', $models) . ']: The [bulk] argument can not to be greater than [limit] argument. We lets ignore [bulk] argument.');
            $bulk = $limit;
        }

        if ($queue && !$async)
        {
            $this->message('warn', '['.implode(',', $models) . ']: The [queue] argument only taken into account if the feed is async.');
        }

        $this->limit = intval($limit);
        $this->bulk = intval($bulk);

        if ($time_out !== null && !is_numeric($time_out))
        {
            $this->message('error', '['.implode(',', $models) . ']: The [time-out] argument has to be a number.');
            exit(1);
        }

        if ($time_out !== null && !is_numeric($time_out <= 0))
        {
            $this->message('error', '['.implode(',', $models) . ']: The [time-out] argument has to be a number.');
            exit(1);
        }

        //TODO test it
        set_time_limit($time_out ?: 0);
        $was_fed = false;

        foreach ($models as $model)
        {
            if (!($model_definition = DocumentDefinition::findDefinition($model, null, $this->document_definitions)))
            {
                $this->message('error', "[$model]: The model is not mapped at Vespa config file.");
                //go to next model
                continue;
            }

            $table_name = $model_definition->getModelTable();

            if (!Schema::hasColumn($table_name, $this->vespa_status_column))
            {
                $this->message('error', "[$model]: Table [$table_name] does not have the column [$this->vespa_status_column].");
                exit(1);
            }

            if (!Schema::hasColumn($table_name, $this->vespa_date_column))
            {
                $this->message('error', "[$model]: Table [$table_name] does not have the column [$this->vespa_date_column].");
                exit(1);
            }

            $this->message('info', "[$model]: Feed is already!");
            $model_class = $model_definition->getModelClass();
            $model = $model_definition->getDocumentType();

            if($async)
            {
                $this->processAsync($model_definition, $model_class, $model, $queue);
            }
            else
            {
                try
                {
                    $was_fed = $this->processNow($model_definition, $model_class, $model);
                }
                catch (\Exception $ex)
                {
                    $e = new VespaException("[$model]: Processing failed. Error: ". $ex->getMessage());
                    VespaExceptionSubject::notifyObservers($e);
                    $this->message('error', $e->getMessage());
                    exit(1);
                }
            }
        }

        if($was_fed)
        {
            $total_duration = Carbon::now()->diffInSeconds($start_time);
            $this->message('info', '['.implode(',', $models) . ']: Vespa was fed in '. gmdate('H:i:s:m', $total_duration));
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
            array('async', 'J', InputOption::VALUE_NONE, 'Create feed job to run async'),
            array('queue', 'Q', InputOption::VALUE_OPTIONAL, 'Queue that receive feed jobs'),
            array('bulk', 'B', InputOption::VALUE_OPTIONAL, 'description here', $this->getBulkDefault()),

            //TODO arguments
            //array('ignore', 'I', InputOption::VALUE_OPTIONAL, 'Which models to ignore', ''),
        );
    }

    protected function message($type = 'debug', $text)
    {
        if (!app()->environment('production'))
        {
            switch ($type)
            {
                case 'error':
                    $this->error($text);
                    break;
                case 'info':
                    $this->info($text);
                    break;
                case 'warn':
                case 'warning':
                    $this->warn($text);
                    break;
                default:
                    $this->info($text);
            }
        }

        $this->logger->log($text, $type);
    }

    private function processNow($model_definition, $model_class, $model)
    {
        $items = $this->limit;
        $total_indexed = 0;
        while($items > 0)
        {
            if($items >= $this->bulk)
            {
                $items -= $this->bulk;
                $requested_documents = $this->bulk;
            }
            else
            {
                $requested_documents = $items;
                $items = 0;
            }

            $documents = $model_class::getVespaDocumentsToIndex($requested_documents);
            $count_docs = count($documents);

            if ($documents !== null && $count_docs <= 0)
            {
                $this->message('info', "[$model] already up-to-date.");
                break;
            }

            //Records on vespa
            $result = $this->vespa_client->sendDocuments($model_definition, $documents);
            $indexed = [];
            $not_indexed = [];
            foreach ($documents as $document)
            {
                if(in_array($document, $result))
                {
                    $indexed[] = $document;
                }
                else
                {
                    $not_indexed[] = $document;
                }
            }

            $count_indexed = count($indexed);
            $total_indexed += $count_indexed;

            //Update model's vespa info in database
            $model_class::markAsVespaIndexed(collect($indexed)->pluck('id')->all());
            $model_class::markAsVespaNotIndexed(collect($not_indexed)->pluck('id')->all());

            $this->message('debug', "[$model]:". $count_indexed . " of " . count($documents) . " were indexed in Vespa.");
        }

        $this->message('info', "[$model]: $total_indexed/$this->limit was done.");

        return $total_indexed > 0;
    }

    private function processAsync($model_definition, $model_class, $model, $queue = null)
    {
        $items = $this->limit;
        $total_scheduled = 0;
        $total_documents = 0;
        while($items > 0)
        {
            if($items >= $this->bulk)
            {
                $items -= $this->bulk;
                $requested_documents = $this->bulk;
            }
            else
            {
                $requested_documents = $items;
                $items = 0;
            }

            $document_ids = $model_class::getVespaDocumentIdsToIndex($requested_documents);
            $count_docs = count($document_ids);

            if ($document_ids !== null && $count_docs <= 0)
            {
                $this->message('info', "[$model] already up-to-date.");
                break;
            }

            // Create FeedJob
            try
            {
                $model_class::markAsVespaIndexed($document_ids);
                FeedDocumentJob::dispatch($model_definition, $model_class, $model, $document_ids, $queue);
            }
            catch (\Exception $ex)
            {
                $model_class::markAsVespaNotIndexed($document_ids);
                $this->message('debug', "[$model]: Failed to create job FeedDocumentJob." . $ex->getMessage());
                return false;
            }

            $total_documents += $count_docs;
            $total_scheduled++;
        }
        $this->message('info', "[$model]: $total_scheduled FeedDocumentJob jobs are created with $total_documents documents.");
        return $total_scheduled > 0;
    }
}
