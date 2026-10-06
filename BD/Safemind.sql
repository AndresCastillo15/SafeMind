-- phpMyAdmin SQL Dump
-- version 4.7.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 162.210.70.175
-- Tiempo de generación: 29-09-2026 a las 12:51:42
-- Versión del servidor: 5.7.23-23
-- Versión de PHP: 7.0.33-0ubuntu0.16.04.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `colegdfs_safemind`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alerta`
--

CREATE TABLE `alerta` (
  `id_alerta` int(11) NOT NULL,
  `id_analisis` int(11) NOT NULL,
  `estado` varchar(30) DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `analisis_ia`
--

CREATE TABLE `analisis_ia` (
  `id_analisis` int(11) NOT NULL,
  `id_evaluacion` int(11) NOT NULL,
  `id_nivel` tinyint(4) NOT NULL,
  `emocion_detectada` varchar(100) DEFAULT NULL,
  `porcentaje_confianza` decimal(5,2) DEFAULT NULL,
  `resumen` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `analisis_recurso`
--

CREATE TABLE `analisis_recurso` (
  `id_analisis` int(11) NOT NULL,
  `id_recurso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante`
--

CREATE TABLE `estudiante` (
  `id_estudiante` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `curso` varchar(30) DEFAULT NULL,
  `chat_id_telegram` bigint(20) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Activo',
  `ultimo_registro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `estudiante`
--

