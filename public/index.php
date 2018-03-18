<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../app/config.php';

$db = new PDO('mysql:host=127.0.0.1;dbname=' . $config['db']['dbname'], $config['db']['username'], $config['db']['password']);

$app = new Application();

// Запрос на получение всех данных по CO2 за указанный промежуток времени (в днях)
$app->get('/co2', function (Request $request) use ($db) {
    $daysLimit = $request->get('days', 3);   // По-умолчанию, показывем данные за последние 5 дней
    $startDate = date('Y-m-d H:i:s', strtotime("-$daysLimit days"));

    $sql = "SELECT * FROM weather WHERE date >= :start_date ORDER BY date";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'start_date' => $startDate
    ]);

    $data = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = [
            'date' => date('c', strtotime($row['date'])),
            'co2'  => $row['co2'],
        ];
    }

    $response = new JsonResponse($data);
    $response->headers->set('Access-Control-Allow-Origin', '*');

    return $response;
});

$app->get('/', function (Request $request) {
    if ($value = $request->get('value')) {
        $socket = stream_socket_client('tcp://127.0.0.1:8082');

        fwrite($socket, json_encode([
            'date' => date('c'),
            'co2'  => intval($_GET['value']),
        ]));

        return "<b>Sent</b>";
    }

    return file_get_contents('index.html');
});

$app->run();