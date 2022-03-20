<?php

return [
    'connection' => 'amqp',
    'contract' => 'asyncapi',
    'connections' => [
        'default' => [
            'protocol' => 'https',
            'host' => 'localhost',
            'port' => 443,
            'user' => 'guest',
            'pass' => 'guest',
        ],
        'amqp' => [
            'protocol' => 'amqp',
            'host' => 'chassis_rabbitmq',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'heartbeat' => 30,
            'connection_timeout' => 5.0,
            'read_write_timeout' => 30.0,
            'channel_rpc_timeout' => 30.0,
        ]
    ],
    'contracts' => [
        'openapi' => [
            'driver' => 'filesystem',
            'paths' => [
                'source' => '',
                'validator' => ''
            ],
            'definitions' => [
                'contract' => ''
            ]
        ],
        'asyncapi' => [
            'driver' => 'filesystem',
            'paths' => [
                'source' => '/var/package/tests/Fixtures/AsyncApiContracts',
                'validator' => '/var/package/tests/Fixtures/AsyncApiContracts/json-schemas/bindings/amqp'
            ],
            'definitions' => [
                'contract' => 'any-dawapack.yml',
                'infrastructure' => 'good-infrastructure.yml'
            ]
        ]
    ]
];