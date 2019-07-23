<?php

namespace Escavador\Vespa\Models;

class Namespace
{
	protected $name;
	protected $documents;

	public function __construct($name, $documents= [])
    {
    	$this->name = $name;
    	$this->documents = $documents;
    }
}
