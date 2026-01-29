<?php
// lib/services/AlumnoService.php
declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

class AlumnoService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene el total de alumnos filtrados
     */
    public function getCount(string $search, bool $isAdmin, int $profId): int
    {
        $params = [];
        $where = "a.deleted_at IS NULL";

        if ($search !== '') {
            $where .= " AND (a.nombre LIKE :q1 OR a.apellidos LIKE :q2 OR a.email LIKE :q3)";
            $like = "%{$search}%";
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        if ($isAdmin) {
            $fromJoin = "
                FROM alumnos a
                LEFT JOIN alumnos_cursos ac ON ac.alumno_id = a.id
                LEFT JOIN cursos c ON c.id = ac.curso_id
            ";
        } else {
            $fromJoin = "
                FROM alumnos a
                JOIN alumnos_cursos ac ON ac.alumno_id = a.id
                JOIN cursos c ON c.id = ac.curso_id
                JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid
            ";
            $params[':pid'] = $profId;
        }

        $sqlCount = "SELECT COUNT(DISTINCT a.id) AS c $fromJoin WHERE $where";
        $stCount = $this->pdo->prepare($sqlCount);
        $stCount->execute($params);
        $res = $stCount->fetch();
        return (int) ($res['c'] ?? 0);
    }

    /**
     * Obtiene la lista paginada de alumnos filtrada
     */
    public function getList(string $search, bool $isAdmin, int $profId, int $limit, int $offset): array
    {
        $params = [':limit' => $limit, ':offset' => $offset];
        $where = "a.deleted_at IS NULL";

        if ($search !== '') {
            $where .= " AND (a.nombre LIKE :q1 OR a.apellidos LIKE :q2 OR a.email LIKE :q3)";
            $like = "%{$search}%";
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        if ($isAdmin) {
            $fromJoin = "
                FROM alumnos a
                LEFT JOIN alumnos_cursos ac ON ac.alumno_id = a.id
                LEFT JOIN cursos c ON c.id = ac.curso_id
            ";
        } else {
            $fromJoin = "
                FROM alumnos a
                JOIN alumnos_cursos ac ON ac.alumno_id = a.id
                JOIN cursos c ON c.id = ac.curso_id
                JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid
            ";
            $params[':pid'] = $profId;
        }

        $sqlList = "
            SELECT 
                a.id, a.nombre, a.apellidos, a.email, a.telefono, a.activo,
                GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS cursos
            $fromJoin
            WHERE $where
            GROUP BY a.id, a.nombre, a.apellidos, a.email, a.telefono, a.activo
            ORDER BY a.apellidos ASC
            LIMIT :limit OFFSET :offset
        ";

        $st = $this->pdo->prepare($sqlList);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $st->execute();
        return $st->fetchAll();
    }

    /**
     * Obtiene un alumno por su ID
     */
    public function getById(int $id): ?array
    {
        $st = $this->pdo->prepare('
            SELECT id, nombre, apellidos, email, telefono, COALESCE(activo,1) AS activo, fecha_nacimiento, notas
            FROM alumnos WHERE id = :id AND deleted_at IS NULL LIMIT 1
        ');
        $st->execute([':id' => $id]);
        $alumno = $st->fetch();
        return $alumno ?: null;
    }

    /**
     * Obtiene el curso actual de un alumno
     */
    public function getCursoActual(int $alumnoId): int
    {
        $st = $this->pdo->prepare('SELECT curso_id FROM alumnos_cursos WHERE alumno_id = :id ORDER BY id DESC LIMIT 1');
        $st->execute([':id' => $alumnoId]);
        return (int) ($st->fetch()['curso_id'] ?? 0);
    }

    /**
     * Crea un alumno
     */
    public function create(array $data, int $cursoId, bool $isAdmin, int $profId): int
    {
        $nombre = trim($data['nombre'] ?? '');
        $apellidos = trim($data['apellidos'] ?? '');
        $email = trim($data['email'] ?? '');
        $fnac = trim($data['fecha_nacimiento'] ?? '');

        if ($nombre === '' || $apellidos === '')
            throw new RuntimeException('Nombre y apellidos son obligatorios.');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new RuntimeException('Email no válido.');
        if ($fnac !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fnac))
            throw new RuntimeException('Fecha de nacimiento inválida.');

        if ($email !== '') {
            $st = $this->pdo->prepare("SELECT 1 FROM alumnos WHERE email = :e AND deleted_at IS NULL LIMIT 1");
            $st->execute([':e' => $email]);
            if ($st->fetch())
                throw new RuntimeException('Ya existe un alumno con ese email.');
        }

        if ($cursoId > 0 && !$isAdmin) {
            $st = $this->pdo->prepare("SELECT 1 FROM cursos_profesores WHERE curso_id = :c AND profesor_id = :p LIMIT 1");
            $st->execute([':c' => $cursoId, ':p' => $profId]);
            if (!$st->fetch())
                throw new RuntimeException('No tienes permiso para asignar este curso.');
        }

        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("
                INSERT INTO alumnos (nombre, apellidos, email, telefono, activo, fecha_nacimiento, notas)
                VALUES (:n, :a, :e, :t, :ac, :fn, :no)
            ");
            $st->execute([
                ':n' => $nombre,
                ':a' => $apellidos,
                ':e' => ($email !== '' ? $email : null),
                ':t' => ($data['telefono'] ?? null),
                ':ac' => (int) ($data['activo'] ?? 1),
                ':fn' => ($fnac !== '' ? $fnac : null),
                ':no' => ($data['notas'] ?? null)
            ]);
            $alumnoId = (int) $this->pdo->lastInsertId();

            if ($cursoId > 0) {
                $st = $this->pdo->prepare("
                    INSERT INTO alumnos_cursos (alumno_id, curso_id, fecha_inicio, estado)
                    VALUES (:al, :cu, CURDATE(), 'matriculado')
                ");
                $st->execute([':al' => $alumnoId, ':cu' => $cursoId]);
            }

            $this->pdo->commit();
            return $alumnoId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza un alumno
     */
    public function update(int $id, array $data, int $cursoId, bool $isAdmin, int $profId): void
    {
        $nombre = trim($data['nombre'] ?? '');
        $apellidos = trim($data['apellidos'] ?? '');
        $email = trim($data['email'] ?? '');
        $fnac = trim($data['fecha_nacimiento'] ?? '');

        if ($nombre === '' || $apellidos === '')
            throw new RuntimeException('Nombre y apellidos son obligatorios.');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new RuntimeException('Email no válido.');
        if ($fnac !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fnac))
            throw new RuntimeException('Fecha de nacimiento inválida.');

        if ($email !== '') {
            $st = $this->pdo->prepare("SELECT 1 FROM alumnos WHERE email = :e AND id <> :id AND deleted_at IS NULL LIMIT 1");
            $st->execute([':e' => $email, ':id' => $id]);
            if ($st->fetch())
                throw new RuntimeException('Ya existe otro alumno con ese email.');
        }

        if ($cursoId > 0 && !$isAdmin) {
            $st = $this->pdo->prepare("SELECT 1 FROM cursos_profesores WHERE curso_id = :c AND profesor_id = :p LIMIT 1");
            $st->execute([':c' => $cursoId, ':p' => $profId]);
            if (!$st->fetch())
                throw new RuntimeException('No tienes permiso para asignar este curso.');
        }

        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("
                UPDATE alumnos SET nombre=:n, apellidos=:a, email=:e, telefono=:t, activo=:ac, fecha_nacimiento=:fn, notas=:no
                WHERE id = :id
            ");
            $st->execute([
                ':n' => $nombre,
                ':a' => $apellidos,
                ':e' => ($email !== '' ? $email : null),
                ':t' => ($data['telefono'] ?? null),
                ':ac' => (int) ($data['activo'] ?? 1),
                ':fn' => ($fnac !== '' ? $fnac : null),
                ':no' => ($data['notas'] ?? null),
                ':id' => $id
            ]);

            $this->pdo->prepare('DELETE FROM alumnos_cursos WHERE alumno_id = :id')->execute([':id' => $id]);
            if ($cursoId > 0) {
                $st = $this->pdo->prepare("
                    INSERT INTO alumnos_cursos (alumno_id, curso_id, fecha_inicio, estado)
                    VALUES (:al, :cu, CURDATE(), 'matriculado')
                ");
                $st->execute([':al' => $id, ':cu' => $cursoId]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Borrado lógico de un alumno
     */
    public function delete(int $id, bool $isAdmin): void
    {
        if (!$isAdmin)
            throw new RuntimeException('No tienes permiso para eliminar alumnos.');
        $st = $this->pdo->prepare('UPDATE alumnos SET deleted_at = NOW() WHERE id = :id');
        $st->execute([':id' => $id]);
    }

    /**
     * Verifica si el profesor tiene acceso a un alumno
     */
    public function checkAccess(int $alumnoId, bool $isAdmin, int $profId): bool
    {
        if ($isAdmin)
            return true;
        $st = $this->pdo->prepare("
            SELECT 1
            FROM alumnos a
            JOIN alumnos_cursos ac ON ac.alumno_id = a.id
            JOIN cursos_profesores cp ON cp.curso_id = ac.curso_id AND cp.profesor_id = :pid
            WHERE a.id = :id AND a.deleted_at IS NULL
            LIMIT 1
        ");
        $st->execute([':pid' => $profId, ':id' => $alumnoId]);
        return (bool) $st->fetch();
    }

    /**
     * Obtiene los cursos disponibles para un profesor
     */
    public function getAvailableCursos(bool $isAdmin, int $profId): array
    {
        if ($isAdmin) {
            return $this->pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();
        } else {
            $st = $this->pdo->prepare("
                SELECT c.id, c.nombre
                FROM cursos c
                JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid
                ORDER BY c.nombre
            ");
            $st->execute([':pid' => $profId]);
            return $st->fetchAll();
        }
    }

    /**
     * Obtiene asignaturas por curso
     */
    public function getAsignaturasByCurso(int $cursoId): array
    {
        if ($cursoId <= 0)
            return [];
        $st = $this->pdo->prepare("
            SELECT DISTINCT a.id, a.nombre
            FROM asignaturas a
            LEFT JOIN asignatura_cursos ac ON ac.asignatura_id = a.id
            WHERE a.deleted_at IS NULL AND (a.curso_id = :c1 OR ac.curso_id = :c2)
            ORDER BY a.nombre
        ");
        $st->execute([':c1' => (int) $cursoId, ':c2' => (int) $cursoId]);
        return $st->fetchAll();
    }

    /**
     * Obtiene empresas disponibles para un alumno/profesor
     */
    public function getAvailableEmpresas(bool $isAdmin, int $profId): array
    {
        if ($isAdmin) {
            return $this->pdo->query("
                SELECT e.id, e.nombre
                FROM empresas e
                WHERE e.deleted_at IS NULL AND COALESCE(e.activo,1)=1
                ORDER BY e.nombre
            ")->fetchAll();
        } else {
            $st = $this->pdo->prepare("
                SELECT DISTINCT e.id, e.nombre
                FROM empresas e
                JOIN empresa_cursos ec ON ec.empresa_id = e.id
                JOIN cursos_profesores cp ON cp.curso_id = ec.curso_id AND cp.profesor_id = :pid
                WHERE e.deleted_at IS NULL AND COALESCE(e.activo,1)=1
                ORDER BY e.nombre
            ");
            $st->execute([':pid' => $profId]);
            return $st->fetchAll();
        }
    }

    /**
     * Obtiene las asignaciones de un alumno
     */
    public function getAsignaciones(int $alumnoId): array
    {
        $st = $this->pdo->prepare("
            SELECT ea.*, e.nombre AS empresa_nombre
            FROM empresa_alumnos ea
            JOIN empresas e ON e.id = ea.empresa_id
            WHERE ea.alumno_id = :al
            ORDER BY (ea.fecha_fin IS NULL) DESC, ea.fecha_inicio DESC, ea.id DESC
        ");
        $st->execute([':al' => $alumnoId]);
        return $st->fetchAll();
    }

    /**
     * Asignar empresa a alumno
     */
    public function asignarEmpresa(int $alumnoId, array $data, bool $isAdmin, int $profId): void
    {
        $empresaId = (int) ($data['empresa_id'] ?? 0);
        $fechaInicio = trim($data['fecha_inicio'] ?? '');
        $fechaFin = trim($data['fecha_fin'] ?? '');
        $asigSel = array_values(array_unique(array_filter(array_map('intval', $data['asignaturas'] ?? []))));

        if ($empresaId <= 0)
            throw new RuntimeException('Selecciona una empresa.');
        if ($fechaInicio === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio))
            throw new RuntimeException('Fecha de inicio inválida.');

        if (!$isAdmin) {
            $st = $this->pdo->prepare("
                SELECT 1 FROM empresa_cursos ec
                JOIN cursos_profesores cp ON cp.curso_id = ec.curso_id AND cp.profesor_id = :pid
                WHERE ec.empresa_id = :eid LIMIT 1
            ");
            $st->execute([':pid' => $profId, ':eid' => $empresaId]);
            if (!$st->fetch())
                throw new RuntimeException('No puedes asignar empresas fuera de tus cursos.');
        }

        $dupe = $this->pdo->prepare("SELECT 1 FROM empresa_alumnos WHERE alumno_id = :a LIMIT 1");
        $dupe->execute([':a' => $alumnoId]);
        if ($dupe->fetch())
            throw new RuntimeException('Este alumno ya tiene una empresa asignada.');

        $cursoId = $this->getCursoActual($alumnoId);

        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("
                INSERT INTO empresa_alumnos
                (empresa_id, alumno_id, curso_id, tipo, fecha_inicio, fecha_fin, horas_previstas, estado, tutor_nombre, tutor_email, tutor_telefono, observaciones)
                VALUES
                (:e, :a, :c, :t, :fi, :ff, :hp, :est, :tn, :te, :tt, :obs)
            ");
            $st->execute([
                ':e' => $empresaId,
                ':a' => $alumnoId,
                ':c' => ($cursoId ?: null),
                ':t' => ($data['tipo'] ?? 'dual'),
                ':fi' => $fechaInicio,
                ':ff' => ($fechaFin !== '' ? $fechaFin : null),
                ':hp' => ($data['horas_previstas'] !== '' ? (int) $data['horas_previstas'] : null),
                ':est' => ($fechaFin !== '' ? 'finalizada' : 'activa'),
                ':tn' => ($data['tutor_nombre'] ?? null),
                ':te' => ($data['tutor_email'] ?? null),
                ':tt' => ($data['tutor_telefono'] ?? null),
                ':obs' => ($data['observaciones'] ?? null)
            ]);

            if (!empty($asigSel)) {
                $stAsig = $this->pdo->prepare("INSERT INTO empresa_alumnos_asignaturas (empresa_id, alumno_id, asignatura_id) VALUES (?, ?, ?)");
                foreach ($asigSel as $asid) {
                    $stAsig->execute([$empresaId, $alumnoId, $asid]);
                }
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar asignación
     */
    public function actualizarAsignacion(int $alumnoId, array $data): void
    {
        $eaId = (int) ($data['ea_id'] ?? 0);
        $empresaId = (int) ($data['empresa_id'] ?? 0);
        $fechaInicio = trim($data['fecha_inicio'] ?? '');
        $fechaFin = trim($data['fecha_fin'] ?? '');
        $asigSel = array_values(array_unique(array_filter(array_map('intval', $data['asignaturas'] ?? []))));

        if ($eaId <= 0 || $empresaId <= 0)
            throw new RuntimeException('Asignación inválida.');

        $stCheck = $this->pdo->prepare("SELECT 1 FROM empresa_alumnos WHERE id = :id AND alumno_id = :al LIMIT 1");
        $stCheck->execute([':id' => $eaId, ':al' => $alumnoId]);
        if (!$stCheck->fetch())
            throw new RuntimeException('Asignación no encontrada.');

        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("
                UPDATE empresa_alumnos
                SET tipo=:t, fecha_inicio=:fi, fecha_fin=:ff, horas_previstas=:hp, estado=:est,
                    tutor_nombre=:tn, tutor_email=:te, tutor_telefono=:tt, observaciones=:obs
                WHERE id = :id
            ");
            $st->execute([
                ':t' => ($data['tipo'] ?? 'dual'),
                ':fi' => $fechaInicio,
                ':ff' => ($fechaFin !== '' ? $fechaFin : null),
                ':hp' => ($data['horas_previstas'] !== '' ? (int) $data['horas_previstas'] : null),
                ':est' => ($fechaFin !== '' ? 'finalizada' : 'activa'),
                ':tn' => ($data['tutor_nombre'] ?? null),
                ':te' => ($data['tutor_email'] ?? null),
                ':tt' => ($data['tutor_telefono'] ?? null),
                ':obs' => ($data['observaciones'] ?? null),
                ':id' => $eaId
            ]);

            $this->pdo->prepare("DELETE FROM empresa_alumnos_asignaturas WHERE empresa_id=:e AND alumno_id=:a")
                ->execute([':e' => $empresaId, ':a' => $alumnoId]);

            if (!empty($asigSel)) {
                $stAsig = $this->pdo->prepare("INSERT INTO empresa_alumnos_asignaturas (empresa_id, alumno_id, asignatura_id) VALUES (?, ?, ?)");
                foreach ($asigSel as $asid) {
                    $stAsig->execute([$empresaId, $alumnoId, $asid]);
                }
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Cerrar asignación
     */
    public function cerrarAsignacion(int $alumnoId, int $eaId, string $fechaFin): void
    {
        if ($eaId <= 0 || $fechaFin === '')
            throw new RuntimeException('Datos inválidos.');
        $st = $this->pdo->prepare("UPDATE empresa_alumnos SET fecha_fin=:ff, estado='finalizada' WHERE id=:id AND alumno_id=:al");
        $st->execute([':ff' => $fechaFin, ':id' => $eaId, ':al' => $alumnoId]);
    }

    /**
     * Eliminar asignación
     */
    public function eliminarAsignacion(int $alumnoId, int $eaId, bool $isAdmin): void
    {
        if (!$isAdmin)
            throw new RuntimeException('Solo el administrador puede eliminar asignaciones.');

        $st = $this->pdo->prepare("SELECT empresa_id FROM empresa_alumnos WHERE id=:id AND alumno_id=:al LIMIT 1");
        $st->execute([':id' => $eaId, ':al' => $alumnoId]);
        $row = $st->fetch();
        if (!$row)
            throw new RuntimeException('Asignación no encontrada.');

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM empresa_alumnos_asignaturas WHERE empresa_id = :e AND alumno_id = :a")
                ->execute([':e' => (int) $row['empresa_id'], ':a' => $alumnoId]);
            $this->pdo->prepare("DELETE FROM empresa_alumnos WHERE id = :id")->execute([':id' => $eaId]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Crear un contacto de alumno
     */
    public function createContacto(int $alumnoId, int $profId, array $data): void
    {
        $tipo = trim($data['tipo'] ?? 'otro');
        $resumen = trim($data['resumen'] ?? '');
        $notas = trim($data['notas'] ?? '');

        if ($resumen === '')
            throw new RuntimeException('Resumen obligatorio.');

        $st = $this->pdo->prepare('
            INSERT INTO alumno_contactos (alumno_id, profesor_id, tipo, resumen, notas)
            VALUES (:a, :p, :t, :r, :n)
        ');
        $st->execute([
            ':a' => $alumnoId,
            ':p' => $profId,
            ':t' => $tipo,
            ':r' => $resumen,
            ':n' => ($notas !== '' ? $notas : null)
        ]);
    }

    /**
     * Eliminar un contacto de alumno
     */
    public function deleteContacto(int $contactoId, int $alumnoId, bool $isAdmin, int $profId): void
    {
        $st = $this->pdo->prepare('SELECT profesor_id FROM alumno_contactos WHERE id = :id AND alumno_id = :al LIMIT 1');
        $st->execute([':id' => $contactoId, ':al' => $alumnoId]);
        $row = $st->fetch();
        if (!$row)
            throw new RuntimeException('Contacto no encontrado.');

        if (!$isAdmin && (int) $row['profesor_id'] !== $profId) {
            throw new RuntimeException('No tienes permiso para eliminar este contacto.');
        }

        $stDel = $this->pdo->prepare('DELETE FROM alumno_contactos WHERE id = :id');
        $stDel->execute([':id' => $contactoId]);
    }
}
