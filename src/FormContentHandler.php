<?php

namespace Relay\Middleware;

class FormContentHandler extends ContentHandler
{
    /**
     * @inheritdoc
     */
    protected function isApplicableMimeType($mime)
    {
        return 'application/x-www-form-urlencoded' === $mime;
    }

    /**
     * @inheritdoc
     */
    protected function getParsedBody($body)
    {
        parse_str($body, $parsed);
        return $parsed;
    }
}
