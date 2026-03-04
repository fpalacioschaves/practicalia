-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-03-2026 a las 20:36:12
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `practicalia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `seg_social` varchar(20) DEFAULT NULL,
  `provincia_localidad` varchar(100) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  `fecha_nacimiento` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`id`, `nombre`, `apellidos`, `dni`, `seg_social`, `provincia_localidad`, `email`, `telefono`, `activo`, `fecha_nacimiento`, `notas`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1003, 'Adrián', 'Arroyo García', NULL, NULL, NULL, 'adrianarroyo93@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:28:00', '2025-10-18 09:28:00', NULL),
(1004, 'Jesús', 'Benitez Maestre', NULL, NULL, NULL, 'jesusbenim78@icloud.com', NULL, 1, NULL, NULL, '2025-10-18 09:29:12', '2025-10-18 09:29:12', NULL),
(1005, 'Juan Carlos', 'Bernal Tortosa', NULL, NULL, NULL, 'jcbernalt@hotmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:29:34', '2025-10-18 09:29:34', NULL),
(1006, 'Carlos', 'Cobos Medina', NULL, NULL, NULL, 'carlosfactory97@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:29:54', '2025-10-18 09:29:54', NULL),
(1007, 'Francisco Javier', 'Jimenez Cortés', NULL, NULL, NULL, 'al1protocol23@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:30:14', '2025-10-18 09:30:14', NULL),
(1008, 'Pablo', 'López Anelo', NULL, NULL, NULL, 'plastg28@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:31:39', '2025-10-18 09:31:39', NULL),
(1009, 'Félix', 'Martín Navarro', NULL, NULL, NULL, 'felixmn99@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:32:03', '2025-10-18 09:32:03', NULL),
(1010, 'Juan Francisco', 'Mena Cobano', NULL, NULL, NULL, 'juanfranciscomenacobano@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:32:26', '2025-10-18 09:32:26', NULL),
(1011, 'Jaime', 'Morejón Díaz', NULL, NULL, NULL, 'jaimemorejdiaz@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:32:46', '2025-10-18 09:32:46', NULL),
(1012, 'Eugenio', 'Nimo Flor', NULL, NULL, NULL, 'enimo@fundacionsafa.es', NULL, 1, NULL, NULL, '2025-10-18 09:33:06', '2025-10-18 09:33:06', NULL),
(1013, 'Álvaro', 'Rodríguez Martínez', NULL, NULL, NULL, 'arm1612004@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:33:35', '2025-10-18 09:33:35', NULL),
(1014, 'José Manuel', 'Ruíz Herrera', NULL, NULL, NULL, 'joserom17@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:33:57', '2025-10-18 09:33:57', NULL),
(1015, 'Eduardo Jaime', 'Vera Olmo', NULL, NULL, NULL, 'ejvo1981@hotmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:34:20', '2025-10-18 09:34:20', NULL),
(1016, 'bj,vbn,', 'nm,bnm,', NULL, NULL, NULL, 'fpalacioschaves@gmail.com', '655925498666', 1, '2007-01-31', 'hgfhjghj', '2026-01-29 17:00:28', '2026-01-29 17:00:28', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos_asignaturas`
--

