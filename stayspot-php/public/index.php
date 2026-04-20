<?php

declare(strict_types=1);

// Autoloader simple — en Laravel usa Composer autoload
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Router simple
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$uri = rtrim($uri, '/') ?: '/';

if (str_starts_with($uri, '/api')) {
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');

    // Simulamos una BD con un array (después usaremos MySQL con PDO)
    $espacios = [
        [
            'id'     => 1,
            'nombre' => 'Casa Azul en Antigua',
            'precio' => 150.00,
            'ciudad' => 'Antigua Guatemala',
        ],
        [
            'id'     => 2,
            'nombre' => 'Apartamento Zona 10',
            'precio' => 80.00,
            'ciudad' => 'Ciudad de Guatemala',
        ],
    ];

    match (true) {
        $method === 'GET' && $uri === '/api/spaces'
        => responder(200, $espacios),

        $method === 'GET' && preg_match('/^\/api\/spaces\/(\d+)$/', $uri, $m)
        => manejarEspacioIndividual((int) $m[1], $espacios),

        $method === 'POST' && $uri === '/api/spaces'
        => crearEspacio(),

        default
        => responder(404, ['error' => 'Ruta no encontrada'])
    };
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StaySpot — Vue 3 Puro</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>

<body class="bg-gray-50 min-h-screen">
    <div id="app"></div>

    <script src="/js/components/SpaceCard.js"></script>
    <script src="/js/components/SearchBar.js"></script>
    <script src="/js/components/ReservationModal.js"></script>
    <script src="/js/App.js"></script>

    <script>
        const app = Vue.createApp(App)

        app.component('SpaceCard', SpaceCard)
        app.component('SearchBar', SearchBar)
        app.component('ReservationModal', ReservationModal)

        app.mount('#app')
    </script>
</body>

</html>

<?php

// ── Funciones ────────────────────────────────────────────────

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function manejarEspacioIndividual(int $id, array $espacios): void
{
    $espacio = array_filter($espacios, fn($e) => $e['id'] === $id);

    if (empty($espacio)) {
        responder(404, ['error' => "Espacio $id no encontrado"]);
    }

    responder(200, array_values($espacio)[0]);
}

function crearEspacio(): void
{
    // Leemos el body JSON — equivale a req.body en Express
    $body = file_get_contents('php://input');
    $datos = json_decode($body, true); // true = array asociativo

    if (json_last_error() !== JSON_ERROR_NONE) {
        responder(400, ['error' => 'JSON inválido']);
    }

    // Validación manual — en Laravel esto lo hacen los Form Requests
    $errores = [];

    if (empty($datos['nombre'])) {
        $errores['nombre'] = 'El nombre es obligatorio';
    } elseif (strlen($datos['nombre']) > 100) {
        $errores['nombre'] = 'El nombre no puede superar 100 caracteres';
    }

    if (!isset($datos['precio']) || !is_numeric($datos['precio'])) {
        $errores['precio'] = 'El precio debe ser un número';
    } elseif ((float)$datos['precio'] <= 0) {
        $errores['precio'] = 'El precio debe ser mayor a 0';
    }

    if (empty($datos['ciudad'])) {
        $errores['ciudad'] = 'La ciudad es obligatoria';
    }

    if (!empty($errores)) {
        responder(422, [
            'message' => 'Datos de entrada inválidos',
            'errors'  => $errores,
        ]);
    }

    // Sanitización — nunca confíes en los datos del cliente
    $espacio = [
        'id'     => random_int(100, 999),
        'nombre' => htmlspecialchars(trim($datos['nombre']), ENT_QUOTES, 'UTF-8'),
        'precio' => round((float)$datos['precio'], 2),
        'ciudad' => htmlspecialchars(trim($datos['ciudad']), ENT_QUOTES, 'UTF-8'),
    ];

    responder(201, $espacio);
}
