<?php
header('Content-Type: application/json; charset=UTF-8');

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Solicitud inválida, no se recibió JSON.']);
    exit();
}

$user_interests = strtolower(trim($data['interests'] ?? ''));
$tipo = strtolower($data['tipo'] ?? 'libro');

$recommendations = [];

// 🔹 Endpoint especial: Top 10 películas de la semana
if (isset($data['top']) && $data['top'] === "peliculas") {
    $apiKey = "ca111b6dc554931edb0d163d0e19a632"; 
    $url = "https://api.themoviedb.org/3/trending/movie/week?api_key=$apiKey&language=es-ES";
    $response = @file_get_contents($url);
    if ($response === FALSE) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo obtener el Top 10 desde TMDb']);
        exit();
    }

    $dataApi = json_decode($response, true);
    $topMovies = [];
    if (isset($dataApi["results"])) {
        foreach (array_slice($dataApi["results"], 0, 10) as $pelicula) {
            $topMovies[] = [
                "titulo" => $pelicula["title"] ?? "Título no disponible",
                "descripcion" => $pelicula["overview"] ?? "Sin descripción.",
                "portada_url" => isset($pelicula["poster_path"])
                    ? "https://image.tmdb.org/t/p/w200" . $pelicula["poster_path"]
                    : null
            ];
        }
    }
    echo json_encode($topMovies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(); 
}

// --- Función para buscar libros en Open Library ---
function fetch_books($interes) {
    $url = "https://openlibrary.org/search.json?q=" . urlencode($interes);
    $response = @file_get_contents($url);
    if ($response === FALSE) return [];

    $data = json_decode($response, true);
    $results = [];
    if (isset($data["docs"])) {
        foreach (array_slice($data["docs"], 0, 6) as $libro) {
            $results[] = [
                "titulo" => $libro["title"] ?? "Título no disponible",
                "descripcion_corta" => "Libro de " . ($libro["author_name"][0] ?? "Autor desconocido"),
                "portada_url" => isset($libro["cover_i"]) 
                    ? "https://covers.openlibrary.org/b/id/" . $libro["cover_i"] . "-M.jpg"
                    : null
            ];
        }
    }
    return $results;
}

// --- Función para buscar películas en TMDb ---
function fetch_movies($interes) {
    $apiKey = "ca111b6dc554931edb0d163d0e19a632"; 
    $url = "https://api.themoviedb.org/3/search/movie?api_key=$apiKey&query=" . urlencode($interes) . "&language=es-ES";
    $response = @file_get_contents($url);
    if ($response === FALSE) return [];

    $data = json_decode($response, true);
    $results = [];
    if (isset($data["results"])) {
        foreach (array_slice($data["results"], 0, 6) as $pelicula) {
            $results[] = [
                "titulo" => $pelicula["title"] ?? "Título no disponible",
                "descripcion_corta" => $pelicula["overview"] ?? "Sin descripción.",
                "portada_url" => isset($pelicula["poster_path"]) 
                    ? "https://image.tmdb.org/t/p/w200" . $pelicula["poster_path"]
                    : null
            ];
        }
    }
    return $results;
}

// --- Función para sinopsis con IA ---
function generate_ai_synopsis($title, $original_description, $interests) {
    $openai_api_key = getenv('OPENAI_API_KEY'); // pon tu API Key en variable de entorno

    // Si no hay API Key, devolver versión básica
    if (!$openai_api_key) {
        return "Si te interesa '$interests', seguramente disfrutarás de '$title'. " . $original_description;
    }

    $prompt = "Genera una sinopsis atractiva y breve para '$title' (interés: $interests). 
    Base: '$original_description'.";

    $headers = [
        'Content-Type: application/json',
        'Authorization: ' . 'Bearer ' . $openai_api_key
    ];

    $post_fields = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'Eres un experto recomendador de libros y películas.'],
            ['role' => 'user', 'content' => $prompt]
        ]
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $response_data = json_decode($response, true);
        return $response_data['choices'][0]['message']['content'] ?? $original_description;
    } else {
        return "Sinopsis generada automáticamente: " . $original_description;
    }
}

// --- Lógica principal ---
if (empty($user_interests)) {
    http_response_code(400);
    echo json_encode(["error" => "Debes ingresar un interés o género."]);
    exit();
}

if ($tipo === "libro") {
    $items = fetch_books($user_interests);
} elseif ($tipo === "pelicula") {
    $items = fetch_movies($user_interests);
} else {
    $items = [];
}

if (empty($items)) {
    http_response_code(404);
    echo json_encode(["error" => "No se encontraron resultados para '$user_interests'"]);
    exit();
}

foreach ($items as $item) {
    $sinopsis_generada = generate_ai_synopsis(
        $item['titulo'],
        $item['descripcion_corta'],
        $user_interests
    );
    $recommendations[] = [
        'titulo' => $item['titulo'],
        'portada_url' => $item['portada_url'],
        'sinopsis' => $sinopsis_generada
    ];
}

echo json_encode($recommendations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