CREATE TABLE `alumnos_asignaturas` (
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `asignatura_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `alumnos_asignaturas`
--

INSERT INTO `alumnos_asignaturas` (`alumno_id`, `asignatura_id`, `created_at`) VALUES
(1003, 2, '2026-03-04 16:51:37'),
(1003, 6, '2026-03-04 16:51:37'),
(1003, 7, '2026-03-04 16:51:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos_cursos`
--

CREATE TABLE `alumnos_cursos` (
  `id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('preinscrito','matriculado','baja','finalizado') NOT NULL DEFAULT 'matriculado',
  `grupo` varchar(30) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumnos_cursos`
--

INSERT INTO `alumnos_cursos` (`id`, `alumno_id`, `curso_id`, `fecha_inicio`, `fecha_fin`, `estado`, `grupo`, `observaciones`, `created_at`, `updated_at`) VALUES
(12, 1003, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:28:00', '2025-10-18 09:28:00'),
(13, 1004, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:29:12', '2025-10-18 09:29:12'),
(14, 1005, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:29:34', '2025-10-18 09:29:34'),
(15, 1007, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:30:14', '2025-10-18 09:30:14'),
(16, 1006, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:30:23', '2025-10-18 09:30:23'),
(18, 1009, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:32:03', '2025-10-18 09:32:03'),
(19, 1010, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:32:26', '2025-10-18 09:32:26'),
(20, 1011, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:32:46', '2025-10-18 09:32:46'),
(21, 1012, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:33:06', '2025-10-18 09:33:06'),
(22, 1013, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:33:35', '2025-10-18 09:33:35'),
(25, 1008, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:37:40', '2025-10-18 09:37:40'),
(28, 1015, 2, '2025-10-21', NULL, 'matriculado', NULL, NULL, '2025-10-21 16:35:51', '2025-10-21 16:35:51'),
(29, 1014, 2, '2025-10-21', NULL, 'matriculado', NULL, NULL, '2025-10-21 16:36:00', '2025-10-21 16:36:00'),
(30, 1016, 2, '2026-01-29', NULL, 'matriculado', NULL, NULL, '2026-01-29 17:00:28', '2026-01-29 17:00:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno_contactos`
--

CREATE TABLE `alumno_contactos` (
  `id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `profesor_id` int(10) UNSIGNED NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('llamada','email','tutoria','visita','reunión','whatsapp') NOT NULL DEFAULT 'tutoria',
  `resumen` varchar(255) NOT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumno_contactos`
--

INSERT INTO `alumno_contactos` (`id`, `alumno_id`, `profesor_id`, `fecha`, `tipo`, `resumen`, `notas`) VALUES
(2, 1005, 4, '2025-10-18 11:36:25', 'email', 'Primer contacto', 'Enviado email al alumno'),
(3, 1008, 4, '2025-10-18 11:37:28', 'email', 'Primer contacto con empresas', 'Tiene un par de conocidos que trabajan en este campo así que les preguntará si es posible pero siendo realista creo que será muy complicado. Según le respondan esta semana ya me lo comentará.'),
(4, 1009, 4, '2025-10-18 11:38:51', 'llamada', 'Primer contacto con empresa', 'enviado email a empresa de contacto de Felix (Brenda Paola Gaviria Guzmán brenda.gaviria@bluumi.com) \r\nA la espera de respuesta de la empresa (Bloomi). Comenzaría las prácticas el 3 de Noviembre'),
(5, 1010, 4, '2025-10-18 11:39:47', 'email', 'Primer contacto', 'Ha mandado el CV y no tiene conocidos en ninguna empresa para hacer las prácticas'),
(6, 1015, 4, '2025-10-21 08:36:29', 'email', 'fghdfhdfgh', 'dfghsdfhsdgfhsdgfh');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaturas`
--

CREATE TABLE `asignaturas` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `nivel` tinyint(3) UNSIGNED DEFAULT 1,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(30) DEFAULT NULL,
  `ects` decimal(4,1) DEFAULT NULL,
  `horas` smallint(5) UNSIGNED DEFAULT NULL,
  `semestre` tinyint(3) UNSIGNED DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asignaturas`
--

INSERT INTO `asignaturas` (`id`, `curso_id`, `nivel`, `nombre`, `codigo`, `ects`, `horas`, `semestre`, `descripcion`, `activo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 'Sistemas informáticos', '0483', NULL, 192, NULL, NULL, 1, '2025-10-23 10:21:41', '2025-10-23 10:21:41', NULL),
(2, 1, 1, 'Bases de datos', '0484', NULL, 192, NULL, NULL, 1, '2025-10-23 10:27:29', '2025-10-23 10:27:29', NULL),
(3, 1, 1, 'Programación', '0485', NULL, 288, NULL, NULL, 1, '2025-10-23 11:41:22', '2025-10-23 11:41:22', NULL),
(4, 1, 1, 'Lenguajes de marcas y sistemas de gestión de información', '0486', NULL, 128, NULL, NULL, 1, '2025-10-23 11:45:44', '2025-10-23 11:45:44', NULL),
(5, 1, 1, 'Entornos de Desarrollo', NULL, NULL, 96, NULL, NULL, 1, '2025-10-26 11:57:32', '2025-10-26 11:57:32', NULL),
(6, 1, 2, 'Acceso a datos', NULL, NULL, 126, NULL, NULL, 1, '2025-10-26 12:06:24', '2026-03-04 19:12:15', NULL),
(7, 1, 1, 'Desarrollo de interfaces', '0488', NULL, 80, NULL, NULL, 1, '2025-10-26 12:10:27', '2025-10-26 12:10:27', NULL),
(8, 1, 2, 'Programación de servicios y procesos', '0490', NULL, 84, NULL, NULL, 1, '2025-10-26 12:13:31', '2026-03-04 19:12:56', NULL),
(9, 1, 2, 'Programación multimedia y dispositivos móviles', '0491', NULL, 84, NULL, NULL, 1, '2025-10-26 12:25:33', '2026-03-04 19:13:04', NULL),
(10, 1, 2, 'Sistemas de gestión empresarial', '0492', NULL, 84, NULL, NULL, 1, '2025-10-26 12:27:40', '2026-03-04 19:13:11', NULL),
(11, 2, 2, 'Desarrollo web en entorno cliente', '0493', NULL, 126, NULL, NULL, 1, '2025-10-26 12:30:48', '2026-03-04 19:12:22', NULL),
(12, 2, 2, 'Desarrollo web en entorno servidor', '0494', NULL, 126, NULL, NULL, 1, '2025-10-26 12:33:17', '2026-03-04 19:12:27', NULL),
(13, 2, 2, 'Despliegue de aplicaciones web', '0495', NULL, 63, NULL, NULL, 1, '2025-10-26 12:35:26', '2026-03-04 19:12:34', NULL),
(14, 2, 2, 'Diseño de interfaces web', '0496', NULL, 84, NULL, NULL, 1, '2025-10-26 12:37:28', '2026-03-04 19:12:40', NULL),
(15, 2, 1, 'Empresa e iniciativa emprendedora', '0497', NULL, 84, NULL, NULL, 1, '2025-10-26 12:39:54', '2025-10-26 12:39:54', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignatura_cursos`
--

CREATE TABLE `asignatura_cursos` (
  `asignatura_id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asignatura_cursos`
--

INSERT INTO `asignatura_cursos` (`asignatura_id`, `curso_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(4, 1),
(4, 2),
(5, 1),
(5, 2),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 2),
(12, 2),
(13, 2),
(14, 2),
(15, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignatura_ras`
--

CREATE TABLE `asignatura_ras` (
  `id` int(10) UNSIGNED NOT NULL,
  `asignatura_id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` smallint(5) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asignatura_ras`
--

INSERT INTO `asignatura_ras` (`id`, `asignatura_id`, `codigo`, `titulo`, `descripcion`, `orden`, `activo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'RA1', 'Evalúa sistemas informáticos identificando sus componentes y características.', NULL, NULL, 1, '2025-10-23 10:22:32', '2025-10-23 10:22:32', NULL),
(2, 1, 'RA2', 'Instala y configura sistemas operativos, valorando su idoneidad y asegurando su operatividad.', NULL, NULL, 1, '2025-10-23 10:22:55', '2025-10-23 10:22:55', NULL),
(3, 1, 'RA3', 'Realiza tareas de virtualización y contenedorización para desplegar sistemas y servicios en entornos controlados.', NULL, NULL, 1, '2025-10-23 10:23:13', '2025-10-23 10:23:13', NULL),
(4, 1, 'RA4', 'Configura redes locales y servicios básicos, verificando conectividad, segmentación y seguridad.', NULL, NULL, 1, '2025-10-23 10:23:33', '2025-10-23 10:23:33', NULL),
(5, 1, 'RA5', 'Aplica procedimientos de seguridad en sistemas, servicios y datos conforme al plan establecido.', NULL, NULL, 1, '2025-10-23 10:23:51', '2025-10-23 10:23:51', NULL),
(6, 1, 'RA6', 'Administra software de propósito general y específico, documentando instalación, actualización y mantenimiento.', NULL, NULL, 1, '2025-10-23 10:24:09', '2025-10-23 10:24:09', NULL),
(7, 1, 'RA7', 'Elabora documentación técnica y verifica el funcionamiento del sistema conforme a especificaciones.', NULL, NULL, 1, '2025-10-23 10:24:27', '2025-10-23 10:24:27', NULL),
(8, 2, 'RA1', 'Reconoce los elementos de una base de datos, describiendo sus funciones y valorando su utilidad.', NULL, NULL, 1, '2025-10-23 10:27:44', '2025-10-23 10:27:44', NULL),
(9, 2, 'RA2', 'Diseña bases de datos relacionales normalizadas, interpretando diagramas entidad–relación y aplicando reglas de integridad.', NULL, NULL, 1, '2025-10-23 10:28:00', '2025-10-23 10:28:00', NULL),
(10, 2, 'RA3', 'Crea bases de datos y elementos asociados mediante lenguajes de definición de datos.', NULL, NULL, 1, '2025-10-23 10:28:15', '2025-10-23 10:28:15', NULL),
(11, 2, 'RA4', 'Realiza consultas sobre una base de datos utilizando lenguajes de manipulación de datos.', NULL, NULL, 1, '2025-10-23 10:28:37', '2025-10-23 10:28:37', NULL),
(12, 2, 'RA5', 'Desarrolla procedimientos almacenados y disparadores, asegurando la integridad y consistencia de la información.', NULL, NULL, 1, '2025-10-23 10:28:57', '2025-10-23 10:28:57', NULL),
(13, 2, 'RA6', 'Gestiona la seguridad de la información identificando usuarios, permisos y copias de seguridad.', NULL, NULL, 1, '2025-10-23 10:29:21', '2025-10-23 10:29:21', NULL),
(14, 2, 'RA7', 'Documenta y mantiene bases de datos aplicando estándares de calidad y buenas prácticas.', NULL, NULL, 1, '2025-10-23 10:29:41', '2025-10-23 10:29:41', NULL),
(15, 3, 'RA1', 'Reconoce las estructuras de un programa informático, analizando y utilizando elementos del lenguaje de programación.', NULL, NULL, 1, '2025-10-23 11:41:44', '2025-10-23 11:41:44', NULL),
(16, 3, 'RA2', 'Utiliza estructuras de control y datos simples para resolver problemas de forma algorítmica.', NULL, NULL, 1, '2025-10-23 11:42:02', '2025-10-23 11:42:02', NULL),
(17, 3, 'RA3', 'Diseña y desarrolla programas aplicando estructuras de datos compuestas y tipos de datos definidos por el usuario.', NULL, NULL, 1, '2025-10-23 11:42:18', '2025-10-23 11:42:18', NULL),
(18, 3, 'RA4', 'Desarrolla programas que incorporan módulos y funciones, garantizando la reutilización y el mantenimiento del código.', NULL, NULL, 1, '2025-10-23 11:42:35', '2025-10-23 11:42:35', NULL),
(19, 3, 'RA5', 'Utiliza técnicas de depuración y documentación para mejorar la calidad del software desarrollado.', NULL, NULL, 1, '2025-10-23 11:42:55', '2025-10-23 11:42:55', NULL),
(20, 3, 'RA6', 'Emplea librerías y recursos del entorno de desarrollo para crear aplicaciones que interactúan con archivos y sistemas externos.', NULL, NULL, 1, '2025-10-23 11:43:17', '2025-10-23 11:43:17', NULL),
(21, 3, 'RA7', 'Comprueba el funcionamiento de los programas mediante pruebas, analizando los resultados y corrigiendo errores.', NULL, NULL, 1, '2025-10-23 11:43:35', '2025-10-23 11:43:35', NULL),
(22, 4, 'RA1', 'Identifica las características y ámbitos de aplicación de los lenguajes de marcas, reconociendo su sintaxis y evolución.', NULL, NULL, 1, '2025-10-23 11:46:02', '2025-10-23 11:46:02', NULL),
(23, 4, 'RA2', 'Modela información con XML, definiendo estructura y restricciones mediante DTD y XML Schema, y valida documentos.', NULL, NULL, 1, '2025-10-23 11:46:31', '2025-10-23 11:46:31', NULL),
(24, 4, 'RA3', 'Realiza consultas y transformaciones sobre documentos XML utilizando XPath y XSLT, generando salidas en distintos formatos.', NULL, NULL, 1, '2025-10-23 11:46:50', '2025-10-23 11:46:50', NULL),
(25, 4, 'RA4', 'Estructura contenidos web con HTML5 y separa presentación con CSS, aplicando criterios de accesibilidad y usabilidad.', NULL, NULL, 1, '2025-10-23 11:47:09', '2025-10-23 11:47:09', NULL),
(26, 4, 'RA5', 'Intercambia y gestiona información semiestructurada (XML/JSON), incluyendo sindicación (RSS/Atom) y conversión entre formatos.', NULL, NULL, 1, '2025-10-23 11:47:27', '2025-10-23 11:47:27', NULL),
(27, 4, 'RA6', 'Integra documentos de marcas en sistemas de información, utilizando almacenamiento/consulta (BD y motores XML/JSON), servicios web y control de versiones.', NULL, NULL, 1, '2025-10-23 11:47:50', '2025-10-23 11:47:50', NULL),
(28, 5, 'RA1', 'Reconoce las características de los entornos integrados de desarrollo analizando sus prestaciones y configurando sus parámetros.', NULL, NULL, 1, '2025-10-26 11:58:05', '2025-10-26 11:58:05', NULL),
(29, 5, 'RA2', 'Utiliza sistemas de control de versiones evaluando su necesidad e implantándolos en proyectos de desarrollo de software.', NULL, NULL, 1, '2025-10-26 11:58:27', '2025-10-26 11:58:27', NULL),
(30, 5, 'RA3', 'Organiza las tareas de desarrollo del software elaborando y manteniendo la documentación asociada.', NULL, NULL, 1, '2025-10-26 11:58:46', '2025-10-26 11:58:46', NULL),
(31, 5, 'RA4', 'Desarrolla aplicaciones sencillas seleccionando y utilizando librerías y componentes adecuados.', NULL, NULL, 1, '2025-10-26 11:59:04', '2025-10-26 11:59:04', NULL),
(32, 5, 'RA5', 'Implanta y utiliza entornos de desarrollo orientados a la productividad y la calidad.', NULL, NULL, 1, '2025-10-26 11:59:20', '2025-10-26 11:59:20', NULL),
(33, 5, 'RA6', 'Documenta aplicaciones y componentes de software utilizando herramientas específicas.', NULL, NULL, 1, '2025-10-26 11:59:37', '2025-10-26 11:59:37', NULL),
(34, 6, 'RA1', 'Define y utiliza conexiones entre aplicaciones y bases de datos empleando APIs y frameworks específicos.', NULL, NULL, 1, '2025-10-26 12:06:46', '2025-10-26 12:06:46', NULL),
(35, 6, 'RA2', 'Implementa operaciones de acceso, inserción, modificación y borrado de información utilizando sentencias y procedimientos adecuados.', NULL, NULL, 1, '2025-10-26 12:07:03', '2025-10-26 12:07:03', NULL),
(36, 6, 'RA3', 'Gestiona transacciones garantizando la integridad de los datos y aplicando mecanismos de control de concurrencia.', NULL, NULL, 1, '2025-10-26 12:07:16', '2025-10-26 12:07:16', NULL),
(37, 6, 'RA4', 'Implementa acceso a datos utilizando objetos de dominio y técnicas de persistencia.', NULL, NULL, 1, '2025-10-26 12:07:32', '2025-10-26 12:07:32', NULL),
(38, 6, 'RA5', 'Aplica técnicas avanzadas de acceso a datos empleando frameworks ORM y bibliotecas equivalentes.', NULL, NULL, 1, '2025-10-26 12:07:48', '2025-10-26 12:07:48', NULL),
(39, 6, 'RA6', 'Documenta y optimiza el código de acceso a datos aplicando buenas prácticas de seguridad y eficiencia.', NULL, NULL, 1, '2025-10-26 12:08:05', '2025-10-26 12:08:05', NULL),
(40, 7, 'RA1', 'Genera interfaces gráficos de usuario mediante editores visuales, adaptando el código generado.  BOE', NULL, NULL, 1, '2025-10-26 12:10:57', '2025-10-26 12:10:57', NULL),
(41, 7, 'RA2', 'Genera interfaces gráficos de usuario basados en XML, adaptando el documento y el código generado.', NULL, NULL, 1, '2025-10-26 12:11:13', '2025-10-26 12:11:13', NULL),
(42, 7, 'RA3', 'Crea componentes visuales utilizando herramientas específicas y realizando pruebas unitarias.', NULL, NULL, 1, '2025-10-26 12:11:28', '2025-10-26 12:11:28', NULL),
(43, 7, 'RA4', 'Diseña interfaces aplicando criterios de usabilidad (estructura, controles, mensajes, pruebas de usabilidad).', NULL, NULL, 1, '2025-10-26 12:11:45', '2025-10-26 12:11:45', NULL),
(44, 7, 'RA5', 'Crea informes con herramientas gráficas, incluyendo filtros, cálculos y gráficos, e integración en la app.', NULL, NULL, 1, '2025-10-26 12:12:00', '2025-10-26 12:12:00', NULL),
(45, 7, 'RA6', 'Documenta aplicaciones utilizando herramientas de ayudas, manuales, guías y documentación técnica.', NULL, NULL, 1, '2025-10-26 12:12:16', '2025-10-26 12:12:16', NULL),
(46, 7, 'RA7', 'Prepara aplicaciones para su distribución (empaquetado, asistentes de instalación, modos desatendidos, desinstalación).', NULL, NULL, 1, '2025-10-26 12:12:34', '2025-10-26 12:12:34', NULL),
(47, 7, 'RA8', 'Evalúa el funcionamiento de aplicaciones diseñando y ejecutando pruebas (integración, regresión, volumen, seguridad, recursos).', NULL, NULL, 1, '2025-10-26 12:12:53', '2025-10-26 12:12:53', NULL),
(48, 8, 'RA1', 'Desarrolla aplicaciones que implementan procesos concurrentes, aplicando técnicas de sincronización y comunicación entre procesos.', NULL, NULL, 1, '2025-10-26 12:13:52', '2025-10-26 12:13:52', NULL),
(49, 8, 'RA2', 'Programa servicios de comunicación entre sistemas, utilizando sockets y protocolos específicos.', NULL, NULL, 1, '2025-10-26 12:14:12', '2025-10-26 12:14:12', NULL),
(50, 8, 'RA3', 'Desarrolla servicios en red aplicando arquitecturas cliente-servidor y multihilo.', NULL, NULL, 1, '2025-10-26 12:14:27', '2025-10-26 12:14:27', NULL),
(51, 8, 'RA4', 'Implanta servicios y demonios en distintos sistemas operativos, configurando su ejecución automática y seguridad.', NULL, NULL, 1, '2025-10-26 12:14:42', '2025-10-26 12:14:42', NULL),
(52, 8, 'RA5', 'Utiliza mecanismos de acceso remoto y ejecución distribuida de procesos.', NULL, NULL, 1, '2025-10-26 12:14:59', '2025-10-26 12:14:59', NULL),
(53, 8, 'RA6', 'Documenta, prueba y mantiene aplicaciones y servicios, aplicando criterios de calidad y eficiencia.', NULL, NULL, 1, '2025-10-26 12:15:21', '2025-10-26 12:15:21', NULL),
(54, 9, 'RA1', 'Desarrolla aplicaciones que integran elementos multimedia (audio, vídeo, gráficos, animaciones) utilizando librerías y APIs específicas.', NULL, NULL, 1, '2025-10-26 12:26:00', '2025-10-26 12:26:00', NULL),
(55, 9, 'RA2', 'Crea interfaces gráficas adaptadas a diferentes dispositivos y resoluciones.', NULL, NULL, 1, '2025-10-26 12:26:14', '2025-10-26 12:26:14', NULL),
(56, 9, 'RA3', 'Programa aplicaciones para dispositivos móviles aplicando las especificaciones del entorno de desarrollo correspondiente.', NULL, NULL, 1, '2025-10-26 12:26:25', '2025-10-26 12:26:25', NULL),
(57, 9, 'RA4', 'Implementa el acceso a servicios y recursos del dispositivo (sensores, cámara, almacenamiento, red, geolocalización).', NULL, NULL, 1, '2025-10-26 12:26:38', '2025-10-26 12:26:38', NULL),
(58, 9, 'RA5', 'Optimiza el rendimiento y la usabilidad de las aplicaciones móviles, aplicando buenas prácticas de diseño y pruebas.', NULL, NULL, 1, '2025-10-26 12:26:50', '2025-10-26 12:26:50', NULL),
(59, 9, 'RA6', 'Publica y mantiene aplicaciones en entornos de distribución, gestionando versiones, permisos y actualizaciones.', NULL, NULL, 1, '2025-10-26 12:27:05', '2025-10-26 12:27:05', NULL),
(60, 10, 'RA1', 'Instala y configura sistemas de planificación de recursos empresariales (ERP) y de gestión de relaciones con clientes (CRM), identificando su estructura y funcionalidad.', NULL, NULL, 1, '2025-10-26 12:28:00', '2025-10-26 12:28:00', NULL),
(61, 10, 'RA2', 'Realiza operaciones básicas de administración y personalización sobre sistemas ERP-CRM.', NULL, NULL, 1, '2025-10-26 12:28:12', '2025-10-26 12:28:12', NULL),
(62, 10, 'RA3', 'Desarrolla componentes y scripts que amplían las funcionalidades de los sistemas de gestión empresarial.', NULL, NULL, 1, '2025-10-26 12:28:27', '2025-10-26 12:28:27', NULL),
(63, 10, 'RA4', 'Integra módulos externos o servicios web con el ERP-CRM, garantizando la coherencia de los datos.', NULL, NULL, 1, '2025-10-26 12:29:04', '2025-10-26 12:29:04', NULL),
(64, 10, 'RA5', 'Migra e importa datos desde otras fuentes o versiones aplicando técnicas seguras y eficientes.', NULL, NULL, 1, '2025-10-26 12:29:17', '2025-10-26 12:29:17', NULL),
(65, 10, 'RA6', 'Elabora documentación técnica y funcional relativa a la implantación y personalización de sistemas de gestión empresarial.', NULL, NULL, 1, '2025-10-26 12:29:30', '2025-10-26 12:29:30', NULL),
(66, 11, 'RA1', 'Desarrolla scripts en el lado cliente empleando lenguajes de programación adecuados.', NULL, NULL, 1, '2025-10-26 12:31:39', '2025-10-26 12:31:39', NULL),
(67, 11, 'RA2', 'Manipula el DOM de documentos web para modificar su contenido y estructura dinámicamente.', NULL, NULL, 1, '2025-10-26 12:31:52', '2025-10-26 12:31:52', NULL),
(68, 11, 'RA3', 'Implementa la comunicación asíncrona con el servidor (AJAX, fetch API, etc.) para intercambiar información sin recargar la página.', NULL, NULL, 1, '2025-10-26 12:32:07', '2025-10-26 12:32:07', NULL),
(69, 11, 'RA4', 'Utiliza APIs del navegador y librerías externas para ampliar las funcionalidades del cliente.', NULL, NULL, 1, '2025-10-26 12:32:20', '2025-10-26 12:32:20', NULL),
(70, 11, 'RA5', 'Aplica buenas prácticas de accesibilidad, rendimiento y seguridad en el desarrollo cliente.', NULL, NULL, 1, '2025-10-26 12:32:33', '2025-10-26 12:32:33', NULL),
(71, 11, 'RA6', 'Documenta, prueba y depura las aplicaciones cliente utilizando herramientas de desarrollo.', NULL, NULL, 1, '2025-10-26 12:32:46', '2025-10-26 12:32:46', NULL),
(72, 12, 'RA1', 'Configura el entorno de desarrollo y ejecución del servidor web, instalando y verificando los componentes necesarios.', NULL, NULL, 1, '2025-10-26 12:33:33', '2025-10-26 12:33:33', NULL),
(73, 12, 'RA2', 'Desarrolla aplicaciones web dinámicas utilizando lenguajes de programación del lado servidor y conectándolas con bases de datos.', NULL, NULL, 1, '2025-10-26 12:33:46', '2025-10-26 12:33:46', NULL),
(74, 12, 'RA3', 'Gestiona sesiones, cookies y autenticación de usuarios aplicando políticas de seguridad y privacidad.', NULL, NULL, 1, '2025-10-26 12:33:57', '2025-10-26 12:33:57', NULL),
(75, 12, 'RA4', 'Implementa comunicación entre aplicaciones mediante servicios web y APIs REST.', NULL, NULL, 1, '2025-10-26 12:34:13', '2025-10-26 12:34:13', NULL),
(76, 12, 'RA5', 'Desarrolla aplicaciones modulares siguiendo patrones de diseño y frameworks del lado servidor.', NULL, NULL, 1, '2025-10-26 12:34:26', '2025-10-26 12:34:26', NULL),
(77, 12, 'RA6', 'Optimiza el rendimiento y la seguridad de las aplicaciones antes de su publicación.', NULL, NULL, 1, '2025-10-26 12:34:40', '2025-10-26 12:34:40', NULL),
(78, 12, 'ra7', 'Documenta, prueba y despliega aplicaciones web en servidores locales o remotos.', NULL, NULL, 1, '2025-10-26 12:34:59', '2025-10-26 12:34:59', NULL),
(79, 13, 'RA1', 'Configura servidores web, de aplicaciones y de bases de datos para el alojamiento de aplicaciones, aplicando criterios de seguridad y rendimiento.', NULL, NULL, 1, '2025-10-26 12:35:46', '2025-10-26 12:35:46', NULL),
(80, 13, 'RA2', 'Implanta aplicaciones web en distintos entornos (local, intranet, nube), automatizando tareas de instalación, actualización y copia de seguridad.', NULL, NULL, 1, '2025-10-26 12:35:59', '2025-10-26 12:35:59', NULL),
(81, 13, 'RA3', 'Gestiona dominios, certificados digitales y mecanismos de cifrado en las comunicaciones cliente-servidor.', NULL, NULL, 1, '2025-10-26 12:36:12', '2025-10-26 12:36:12', NULL),
(82, 13, 'RA4', 'Automatiza procesos de integración y despliegue continuo (CI/CD) utilizando herramientas específicas.', NULL, NULL, 1, '2025-10-26 12:36:26', '2025-10-26 12:36:26', NULL),
(83, 13, 'RA5', 'Supervisa y mantiene las aplicaciones desplegadas, interpretando registros y aplicando planes de contingencia.', NULL, NULL, 1, '2025-10-26 12:36:39', '2025-10-26 12:36:39', NULL),
(84, 13, 'RA6', 'Documenta los procedimientos de despliegue y mantenimiento, siguiendo estándares de calidad y seguridad.', NULL, NULL, 1, '2025-10-26 12:37:00', '2025-10-26 12:37:00', NULL),
(85, 14, 'RA1', 'Diseña la estructura y la navegación de interfaces web teniendo en cuenta la experiencia de usuario (UX) y los principios de usabilidad.', NULL, NULL, 1, '2025-10-26 12:37:46', '2025-10-26 12:37:46', NULL),
(86, 14, 'RA2', 'Aplica hojas de estilo (CSS) y preprocesadores para desarrollar interfaces adaptables, accesibles y coherentes.', NULL, NULL, 1, '2025-10-26 12:38:00', '2025-10-26 12:38:00', NULL),
(87, 14, 'RA3', 'Integra recursos multimedia (imágenes, audio, vídeo, animaciones, iconografía) optimizando tiempos de carga y compatibilidad.', NULL, NULL, 1, '2025-10-26 12:38:12', '2025-10-26 12:38:12', NULL),
(88, 14, 'RA4', 'Implementa interfaces responsive y multiplataforma empleando frameworks y metodologías de diseño adaptativo.', NULL, NULL, 1, '2025-10-26 12:38:25', '2025-10-26 12:38:25', NULL),
(89, 14, 'RA5', 'Aplica principios de diseño inclusivo, accesibilidad y coherencia visual en los distintos componentes de la interfaz.', NULL, NULL, 1, '2025-10-26 12:38:37', '2025-10-26 12:38:37', NULL),
(90, 14, 'RA6', 'Realiza prototipos interactivos y pruebas de usabilidad, incorporando las conclusiones al producto final.', NULL, NULL, 1, '2025-10-26 12:38:52', '2025-10-26 12:38:52', NULL),
(91, 14, 'RA7', 'Documenta las guías de estilo y componentes de la interfaz, asegurando su mantenimiento y escalabilidad.', NULL, NULL, 1, '2025-10-26 12:39:17', '2025-10-26 12:39:17', NULL),
(92, 15, 'RA1', 'Reconoce las capacidades asociadas a la iniciativa emprendedora, valorando la creatividad y la innovación como pilares del desarrollo personal y profesional.', NULL, NULL, 1, '2025-10-26 12:40:11', '2025-10-26 12:40:16', NULL),
(93, 15, 'RA2', 'Analiza las oportunidades de negocio y las principales fuentes de financiación vinculadas al sector de las TIC.', NULL, NULL, 1, '2025-10-26 12:40:29', '2025-10-26 12:40:29', NULL),
(94, 15, 'RA3', 'Define la forma jurídica y los trámites de constitución de una empresa adaptada al tamaño y objetivos del proyecto.', NULL, NULL, 1, '2025-10-26 12:40:42', '2025-10-26 12:40:42', NULL),
(95, 15, 'RA4', 'Elabora un plan de empresa describiendo los recursos humanos, materiales y financieros necesarios para su viabilidad.', NULL, NULL, 1, '2025-10-26 12:40:55', '2025-10-26 12:40:55', NULL),
(96, 15, 'RA5', 'Realiza previsiones económicas, financieras y fiscales básicas, interpretando balances y cuentas de resultados.', NULL, NULL, 1, '2025-10-26 12:41:07', '2025-10-26 12:41:07', NULL),
(97, 15, 'RA6', 'Aplica estrategias de marketing digital, comunicación y responsabilidad social corporativa adecuadas al entorno actual.', NULL, NULL, 1, '2025-10-26 12:41:20', '2025-10-26 12:41:20', NULL),
(98, 15, 'RA7', 'Evalúa la sostenibilidad y el impacto social de la actividad empresarial, integrando criterios éticos y medioambientales.', NULL, NULL, 1, '2025-10-26 12:41:34', '2025-10-26 12:41:34', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `centros`
--

CREATE TABLE `centros` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `web` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `centros`
--

INSERT INTO `centros` (`id`, `nombre`, `telefono`, `email`, `direccion`, `ciudad`, `provincia`, `cp`, `web`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'SAFA ICET El Palo', '666666666', 'safa@fundacionsafa.es', 'El Palo, 23', 'Málaga', 'Málaga', NULL, 'safaicetmalaga.com', '2025-10-17 16:15:28', '2025-10-17 16:15:28', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos_empresa`
--

CREATE TABLE `contactos_empresa` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `canal` enum('llamada','email','visita','reunión','whatsapp','otros') NOT NULL DEFAULT 'otros',
  `asunto` varchar(255) NOT NULL,
  `resumen` text DEFAULT NULL,
  `resultado` varchar(255) DEFAULT NULL,
  `proxima_accion` varchar(255) DEFAULT NULL,
  `confidencial` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `contactos_empresa`
--

INSERT INTO `contactos_empresa` (`id`, `empresa_id`, `usuario_id`, `fecha`, `canal`, `asunto`, `resumen`, `resultado`, `proxima_accion`, `confidencial`, `created_at`, `updated_at`) VALUES
(1, 503, 4, '2025-10-22 17:27:00', 'email', 'Primer contacto para convenio de prácticas', 'Carolina, el contacto de RRHH, nos informa de que ya tienen cerrado un convenio con otra escuela. Pero que podemos contactar para el año que viene.', 'Este año no. Mirar para el año que viene', 'Mantener el contacto', 0, '2025-10-22 08:29:23', '2025-10-22 08:29:23'),
(2, 504, 4, '2025-10-22 19:01:00', 'email', 'Convenio Dual', 'Este año ya tienen concertado con otro centro dos alumnos en practicas.', 'No es posible la colaboración este año', 'Mantener el contacto para el año que viene', 0, '2025-10-22 10:02:31', '2025-10-22 10:02:31'),
(3, 505, 4, '2025-10-22 17:09:00', 'email', 'Primer contacto para convenio de prácticas', 'Son una empresa pequeña y el cupo lo tienen completo con alumnos de un Instituto de Málaga.', 'Este año no. Mirar para el año que viene', 'Volver a contactar para el curso que viene', 0, '2025-10-23 08:10:31', '2025-10-23 08:10:31'),
(4, 506, 7, '2025-10-23 08:18:34', 'otros', 'ultima contestacion', 'para el año que viene si', NULL, NULL, 0, '2025-10-23 08:18:34', '2025-10-23 08:18:34'),
(5, 509, 4, '2026-02-03 10:31:31', 'email', 'Dualización', 'Enviado masivo: Mensaje para la dualización...', 'Error al enviar', NULL, 0, '2026-02-03 10:31:31', '2026-02-03 10:31:31'),
(6, 509, 4, '2026-02-03 11:04:29', 'email', 'Dualización', 'Enviado masivo: Mensaje para la dualización...', 'Error al enviar', NULL, 0, '2026-02-03 11:04:29', '2026-02-03 11:04:29'),
(7, 509, 4, '2026-02-03 11:06:02', 'email', 'Dualización', 'Enviado masivo: Mensaje para la dualización...', 'Enviado correctamente', NULL, 0, '2026-02-03 11:06:02', '2026-02-03 11:06:02'),
(8, 509, 4, '2026-02-03 11:25:04', 'email', 'Dualización', 'Enviado masivo: Mensaje para la dualización...', 'Enviado correctamente', NULL, 0, '2026-02-03 11:25:04', '2026-02-03 11:25:04'),
(9, 509, 4, '2026-02-03 12:06:41', 'email', 'Dualización', 'Enviado masivo: Asunto: Propuesta de colaboración FP Dual - 10Code — Desarrollo de software a medida y equipos de...', 'Enviado correctamente', NULL, 0, '2026-02-03 12:06:41', '2026-02-03 12:06:41'),
(10, 509, 4, '2026-02-03 12:08:48', 'email', 'Dualización', 'Enviado masivo: Asunto: Propuesta de colaboración FP Dual - 10Code — Desarrollo de software a medida y equipos de...', 'Enviado correctamente', NULL, 0, '2026-02-03 12:08:48', '2026-02-03 12:08:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos_prospecto`
--

CREATE TABLE `contactos_prospecto` (
  `id` int(10) UNSIGNED NOT NULL,
  `prospecto_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `canal` varchar(32) NOT NULL DEFAULT 'otros',
  `asunto` varchar(190) NOT NULL,
  `resumen` text DEFAULT NULL,
  `resultado` varchar(190) DEFAULT NULL,
  `proxima_accion` varchar(190) DEFAULT NULL,
  `confidencial` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `anyo` smallint(6) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `nombre`, `codigo`, `anyo`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Desarrollo de Aplicaciones Multiplataforma', 'DAM', 2025, 1, '2025-10-15 16:32:32', '2025-10-15 16:35:36'),
(2, 'Desarrollo de Aplicaciones Web', 'DAM/DAW', 2025, 1, '2025-10-15 16:35:13', '2025-10-21 16:36:13'),
(101, 'Carpintería', 'CAR', 2025, 1, '2025-10-17 15:57:45', '2025-10-17 17:35:08'),
(102, 'Educación Infantil', 'INF', 2025, 1, '2025-10-17 15:57:45', '2025-10-17 17:34:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos_profesores`
--

CREATE TABLE `cursos_profesores` (
  `curso_id` int(10) UNSIGNED NOT NULL,
  `profesor_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos_profesores`
--

INSERT INTO `cursos_profesores` (`curso_id`, `profesor_id`, `created_at`, `updated_at`) VALUES
(1, 7, '2025-10-21 16:36:25', '2025-10-21 16:36:25'),
(2, 3, '2025-11-12 18:10:06', '2025-11-12 18:10:06'),
(2, 4, '2026-01-29 17:32:09', '2026-01-29 17:32:09'),
(2, 7, '2025-10-21 16:36:29', '2025-10-21 16:36:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `profesor_id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `cuerpo` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `email_templates`
--

INSERT INTO `email_templates` (`id`, `profesor_id`, `titulo`, `asunto`, `cuerpo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 4, 'dual', 'Dualización', 'Mensaje para la dualización', '2026-02-03 09:31:21', '2026-02-03 09:31:21', NULL),
(2, 4, 'dual2', 'Dualización', 'Asunto: Propuesta de colaboración FP Dual - {empresa}\n\nHola, {responsable}:\n\nSoy profesor en el centro SAFA y le escribo porque estamos buscando plazas de prácticas en {ciudad} para nuestros alumnos de Grado Superior.\n\nHe visto que {empresa} tiene una trayectoria excelente y nos encantaría que algún alumno pudiera aprender con ustedes...', '2026-02-03 11:06:35', '2026-02-03 11:06:35', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `cif` varchar(50) DEFAULT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `web` varchar(190) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(15) DEFAULT NULL,
  `sector` varchar(120) DEFAULT NULL,
  `horario_practicas` varchar(255) DEFAULT NULL,
  `es_publica` tinyint(1) NOT NULL DEFAULT 0,
  `responsable_nombre` varchar(150) DEFAULT NULL,
  `responsable_cargo` varchar(120) DEFAULT NULL,
  `responsable_email` varchar(190) DEFAULT NULL,
  `responsable_telefono` varchar(30) DEFAULT NULL,
  `tutor_nif` varchar(20) DEFAULT NULL,
  `tutor_departamento` varchar(100) DEFAULT NULL,
  `rep_legal_nombre` varchar(150) DEFAULT NULL,
  `rep_legal_nif` varchar(20) DEFAULT NULL,
  `rep_legal_email` varchar(190) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `nombre`, `cif`, `nif`, `email`, `telefono`, `web`, `direccion`, `ciudad`, `provincia`, `codigo_postal`, `sector`, `horario_practicas`, `es_publica`, `responsable_nombre`, `responsable_cargo`, `responsable_email`, `responsable_telefono`, `tutor_nif`, `tutor_departamento`, `rep_legal_nombre`, `rep_legal_nif`, `rep_legal_email`, `activo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(503, 'Atech', NULL, NULL, 'administracion@atech.es', '691269705', 'www.atech.es', 'C/ Fernan Núñez 16, oficina 4', 'Málaga', 'Málaga', '29002', 'TIC', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-22 15:01:51', '2025-10-22 15:01:51', NULL),
(504, 'Dadisa', NULL, NULL, 'joseluis@dadisa.net', '952360412', 'www.dadisa.es', 'C/ Almogía 14, bloque 14, local 4', 'Malaga', 'Málaga', '29007', 'Informática', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-22 17:01:29', '2025-10-22 17:01:29', NULL),
(505, 'Factoria Biz', NULL, NULL, 'rzaragoza@factoriabiz.com', '951923301', 'https://www.factoriabiz.com/', 'Parque Empresarial El Pinillo, C. Decano Antonio Zedano, 3, Nave 5A', 'Torremolinos', 'Málaga', '29620', 'TIC', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-23 15:09:17', '2025-10-23 15:09:17', NULL),
(506, 'factoriakreativa.com', 'ertyertyerty', 'rtyertyerty', 'rrhh@factoriakreativa.com', '5674567567', 'factoriakreativa.com', NULL, 'Málaga', 'Málaga', '567456', 'tecnología', NULL, 0, 'Nombre y Apellidos del Contacto', 'Currito', 'currito@curro.com', '666666666', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-23 15:17:16', '2025-10-29 17:04:32', NULL),
(507, 'Solbyte: Empresa de Servicios Informáticos en Málaga', NULL, NULL, NULL, NULL, 'https://www.solbyte.com/', NULL, NULL, NULL, NULL, 'software, frontend, backend, móvil, móviles', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-02 16:59:03', '2025-11-02 16:59:03', NULL),
(508, 'Innovacodex: Desarrollo de Software Personalizado en España', NULL, NULL, NULL, NULL, 'https://innovacodex.io/', NULL, NULL, NULL, NULL, 'software, frontend, backend, móvil, móviles', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-02 16:59:54', '2025-11-02 16:59:54', NULL),
(509, '10Code — Desarrollo de software a medida y equipos dedicados', 'A87654321', 'fghfghdfghdfgh', 'fpalacioschaves@gmail.com', '952360412', 'https://desarrollosoftware.es/', 'C/ Almogía 14, bloque 14, local 4', 'Malaga', 'Málaga', '29007', 'software, frontend, backend, móvil, móviles', NULL, 1, 'Pepito Responsable', 'Gargo de Responsable', 'joseluis@dadisa.net', '+34952360412', NULL, NULL, NULL, NULL, NULL, 1, '2025-11-02 17:01:14', '2026-02-03 11:08:26', NULL),
(510, 'Auroralabs', NULL, NULL, NULL, NULL, 'https://es.linkedin.com/company/auroralabsapps', NULL, NULL, NULL, NULL, 'software, frontend, backend, móvil, móviles', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-02 17:02:01', '2025-11-02 17:02:01', NULL),
(511, 'CSI Servicios Informática', NULL, NULL, NULL, '+34 952 31 28 86', 'https://www.infocsi.es/', NULL, 'Málaga', 'Málaga', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-02 20:37:58', '2025-11-02 20:37:58', NULL),
(512, 'La Tienda de Informática del Centro', NULL, NULL, NULL, '+34 952 21 75 96', 'https://www.openstreetmap.org/node/5574303199', NULL, 'Málaga', 'Málaga', NULL, 'TIC', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-02 20:42:29', '2025-11-09 12:53:51', '2025-11-09 12:53:51'),
(513, 'App Informática', NULL, NULL, NULL, NULL, 'https://www.openstreetmap.org/node/4856539942', NULL, 'Málaga', 'Málaga', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-02 20:43:07', '2025-11-09 12:53:30', '2025-11-09 12:53:30'),
(514, 'App Informática', NULL, NULL, NULL, NULL, 'https://www.appinformatica.com/', NULL, 'Sevilla', 'Sevilla', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-09 12:52:45', '2026-02-22 15:40:43', NULL),
(515, 'PC BOX', NULL, NULL, NULL, NULL, 'https://www.pcbox.com/', NULL, 'Sevilla', 'Sevilla', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-09 12:52:51', '2026-01-29 17:03:02', NULL),
(516, 'App Informática', NULL, NULL, NULL, NULL, 'https://www.openstreetmap.org/node/2211649435', NULL, 'sevilla', 'sevilla', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-09 12:52:57', '2025-11-09 12:52:57', NULL),
(517, 'Andaluza de Programación', NULL, NULL, NULL, '+34954086600', 'https://www.apsl.es/', NULL, 'Sevilla', 'Sevilla', NULL, 'TIC', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-09 12:53:06', '2026-02-22 15:40:20', NULL),
(518, 'sdsadtswft', NULL, NULL, NULL, NULL, 'sdfgsdfg', 'dfgsdfgsdfg', 'sdfgsdfgdfg', 'sdfgsdfgsdf', 'sdfdfgdfg', 'sdtsdrtsdert', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-12 18:02:38', '2025-11-12 18:03:25', '2025-11-12 18:03:25'),
(519, 'Empresa de Testeoq', NULL, NULL, NULL, '666666666', NULL, NULL, NULL, NULL, NULL, 'TIC', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-12 18:09:25', '2025-11-12 18:09:25', NULL),
(520, 'Emergya', NULL, NULL, '', '', 'http://www.emergya.es', NULL, 'Sevilla', NULL, NULL, 'software', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-12 18:20:12', '2026-02-12 18:20:12', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_alumnos`
--

CREATE TABLE `empresa_alumnos` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED DEFAULT NULL,
  `tipo` enum('dual','fct','practicas','otros') NOT NULL DEFAULT 'dual',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `horas_previstas` smallint(5) UNSIGNED DEFAULT NULL,
  `horas_realizadas` smallint(5) UNSIGNED DEFAULT NULL,
  `estado` enum('activa','finalizada','pausada','cancelada') NOT NULL DEFAULT 'activa',
  `tutor_nombre` varchar(120) DEFAULT NULL,
  `tutor_email` varchar(150) DEFAULT NULL,
  `tutor_telefono` varchar(30) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empresa_alumnos`
--

INSERT INTO `empresa_alumnos` (`id`, `empresa_id`, `alumno_id`, `curso_id`, `tipo`, `fecha_inicio`, `fecha_fin`, `horas_previstas`, `horas_realizadas`, `estado`, `tutor_nombre`, `tutor_email`, `tutor_telefono`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, 509, 1003, 1, 'dual', '2026-02-02', '2026-05-02', NULL, NULL, 'finalizada', '', '', '', '', '2026-02-02 18:46:46', '2026-02-02 18:47:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_alumnos_asignaturas`
--

CREATE TABLE `empresa_alumnos_asignaturas` (
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `asignatura_id` int(10) UNSIGNED NOT NULL,
  `horas_previstas` smallint(5) UNSIGNED DEFAULT NULL,
  `horas_realizadas` smallint(5) UNSIGNED DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresa_alumnos_asignaturas`
--

INSERT INTO `empresa_alumnos_asignaturas` (`empresa_id`, `alumno_id`, `asignatura_id`, `horas_previstas`, `horas_realizadas`, `observaciones`) VALUES
(509, 1003, 2, NULL, NULL, NULL),
(509, 1003, 6, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_alumno_ras`
--

CREATE TABLE `empresa_alumno_ras` (
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `ra_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresa_alumno_ras`
--

INSERT INTO `empresa_alumno_ras` (`empresa_id`, `alumno_id`, `ra_id`) VALUES
(509, 1003, 8),
(509, 1003, 9),
(509, 1003, 10),
(509, 1003, 34),
(509, 1003, 35),
(509, 1003, 36);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_contactos`
--

CREATE TABLE `empresa_contactos` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `profesor_id` int(10) UNSIGNED NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('llamada','email','visita','otro') NOT NULL DEFAULT 'otro',
  `resumen` varchar(255) NOT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_cursos`
--

CREATE TABLE `empresa_cursos` (
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresa_cursos`
--

INSERT INTO `empresa_cursos` (`empresa_id`, `curso_id`) VALUES
(503, 2),
(504, 1),
(505, 1),
(506, 1),
(508, 2),
(509, 1),
(509, 2),
(510, 2),
(511, 2),
(512, 2),
(513, 2),
(514, 1),
(514, 2),
(515, 2),
(517, 1),
(517, 2),
(518, 101),
(519, 1),
(519, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones_notas`
--

CREATE TABLE `evaluaciones_notas` (
  `id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `asignatura_id` int(10) UNSIGNED NOT NULL,
  `convocatoria` enum('Enero','Mayo','Junio') NOT NULL,
  `nota_foro` decimal(4,2) DEFAULT NULL,
  `nota_actividades` decimal(4,2) DEFAULT NULL,
  `nota_examen` decimal(4,2) DEFAULT NULL,
  `nota_dualizacion` decimal(4,2) DEFAULT NULL,
  `nota_final` decimal(4,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluacion_config`
--

CREATE TABLE `evaluacion_config` (
  `id` int(10) UNSIGNED NOT NULL,
  `asignatura_id` int(10) UNSIGNED NOT NULL,
  `convocatoria` enum('Enero','Mayo','Junio') NOT NULL,
  `peso_foro` decimal(5,2) DEFAULT 10.00,
  `peso_actividades` decimal(5,2) DEFAULT 30.00,
  `peso_examen` decimal(5,2) DEFAULT 40.00,
  `peso_dualizacion` decimal(5,2) DEFAULT 20.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicas`
--

CREATE TABLE `practicas` (
  `id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `tutor_centro_id` int(10) UNSIGNED DEFAULT NULL,
  `tutor_empresa_nombre` varchar(150) DEFAULT NULL,
  `tutor_empresa_email` varchar(190) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `horas_previstas` smallint(6) DEFAULT NULL,
  `horas_realizadas` smallint(6) DEFAULT NULL,
  `estado` enum('pendiente','activa','finalizada','cancelada') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `codigo`, `nombre`, `descripcion`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrador', NULL, '2025-10-15 11:34:12', '2025-10-15 11:34:12'),
(2, 'profesor', 'Profesor', NULL, '2025-10-15 11:34:12', '2025-10-15 11:34:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(150) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `centro_id` int(10) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellidos`, `email`, `password_hash`, `telefono`, `centro_id`, `activo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 'Francisco', 'Palacios Chaves', 'fpalacioschaves@gmail.com', '$2y$10$uWyt1RgBYPLTwkgbEiWUquPgqIDocAviHVwr3v5jWKvvVlmfo.AES', '655925498', 1, 1, '2025-10-15 12:08:36', '2025-11-12 18:10:06', NULL),
(4, 'Administrador', 'Administrador', 'fpalacios@fundacionsafa.es', '$2y$10$/vl/ncSfGvLtE9eshjxI6OJ0zNnkHzwkYXmscZIGUQwHgf5CgaJhy', '655925498', 1, 1, '2025-10-15 12:25:33', '2026-01-29 17:32:09', NULL),
(5, 'Paco José', 'López', 'pacolopez@gmail.com', '$2y$10$r8SnLmiPDi9AUSgQTnxSnuCyGH0R4GyrJY/je/.wxHgEr2hXm.Xxy', NULL, 1, 1, '2025-10-15 15:09:55', '2025-10-18 09:30:37', '2025-10-18 09:30:37'),
(6, 'Otro', 'Usuario', 'otrousuario@gmail.com', '$2y$10$ePuEGaYkXXy9UrGmxDoY0eFIcj7rClYcPp4m4oqMBjvl.cmJS5Oy6', '777777777', NULL, 1, '2025-10-16 11:41:46', '2025-10-18 09:30:33', '2025-10-18 09:30:33'),
(7, 'Alberto', 'Ruiz Rodriguez', 'albertoruiz@fundacionsafa.es', '$2y$10$iFIJ4EYtFufgLuX9YsVIaucKy3.ZDu0dYD/EZE9ThiLPya0Ji47hS', NULL, 1, 1, '2025-10-18 09:35:29', '2025-10-21 15:39:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_roles`
--

CREATE TABLE `usuarios_roles` (
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `rol_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios_roles`
--

INSERT INTO `usuarios_roles` (`usuario_id`, `rol_id`) VALUES
(3, 2),
(4, 1),
(4, 2),
(7, 1),
(7, 2);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_alumnos_nombre` (`apellidos`,`nombre`);

--
-- Indices de la tabla `alumnos_asignaturas`
--
ALTER TABLE `alumnos_asignaturas`
  ADD PRIMARY KEY (`alumno_id`,`asignatura_id`),
  ADD KEY `fk_enroll_asignatura` (`asignatura_id`);

--
-- Indices de la tabla `alumnos_cursos`
--
ALTER TABLE `alumnos_cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ac_alumno` (`alumno_id`,`fecha_inicio`),
  ADD KEY `idx_ac_curso` (`curso_id`,`fecha_inicio`);

--
-- Indices de la tabla `alumno_contactos`
--
ALTER TABLE `alumno_contactos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_alumno_contactos_profesor` (`profesor_id`),
  ADD KEY `idx_ac_alumno_profesor_fecha` (`alumno_id`,`profesor_id`,`fecha`);

--
-- Indices de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_asig_curso_nombre` (`curso_id`,`nombre`),
  ADD UNIQUE KEY `uq_asig_curso_codigo` (`curso_id`,`codigo`),
  ADD KEY `idx_asig_curso` (`curso_id`);

--
-- Indices de la tabla `asignatura_cursos`
--
ALTER TABLE `asignatura_cursos`
  ADD PRIMARY KEY (`asignatura_id`,`curso_id`),
  ADD KEY `fk_asigcur_curso` (`curso_id`);

--
-- Indices de la tabla `asignatura_ras`
--
ALTER TABLE `asignatura_ras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ra_asig_codigo` (`asignatura_id`,`codigo`),
  ADD UNIQUE KEY `uq_ra_asig_titulo` (`asignatura_id`,`titulo`),
  ADD KEY `idx_ra_asignatura` (`asignatura_id`),
  ADD KEY `idx_ra_orden` (`asignatura_id`,`orden`),
  ADD KEY `idx_ra_activo` (`activo`);

--
-- Indices de la tabla `centros`
--
ALTER TABLE `centros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `contactos_empresa`
--
ALTER TABLE `contactos_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ce_empresa` (`empresa_id`),
  ADD KEY `idx_ce_usuario` (`usuario_id`);

--
-- Indices de la tabla `contactos_prospecto`
--
ALTER TABLE `contactos_prospecto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cp_usuario` (`usuario_id`),
  ADD KEY `idx_cp_prospecto_fecha` (`prospecto_id`,`fecha`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `cursos_profesores`
--
ALTER TABLE `cursos_profesores`
  ADD PRIMARY KEY (`curso_id`,`profesor_id`),
  ADD KEY `fk_cp_profesor` (`profesor_id`);

--
-- Indices de la tabla `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_email_templates_profesor` (`profesor_id`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nif` (`nif`),
  ADD UNIQUE KEY `idx_empresas_cif` (`cif`),
  ADD KEY `idx_empresas_nombre` (`nombre`),
  ADD KEY `idx_empresas_resp_email` (`responsable_email`),
  ADD KEY `idx_empresas_resp_tel` (`responsable_telefono`);

--
-- Indices de la tabla `empresa_alumnos`
--
ALTER TABLE `empresa_alumnos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_empresa_alumno_inicio` (`empresa_id`,`alumno_id`,`fecha_inicio`),
  ADD KEY `idx_ea_alumno` (`alumno_id`),
  ADD KEY `idx_ea_empresa` (`empresa_id`),
  ADD KEY `idx_ea_estado` (`estado`),
  ADD KEY `idx_ea_curso` (`curso_id`);

--
-- Indices de la tabla `empresa_alumnos_asignaturas`
--
ALTER TABLE `empresa_alumnos_asignaturas`
  ADD PRIMARY KEY (`empresa_id`,`alumno_id`,`asignatura_id`),
  ADD KEY `fk_eaa_asig` (`asignatura_id`);

--
-- Indices de la tabla `empresa_alumno_ras`
--
ALTER TABLE `empresa_alumno_ras`
  ADD PRIMARY KEY (`empresa_id`,`alumno_id`,`ra_id`),
  ADD KEY `fk_ear_ra` (`ra_id`);

--
-- Indices de la tabla `empresa_contactos`
--
ALTER TABLE `empresa_contactos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ec_profesor` (`profesor_id`),
  ADD KEY `idx_empresa_profesor_fecha` (`empresa_id`,`profesor_id`,`fecha`);

--
-- Indices de la tabla `empresa_cursos`
--
ALTER TABLE `empresa_cursos`
  ADD PRIMARY KEY (`empresa_id`,`curso_id`),
  ADD KEY `fk_empcur_curso` (`curso_id`);

--
-- Indices de la tabla `evaluaciones_notas`
--
ALTER TABLE `evaluaciones_notas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_alumno_asig_conv` (`alumno_id`,`asignatura_id`,`convocatoria`),
  ADD KEY `asignatura_id` (`asignatura_id`);

--
-- Indices de la tabla `evaluacion_config`
--
ALTER TABLE `evaluacion_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_asig_conv` (`asignatura_id`,`convocatoria`);

--
-- Indices de la tabla `practicas`
--
ALTER TABLE `practicas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pr_tutor` (`tutor_centro_id`),
  ADD KEY `idx_practicas_alumno` (`alumno_id`),
  ADD KEY `idx_practicas_empresa` (`empresa_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_usuarios_centro` (`centro_id`);

--
-- Indices de la tabla `usuarios_roles`
--
ALTER TABLE `usuarios_roles`
  ADD PRIMARY KEY (`usuario_id`,`rol_id`),
  ADD KEY `fk_ur_rol` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1017;

--
-- AUTO_INCREMENT de la tabla `alumnos_cursos`
--
ALTER TABLE `alumnos_cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `alumno_contactos`
--
ALTER TABLE `alumno_contactos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `asignatura_ras`
--
ALTER TABLE `asignatura_ras`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT de la tabla `centros`
--
ALTER TABLE `centros`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `contactos_empresa`
--
ALTER TABLE `contactos_empresa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `contactos_prospecto`
--
ALTER TABLE `contactos_prospecto`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de la tabla `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=521;

--
-- AUTO_INCREMENT de la tabla `empresa_alumnos`
--
ALTER TABLE `empresa_alumnos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `empresa_contactos`
--
ALTER TABLE `empresa_contactos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `evaluaciones_notas`
--
ALTER TABLE `evaluaciones_notas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `evaluacion_config`
--
ALTER TABLE `evaluacion_config`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `practicas`
--
ALTER TABLE `practicas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumnos_asignaturas`
--
ALTER TABLE `alumnos_asignaturas`
  ADD CONSTRAINT `fk_enroll_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enroll_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `alumnos_cursos`
--
ALTER TABLE `alumnos_cursos`
  ADD CONSTRAINT `fk_ac_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ac_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `alumno_contactos`
--
ALTER TABLE `alumno_contactos`
  ADD CONSTRAINT `fk_alumno_contactos_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_alumno_contactos_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD CONSTRAINT `fk_asig_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignatura_cursos`
--
ALTER TABLE `asignatura_cursos`
  ADD CONSTRAINT `fk_asigcur_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asigcur_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignatura_ras`
--
ALTER TABLE `asignatura_ras`
  ADD CONSTRAINT `fk_ra_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `contactos_empresa`
--
ALTER TABLE `contactos_empresa`
  ADD CONSTRAINT `fk_ce_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ce_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `contactos_prospecto`
--
ALTER TABLE `contactos_prospecto`
  ADD CONSTRAINT `fk_cp_prospecto` FOREIGN KEY (`prospecto_id`) REFERENCES `empresas_prospectos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cursos_profesores`
--
ALTER TABLE `cursos_profesores`
  ADD CONSTRAINT `fk_cp_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cp_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `email_templates`
--
ALTER TABLE `email_templates`
  ADD CONSTRAINT `fk_email_templates_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `empresa_alumnos`
--
ALTER TABLE `empresa_alumnos`
  ADD CONSTRAINT `fk_ea_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ea_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ea_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `empresa_alumnos_asignaturas`
--
ALTER TABLE `empresa_alumnos_asignaturas`
  ADD CONSTRAINT `fk_eaa_asig` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eaa_ea` FOREIGN KEY (`empresa_id`,`alumno_id`) REFERENCES `empresa_alumnos` (`empresa_id`, `alumno_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `empresa_alumno_ras`
--
ALTER TABLE `empresa_alumno_ras`
  ADD CONSTRAINT `fk_ear_ea` FOREIGN KEY (`empresa_id`,`alumno_id`) REFERENCES `empresa_alumnos` (`empresa_id`, `alumno_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ear_ra` FOREIGN KEY (`ra_id`) REFERENCES `asignatura_ras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `empresa_contactos`
--
ALTER TABLE `empresa_contactos`
  ADD CONSTRAINT `fk_ec_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `empresa_cursos`
--
ALTER TABLE `empresa_cursos`
  ADD CONSTRAINT `fk_empcur_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_empcur_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `evaluaciones_notas`
--
ALTER TABLE `evaluaciones_notas`
  ADD CONSTRAINT `evaluaciones_notas_ibfk_1` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluaciones_notas_ibfk_2` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evaluacion_config`
--
ALTER TABLE `evaluacion_config`
  ADD CONSTRAINT `evaluacion_config_ibfk_1` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `practicas`
--
ALTER TABLE `practicas`
  ADD CONSTRAINT `fk_pr_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pr_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pr_tutor` FOREIGN KEY (`tutor_centro_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_centro` FOREIGN KEY (`centro_id`) REFERENCES `centros` (`id`);

--
-- Filtros para la tabla `usuarios_roles`
--
ALTER TABLE `usuarios_roles`
  ADD CONSTRAINT `fk_ur_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ur_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
