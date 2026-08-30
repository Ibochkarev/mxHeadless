<?php

return [
    'mxHeadless' => [
        'file' => 'mxheadless',
        'description' => 'HTTP gateway for mxHeadless API requests.',
        'events' => [
            'OnHandleRequest' => [
                'priority' => -100,
            ],
        ],
    ],
];
