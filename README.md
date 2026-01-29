<!--
README (HTML) — Practicalia
Repositorio: https://github.com/fpalacioschaves/practicalia
-->

<div align="center">
  <h1>Practicalia</h1>
  <p><strong>Gestión minimalista y efectiva de FP Dual:</strong> alumnado, empresas y el histórico real de contactos.</p>
  <p>
    Un CRUD pensado para el día a día del centro: menos “¿dónde estaba aquello?”, más “lo tengo a un clic”.
  </p>

  <p>
    <a href="#que-es">Qué es</a> ·
    <a href="#funcionalidades">Funcionalidades</a> ·
    <a href="#roles-y-permisos">Roles</a> ·
    <a href="#modelo-de-datos">Modelo de datos</a> ·
    <a href="#requisitos">Requisitos</a> ·
    <a href="#instalacion-rapida">Instalación</a> ·
    <a href="#seguridad">Seguridad</a> ·
    <a href="#roadmap">Roadmap</a>
  </p>
</div>

<hr />

<h2 id="que-es">Qué es</h2>
<p>
  <strong>Practicalia</strong> es una aplicación web tipo <strong>CRUD</strong> orientada a la gestión de la
  <strong>FP Dual</strong> (o prácticas en empresa): alumnado, empresas colaboradoras y un histórico de contactos
  y acciones realizadas. Además, permite gestionar <strong>usuarios</strong> y <strong>cursos/grados</strong>
  con control de acceso por roles.
</p>
<p>
  Está diseñada con una idea fija: <em>hacer lo necesario, bien, y sin barroquismos</em>.
  (Nada de pantallas que parezcan el panel de control de un cohete… salvo que el cohete sea un CRUD 🧾).
</p>

<hr />

<h2 id="funcionalidades">Funcionalidades principales</h2>

<h3>📌 Alumnado</h3>
<ul>
  <li>Alta, edición, listado y baja lógica de alumnos/as.</li>
  <li>Datos básicos: nombre, apellidos, email, teléfono, fecha de nacimiento, notas internas.</li>
  <li>Asociación del alumno a un <strong>curso/grado</strong> (matriculado, baja, finalizado, etc.).</li>
  <li>Histórico de contactos del alumno (tutorías, emails, llamadas, visitas...).</li>
</ul>

<h3>🏢 Empresas</h3>
<ul>
  <li>Alta, edición, listado y baja lógica de empresas colaboradoras.</li>
  <li>Datos de empresa: CIF/NIF, contacto, sector, dirección, web, etc.</li>
  <li>Vinculación empresa ⇄ alumnado cuando procede.</li>
  <li>Histórico de contactos con la empresa (llamadas, emails, visitas...) y seguimiento.</li>
</ul>

<h3>🧠 Histórico de contactos (la parte que “salva la vida”)</h3>
<ul>
  <li>Registro de cada contacto: fecha, tipo/canal, asunto/resumen, notas.</li>
  <li>Seguimiento de estado (pendiente / en proceso / hecho / no interesado) y próxima acción cuando aplique.</li>
  <li>Opción de marcar ciertos contactos como confidenciales (para notas “solo para adultos”).</li>
</ul>

<h3>🎓 Cursos/Grados</h3>
<ul>
  <li>CRUD de cursos (DAM, DAW, etc.).</li>
  <li>Asignación de profesorado a cursos.</li>
  <li>Matriculación/relación alumno ⇄ curso con fechas y estado.</li>
</ul>

<h3>👤 Usuarios</h3>
<ul>
  <li>Gestión de usuarios del sistema.</li>
  <li>Control de acceso por roles.</li>
</ul>

<hr />

<h2 id="roles-y-permisos">Roles y permisos</h2>
<ul>
  <li><strong>Administrador</strong>: acceso total a la aplicación.</li>
  <li><strong>Profesor</strong>: gestión de alumnado asociado a su curso/grado (según asignaciones).</li>
</ul>
<p>
  Internamente se apoya en un modelo de <strong>usuarios</strong>, <strong>roles</strong> y la tabla puente
  <strong>usuarios_roles</strong>, más la asociación de profesores a cursos mediante <strong>cursos_profesores</strong>.
</p>

<hr />

<h2 id="modelo-de-datos">Modelo de datos (resumen)</h2>
<p>
  La base de datos incluye, entre otras, las siguientes tablas:
