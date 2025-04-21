<?php

require __DIR__ . '/../vendor/autoload.php';

$requestHeaders = new \A\Http\Headers([
    'Host'           => 'example.com',
    'Accept'         => 'application/json',
    'X-Custom-Token' => ['abc123', 'def456'],
]);

$responseHeaders = new \A\Http\Headers();
$responseHeaders['Content-Type'] = 'text/html; charset=utf-8';
$responseHeaders->add('Set-Cookie', 'session=xyz; Path=/; HttpOnly');

var_dump( $responseHeaders['coNTENT-typexx'] ?? 'xx' );
