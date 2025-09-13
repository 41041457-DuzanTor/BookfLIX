<?php
function send_json($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function handle_error($message, $status_code = 500) {
    send_json(["error" => $message], $status_code);
}
