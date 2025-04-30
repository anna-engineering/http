<?php

namespace A\Http;

use CurlHandle;
use CurlMultiHandle;
use RuntimeException;

class CurlTransaction
{
    const int SENDING        = 0;
    const int RECEIVING_HEAD = 1;
    const int RECEIVING_BODY = 2;
    const int DONE           = -1;

    protected CurlMultiHandle $mh;
    protected CurlHandle      $ch;
    protected int             $status   = 0;
    protected int             $packets  = 0;
    protected string          $buffer   = '';
    protected string          $message  = '';
    protected Response|null   $response = null;
    protected int             $running  = 1;

    protected(set) int $id;

    public function __construct(protected(set) Request $request)
    {
        static $id = 0;
        $this->id = ++$id;

        $this->mh = curl_multi_init();
        $this->ch = curl_init();

        // Method
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->request->method);

        // URL
        curl_setopt($this->ch, CURLOPT_URL, $this->request->target);

        // Protocol
        // $versionInfo = curl_version();
        // if ( ! ( $versionInfo['features'] & CURL_VERSION_HTTP2 ) )
        // printf("Warning: this cURL build (version %s) does not support HTTP/2.\n", $versionInfo['version']);
        // curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

        // Headers
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->request->headers->allNotBugged());

        // Data
        if (!empty($this->request->body))
        {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->request->body);
        }

        // Options
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_FAILONERROR, true);
        curl_setopt($this->ch, CURLOPT_WRITEFUNCTION, function (CurlHandle $ch, string $chunk) {
            $size = strlen($chunk);

            $this->buffer  .= $chunk;
            $this->message .= $chunk;
            $this->packets++;

            return $size;
        });

        curl_multi_add_handle($this->mh, $this->ch);
    }

    protected function loop()
    {
        if ($this->status === self::SENDING)
        {
            $this->status = self::RECEIVING_HEAD;

            \A\Event\EventDispatcher::instance()->dispatchEvent('curl.sending', $this, $this->request);
        }

        if (($status = curl_multi_exec($this->mh, $this->running)) !== CURLM_OK)
        {
            throw new RuntimeException("curl_multi_exec: " . curl_multi_strerror($status), $status);
        }

        if (curl_multi_select($this->mh, 0) === -1)
        {
            throw new RuntimeException("curl_multi_select: " . curl_multi_strerror($status), $status);
        }

        asleep(0);
    }

    public function exec() : Response
    {
        if ($this->status === self::DONE)
        {
            return $this->toResponse();
        }

        for ($this->running = 1 ; $this->running > 0 ; )
        {
            $this->loop();
        }

        $this->status = self::DONE;

        $response = $this->toResponse();

        \A\Event\EventDispatcher::instance()->dispatchEvent('curl.done', $this, $this->request, $response);

        return $response;
    }

    protected function toResponse() : Response
    {
        if ($this->status !== self::DONE)
        {
            return $this->exec();
        }
        else if (!$this->response)
        {
            $this->response = Response::fromMessage($this->message);
        }

        $result = curl_multi_getcontent($this->ch);

        return $this->response;
    }

    public function getResponse() : Response
    {
        if ($this->response)
            return $this->response;
        return $this->toResponse();
    }

    public function __destruct()
    {
        $result = curl_multi_getcontent($this->ch);
        curl_multi_remove_handle($this->mh, $this->ch);
        curl_close($this->ch);
    }

    public function stream($separator = Message::LINEBREAK)
    {
        while ($this->running > 0)
        {
            $this->loop();

            yield from $this->stream_process($separator);
        }

        yield from $this->stream_process($separator);

        if (!empty($this->buffer))
        {
            yield $this->buffer;
        }
    }

    protected function stream_process($separator)
    {
        if ($this->status === self::RECEIVING_HEAD)
        {
            if (str_contains($this->buffer, Message::DELIMITER))
            {
                $parts = explode(Message::DELIMITER, $this->buffer, 2);
                $this->buffer = $parts[1];
                $this->status = self::RECEIVING_BODY;
            }
        }
        else
        {
            if (str_contains($this->buffer, $separator))
            {
                $parts = explode($separator, $this->buffer);
                foreach ($parts as $key => $part)
                {
                    if ($key !== array_key_last($parts))
                    {
                        if (!empty($part))
                            yield $part;
                    }
                    else
                    {
                        $this->buffer = $part;
                    }
                }
            }
        }
    }
}
