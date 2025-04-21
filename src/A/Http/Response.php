<?php

namespace A\Http;

use InvalidArgumentException;

class Response extends Message
{
    readonly int    $status_code;
    readonly string $status_text;

    /**
     * Standard HTTP reason phrases
     */
    private const REASON_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    ];

    /**
     * Constructor
     *
     * @param int      $statusCode   HTTP status code (100–599)
     * @param string   $reasonPhrase Optional custom reason phrase
     * @param string   $protocol     HTTP protocol/version (default "HTTP/1.1")
     * @param Headers  $headers      HTTP headers
     * @param string   $body         Response body
     *
     * @throws InvalidArgumentException If status code is out of range
     */
    public function __construct
    (
        int     $statusCode = 200,
        string  $reasonPhrase = '',
        string  $protocol = 'HTTP/1.1',
        Headers $headers = new Headers(),
        string  $body = ''
    )
    {
        if ($statusCode < 100 || $statusCode > 599)
        {
            throw new InvalidArgumentException("Invalid HTTP status code: {$statusCode}");
        }

        $this->status_code  = $statusCode;
        $this->status_text = $reasonPhrase !== ''
            ? $reasonPhrase
            : (self::REASON_PHRASES[$statusCode] ?? '');

        parent::__construct($protocol, $headers, $body);
    }


    /**
     * Parse a raw HTTP response message into a Response instance
     *
     * @param string $raw Full HTTP response (status line + headers + CRLF + body)
     * @return static
     * @throws InvalidArgumentException On malformed status line or headers
     */
    public static function fromMessage
    (
        string $raw
    ) : static
    {
        // Split head (status + headers) and body
        [$head, $body] = array_pad(explode("\r\n\r\n", $raw, 2), 2, '');

        $lines = explode("\r\n", $head);
        $statusLine = array_shift($lines);

        // Parse status line: e.g. "HTTP/1.1 200 OK"
        if (!\preg_match(
            '~^(HTTP/\d+(?:\.\d+)?)\s+(\d{3})(?:\s+(.*))?$~',
            $statusLine,
            $matches
        ))
        {
            throw new \InvalidArgumentException("Malformed status line: {$statusLine}");
        }

        $protocol     = $matches[1];
        $statusCode   = (int) $matches[2];
        $reasonPhrase = $matches[3] ?? '';

        // Parse headers
        $hdrs = [];
        foreach ($lines as $line)
        {
            if ($line === '') continue;
            $parts = explode(':', $line, 2);
            if (count($parts) !== 2)
            {
                throw new InvalidArgumentException("Malformed header line: {$line}");
            }
            [$name, $value] = $parts;
            $hdrs[trim($name)] = ltrim($value);
        }

        return new static
        (
            $statusCode,
            $reasonPhrase,
            $protocol,
            new Headers($hdrs),
            $body
        );
    }

    /**
     * Convert this Response to a raw HTTP message string
     *
     * @return string
     */
    public function __toString() : string
    {
        // Status line
        $status_line  = "{$this->protocol} {$this->status_code} {$this->status_text}\r\n";
        $headers = (string) $this->getHeaders();

        return $status_line . $headers . self::DELIMITER . $this->getBody();
    }

    /**
     * 200 OK response
     */
    public static function create
    (
        string $body = '',
        array  $headers = []
    ) : static
    {
        return new static
        (
                     200,
                     'OK',
            headers: new Headers($headers),
            body:    $body
        );
    }

    /**
     * JSON response with given status code
     */
    public static function createJson
    (
        int   $statusCode,
        mixed $data,
        array $headers = []
    ) : static
    {
        $payload = json_encode($data);

        $hdrs = new Headers($headers);
        $hdrs['Content-Type']   = 'application/json';
        $hdrs['Content-Length'] = strlen($payload);

        return new static
        (
                     $statusCode,
                     '',
            headers: $hdrs,
            body:    $payload
        );
    }

    /**
     * Redirect response (Location header)
     */
    public static function createRedirect
    (
        string $location,
        int    $statusCode = 302,
        array  $headers    = []
    ) : static
    {
        $hdrs = new Headers($headers);
        $hdrs['Location'] = $location;

        return new static
        (
                     $statusCode,
                     '',
            headers: $hdrs
        );
    }
}
