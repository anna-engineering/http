<?php

require __DIR__ . '/../vendor/autoload.php';

$message = <<<EOT
HTTP/1.1 201 Created
Content-Type: application/json
Location: http://example.com/users/123

{
  "message": "New user created",
  "user": {
    "id": 123,
    "firstName": "Example",
    "lastName": "Person",
    "email": "bsmth@example.com"
  }
}
EOT;

$response = \A\Http\Response::fromMessage($message);

echo "Protocol: {$response->protocol}\n";
echo "Status-Code: {$response->status_code}\n";
echo "Status-Text: {$response->status_text}\n";
echo "Headers:\n";
foreach ($response->headers as $name => $value)
{
    echo "- $name: $value\n";
}
echo "Body: {$response->body}\n";

echo "-------" . PHP_EOL;

echo $response;
