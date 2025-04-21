<?php

require __DIR__ . '/../vendor/autoload.php';

$message = 'POST /users HTTP/1.1';
$message.= 'Host: example.com';
$message.= 'Content-Type: application/x-www-form-urlencoded';
$message.= 'Content-Length: 50';
$message.= '';
$message.= 'name=FirstName%20LastName&email=bsmth%40example.com';

$request = \A\Http\Request::fromMessage($message);

echo "Method: {$request->method}\n";
echo "Target: {$request->target}\n";
echo "Protocol: {$request->protocol}\n";
echo "Headers:\n";
foreach ($request->headers as $name => $value)
{
    echo "- $name: $value\n";
}
echo "Body: {$request->body}\n";

echo "-------" . PHP_EOL;

echo $request;
