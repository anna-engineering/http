<?php

require __DIR__ . '/../vendor/autoload.php';

// Basic usage of the fetch function
// The function fetch() is used to make HTTP requests and retrieve the body response.

echo fetch('https://www.example.org/');
