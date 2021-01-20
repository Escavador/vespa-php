<?php

namespace Escavador\Vespa\Jobs;

use Carbon\Carbon;
use Escavador\Vespa\Common\LogManager;
use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Common\VespaExceptionSubject;
use Escavador\Vespa\Enums\LogManagerOptionsEnum;
use Escavador\Vespa\Exception\VespaExecuteJobException;
use Escavador\Vespa\Exception\VespaFailDeleteDocumentException;
use Escavador\Vespa\Models\DocumentDefinition;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $model_class;
    protected $document_ids;
    protected $document_type;
    protected $document_namespace;
    protected $model_definition;
    protected $vespa_client;
    protected $logger;
    protected $update_chunk_size;
    protected $update_model_database;

    public function __construct(DocumentDefinition $model_definition, array $document_ids, bool $update_model_database = true, string $queue = null)
    {
        $this->model_definition = $model_definition;
        $this->model_class = $model_definition->getModelClass();
        $this->document_type = $model_definition->getDocumentType();
        $this->document_namespace = $model_definition->getDocumentNamespace();
        $this->update_model_database = $update_model_database;

        $this->document_ids = $document_ids;
        $this->update_chunk_size =  intval(config('vespa.default.max_parallel_requests.update', 1000));

        if ($queue == null) {
            $queue = config('vespa.default.queue', 'vespa');
        }
        $this->onQueue($queue);
    }

    public function handle()
    {
        $successful_results = [];
        $failed_results = [];

        try {
            $start_time = Carbon::now();
            $this->logger =  new LogManager();
            $this->vespa_client = Utils::defaultVespaClient();

            if (count($this->document_ids) == 0) {
                $message = "No documents to be processed.";
                $this->logger->log("[$this->document_type] $message", LogManagerOptionsEnum::INFO);
                throw new VespaExecuteJobException(self::class, $this->document_type, null, $message);
            }

            foreach ($this->document_ids as $id) {
                try {
                    $scheme = "id:$this->document_namespace:$this->document_type::$id";
                    $this->vespa_client->removeDocument($scheme);
                    $successful_results[] = $id;
                } catch (VespaFailDeleteDocumentException $e) {
                    $failed_results[] = $id;
                    $this->logger->log($e->getMessage(), LogManagerOptionsEnum::ERROR);
                    continue;
                }
            }


            if ($this->update_model_database) {
                $documents_chunk = array_chunk($this->document_ids, $this->update_chunk_size);
                foreach ($documents_chunk as $chunk) {
                    $this->model_class::markAsVespaIndexed($chunk);
                }
            }
        } catch (\Exception $ex) {
            if ($this->update_model_database) {
                $documents_chunk = array_chunk($this->document_ids, $this->update_chunk_size);
                foreach ($documents_chunk as $chunk) {
                    $this->model_class::markAsVespaNotIndexed($chunk);
                }
            }

            $e = new VespaExecuteJobException(self::class, $this->document_type, $ex);
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
        $total_duration = Carbon::now()->diffInSeconds($start_time);
        $this->logger->log("[$this->document_type]: " . class_basename(self::class) . " was processed in " . gmdate('H:i:s:m', $total_duration), LogManagerOptionsEnum::INFO);
        $this->logger->log("[$this->document_type]: " . count($successful_results) . " of " . count($failed_results) . " were deleted in Vespa.", LogManagerOptionsEnum::DEBUG);
    }
}
