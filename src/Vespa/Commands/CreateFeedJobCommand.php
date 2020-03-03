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

class CreateFeedJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'vespa:create-feedjob';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Job to feed the Vespa with documents.';

    protected $limit;

    protected $time_out;

    protected $logger;

    public function __construct()
    {
        parent::__construct();
        $this->document_definitions = DocumentDefinition::loadDefinition();

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

        $model = $this->option('model');
        $limit = $this->option('limit');

        if (!is_numeric($limit))
        {
            $this->message('error', "[{$model}]: The [limit] argument has to be a number.");
            exit(1);
        }

        if ($limit <= 0)
        {
            $this->message('error', "[{$model}]: The [limit] argument has to be greater than 0.");
            exit(1);
        }
        $this->limit = intval($limit);

        if (!($model_definition = DocumentDefinition::findDefinition($model, null, $this->document_definitions)))
        {
            $this->message('error', "[$model]: The model is not mapped at Vespa config file.");
            exit(1);
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

        //TODO: make this async
        try
        {
            dispatch(new FeedDocumentJob($model_definition, $this));
        }
        catch (\Exception $ex)
        {
            $e = new VespaException("[$model]: . Error: ". $ex->getMessage());
            VespaExceptionSubject::notifyObservers($e);
            $this->message('error', $e->getMessage());
            exit(1);
        }

        $total_duration = Carbon::now()->diffInSeconds($start_time);
        $this->message('info', "[$model]: Vespa was fed in ". gmdate('H:i:s:m', $total_duration));
    }

    protected function getLimitDefault()
    {
        return config('vespa.default.limit', 0);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array (
            array('model', 'M', InputOption::VAL, 'Which model to include'),
            array('limit', 'L', InputOption::VALUE_OPTIONAL, 'Limit of model to feed Vespa', $this->getLimitDefault()),
            array('bulk', 'B', InputOption::VALUE_OPTIONAL, 'description here', $this->getBulkDefault()),
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
}
