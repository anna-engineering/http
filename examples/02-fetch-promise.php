<?php

require __DIR__ . '/../vendor/autoload.php';

// Basic usage of the fetch function
// The function fetch() is used to make HTTP requests and retrieve the body response.
// The fetch function returns a promise, which can be used to handle the response asynchronously.

fetch('https://www.example.org/')->then(function ($response) {
    echo $response;
});

echo 'Request Sent... waiting for promise' . PHP_EOL;
