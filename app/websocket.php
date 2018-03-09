<?php

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

include __DIR__ . '/../vendor/autoload.php';


$wsWorker = new Worker('websocket://127.0.0.1:8081');
$wsWorker->count = 2;

/** @var TcpConnection[] $connections */
$connections = [];

$wsWorker->onWorkerStart = function () use (&$connections) {

    // Стартуем сразу TCP server, который будет принимать новые данные и отправлять всем ws клиентам
    $tcpWorker = new Worker('tcp://127.0.0.1:8082');
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
    $connections[] = $connection;
};

$wsWorker->onClose = function ($connection) use (&$connections) {
    // Несмотря на то, что это неэффективно, нам такой подход подойдет для обучения
    $key = array_search($connection, $connections);
    if ($key !== false ) {
        echo "Connection closed\n";
        unset($connections[$key]);
    }
};

Worker::runAll();
