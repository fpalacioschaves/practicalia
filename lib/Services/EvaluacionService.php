<?php
// practicalia/lib/Services/EvaluacionService.php
declare(strict_types=1);

namespace App\Services;

use PDO;
use Throwable;

class EvaluacionService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene la configuración de pesos para una asignatura y convocatoria
     */
    public function getConfig(int $asignaturaId, string $convocatoria): array
    {
        $st = $this->pdo->prepare("SELECT * FROM evaluacion_config WHERE asignatura_id = :aid AND convocatoria = :conv");
        $st->execute([':aid' => $asignaturaId, ':conv' => $convocatoria]);
        $config = $st->fetch();

        if (!$config) {
            // Valores por defecto
            return [
                'peso_foro' => 10.00,
                'peso_actividades' => 30.00,
                'peso_examen' => 40.00,
                'peso_dualizacion' => 20.00
            ];
        }

        return $config;
    }

    /**
     * Guarda o actualiza la configuración de pesos
     */
    public function saveConfig(int $asignaturaId, string $convocatoria, array $data): void
    {
        $sql = "INSERT INTO evaluacion_config 
                (asignatura_id, convocatoria, peso_foro, peso_actividades, peso_examen, peso_dualizacion)
                VALUES (:aid, :conv, :pf, :pa, :pe, :pd)
                ON DUPLICATE KEY UPDATE 
                peso_foro = VALUES(peso_foro), 
                peso_actividades = VALUES(peso_actividades), 
                peso_examen = VALUES(peso_examen), 
                peso_dualizacion = VALUES(peso_dualizacion)";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':aid' => $asignaturaId,
            ':conv' => $convocatoria,
            ':pf' => $data['peso_foro'] ?? 10.00,
            ':pa' => $data['peso_actividades'] ?? 30.00,
            ':pe' => $data['peso_examen'] ?? 40.00,
            ':pd' => $data['peso_dualizacion'] ?? 20.00
        ]);

        // Recalcular notas finales si los pesos cambian
        $this->recalculateFinalGrades($asignaturaId, $convocatoria);
    }

    /**
     * Obtiene las notas de los alumnos para una asignatura y convocatoria
     */
    public function getNotas(int $asignaturaId, string $convocatoria): array
    {
        // Traemos a los alumnos matriculados y sus notas (si existen)
        $sql = "SELECT a.id, a.nombre, a.apellidos,
                       en.nota_foro, en.nota_actividades, en.nota_examen, en.nota_dualizacion, en.nota_final, en.observaciones
                FROM alumnos_asignaturas aa
                JOIN alumnos a ON a.id = aa.alumno_id
                LEFT JOIN evaluaciones_notas en ON en.alumno_id = a.id 
                     AND en.asignatura_id = aa.asignatura_id 
                     AND en.convocatoria = :conv
                WHERE aa.asignatura_id = :aid
                ORDER BY a.apellidos, a.nombre";

        $st = $this->pdo->prepare($sql);
        $st->execute([':aid' => $asignaturaId, ':conv' => $convocatoria]);
        return $st->fetchAll();
    }

    /**
     * Guarda las notas de múltiples alumnos en lote
     */
    public function saveNotasBatch(int $asignaturaId, string $convocatoria, array $notasBatch): void
    {
        $config = $this->getConfig($asignaturaId, $convocatoria);

        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO evaluaciones_notas 
                    (alumno_id, asignatura_id, convocatoria, nota_foro, nota_actividades, nota_examen, nota_dualizacion, nota_final, observaciones)
                    VALUES (:aid, :asig, :conv, :nf, :na, :ne, :nd, :nfinal, :obs)
                    ON DUPLICATE KEY UPDATE 
                    nota_foro = VALUES(nota_foro),
                    nota_actividades = VALUES(nota_actividades),
                    nota_examen = VALUES(nota_examen),
                    nota_dualizacion = VALUES(nota_dualizacion),
                    nota_final = VALUES(nota_final),
                    observaciones = VALUES(observaciones)";

            $st = $this->pdo->prepare($sql);

            foreach ($notasBatch as $alumnoId => $vals) {
                $nf = $this->toNum($vals['nota_foro'] ?? null);
                $na = $this->toNum($vals['nota_actividades'] ?? null);
                $ne = $this->toNum($vals['nota_examen'] ?? null);
                $nd = $this->toNum($vals['nota_dualizacion'] ?? null);

                $nfinal = $this->calculateFinal($nf, $na, $ne, $nd, $config);

                $st->execute([
                    ':aid' => $alumnoId,
                    ':asig' => $asignaturaId,
                    ':conv' => $convocatoria,
                    ':nf' => $nf,
                    ':na' => $na,
                    ':ne' => $ne,
                    ':nd' => $nd,
                    ':nfinal' => $nfinal,
                    ':obs' => $vals['observaciones'] ?? null
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function calculateFinal(?float $f, ?float $a, ?float $e, ?float $d, array $config): ?float
    {
        if ($f === null && $a === null && $e === null && $d === null)
            return null;

        $nota = 0.0;
        $totalPeso = 0.0;

        if ($f !== null) {
            $nota += $f * ($config['peso_foro'] / 100);
        }
        if ($a !== null) {
            $nota += $a * ($config['peso_actividades'] / 100);
        }
        if ($e !== null) {
            $nota += $e * ($config['peso_examen'] / 100);
        }
        if ($d !== null) {
            $nota += $d * ($config['peso_dualizacion'] / 100);
        }

        return round($nota, 2);
    }

    private function recalculateFinalGrades(int $asignaturaId, string $convocatoria): void
    {
        $config = $this->getConfig($asignaturaId, $convocatoria);
        $notas = $this->getNotas($asignaturaId, $convocatoria);

        $st = $this->pdo->prepare("UPDATE evaluaciones_notas SET nota_final = :nf WHERE alumno_id = :aid AND asignatura_id = :asig AND convocatoria = :conv");

        foreach ($notas as $n) {
            if ($n['nota_foro'] === null && $n['nota_actividades'] === null && $n['nota_examen'] === null && $n['nota_dualizacion'] === null)
                continue;

            $nf = $this->calculateFinal(
                $this->toNum($n['nota_foro']),
                $this->toNum($n['nota_actividades']),
                $this->toNum($n['nota_examen']),
                $this->toNum($n['nota_dualizacion']),
                $config
            );

            $st->execute([
                ':nf' => $nf,
                ':aid' => $n['id'],
                ':asig' => $asignaturaId,
                ':conv' => $convocatoria
            ]);
        }
    }

    private function toNum($val): ?float
    {
        if ($val === null || $val === '')
            return null;
        return (float) $val;
    }
}
