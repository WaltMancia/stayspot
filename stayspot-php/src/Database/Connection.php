<?php

declare(strict_types=1);

namespace Database;

use PDO;
use PDOException;

// Patrón Singleton — una sola conexión reutilizable
class Connection
{

    private static ?PDO $instance = null;

    // Constructor privado — nadie puede hacer new Connection()
    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    private static function createConnection(): PDO
    {
        // Las credenciales NUNCA van hardcodeadas
        // En PHP puro las leemos de variables de entorno
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_DATABASE') ?: 'stayspot';
        $user = getenv('DB_USERNAME') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                // Lanza excepciones en vez de errores silenciosos
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Devuelve arrays asociativos por defecto
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Desactiva emulación de prepared statements
                // Usa prepared statements REALES del servidor MySQL
                // Esto es CRÍTICO para prevenir SQL Injection
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Conexión persistente (reutiliza la conexión)
                PDO::ATTR_PERSISTENT         => true,
            ]);

            return $pdo;
        } catch (PDOException $e) {
            // Nunca exponemos el error de BD al cliente
            // Lo logueamos internamente pero devolvemos un mensaje genérico
            error_log("DB Connection failed: " . $e->getMessage());
            throw new \RuntimeException("Error de conexión a la base de datos");
        }
    }
}
