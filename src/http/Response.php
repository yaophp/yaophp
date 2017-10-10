<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http;

use yaophp\http\datagram\Datagram;
use yaophp\http\datagram\Header;
use yaophp\http\datagram\Body;

class Response extends Datagram
{
    const EOL = "\r\n";
    
    protected $status = 200;
    protected $reason_phrase;

    protected static $messages = [
        //Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        //Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        //Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        //Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        //Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    public function __construct()
    {
        $this->header = new Header(null);
        $this->body = new Body;
    }
    
    public function __toString()
    {
        $output = sprintf(
            'HTTP/%s %s %s',
            $this->getProtocol(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        $output .= static::EOL;
        foreach ($this->getHeaderAll() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $this->getHeaderLine($name)) . static::EOL;
        }
        $output .= static::EOL;
        $output .= (string)$this->getBody();

        return $output;
    }
    
    public function redirect($url, $status=null)
    {
        $this->withRedirect($url, $status);
        $this->output();
    }
    
    public function output()
    {
        $this->outputHeader();
        $this->outputBody();
    }

    protected function outputHeader()
    {
        if (!headers_sent()) {
            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $this->getProtocol(),
                $this->getStatusCode(),
                $this->getReasonPhrase()
            ));

            // Headers
            foreach ($this->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }
    }
    
    protected function outputBody()
    {
        if (!in_array($this->getStatusCode(), [204, 205, 304])) {
            $body = $this->body;
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $chunkSize      = 4096;

            $contentLength  = $this->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }

            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
    }
    
    public function withJson($data, $status = null, $encodingOptions = JSON_UNESCAPED_UNICODE)
    {
        $response = $this->withBody(new Body(fopen('php://temp', 'r+')));
        $response->body->write($json = json_encode($data, $encodingOptions));

        // Ensure that the json encoding passed successfully
        if ($json === false) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }

        $responseWithJson = $response->withHeader('Content-Type', 'application/json;charset=utf-8');
        if (isset($status)) {
            return $responseWithJson->withStatus($status);
        }
        return $responseWithJson;
    }
    
    public function getStatusCode()
    {
        return $this->status;
    }
    
    public function withStatus($code, $reasonPhrase = '')
    {
        $code = $this->filterStatus($code);
        if (!is_string($reasonPhrase) && !method_exists($reasonPhrase, '__toString')) {
            throw new InvalidArgumentException('ReasonPhrase must be a string');
        }

        $this->status = $code;
        if ($reasonPhrase === '' && isset(static::$messages[$code])) {
            $reasonPhrase = static::$messages[$code];
        }

        if ($reasonPhrase === '') {
            throw new InvalidArgumentException('ReasonPhrase must be supplied for this code');
        }

        $this->reason_phrase = $reasonPhrase;

        return $this;
    }
    
    public function withRedirect($url, $status=null)
    {
        $responseWithRedirect = $this->withHeader('Location', (string)$url);
        if (is_null($status) && $this->getStatusCode() === 200) {
            $status = 302;
        }
        if (!is_null($status)) {
            return $responseWithRedirect->withStatus($status);
        }
        return $responseWithRedirect;
    }
    
    public function getReasonPhrase()
    {
        if ($this->reason_phrase) {
            return $this->reason_phrase;
        }
        if (isset(static::$messages[$this->status])) {
            return static::$messages[$this->status];
        }
        return '';
    }
    
    protected function filterStatus($status)
    {
        if (!is_integer($status) || $status<100 || $status>599) {
            throw new InvalidArgumentException('Invalid HTTP status code');
        }
        return $status;
    }
    
}