<?php

namespace Atol\Api\Exception;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;

class HttpClientException extends Exception implements ClientExceptionInterface
{

}
