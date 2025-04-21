<?php

namespace A\Http;

use InvalidArgumentException;

class Request extends Message
{
    readonly string $method;
    readonly string $target;

    public function __construct(
        string $method,
        string $target,
        string $protocol = 'HTTP/1.1',
        Headers $headers = new Headers(),
        string $body = '',
    )
    {
        parent::__construct($protocol, $headers, $body);

        $this->method = strtoupper($method);
        $this->target = rtrim($target, '/');
    }

    /**
     * Return full HTTP request string
     */
    public function __toString() : string
    {
        $requestLine = sprintf('%s %s %s', $this->method, $this->target, $this->getProtocol());
        $headersStr = (string) $this->getHeaders();

        return $requestLine . "\r\n" . $headersStr . "\r\n\r\n" . $this->getBody();
    }

    public static function fromMessage(string $message) : static
    {
        $parts = explode(self::DELIMITER, $message, 2);

        $head = explode(static::LINEBREAK, $parts[0]);

        $body = $parts[1] ?? '';

        // Request-line

        $request_line = array_shift($head);

        [$method, $target, $protocol] = [...explode(' ', $request_line, 3), null, null, null];

        if ($method === null || $target === null || $protocol === null)
        {
            throw new InvalidArgumentException(sprintf('Invalid request line: "%s".', $lines[0]));
        }

        // Headers

        $headers = new Headers();

        foreach ($head as $line)
        {
            [$name, $value] = [...explode(': ', $line, 2), null, null];

            if ($name === null || $value === null)
            {
                throw new InvalidArgumentException(sprintf('Invalid header line: "%s".', $line));
            }

            $headers[$name] = $value;
        }

        // Create a new Request object
        return new static($method, $target, $protocol, $headers, $body);
    }

    public static function GET(string $target, array $headers = []) : static
    {
        return new static('GET', $target, headers: new Headers($headers));
    }

    public static function POST(string $target, string $data, array $headers = []) : static
    {
        $headers = new Headers($headers);
        $headers['Content-Length'] = strlen($data);
        return new static('POST', $target, headers: $headers, body: $data);
    }

    public static function POSTjson(string $target, mixed $data, array $headers = []) : static
    {
        return static::POST($target, json_encode($data), ['Content-Type' => 'application/json']);
    }
}
