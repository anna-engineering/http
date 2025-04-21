<?php

namespace A\Http;

class Message implements \Stringable, \JsonSerializable
{
    const LINEBREAK = "\r\n";
    const DELIMITER = "\r\n\r\n";

    const PROTOCOL_1_0 = 'HTTP/1.0';
    const PROTOCOL_1_1 = 'HTTP/1.1';
    const PROTOCOL_2_0 = 'HTTP/2.0';
    const PROTOCOL_3_0 = 'HTTP/3.0';

    public function __construct(
        protected(set) string $protocol = 'HTTP/1.1',
        protected(set) Headers $headers = new Headers(),
        protected(set) string $body = '',
    )
    {
    }

    public function getProtocol() : string
    {
        return $this->protocol;
    }

    public function getHeaders() : Headers
    {
        return $this->headers;
    }

    public function getBody() : string
    {
        return $this->body;
    }

    public function jsonSerialize() : array
    {
        return $this->__serialize();
    }

    public function __serialize() : array
    {
        return [
            'protocol' => $this->protocol,
            'headers'  => $this->headers,
            'body'     => $this->body,
        ];
    }

    public function __unserialize(array $data)
    {
        $this->protocol = $data['protocol'];
        $this->headers  = $data['headers'];
        $this->body     = $data['body'];
    }

    public function __toString() : string
    {
        return $this->body;
    }
}
