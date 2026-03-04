<?php
// lib/services/EmpresaService.php
declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

class EmpresaService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene una empresa por su ID
     */
    public function getById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM empresas WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $st->execute([':id' => $id]);
        $empresa = $st->fetch();
        return $empresa ?: null;
    }

    /**
     * Obtiene los IDs de los cursos asociados a una empresa
     */
    public function getAssociatedCursos(int $empresaId): array
    {
        $st = $this->pdo->prepare('SELECT curso_id FROM empresa_cursos WHERE empresa_id = :e ORDER BY curso_id');
        $st->execute([':e' => $empresaId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Crea una nueva empresa
     */
    public function create(array $data, array $cursosIds, bool $isAdmin, int $profId): int
    {
        $nombre = trim($data['nombre'] ?? '');
        $email = trim($data['email'] ?? '');
        $responsable_email = trim($data['responsable_email'] ?? '');
        $nif = trim($data['nif'] ?? '');
        $cp = trim($data['codigo_postal'] ?? '');
        $esPublica = !empty($data['es_publica']);

        if ($nombre === '')
            throw new RuntimeException('El nombre es obligatorio.');
        if (count($cursosIds) === 0)
            throw new RuntimeException('Selecciona al menos un curso.');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new RuntimeException('Email no válido.');
        if ($responsable_email !== '' && !filter_var($responsable_email, FILTER_VALIDATE_EMAIL))
            throw new RuntimeException('Email del responsable no es válido.');
        if ($cp !== '' && !preg_match('/^[0-9A-Za-z -]{3,10}$/', $cp))
            throw new RuntimeException('Código postal no válido.');

        if ($nif !== '') {
            $stN = $this->pdo->prepare('SELECT 1 FROM empresas WHERE nif = :nif AND deleted_at IS NULL LIMIT 1');
            $stN->execute([':nif' => $nif]);
            if ($stN->fetch())
                throw new RuntimeException('Ya existe otra empresa con ese NIF.');
        }

        // Validar cursos existentes
        $inMarks = implode(',', array_fill(0, count($cursosIds), '?'));
        $stChkC = $this->pdo->prepare("SELECT id FROM cursos WHERE id IN ($inMarks)");
        $stChkC->execute($cursosIds);
        $existentes = array_map('intval', $stChkC->fetchAll(PDO::FETCH_COLUMN));
        $cursosIds = array_values(array_intersect($cursosIds, $existentes));
        if (count($cursosIds) === 0)
            throw new RuntimeException('Los cursos seleccionados no existen.');

        // Validar permisos si no es admin
        if (!$isAdmin) {
            $stChkP = $this->pdo->prepare("
                SELECT curso_id
                FROM cursos_profesores
                WHERE profesor_id = ? AND curso_id IN ($inMarks)
            ");
            $stChkP->bindValue(1, $profId, PDO::PARAM_INT);
            foreach ($cursosIds as $i => $val) {
                $stChkP->bindValue($i + 2, $val, PDO::PARAM_INT);
            }
            $stChkP->execute();
            $permisos = array_map('intval', $stChkP->fetchAll(PDO::FETCH_COLUMN));
            sort($permisos);
            sort($cursosIds);
            if ($permisos !== $cursosIds)
                throw new RuntimeException('No puedes seleccionar alguno de los cursos marcados.');
        }

        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('
                INSERT INTO empresas (
                    nombre, cif, nif, email, telefono, web,
                    direccion, ciudad, provincia, codigo_postal,
                    sector, horario_practicas, es_publica, 
                    responsable_nombre, responsable_cargo, responsable_email, responsable_telefono,
                    tutor_nif, tutor_departamento,
                    rep_legal_nombre, rep_legal_nif, rep_legal_email,
                    activo
                ) VALUES (
                    :nombre, :cif, :nif, :email, :tel, :web,
                    :dir, :ciudad, :provincia, :cp,
                    :sector, :horario, :publica,
                    :rnom, :rcargo, :remail, :rtel,
                    :tnif, :tdep,
                    :rpnom, :rpnif, :rpemail,
                    :activo
                )
            ');
            $st->execute([
                ':nombre' => $nombre,
                ':cif' => ($data['cif'] !== '' ? $data['cif'] : null),
                ':nif' => ($nif !== '' ? $nif : null),
                ':email' => ($email !== '' ? $email : null),
                ':tel' => ($data['telefono'] !== '' ? $data['telefono'] : null),
                ':web' => ($data['web'] !== '' ? $data['web'] : null),
                ':dir' => ($data['direccion'] !== '' ? $data['direccion'] : null),
                ':ciudad' => ($data['ciudad'] !== '' ? $data['ciudad'] : null),
                ':provincia' => ($data['provincia'] !== '' ? $data['provincia'] : null),
                ':cp' => ($cp !== '' ? $cp : null),
                ':sector' => ($data['sector'] !== '' ? $data['sector'] : null),
                ':horario' => ($data['horario_practicas'] !== '' ? $data['horario_practicas'] : null),
                ':publica' => $esPublica ? 1 : 0,
                ':rnom' => ($data['responsable_nombre'] !== '' ? $data['responsable_nombre'] : null),
                ':rcargo' => ($data['responsable_cargo'] !== '' ? $data['responsable_cargo'] : null),
                ':remail' => ($responsable_email !== '' ? $responsable_email : null),
                ':rtel' => ($data['responsable_telefono'] !== '' ? $data['responsable_telefono'] : null),
                ':tnif' => ($data['tutor_nif'] !== '' ? $data['tutor_nif'] : null),
                ':tdep' => ($data['tutor_departamento'] !== '' ? $data['tutor_departamento'] : null),
                ':rpnom' => ($data['rep_legal_nombre'] !== '' ? $data['rep_legal_nombre'] : null),
                ':rpnif' => ($data['rep_legal_nif'] !== '' ? $data['rep_legal_nif'] : null),
                ':rpemail' => ($data['rep_legal_email'] !== '' ? $data['rep_legal_email'] : null),
                ':activo' => (int) ($data['activo'] ?? 1)
            ]);
            $empresaId = (int) $this->pdo->lastInsertId();

            // Insertar cursos asociados
            $stRel = $this->pdo->prepare('INSERT INTO empresa_cursos (empresa_id, curso_id) VALUES (:e,:c)');
            foreach ($cursosIds as $cid) {
                $stRel->execute([':e' => $empresaId, ':c' => $cid]);
            }

            $this->pdo->commit();
            return $empresaId;
        } catch (RuntimeException $e) {
            $this->pdo->rollBack();
            throw $e;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('Error al crear la empresa.');
        }
    }

    /**
     * Actualiza los datos de una empresa
     */
    public function update(int $id, array $data, array $cursosIds, bool $isAdmin, int $profId): void
    {
        $nombre = trim($data['nombre'] ?? '');
        $email = trim($data['email'] ?? '');
        $responsable_email = trim($data['responsable_email'] ?? '');
        $nif = trim($data['nif'] ?? '');
        $cp = trim($data['codigo_postal'] ?? '');
        $esPublica = !empty($data['es_publica']);

        if ($nombre === '')
            throw new RuntimeException('El nombre es obligatorio.');
        if (count($cursosIds) === 0)
            throw new RuntimeException('Selecciona al menos un curso.');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new RuntimeException('Email no válido.');
        if ($responsable_email !== '' && !filter_var($responsable_email, FILTER_VALIDATE_EMAIL))
            throw new RuntimeException('Email del responsable no es válido.');
        if ($cp !== '' && !preg_match('/^[0-9A-Za-z -]{3,10}$/', $cp))
            throw new RuntimeException('Código postal no válido.');

        if ($nif !== '') {
            $stN = $this->pdo->prepare('SELECT 1 FROM empresas WHERE nif = :nif AND id <> :id AND deleted_at IS NULL LIMIT 1');
            $stN->execute([':nif' => $nif, ':id' => $id]);
            if ($stN->fetch())
                throw new RuntimeException('Ya existe otra empresa con ese NIF.');
        }

        // Validar cursos existentes
        $inMarks = implode(',', array_fill(0, count($cursosIds), '?'));
        $stChkC = $this->pdo->prepare("SELECT id FROM cursos WHERE id IN ($inMarks)");
        $stChkC->execute($cursosIds);
        $existentes = array_map('intval', $stChkC->fetchAll(PDO::FETCH_COLUMN));
        $cursosIds = array_values(array_intersect($cursosIds, $existentes));
        if (count($cursosIds) === 0)
            throw new RuntimeException('Los cursos seleccionados no existen.');

        // Validar permisos si no es admin
        if (!$isAdmin) {
            // IMPORTANTE: Si es pública, la puede editar CUALQUIERA? No, normalmente solo el creador o quien tenga acceso.
            // Mantenemos la lógica de que para editar debe tener permiso sobre los cursos asignados (o al menos uno).
            // Pero si es pública, ¿permitimos que cualquier profesor la edite?
            // Asumimos: Para EDITAR, debe seguir teniendo relación con los cursos.
            // La visibilidad pública es solo para VER (getList/getCount).
            $stChkP = $this->pdo->prepare("
                SELECT curso_id
                FROM cursos_profesores
                WHERE profesor_id = ? AND curso_id IN ($inMarks)
            ");
            $stChkP->bindValue(1, $profId, PDO::PARAM_INT);
            foreach ($cursosIds as $i => $val) {
                $stChkP->bindValue($i + 2, $val, PDO::PARAM_INT);
            }
            $stChkP->execute();
            $permisos = array_map('intval', $stChkP->fetchAll(PDO::FETCH_COLUMN));
            sort($permisos);
            sort($cursosIds);
            if ($permisos !== $cursosIds)
                throw new RuntimeException('No puedes seleccionar alguno de los cursos marcados.');
        }

        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('
                UPDATE empresas SET
                    nombre=:nombre, cif=:cif, nif=:nif, email=:email, telefono=:tel, web=:web,
                    direccion=:dir, ciudad=:ciudad, provincia=:provincia, codigo_postal=:cp,
                    sector=:sector, horario_practicas=:horario, es_publica=:publica,
                    responsable_nombre=:rnom,
                    responsable_cargo=:rcargo,
                    responsable_email=:remail,
                    responsable_telefono=:rtel,
                    tutor_nif=:tnif,
                    tutor_departamento=:tdep,
                    rep_legal_nombre=:rpnom,
                    rep_legal_nif=:rpnif,
                    rep_legal_email=:rpemail,
                    activo=:activo
                WHERE id=:id
            ');
            $st->execute([
                ':nombre' => $nombre,
                ':cif' => ($data['cif'] !== '' ? $data['cif'] : null),
                ':nif' => ($nif !== '' ? $nif : null),
                ':email' => ($email !== '' ? $email : null),
                ':tel' => ($data['telefono'] !== '' ? $data['telefono'] : null),
                ':web' => ($data['web'] !== '' ? $data['web'] : null),
                ':dir' => ($data['direccion'] !== '' ? $data['direccion'] : null),
                ':ciudad' => ($data['ciudad'] !== '' ? $data['ciudad'] : null),
                ':provincia' => ($data['provincia'] !== '' ? $data['provincia'] : null),
                ':cp' => ($cp !== '' ? $cp : null),
                ':sector' => ($data['sector'] !== '' ? $data['sector'] : null),
                ':horario' => ($data['horario_practicas'] !== '' ? $data['horario_practicas'] : null),
                ':publica' => $esPublica ? 1 : 0,
                ':rnom' => ($data['responsable_nombre'] !== '' ? $data['responsable_nombre'] : null),
                ':rcargo' => ($data['responsable_cargo'] !== '' ? $data['responsable_cargo'] : null),
                ':remail' => ($responsable_email !== '' ? $responsable_email : null),
                ':rtel' => ($data['responsable_telefono'] !== '' ? $data['responsable_telefono'] : null),
                ':tnif' => ($data['tutor_nif'] !== '' ? $data['tutor_nif'] : null),
                ':tdep' => ($data['tutor_departamento'] !== '' ? $data['tutor_departamento'] : null),
                ':rpnom' => ($data['rep_legal_nombre'] !== '' ? $data['rep_legal_nombre'] : null),
                ':rpnif' => ($data['rep_legal_nif'] !== '' ? $data['rep_legal_nif'] : null),
                ':rpemail' => ($data['rep_legal_email'] !== '' ? $data['rep_legal_email'] : null),
                ':activo' => (int) ($data['activo'] ?? 0),
                ':id' => $id
            ]);

            // Actualizar cursos asociados
            $this->pdo->prepare('DELETE FROM empresa_cursos WHERE empresa_id = :e')->execute([':e' => $id]);
            $stRel = $this->pdo->prepare('INSERT INTO empresa_cursos (empresa_id, curso_id) VALUES (:e,:c)');
            foreach ($cursosIds as $cid) {
                $stRel->execute([':e' => $id, ':c' => $cid]);
            }

            $this->pdo->commit();
        } catch (RuntimeException $e) {
            $this->pdo->rollBack();
            throw $e;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('Error al guardar la empresa.');
        }
    }

    /**
     * Obtiene el total de empresas filtradas
     */
    public function getCount(string $search, bool $isAdmin, int $profId): int
    {
        $params = [];
        $where = "e.deleted_at IS NULL";
        if ($search !== '') {
            $where .= " AND (e.nombre LIKE :q1 OR e.ciudad LIKE :q2 OR e.provincia LIKE :q3 OR e.codigo_postal LIKE :q4)";
            $like = "%{$search}%";
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
        }

        if ($isAdmin) {
            $sql = "SELECT COUNT(*) c FROM empresas e WHERE $where";
        } else {
            $sql = "
                SELECT COUNT(DISTINCT e.id) c
                FROM empresas e
                LEFT JOIN empresa_cursos ec ON ec.empresa_id = e.id
                LEFT JOIN cursos_profesores cp ON cp.curso_id = ec.curso_id AND cp.profesor_id = :pid
                WHERE $where AND (e.es_publica = 1 OR cp.profesor_id IS NOT NULL)
            ";
            $params[':pid'] = $profId;
        }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $res = $st->fetch();
        return (int) ($res['c'] ?? 0);
    }

    /**
     * Obtiene la lista paginada de empresas filtradas
     */
    public function getList(string $search, bool $isAdmin, int $profId, int $limit, int $offset): array
    {
        $params = [':limit' => $limit, ':offset' => $offset];
        $where = "e.deleted_at IS NULL";
        if ($search !== '') {
            $where .= " AND (e.nombre LIKE :q1 OR e.ciudad LIKE :q2 OR e.provincia LIKE :q3 OR e.codigo_postal LIKE :q4)";
            $like = "%{$search}%";
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
        }

        if ($isAdmin) {
            $sql = "
                SELECT e.*
                FROM empresas e
                WHERE $where
                ORDER BY e.nombre ASC
                LIMIT :limit OFFSET :offset
            ";
        } else {
            $sql = "
                SELECT DISTINCT e.*
                FROM empresas e
                LEFT JOIN empresa_cursos ec ON ec.empresa_id = e.id
                LEFT JOIN cursos_profesores cp ON cp.curso_id = ec.curso_id AND cp.profesor_id = :pid
                WHERE $where AND (e.es_publica = 1 OR cp.profesor_id IS NOT NULL)
                ORDER BY e.id DESC
                LIMIT :limit OFFSET :offset
            ";
            $params[':pid'] = $profId;
        }

        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $st->execute();
        return $st->fetchAll();
    }

    /**
     * Obtiene los nombres de los cursos para un conjunto de IDs de empresas
     */
    public function getCursoNombresByEmpresaIds(array $ids): array
    {
        if (empty($ids))
            return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->pdo->prepare("
            SELECT ec.empresa_id, c.nombre
            FROM empresa_cursos ec
            JOIN cursos c ON c.id = ec.curso_id
            WHERE ec.empresa_id IN ($placeholders)
            ORDER BY c.nombre
        ");
        $st->execute($ids);

        $res = [];
        foreach ($st->fetchAll() as $r) {
            $res[(int) $r['empresa_id']][] = $r['nombre'];
        }
        return $res;
    }

    /**
     * Elimina una empresa
     */
    public function delete(int $id, bool $isAdmin, bool $force = false): void
    {
        if (!$isAdmin)
            throw new RuntimeException('No tienes permiso para eliminar empresas.');

        $this->pdo->beginTransaction();
        try {
            if ($force) {
                $st = $this->pdo->prepare('DELETE FROM empresas WHERE id = :id');
                $st->execute([':id' => $id]);
            } else {
                $st = $this->pdo->prepare('
                    UPDATE empresas SET
                        deleted_at = NOW(),
                        responsable_nombre = NULL,
                        responsable_cargo = NULL,
                        responsable_email = NULL,
                        responsable_telefono = NULL
                    WHERE id = :id
                ');
                $st->execute([':id' => $id]);
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('Error al eliminar la empresa: ' . $e->getMessage());
        }
    }
}
