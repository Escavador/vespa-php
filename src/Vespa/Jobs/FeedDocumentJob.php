<?php

namespace Escavador\Vespa\Jobs;

use Carbon\Carbon;
use Escavador\Vespa\Common\LogManager;
use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Common\VespaExceptionSubject;
use Escavador\Vespa\Exception\VespaException;
use Escavador\Vespa\Models\DocumentDefinition;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class FeedDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $model_class;
    protected $document_ids;
    protected $model;
    protected $model_definition;
    protected $vespa_client;
    protected $logger;

    public function __construct(DocumentDefinition $model_definition, string $model_class, $model, array $document_ids, string $queue = null)
    {
        $this->model_definition = $model_definition;
        $this->model_class = $model_class;
        $this->model = $model;
        $this->document_ids = $document_ids;
        if($queue == null)
        {
            $queue = config('vespa.default.queue', 'vespa');
        }
        $this->onQueue($queue);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $start_time = Carbon::now();
        $this->logger =  new LogManager();
        $this->vespa_client = Utils::defaultVespaClient();

        $documents = $this->model_class::getVespaDocumentsToIndex(count($this->document_ids), $this->document_ids);
        try
        {
            //Records on vespa
            $result = $this->vespa_client->sendDocuments($this->model_definition, $documents);
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
            if($count_indexed == 0)
            {
                throw new VespaException("It was not possible to index any document to the Vespa.");
            }
            //Update model's vespa info in database
            $this->model_class::markAsVespaIndexed(collect($indexed)->pluck('id')->all());
            $this->model_class::markAsVespaNotIndexed(collect($not_indexed)->pluck('id')->all());
        }
        catch (\Exception $ex)
        {
            $this->model_class::markAsVespaNotIndexed(collect($documents)->pluck('id')->all());
            VespaExceptionSubject::notifyObservers($ex);
            throw $ex;
        }
        $total_duration = Carbon::now()->diffInSeconds($start_time);
        $this->logger->log("[$this->model]: Vespa was fed in ". gmdate('H:i:s:m', $total_duration), "info");
        $this->logger->log("[$this->model]: $count_indexed of ". count($documents) . " were indexed in Vespa.", "debug");
    }
}