INSERT INTO `estudiante` (`id_estudiante`, `nombre`, `apellido`, `correo`, `telefono`, `curso`, `chat_id_telegram`, `estado`, `ultimo_registro`) VALUES
(1, 'Nicolás Alfonso', 'Cabrera Suárez', 'nicolascabrera@colegioguanenta.edu.co', '3202885619', '11-1', 8860427697, 'activo', '2026-09-28 17:25:16'),
(2, 'Leydi', 'Piracón', 'Leydisaxot@hotmail.com', '3002622648', '11-1', 8707527737, 'activo', '2026-09-28 22:56:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluacion`
--

CREATE TABLE `evaluacion` (
  `id_evaluacion` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `estado_animo` varchar(30) DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` datetime DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'Activa',
  `estado_conversacion` varchar(30) NOT NULL DEFAULT 'esperando_descripcion'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `evaluacion`
--

INSERT INTO `evaluacion` (`id_evaluacion`, `id_estudiante`, `estado_animo`, `fecha_inicio`, `fecha_fin`, `estado`, `estado_conversacion`) VALUES
(1, 1, 'Malísimo', '2026-09-28 17:25:16', NULL, 'Activa', 'en_conversacion'),
(2, 2, 'No tan bien', '2026-09-28 22:56:07', NULL, 'Activa', 'en_conversacion');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensaje`
--

CREATE TABLE `mensaje` (
  `id_mensaje` int(11) NOT NULL,
  `id_evaluacion` int(11) NOT NULL,
  `remitente` enum('estudiante','ia') NOT NULL,
  `contenido` text NOT NULL,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `mensaje`
--

INSERT INTO `mensaje` (`id_mensaje`, `id_evaluacion`, `remitente`, `contenido`, `fecha_hora`) VALUES
(1, 1, 'estudiante', 'Hoy me pasó algo muy grave en el colegio', '2026-09-28 17:29:18'),
(2, 1, 'ia', 'Lamento mucho escuchar que tuviste un día tan difícil. Si te sientes cómodo, ¿podrías contarme un poco más sobre lo que pasó?', '2026-09-28 17:29:39'),
(3, 1, 'estudiante', 'Un estudiante me esta amenazando Y me dijo que después de clases me va a hacer daño ahora mismo estoy muy asustado', '2026-09-28 17:30:52'),
(4, 1, 'ia', 'Lamento mucho que estés pasando por esto, es comprensible que sientas miedo en una situación así. Por favor, busca de inmediato a un profesor, orientador o un adulto de confianza en el colegio para que te ayude a estar seguro. ¿Hay algún adulto cerca a quien puedas acudir ahora mismo?', '2026-09-28 17:30:58'),
(5, 1, 'estudiante', 'No realmente esto estoy solo y a la solidana y me acompaña entonces tengo mucho miedo lo que pueda pasar necesita ayuda urgente', '2026-09-28 17:31:24'),
(6, 2, 'estudiante', 'No sé que hacer con una amiga de 16 años que tiene anorexia', '2026-09-28 22:56:43'),
(7, 2, 'ia', 'Lamento mucho que estés pasando por esto, debe ser muy difícil ver a una amiga en una situación así. ¿Qué es lo que más te preocupa de lo que está viviendo ella en este momento?', '2026-09-28 22:56:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nivel_riesgo`
--

CREATE TABLE `nivel_riesgo` (
  `id_nivel` tinyint(4) NOT NULL,
  `nombre` varchar(20) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `nivel_riesgo`
--

INSERT INTO `nivel_riesgo` (`id_nivel`, `nombre`, `descripcion`, `color`) VALUES
(1, 'Bien', 'Sin riesgo', 'Verde'),
(2, 'Alerta', 'Seguimiento', 'Amarillo'),
(3, 'Riesgo', 'Intervención', 'Naranja'),
(4, 'Crisis', 'Urgente', 'Rojo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesor`
--

CREATE TABLE `profesor` (
  `id_profesor` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `curso` varchar(30) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'Activo',
  `contraseña` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `profesor`
--

INSERT INTO `profesor` (`id_profesor`, `usuario`, `nombre`, `apellido`, `correo`, `telefono`, `curso`, `estado`, `contraseña`) VALUES
(1, 'docente.prueba', 'Docente', 'Prueba', 'docente.prueba@safemind.test', '3000000001', '11-1', 'Activo', '$2y$12$ZeV/Tg2IrjsQev0EQRCBCO0E/ermCWxGcQq.4cYLCW0JgXm6FW0ke');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `psicologo`
--

CREATE TABLE `psicologo` (
  `id_psicologo` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `contraseña` varchar(255) NOT NULL,
  `estado` varchar(20) DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `psicologo`
--

INSERT INTO `psicologo` (`id_psicologo`, `usuario`, `nombre`, `apellido`, `correo`, `telefono`, `contraseña`, `estado`) VALUES
(1, 'psicologo.prueba', 'Psicologo', 'Prueba', 'psicologo.prueba@safemind.com', '3001234567', '9917669f25623706847991c659e6027fb4e3721c9905b2d09b0441582e7f8852', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recurso_apoyo`
--

CREATE TABLE `recurso_apoyo` (
  `id_recurso` int(11) NOT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `descripcion` text,
  `tipo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `recurso_apoyo`
--

INSERT INTO `recurso_apoyo` (`id_recurso`, `titulo`, `descripcion`, `tipo`) VALUES
(1, 'Pausa de respiración', 'Realizar una pausa breve y acompañar la respiración de manera lenta y tranquila durante unos minutos.', 'Ejercicio'),
(2, 'Organizar lo que estoy sintiendo', 'Escribir brevemente qué situación está generando malestar y qué emociones se están experimentando.', 'Ejercicio'),
(3, 'Hablar con alguien de confianza', 'Buscar una persona de confianza, como un familiar, docente u orientador, y contarle cómo te estás sintiendo.', 'Apoyo social'),
(4, 'Manejo del estrés académico', 'Dividir las tareas pendientes en actividades pequeñas y priorizar una tarea a la vez para reducir la sensación de sobrecarga.', 'Orientación'),
(5, 'Pausa y descanso', 'Tomarse un momento para descansar de la actividad que está generando tensión y retomar cuando exista mayor tranquilidad.', 'Bienestar'),
(6, 'Buscar apoyo profesional', 'Considerar hablar con un psicólogo u otro profesional de apoyo cuando el malestar persiste o afecta significativamente el bienestar.', 'Derivación');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_temporal`
--

CREATE TABLE `registro_temporal` (
  `chat_id_telegram` bigint(20) NOT NULL,
  `paso` varchar(20) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `curso` varchar(100) DEFAULT NULL,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimiento`
--

CREATE TABLE `seguimiento` (
  `id_seguimiento` int(11) NOT NULL,
  `id_alerta` int(11) NOT NULL,
  `id_psicologo` int(11) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `observacion` text,
  `estado` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alerta`
--
ALTER TABLE `alerta`
  ADD PRIMARY KEY (`id_alerta`),
  ADD UNIQUE KEY `id_analisis` (`id_analisis`);

--
-- Indices de la tabla `analisis_ia`
--
ALTER TABLE `analisis_ia`
  ADD PRIMARY KEY (`id_analisis`),
  ADD UNIQUE KEY `id_evaluacion` (`id_evaluacion`),
  ADD KEY `fk_ai_nivel` (`id_nivel`);

--
-- Indices de la tabla `analisis_recurso`
--
ALTER TABLE `analisis_recurso`
  ADD PRIMARY KEY (`id_analisis`,`id_recurso`),
  ADD KEY `fk_ar_rec` (`id_recurso`);

--
-- Indices de la tabla `estudiante`
--
ALTER TABLE `estudiante`
  ADD PRIMARY KEY (`id_estudiante`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `chat_id_telegram` (`chat_id_telegram`);

--
-- Indices de la tabla `evaluacion`
--
ALTER TABLE `evaluacion`
  ADD PRIMARY KEY (`id_evaluacion`),
  ADD KEY `fk_eval_est` (`id_estudiante`);

--
-- Indices de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `fk_msg_eval` (`id_evaluacion`);

--
-- Indices de la tabla `nivel_riesgo`
--
ALTER TABLE `nivel_riesgo`
  ADD PRIMARY KEY (`id_nivel`);

--
-- Indices de la tabla `profesor`
--
ALTER TABLE `profesor`
  ADD PRIMARY KEY (`id_profesor`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `psicologo`
--
ALTER TABLE `psicologo`
  ADD PRIMARY KEY (`id_psicologo`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `recurso_apoyo`
--
ALTER TABLE `recurso_apoyo`
  ADD PRIMARY KEY (`id_recurso`);

--
-- Indices de la tabla `registro_temporal`
--
ALTER TABLE `registro_temporal`
  ADD PRIMARY KEY (`chat_id_telegram`);

--
-- Indices de la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  ADD PRIMARY KEY (`id_seguimiento`),
  ADD KEY `fk_seg_alert` (`id_alerta`),
  ADD KEY `fk_seg_psico` (`id_psicologo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alerta`
--
ALTER TABLE `alerta`
  MODIFY `id_alerta` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT de la tabla `analisis_ia`
--
ALTER TABLE `analisis_ia`
  MODIFY `id_analisis` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT de la tabla `estudiante`
--
ALTER TABLE `estudiante`
  MODIFY `id_estudiante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT de la tabla `evaluacion`
--
ALTER TABLE `evaluacion`
  MODIFY `id_evaluacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  MODIFY `id_mensaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT de la tabla `profesor`
--
ALTER TABLE `profesor`
  MODIFY `id_profesor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT de la tabla `psicologo`
--
ALTER TABLE `psicologo`
  MODIFY `id_psicologo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT de la tabla `recurso_apoyo`
--
ALTER TABLE `recurso_apoyo`
  MODIFY `id_recurso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT de la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  MODIFY `id_seguimiento` int(11) NOT NULL AUTO_INCREMENT;
--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alerta`
--
ALTER TABLE `alerta`
  ADD CONSTRAINT `fk_alert_ai` FOREIGN KEY (`id_analisis`) REFERENCES `analisis_ia` (`id_analisis`) ON DELETE CASCADE;

--
-- Filtros para la tabla `analisis_ia`
--
ALTER TABLE `analisis_ia`
  ADD CONSTRAINT `fk_ai_eval` FOREIGN KEY (`id_evaluacion`) REFERENCES `evaluacion` (`id_evaluacion`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ai_nivel` FOREIGN KEY (`id_nivel`) REFERENCES `nivel_riesgo` (`id_nivel`);

--
-- Filtros para la tabla `analisis_recurso`
--
ALTER TABLE `analisis_recurso`
  ADD CONSTRAINT `fk_ar_ai` FOREIGN KEY (`id_analisis`) REFERENCES `analisis_ia` (`id_analisis`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ar_rec` FOREIGN KEY (`id_recurso`) REFERENCES `recurso_apoyo` (`id_recurso`);

--
-- Filtros para la tabla `evaluacion`
--
ALTER TABLE `evaluacion`
  ADD CONSTRAINT `fk_eval_est` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiante` (`id_estudiante`);

--
-- Filtros para la tabla `mensaje`
--
ALTER TABLE `mensaje`
  ADD CONSTRAINT `fk_msg_eval` FOREIGN KEY (`id_evaluacion`) REFERENCES `evaluacion` (`id_evaluacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  ADD CONSTRAINT `fk_seg_alert` FOREIGN KEY (`id_alerta`) REFERENCES `alerta` (`id_alerta`),
  ADD CONSTRAINT `fk_seg_psico` FOREIGN KEY (`id_psicologo`) REFERENCES `psicologo` (`id_psicologo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
