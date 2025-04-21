<?php

if (!function_exists('fetch'))
{
    // https://curl.se/docs/caextract.html
    /**
     * @param string $url
     * @param string|null $data
     * @param string|null $method
     * @param array $headers
     * @return \A\Async\PromiseProxyInterface|\A\Http\Response
     */
    function fetch($url, string $data = '', string|null $method = '', $headers = []) : \A\Async\PromiseProxyInterface
    {
        $curl = new \A\Http\Curl(new \A\Http\Request(
            $method ?: 'GET',
            $url,
            \A\Http\Message::PROTOCOL_1_1,
            new \A\Http\Headers($headers),
            $data,
        ));

        return new \A\Async\PromiseProxy(function () use ($curl) {
            return $curl->exec();
        });
    }
}

if (!function_exists('fetch_json'))
{
    function fetch_json($url, string $data = '', string|null $method = '', $headers = []) : \A\Async\PromiseProxyInterface
    {
        $data = json_encode($data);
        $headers['Content-Type'] = 'application/json';
        $promise = fetch($url, $data, $method, $headers);

        return new \A\Async\PromiseProxy(function () use ($promise) {
            return json_decode($promise->getBody());
        });
    }
}

if (!function_exists('fetch_stream'))
{
    // https://curl.se/docs/caextract.html
    function fetch_stream($url, string|null $data = null, string|null $method = null, $headers = [], $separator = \A\Http\Message::LINEBREAK)
    {
        $curl = new \A\Http\Curl(new \A\Http\Request(
                                     $method ?: 'GET',
                                     $url,
                                     \A\Http\Message::PROTOCOL_1_1,
                                     new \A\Http\Headers($headers),
                                     $data,
        ));

        yield from $curl->stream($separator);
    }
}
