<?php 

echo "Hello, World!";

// Отправим новое значение клиентам по WS
if (isset($_GET['value'])) {
    $socket = stream_socket_client('tcp://127.0.0.1:8082');

    fwrite($socket, json_encode([
        'label' => date('d.H:i'),
        'value' => intval($_GET['value'])
    ]));

    echo "<b>Sent</b>";
}