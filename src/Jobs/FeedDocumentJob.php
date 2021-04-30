<?php

namespace Escavador\Vespa\Jobs;

use Carbon\Carbon;
use Escavador\Vespa\Common\LogManager;
use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Common\VespaExceptionSubject;
use Escavador\Vespa\Exception\VespaFeedException;
use Escavador\Vespa\Models\DocumentDefinition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class FeedDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $model_class;
    protected $document_ids;
    protected $model;
    protected $model_definition;
    protected $vespa_client;
    protected $logger;
    protected $update_chunk_size;

    public function __construct(DocumentDefinition $model_definition, string $model_class, $model, array $document_ids, string $queue = null)
    {
        $this->model_definition = $model_definition;
        $this->model_class = $model_class;
        $this->model = $model;
        $this->document_ids = $document_ids;
        $this->update_chunk_size = intval(config('vespa.default.max_parallel_requests.update', 1000));

        if ($queue == null) {
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
        $this->logger = new LogManager();
        $this->vespa_client = Utils::defaultVespaClient();
        $documents = $this->model_class::getVespaDocumentsToIndex(count($this->document_ids), $this->document_ids);

        try {
            if (count($documents) == 0) {
                $this->logger->log("[$this->model]: No documents to be indexed were returned", "info");
                throw new VespaFeedException($this->model, null, "It was not possible to index any document to the Vespa.");
            }

            //Records on vespa
            $result = $this->vespa_client->sendDocuments($this->model_definition, $documents);
            $indexed = [];
            $not_indexed = [];
            foreach ($documents as $document) {
                if (in_array($document, $result)) {
                    $indexed[] = $document;
                } else {
                    $not_indexed[] = $document;
                }
            }

            $count_indexed = count($indexed);
            if ($count_indexed == 0) {
                throw new VespaFeedException($this->model, null, "It was not possible to index any document to the Vespa.");
            }
            // Update model's vespa info in database
            $documents_chunk = array_chunk(collect($indexed)->pluck('id')->unique()->all(), $this->update_chunk_size);
            foreach ($documents_chunk as $chunk) {
                $this->model_class::markAsVespaIndexed($chunk);
            }
            $documents_chunk = array_chunk(collect($not_indexed)->pluck('id')->unique()->all(), $this->update_chunk_size);
            foreach ($documents_chunk as $chunk) {
                $this->model_class::markAsVespaNotIndexed($chunk);
            }
        } catch (\Exception $ex) {
            // If there are documents, mark them as not indexed
            if (!empty($documents)) {
                $documents_chunk = array_chunk(collect($documents)->pluck('id')->unique()->all(), $this->update_chunk_size);
                foreach ($documents_chunk as $chunk) {
                    $this->model_class::markAsVespaNotIndexed($chunk);
                }
            }
            $e = new VespaFeedException($this->model, $ex);
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
        $total_duration = Carbon::now()->diffInSeconds($start_time);
        $this->logger->log("[$this->model]: Vespa was fed in " . gmdate('H:i:s:m', $total_duration), "info");
        $this->logger->log("[$this->model]: $count_indexed of " . count($documents) . " were indexed in Vespa.", "debug");
    }
}
