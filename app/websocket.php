<?php

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

include __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../app/config.php';

/** @var TcpConnection[] $connections */
$connections = [];

$wsWorker = new Worker('websocket://0.0.0.0:' . $config['ws_worker_port']);
$wsWorker->count = 1;

$wsWorker->onWorkerStart = function () use (&$connections, $config) {
    // Стартуем сразу TCP server, который будет принимать новые данные и отправлять всем ws клиентам
    $tcpWorker = new Worker('tcp://0.0.0.0:' . $config['tcp_worker_port']);
    $tcpWorker->onMessage = function ($connection, $data) use (&$connections) {
        echo "Send $data to " . count($connections) . " connection\n";
        foreach ($connections as $connection) {
            $connection->send($data);
        }
    };
    $tcpWorker->listen();
};

$wsWorker->onConnect = function ($connection) use (&$connections) {
    echo "New connection\n";
    echo "Total connections: " . count($connections) . "\n";
    $connections[] = $connection;
};

$wsWorker->onClose = function ($connection) use (&$connections) {
    // Несмотря на то, что это неэффективно, нам такой подход подойдет для обучения
    $key = array_search($connection, $connections);
    echo "Close connection...";
    if ($key !== false ) {
        echo " done\n";
        unset($connections[$key]);
    }
};

Worker::runAll();
