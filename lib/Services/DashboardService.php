<?php
// lib/services/DashboardService.php
declare(strict_types=1);

namespace App\Services;

use PDO;

class DashboardService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene estadísticas para el dashboard según el rol del usuario
     */
    public function getStats(bool $isAdmin, int $profId): array
    {
        $stats = [];

        // Alumnos
        if ($isAdmin) {
            $st = $this->pdo->query("SELECT COUNT(*) AS c FROM alumnos WHERE deleted_at IS NULL");
            $stats['alumnos'] = (int) ($st->fetch()['c'] ?? 0);
        } else {
            $st = $this->pdo->prepare("
                SELECT COUNT(DISTINCT a.id) AS c
                FROM alumnos a
                JOIN alumnos_cursos ac ON ac.alumno_id = a.id
                JOIN cursos_profesores cp ON cp.curso_id = ac.curso_id AND cp.profesor_id = :pid
                WHERE a.deleted_at IS NULL
            ");
            $st->execute([':pid' => $profId]);
            $stats['alumnos'] = (int) ($st->fetch()['c'] ?? 0);
        }

        // Empresas
        $st = $this->pdo->query("SELECT COUNT(*) AS c FROM empresas WHERE deleted_at IS NULL");
        $stats['empresas'] = (int) ($st->fetch()['c'] ?? 0);

        // Usuarios
        $st = $this->pdo->query("SELECT COUNT(*) AS c FROM usuarios WHERE deleted_at IS NULL");
        $stats['usuarios'] = (int) ($st->fetch()['c'] ?? 0);

        // Cursos (Grados)
        $st = $this->pdo->query("SELECT COUNT(*) AS c FROM cursos");
        $stats['cursos'] = (int) ($st->fetch()['c'] ?? 0);

        // Centros
        $st = $this->pdo->query("SELECT COUNT(*) AS c FROM centros");
        $stats['centros'] = (int) ($st->fetch()['c'] ?? 0);

        // Asignaturas
        if ($isAdmin) {
            $st = $this->pdo->query("SELECT COUNT(*) AS c FROM asignaturas WHERE deleted_at IS NULL");
        } else {
            $st = $this->pdo->prepare("
                SELECT COUNT(DISTINCT a.id) AS c
                FROM asignaturas a
                JOIN cursos_profesores cp ON cp.curso_id = a.curso_id AND cp.profesor_id = :pid
                WHERE a.deleted_at IS NULL
            ");
            $st->execute([':pid' => $profId]);
        }
        $stats['asignaturas'] = (int) ($st->fetch()['c'] ?? 0);

        // Resultados de Aprendizaje (RAs)
        if ($isAdmin) {
            $st = $this->pdo->query("SELECT COUNT(*) AS c FROM asignatura_ras WHERE deleted_at IS NULL");
        } else {
            $st = $this->pdo->prepare("
                SELECT COUNT(DISTINCT ra.id) AS c
                FROM asignatura_ras ra
                JOIN asignaturas a ON a.id = ra.asignatura_id
                JOIN cursos_profesores cp ON cp.curso_id = a.curso_id AND cp.profesor_id = :pid
                WHERE ra.deleted_at IS NULL AND a.deleted_at IS NULL
            ");
            $st->execute([':pid' => $profId]);
        }
        $stats['ras'] = (int) ($st->fetch()['c'] ?? 0);

        // Alumnos con asignación activa (fecha_fin es NULL o futura)
        if ($isAdmin) {
            $st = $this->pdo->query("
                SELECT COUNT(DISTINCT alumno_id) AS c
                FROM empresa_alumnos
                WHERE (fecha_fin IS NULL OR fecha_fin >= CURDATE())
            ");
        } else {
            $st = $this->pdo->prepare("
                SELECT COUNT(DISTINCT ea.alumno_id) AS c
                FROM empresa_alumnos ea
                JOIN alumnos al ON al.id = ea.alumno_id
                JOIN alumnos_cursos ac ON ac.alumno_id = al.id
                JOIN cursos_profesores cp ON cp.curso_id = ac.curso_id AND cp.profesor_id = :pid
                WHERE (ea.fecha_fin IS NULL OR ea.fecha_fin >= CURDATE())
                  AND al.deleted_at IS NULL
            ");
            $st->execute([':pid' => $profId]);
        }
        $stats['alumnos_activos'] = (int) ($st->fetch()['c'] ?? 0);

        // Asignaciones activas totales (fecha_fin es NULL o futura)
        if ($isAdmin) {
            $st = $this->pdo->query("
                SELECT COUNT(*) AS c FROM empresa_alumnos
                WHERE (fecha_fin IS NULL OR fecha_fin >= CURDATE())
            ");
        } else {
            $st = $this->pdo->prepare("
                SELECT COUNT(ea.id) AS c
                FROM empresa_alumnos ea
                JOIN alumnos al ON al.id = ea.alumno_id
                JOIN alumnos_cursos ac ON ac.alumno_id = al.id
                JOIN cursos_profesores cp ON cp.curso_id = ac.curso_id AND cp.profesor_id = :pid
                WHERE (ea.fecha_fin IS NULL OR ea.fecha_fin >= CURDATE())
                  AND al.deleted_at IS NULL
            ");
            $st->execute([':pid' => $profId]);
        }
        $stats['asignaciones_activas'] = (int) ($st->fetch()['c'] ?? 0);

        // Últimas 4 empresas añadidas
        $st = $this->pdo->query("
            SELECT id, nombre, sector, ciudad
            FROM empresas
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT 4
        ");
        $stats['ultimas_empresas'] = $st->fetchAll();

        // Últimos 4 alumnos modificados (para el profesor, filtrado por sus cursos)
        if ($isAdmin) {
            $st = $this->pdo->query("
                SELECT a.id, a.nombre, a.apellidos, a.email
                FROM alumnos a
                WHERE a.deleted_at IS NULL
                ORDER BY a.updated_at DESC
                LIMIT 4
            ");
        } else {
            $st = $this->pdo->prepare("
                SELECT DISTINCT a.id, a.nombre, a.apellidos, a.email
                FROM alumnos a
                JOIN alumnos_cursos ac ON ac.alumno_id = a.id
                JOIN cursos_profesores cp ON cp.curso_id = ac.curso_id AND cp.profesor_id = :pid
                WHERE a.deleted_at IS NULL
                ORDER BY a.updated_at DESC
                LIMIT 4
            ");
            $st->execute([':pid' => $profId]);
        }
        $stats['ultimos_alumnos'] = $st->fetchAll();

        return $stats;
    }
}