</p>
<ul>
  <li><strong>alumnos</strong>: datos del alumnado (con baja lógica).</li>
  <li><strong>cursos</strong>: DAM/DAW… (unificación del concepto “curso/grupo” en una sola entidad).</li>
  <li><strong>alumnos_cursos</strong>: matrícula/relación alumno-curso con estado y fechas.</li>
  <li><strong>empresas</strong>: datos de empresas (con baja lógica).</li>
  <li><strong>empresa_alumnos</strong>: relación empresa ⇄ alumno.</li>
  <li><strong>alumno_contactos</strong>: histórico de contactos del alumno con profesor/tutor.</li>
  <li><strong>contactos_empresa</strong> y <strong>empresa_contactos</strong>: registro de contactos y seguimiento con empresa.</li>
  <li><strong>practicas</strong>: información de prácticas (fechas, tutores, estado, horas previstas/realizadas).</li>
  <li><strong>usuarios</strong>, <strong>roles</strong>, <strong>usuarios_roles</strong>: autenticación y permisos.</li>
</ul>

<p>
  Para ver el esquema completo (DDL + índices + claves foráneas) revisa el dump SQL incluido en el proyecto.
</p>

<hr />

<h2 id="requisitos">Requisitos</h2>
<ul>
  <li><strong>PHP</strong> 8.x</li>
  <li><strong>MariaDB</strong> / MySQL</li>
  <li>Servidor web (Apache recomendado)</li>
  <li>Extensión PHP para MySQL (mysqli o PDO; el proyecto trabaja con <strong>PDO</strong>)</li>
</ul>

<hr />

<h2 id="instalacion-rapida">Instalación rápida</h2>

<ol>
  <li>
    <strong>Clona el repositorio</strong>
    <pre><code>git clone https://github.com/fpalacioschaves/practicalia.git
cd practicalia</code></pre>
  </li>

  <li>
    <strong>Crea la base de datos</strong> (por ejemplo, <code>practicalia</code>) en tu MySQL/MariaDB.
  </li>

  <li>
    <strong>Importa el SQL</strong> (phpMyAdmin o CLI).
    <p>Con CLI:</p>
    <pre><code>mysql -u TU_USUARIO -p practicalia &lt; practicalia.sql</code></pre>
  </li>

  <li>
    <strong>Configura la conexión</strong> a base de datos en el archivo de configuración del proyecto
    (host, usuario, password, nombre de BD).
    <p>
      Si lo despliegas en local con WAMP/XAMPP, asegúrate de que el usuario tenga permisos de
      CREATE/ALTER/INDEX, especialmente durante el import del dump.
    </p>
  </li>

  <li>
    <strong>Arranca el servidor</strong> y entra en la aplicación.
    <p>
      Ejemplo: <code>http://localhost/practicalia</code>
    </p>
  </li>
</ol>

<p>
  El dump incluye usuarios y roles de ejemplo. Por seguridad, no se documentan contraseñas “de serie” en el README:
  lo sensato es crear tu propio usuario admin en tu entorno o forzar cambio de contraseña tras el primer acceso.
</p>

<hr />

<h2 id="seguridad">Seguridad y buenas prácticas</h2>
<ul>
  <li>Contraseñas almacenadas como <strong>hash</strong> (password_hash) en base de datos.</li>
  <li>Uso de <strong>PDO</strong> y consultas parametrizadas para evitar inyección SQL.</li>
  <li>Baja lógica en entidades sensibles (alumnos, empresas, usuarios) para trazabilidad.</li>
  <li>Separación clara por roles (admin/profesor) para evitar “curiosidades” accidentales.</li>
</ul>

<hr />

<h2 id="roadmap">Roadmap (ideas de evolución)</h2>
<ul>
  <li>Panel de “resumen de Dual”: alumnos sin empresa, empresas sin seguimiento reciente, próximas acciones.</li>
  <li>Filtros avanzados en listados (curso, estado, sector, provincia, activo/inactivo).</li>
  <li>Exportación de listados (CSV/XLSX) y reportes de seguimiento.</li>
  <li>Calendario de contactos/prácticas (próximas acciones, fechas de inicio/fin).</li>
  <li>Auditoría de acciones por usuario (quién cambió qué y cuándo).</li>
</ul>

<hr />

<h2 id="contribuir">Contribuir</h2>
<p>
  Pull requests y sugerencias son bienvenidas. Si encuentras un bug, abre un issue con:
  pasos para reproducir, comportamiento esperado y logs si aplica.
</p>

<hr />

<h2 id="licencia">Licencia</h2>
<p>
  Define aquí tu licencia (MIT, GPL, privada, etc.). Si no hay licencia, por defecto no se asume permiso de uso/reutilización.
</p>

<hr />

<p>
  <small>
    Nota: el esquema de base de datos, índices y relaciones está definido en el dump SQL del proyecto.
    (Fuente: <em>practicalia.sql</em> incluido en el repositorio). :contentReference[oaicite:0]{index=0}
  </small>
</p>
