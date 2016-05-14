<?php
/**
 *
 * This file is part of Relay for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @copyright 2015-2016, Relay for PHP
 *
 */
namespace Relay\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 *
 * Handles JSON content.
 *
 * @package relay/middleware
 *
 */
class JsonContentHandler extends ContentHandler
{
    /**
     * @var bool
     */
    protected $assoc;

    /**
     * @var int
     */
    protected $maxDepth;

    /**
     * @var int
     */
    protected $options;

    /**
     * @param bool $assoc
     * @param int $maxDepth
     * @param int $options
     */
    public function __construct($assoc = false, $maxDepth = 512, $options = 0)
    {
        $this->assoc    = $assoc;
        $this->maxDepth = $maxDepth;
        $this->options  = $options;
    }

    /**
     * @inheritDoc
     */
    protected function isApplicableMimeType($mime)
    {
        return preg_match('~^application/([a-z.]+\+)?json($|;)~', $mime);
    }

    /**
     * @inheritDoc
     */
    protected function getParsedBody($body)
    {
        $body = json_decode($body, $this->assoc, $this->maxDepth, $this->options);

        if (! json_last_error()) {
            return $body;
        }

        return $this->throwException('Error parsing JSON: ' . json_last_error_msg());
    }
}
