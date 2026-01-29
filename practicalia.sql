-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-10-2025 a las 13:06:00
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

INSERT INTO `alumnos` (`id`, `nombre`, `apellidos`, `email`, `telefono`, `activo`, `fecha_nacimiento`, `notas`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1003, 'Adrián', 'Arroyo García', 'adrianarroyo93@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:28:00', '2025-10-18 09:28:00', NULL),
(1004, 'Jesús', 'Benitez Maestre', 'jesusbenim78@icloud.com', NULL, 1, NULL, NULL, '2025-10-18 09:29:12', '2025-10-18 09:29:12', NULL),
(1005, 'Juan Carlos', 'Bernal Tortosa', 'jcbernalt@hotmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:29:34', '2025-10-18 09:29:34', NULL),
(1006, 'Carlos', 'Cobos Medina', 'carlosfactory97@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:29:54', '2025-10-18 09:29:54', NULL),
(1007, 'Francisco Javier', 'Jimenez Cortés', 'al1protocol23@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:30:14', '2025-10-18 09:30:14', NULL),
(1008, 'Pablo', 'López Anelo', 'plastg28@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:31:39', '2025-10-18 09:31:39', NULL),
(1009, 'Félix', 'Martín Navarro', 'felixmn99@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:32:03', '2025-10-18 09:32:03', NULL),
(1010, 'Juan Francisco', 'Mena Cobano', 'juanfranciscomenacobano@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:32:26', '2025-10-18 09:32:26', NULL),
(1011, 'Jaime', 'Morejón Díaz', 'jaimemorejdiaz@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:32:46', '2025-10-18 09:32:46', NULL),
(1012, 'Eugenio', 'Nimo Flor', 'enimo@fundacionsafa.es', NULL, 1, NULL, NULL, '2025-10-18 09:33:06', '2025-10-18 09:33:06', NULL),
(1013, 'Álvaro', 'Rodríguez Martínez', 'arm1612004@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:33:35', '2025-10-18 09:33:35', NULL),
(1014, 'José Manuel', 'Ruíz Herrera', 'joserom17@gmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:33:57', '2025-10-18 09:33:57', NULL),
(1015, 'Eduardo Jaime', 'Vera Olmo', 'ejvo1981@hotmail.com', NULL, 1, NULL, NULL, '2025-10-18 09:34:20', '2025-10-18 09:34:20', NULL);

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
(23, 1014, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:33:57', '2025-10-18 09:33:57'),
(24, 1015, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:34:20', '2025-10-18 09:34:20'),
(25, 1008, 1, '2025-10-18', NULL, 'matriculado', NULL, NULL, '2025-10-18 09:37:40', '2025-10-18 09:37:40');

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
(5, 1010, 4, '2025-10-18 11:39:47', 'email', 'Primer contacto', 'Ha mandado el CV y no tiene conocidos en ninguna empresa para hacer las prácticas');

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
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED DEFAULT NULL,
  `fecha_contacto` datetime NOT NULL DEFAULT current_timestamp(),
  `canal` enum('telefono','email','visita','videollamada','otro') NOT NULL DEFAULT 'otro',
  `asunto` varchar(200) NOT NULL,
  `notas` text DEFAULT NULL,
  `resultado` enum('pendiente','en_proceso','hecho','no_interesado') NOT NULL DEFAULT 'pendiente',
  `proxima_accion` datetime DEFAULT NULL,
  `confidencial` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(2, 'Desarrollo de Aplicaciones Web', 'DAW', 2025, 1, '2025-10-15 16:35:13', '2025-10-15 16:35:13'),
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
(1, 7, '2025-10-18 10:10:45', '2025-10-18 10:10:45'),
(2, 3, '2025-10-18 10:20:31', '2025-10-18 10:20:31');

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
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `nombre`, `cif`, `nif`, `email`, `telefono`, `web`, `direccion`, `ciudad`, `provincia`, `codigo_postal`, `sector`, `activo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Empresita 1', '123456789', '123456789', 'suemail@empresita.com', '666666666', NULL, 'Avenida de Andalucía, 25', 'Malaga', 'Málaga', '29006', 'TIC', 1, '2025-10-16 10:29:33', '2025-10-16 13:53:36', NULL),
(501, 'Tech Sur S.L.', 'B12345678', NULL, 'contacto@techsur.local', '952000111', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-17 15:58:40', '2025-10-17 15:58:40', NULL),
(502, 'Málaga Cloud S.A.', 'A87654321', NULL, 'hola@malagacloud.local', '952000222', NULL, NULL, NULL, NULL, NULL, 'Mobiliario', 1, '2025-10-17 15:58:40', '2025-10-17 17:35:26', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_alumnos`
--

CREATE TABLE `empresa_alumnos` (
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Volcado de datos para la tabla `empresa_contactos`
--

INSERT INTO `empresa_contactos` (`id`, `empresa_id`, `profesor_id`, `fecha`, `tipo`, `resumen`, `notas`) VALUES
(1, 1, 4, '2025-10-16 12:30:16', 'llamada', 'Convenio', 'Llamada realizada para tratar convenio de colaboración');

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
(502, 101);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellidos`, `email`, `password_hash`, `telefono`, `centro_id`, `activo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 'Francisco', 'Palacios Chaves', 'fpalacioschaves@gmail.com', '$2y$10$Je6ECsrE2BijcxYHHHnHoeIiyszpG5l3hR8hfiC8wzjLc8R7pS5Y6', '655925498', 1, 1, '2025-10-15 12:08:36', '2025-10-18 10:20:31', NULL),
(4, 'Administrador', 'Administrador', 'fpalacios@fundacionsafa.es', '$2y$10$LkDLNusxXkD98poIQBvBVObpIMFVgGYj6TvT5WoE1otvK/QnBu7su', NULL, 1, 1, '2025-10-15 12:25:33', '2025-10-18 10:20:17', NULL),
(5, 'Paco José', 'López', 'pacolopez@gmail.com', '$2y$10$r8SnLmiPDi9AUSgQTnxSnuCyGH0R4GyrJY/je/.wxHgEr2hXm.Xxy', NULL, 1, 1, '2025-10-15 15:09:55', '2025-10-18 09:30:37', '2025-10-18 09:30:37'),
(6, 'Otro', 'Usuario', 'otrousuario@gmail.com', '$2y$10$ePuEGaYkXXy9UrGmxDoY0eFIcj7rClYcPp4m4oqMBjvl.cmJS5Oy6', '777777777', NULL, 1, '2025-10-16 11:41:46', '2025-10-18 09:30:33', '2025-10-18 09:30:33'),
(7, 'Alberto', 'Ruiz Rodriguez', 'albertoruiz@fundacionsafa.es', '$2y$10$YaFoRPs/W8R0jQkX79h8Zepp0bedmboZB7YqLlMwj4LZIR7b684ku', NULL, 1, 1, '2025-10-18 09:35:29', '2025-10-18 10:10:45', NULL);

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
-- Indices de la tabla `centros`
--
ALTER TABLE `centros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `contactos_empresa`
--
ALTER TABLE `contactos_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ce_usuario` (`usuario_id`),
  ADD KEY `fk_ce_alumno` (`alumno_id`),
  ADD KEY `idx_ce_empresa_fecha` (`empresa_id`,`fecha_contacto`),
  ADD KEY `idx_ce_proxima` (`proxima_accion`);

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
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nif` (`nif`),
  ADD UNIQUE KEY `idx_empresas_cif` (`cif`),
  ADD KEY `idx_empresas_nombre` (`nombre`);

--
-- Indices de la tabla `empresa_alumnos`
--
ALTER TABLE `empresa_alumnos`
  ADD PRIMARY KEY (`empresa_id`,`alumno_id`),
  ADD KEY `fk_ea_alumno` (`alumno_id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1016;

--
-- AUTO_INCREMENT de la tabla `alumnos_cursos`
--
ALTER TABLE `alumnos_cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `alumno_contactos`
--
ALTER TABLE `alumno_contactos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `centros`
--
ALTER TABLE `centros`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `contactos_empresa`
--
ALTER TABLE `contactos_empresa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=503;

--
-- AUTO_INCREMENT de la tabla `empresa_contactos`
--
ALTER TABLE `empresa_contactos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Filtros para la tabla `contactos_empresa`
--
ALTER TABLE `contactos_empresa`
  ADD CONSTRAINT `fk_ce_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ce_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ce_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `cursos_profesores`
--
ALTER TABLE `cursos_profesores`
  ADD CONSTRAINT `fk_cp_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cp_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `empresa_alumnos`
--
ALTER TABLE `empresa_alumnos`
  ADD CONSTRAINT `fk_ea_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ea_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

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
