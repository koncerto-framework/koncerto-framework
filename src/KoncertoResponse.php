<?php

/**
 * Helper class to prepare response
 */
class KoncertoResponse
{
    /** @var array<string, string> */
    private $headers = array();

    /** @var ?string */
    private $content = null;

    /**
     * @param string $headerName
     * @param ?string $headerValue
     * @return KoncertoResponse
     */
    public function setHeader($headerName, $headerValue = null) {
        $headerName = strtolower(($headerName));

        if (null === $headerValue && array_key_exists($headerName, $this->headers)) {
            unset($this->headers[$headerName]);

            return $this;
        }

        $this->headers[$headerName] = $headerValue;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @param ?string $content
     * @return KoncertoResponse
     */
    public function setContent($content) {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }
}
