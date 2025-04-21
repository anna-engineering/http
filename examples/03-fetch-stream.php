<?php

require __DIR__ . '/../vendor/autoload.php';

$url = 'http://localhost:11434/api/chat';
$data = [
    'model'    => 'llama3.2:3b',
    'messages' => [
        [
            'role'    => 'system',
            'content' => 'Tu es un assistant utile et amical. Tu es capable de répondre à des questions, faire des blagues et donner la méteo si le client te le demande.',
        ],
        [
            'role'    => 'assistant',
            'content' => 'Bonjour, comment puis-je vous aider aujourd\'hui ?',
        ],
        [
            'role' => 'user',
            'content' => 'Raconte moi une blague',
        ],
    ],
    //'tools'    => [
    //    [
    //        'type'     => 'function',
    //        'function' => [
    //            'name'        => 'get_weather',
    //            'description' => 'Get the weather for a specific location',
    //            'parameters'  => [
    //                'type'       => 'object',
    //                'properties' => [
    //                    'location' => [
    //                        'type'        => 'string',
    //                        'description' => 'The location to get the weather for, e.g. San Francisco, CA',
    //                    ],
    //                    'format'   => [
    //                        'type'        => 'string',
    //                        'description' => "The format to return the weather in, e.g. 'celsius' or 'fahrenheit'",
    //                        'enum'        => ['celsius', 'fahrenheit'],
    //                    ],
    //                ],
    //                "required"   => ["location", "format"],
    //            ],
    //        ],
    //    ],
    //],
    'stream'   => true,
];

foreach (fetch_stream('http://localhost:11434/api/chat', method: 'POST', data: json_encode($data), separator: "\n") as $data)
{
    //echo ">> " . $data . PHP_EOL;

    $response = json_decode($data);

    echo $response->message->content;

    if ($response->done === true) // AND $response->done_reason === 'stop')
    {
        echo PHP_EOL . PHP_EOL . '~~~~~' . PHP_EOL;
    }
}
