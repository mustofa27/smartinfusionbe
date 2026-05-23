<?php

return [
    'host' => env('MQTT_HOST', '127.0.0.1'),
    'port' => (int) env('MQTT_PORT', 1883),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'client_id' => env('MQTT_CLIENT_ID', 'smartinfus-backend-consumer'),
    'use_tls' => (bool) env('MQTT_USE_TLS', false),
    'topic' => env('MQTT_TOPIC', 'smart-infusion/+/weight'),
    'qos' => (int) env('MQTT_QOS', 1),
];
