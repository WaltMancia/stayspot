<?php

declare(strict_types=1);

namespace Models;

use Database\Connection;
use PDO;
use InvalidArgumentException;

class Space
{

    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    // Obtiene todos los espacios con filtros opcionales
    public function getAll(array $filtros = []): array
    {
        // Construimos la query dinámicamente con prepared statements
        $sql    = "SELECT s.*, u.name as host_name
                   FROM spaces s
                   JOIN users u ON s.host_id = u.id
                   WHERE s.is_active = 1";
        $params = [];

        // Filtro por ciudad — NUNCA concatenamos directamente
        if (!empty($filtros['ciudad'])) {
            $sql .= " AND s.city LIKE :ciudad";
            // El % va en el parámetro, no en el SQL
            $params[':ciudad'] = '%' . $filtros['ciudad'] . '%';
        }

        // Filtro por precio mínimo
        if (!empty($filtros['precio_min'])) {
            $sql .= " AND s.price >= :precio_min";
            $params[':precio_min'] = (float) $filtros['precio_min'];
        }

        // Filtro por precio máximo
        if (!empty($filtros['precio_max'])) {
            $sql .= " AND s.price <= :precio_max";
            $params[':precio_max'] = (float) $filtros['precio_max'];
        }

        // Paginación
        $pagina  = max(1, (int)($filtros['pagina'] ?? 1));
        $limite  = min(50, max(1, (int)($filtros['limite'] ?? 12)));
        $offset  = ($pagina - 1) * $limite;
        $sql    .= " ORDER BY s.created_at DESC LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        // bindValue es más seguro que bindParam para valores directos
        // Especificamos el tipo explícitamente
        foreach ($params as $key => $value) {
            $stmt->bindValue(
                $key,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }

        // LIMIT y OFFSET deben ser enteros — PDO los trata como strings
        // sin el tipo explícito
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        // Los prepared statements hacen imposible la SQL injection
        $stmt = $this->db->prepare(
            "SELECT s.*, u.name as host_name
             FROM spaces s
             JOIN users u ON s.host_id = u.id
             WHERE s.id = :id AND s.is_active = 1"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        // fetch() devuelve false si no hay resultado — usamos null
        return $result ?: null;
    }

    public function create(array $data, int $hostId): array
    {
        $this->validar($data);

        $stmt = $this->db->prepare(
            "INSERT INTO spaces
                (host_id, name, description, city, address,
                 price_per_night, max_guests, created_at, updated_at)
             VALUES
                (:host_id, :name, :description, :city, :address,
                 :price, :max_guests, NOW(), NOW())"
        );

        $stmt->execute([
            ':host_id'     => $hostId,
            ':name'        => $this->sanitizar($data['name']),
            ':description' => $this->sanitizar($data['description'] ?? ''),
            ':city'        => $this->sanitizar($data['city']),
            ':address'     => $this->sanitizar($data['address'] ?? ''),
            ':price'       => round((float)$data['price_per_night'], 2),
            ':max_guests'  => (int)$data['max_guests'],
        ]);

        $id = (int)$this->db->lastInsertId();
        return $this->findById($id);
    }

    // Sanitización — limpia el input antes de guardar
    private function sanitizar(string $valor): string
    {
        return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
    }

    // Validación — lanza excepción si los datos son inválidos
    private function validar(array $data): void
    {
        $errores = [];

        if (empty($data['name']) || strlen(trim($data['name'])) < 3) {
            $errores['name'] = 'El nombre debe tener al menos 3 caracteres';
        }

        if (
            !isset($data['price_per_night']) ||
            !is_numeric($data['price_per_night']) ||
            (float)$data['price_per_night'] <= 0
        ) {
            $errores['price_per_night'] = 'El precio debe ser mayor a 0';
        }

        if (empty($data['city'])) {
            $errores['city'] = 'La ciudad es obligatoria';
        }

        if (!empty($errores)) {
            throw new InvalidArgumentException(json_encode($errores));
        }
    }
}
