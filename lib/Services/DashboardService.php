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

        return $stats;
    }
}
