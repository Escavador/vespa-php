<?php

namespace Escavador\Vespa\Jobs;

use Carbon\Carbon;
use Escavador\Vespa\Common\LogManager;
use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Common\VespaExceptionSubject;
use Escavador\Vespa\Enum\OperationDocumentEnum;
use Escavador\Vespa\Exception\VespaException;
use Escavador\Vespa\Exception\VespaFailDeleteDocumentException;
use Escavador\Vespa\Exception\VespaFeedException;
use Escavador\Vespa\Exception\VespaInvalidOperationDocumentException;
use Escavador\Vespa\Interfaces\AbstractClient;
use Escavador\Vespa\Models\DocumentDefinition;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class UpdateDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $model_class;
    protected $documents;
    protected $document_type;
    protected $document_namespace;
    protected $model_definition;
    protected $vespa_client;
    protected $logger;
    protected $update_chunk_size;
    protected $update_model_database;

    public function __construct(string $operation, DocumentDefinition $model_definition, array $document_ids, bool $update_model_database = true, AbstractClient $client = null, string $queue = null)
    {
        $this->update_model_database = $update_model_database;
        $this->model_definition = $model_definition;
        $this->model_class = $model_definition->getModelClass();
        $this->document_type = $model_definition->getDocumentType();
        $this->document_namespace = $model_definition->getDocumentNamespace();

        $this->documents = $document_ids;
        $this->vespa_client = $client?: Utils::defaultVespaClient();
        $this->update_chunk_size =  intval(config('vespa.default.max_parallel_requests.update', 1000));

        if($queue == null)
        {
            $queue = config('vespa.default.queue', 'vespa');
        }
        $this->onQueue($queue);
    }

    public function handle()
    {
        try
        {
            $start_time = Carbon::now();
            $this->logger =  new LogManager();

            if(count($this->documents) == 0)
            {
                $message = "[$this->model]: No documents to be processed";
                $this->logger->log($message, "info");
                throw new VespaProcessDocumentExeception($message);
            }

            $successful_results = [];
            $failed_results = [];

            foreach ($this->documents as $id)
            {
                try
                {
                    $schema = "id:$this->document_namespace:$this->document_type::$id";
                    $this->vespa_client->removeDocument($schema);
                    $successful_results[] = $id;
                } catch (VespaFailDeleteDocumentException $e)
                {
                    $failed_results[] = $id;
                    $this->logger->log($e->getMessage(), "error");
                    continue;
                }
            }


            if ($this->update_model_database)
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
        }
        catch (\Exception $ex)
        {
            if ($this->update_model_database)
            {
                $documents_chunk = array_chunk($documents_id, $this->update_chunk_size);
                foreach ($documents_chunk as $chunk)
                {
                    $this->model_class::markAsVespaNotIndexed($chunk);
                }
            }

            $e = new VespaFeedException($this->document_type, $ex);
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
        $total_duration = Carbon::now()->diffInSeconds($start_time);
        $this->logger->log("[$this->document_type]: ".class_basename(self::class)." was fed in ". gmdate('H:i:s:m', $total_duration), "info");
        $this->logger->log("[$this->document_type]: $count_indexed of ". count($documents) . " were deleted in Vespa.", "debug");
    }
}
