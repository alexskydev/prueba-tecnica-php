<?php
/** Única capa con consultas a la tabla persona y al procedimiento almacenado. */
final class PersonRepository
{
    private PDO $connection;

    private const PUBLIC_COLUMNS = 'IdPersona AS id, Nombres AS firstName, Apellidos AS lastName, '
        . 'Identificacion AS identification, Genero AS gender, '
        . 'FechaNacimiento AS birthDate, Activo AS active';

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function find(int $id): ?array
    {
        $query = $this->connection->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM persona WHERE IdPersona = ? LIMIT 1'
        );
        $query->execute([$id]);
        $person = $query->fetch();
        return $person ?: null;
    }

    public function paginate(string $searchTerm, string $status, int $page, int $pageSize = 8): array
    {
        $conditions = [];
        $parameters = [];
        if ($searchTerm !== '') {
            $conditions[] = '(Nombres LIKE ? OR Apellidos LIKE ? OR Identificacion LIKE ?)';
            $searchPattern = '%' . $searchTerm . '%';
            $parameters = [$searchPattern, $searchPattern, $searchPattern];
        }
        if ($status !== 'all') {
            $conditions[] = 'Activo = ?';
            $parameters[] = (int) $status;
        }
        $whereClause = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $countQuery = $this->connection->prepare('SELECT COUNT(*) FROM persona' . $whereClause);
        $countQuery->execute($parameters);
        $filteredCount = (int) $countQuery->fetchColumn();
        $pageCount = max(1, (int) ceil($filteredCount / $pageSize));
        $page = min($page, $pageCount);
        $offset = ($page - 1) * $pageSize;

        $listQuery = $this->connection->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM persona' . $whereClause .
            ' ORDER BY IdPersona DESC LIMIT ? OFFSET ?'
        );
        $position = 1;
        foreach ($parameters as $parameter) {
            $listQuery->bindValue(
                $position++, $parameter, is_int($parameter) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
        $listQuery->bindValue($position++, $pageSize, PDO::PARAM_INT);
        $listQuery->bindValue($position, $offset, PDO::PARAM_INT);
        $listQuery->execute();

        $statistics = $this->connection->query(
            'SELECT COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN Activo = 1 THEN 1 ELSE 0 END), 0) AS active,
                COALESCE(SUM(CASE WHEN Activo = 0 THEN 1 ELSE 0 END), 0) AS inactive
             FROM persona'
        )->fetch();

        return [
            'people' => $listQuery->fetchAll(),
            'statistics' => [
                'total' => (int) $statistics['total'],
                'active' => (int) $statistics['active'],
                'inactive' => (int) $statistics['inactive'],
            ],
            'pagination' => [
                'page' => $page, 'pages' => $pageCount,
                'perPage' => $pageSize, 'filtered' => $filteredCount,
            ],
        ];
    }

    public function create(array $person): int
    {
        $query = $this->connection->prepare(
            'INSERT INTO persona
              (Nombres, Apellidos, Identificacion, Genero, FechaNacimiento, Contrasena, Activo)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $query->execute([
            $person['firstName'], $person['lastName'], $person['identification'],
            $person['gender'], $person['birthDate'],
            password_hash($person['password'], PASSWORD_BCRYPT), (int) $person['active'],
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $person): bool
    {
        if ($this->find($id) === null) {
            return false;
        }
        $sql = 'UPDATE persona SET Nombres=?, Apellidos=?, Identificacion=?, Genero=?, '
            . 'FechaNacimiento=?, Activo=?';
        $parameters = [
            $person['firstName'], $person['lastName'], $person['identification'],
            $person['gender'], $person['birthDate'], (int) $person['active'],
        ];
        if ($person['password'] !== '') {
            $sql .= ', Contrasena=?';
            $parameters[] = password_hash($person['password'], PASSWORD_BCRYPT);
        }
        $sql .= ' WHERE IdPersona=?';
        $parameters[] = $id;
        $query = $this->connection->prepare($sql);
        $query->execute($parameters);
        return true;
    }

    public function delete(int $id): bool
    {
        $query = $this->connection->prepare('DELETE FROM persona WHERE IdPersona = ?');
        $query->execute([$id]);
        return $query->rowCount() > 0;
    }

    public function cloneTable(): int
    {
        $query = $this->connection->prepare('CALL sp_clonar_personas()');
        $query->execute();
        $query->closeCursor();
        return (int) $this->connection->query('SELECT COUNT(*) FROM persona_respaldo')->fetchColumn();
    }
}
