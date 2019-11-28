<?php
namespace Escavador\Vespa\Exception;

use Escavador\Vespa\Models\DocumentDefinition;

class VespaFailSearchException extends VespaException
{
    protected $data;

    public function __construct(array $data, $code, $message)
    {
        $this->code = $code;
        $this->data = $data;
        $this->message = "An error occurred while performing the search on Vespa.".
                         " Exception Message: $message. Payload: ". json_encode($this->data);
    }
}
