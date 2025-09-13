<?php
$endpoint = "http://localhost/BookfLIX/backend/recommend.php";

function run_test($description, $payload) {
    global $endpoint;

    $options = [
        "http" => [
            "method"  => "POST",
            "header"  => "Content-Type: application/json\r\n",
            "content" => json_encode($payload)
        ]
    ];
    $context  = stream_context_create($options);
    $result   = file_get_contents($endpoint, false, $context);
    $status   = $http_response_header[0];

    echo "🔹 $description → $status\n";
    echo "Respuesta: $result\n\n";
}

run_test("Caso válido (terror)", ["interests" => "terror", "tipo" => "libro"]);
run_test("Caso inválido (sin parámetros)", []);
run_test("Caso sin resultados", ["interests" => "zzzzzzzzzzz"]);
