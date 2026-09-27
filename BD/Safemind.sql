-- phpMyAdmin SQL Dump
-- version 4.7.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 162.210.70.175
-- Tiempo de generación: 27-09-2026 a las 02:49:29
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

--
-- Volcado de datos para la tabla `analisis_ia`
--

INSERT INTO `analisis_ia` (`id_analisis`, `id_evaluacion`, `id_nivel`, `emocion_detectada`, `porcentaje_confianza`, `resumen`, `fecha`) VALUES
(1, 2, 2, 'Agotamiento y desmotivación', '90.00', 'El estudiante manifiesta una falta de energía persistente desde hace varios meses, vinculada a hábitos de sueño irregulares por el uso de videojuegos para lidiar con el aburrimiento. Esta situación está impactando negativamente su rendimiento y participación académica.', '2026-09-24 06:16:23'),
(2, 3, 2, 'Nerviosismo y expectativa académica', '95.00', 'El estudiante manifiesta estar muy nervioso y pensativo debido a la entrega de los resultados del examen ICFES programada para el día de mañana. Aunque aclara que el malestar no es extremo, reconoce tener miedo de obtener un mal resultado.', '2026-09-24 06:18:09'),
(3, 5, 1, 'Incertidumbre', '80.00', 'El estudiante se muestra poco comunicativo, respondiendo de manera evasiva o incierta ante las preguntas de la IA. No se identifican señales de malestar, estrés o riesgo en sus intervenciones.', '2026-09-24 06:19:56'),
(4, 1, 2, 'Tristeza y pesadumbre', '90.00', 'El estudiante manifiesta malestar emocional debido a una ruptura amorosa reciente. Aunque expresa sentirse mal, la conversación se encuentra en una etapa inicial de desahogo sin indicios de riesgo inmediato.', '2026-09-24 06:21:00'),
(5, 4, 1, 'Bienestar', '95.00', 'El estudiante manifiesta sentirse muy bien y con ánimo positivo al iniciar su día, sin presentar señales de malestar o riesgo.', '2026-09-24 06:21:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `analisis_recurso`
--

CREATE TABLE `analisis_recurso` (
  `id_analisis` int(11) NOT NULL,
  `id_recurso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `analisis_recurso`
--

INSERT INTO `analisis_recurso` (`id_analisis`, `id_recurso`) VALUES
(1, 2),
(4, 2),
(2, 4);

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
(1, 'Mariangel Duque', 'Duque', 'mariangelduque@colegioguanenta.edu.co', '3204638734', '11-5', 8562258004, 'activo', '2026-09-24 06:13:01'),
(2, 'Jorge Silva', 'Morales', 'jorgesilva@colegioguanenta.edu.co', '3152964191', '11-5', 6559720998, 'activo', '2026-09-24 06:13:27'),
(3, 'Andres Felipe', 'Castillo Neira', 'andrescastillo@colegioguanenta.edu.co', '3001083859', '11-4', 7402841152, 'activo', '2026-09-24 06:15:21'),
(4, 'edward santiago\r\n', 'Silva joya', 'edwarsilva995@gmail.com', '3167674589', '11-3°', 8857466028, 'activo', '2026-09-24 06:15:43'),
(5, 'Sebastián', 'Carreño araque', 'sebascarrenoaraque@gmail.com', '3159080517', '11-4', 8905121611, 'activo', '2026-09-24 06:16:01'),
(6, 'Me llamo Nicolle', 'Macías', 'nicollemacias@colegioguanenta.edu.co', '3213861012', '11-5', 8707527737, 'activo', '2026-09-24 06:20:56');

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
(1, 1, 'Malísimo', '2026-09-24 06:13:01', '2026-09-24 06:21:01', 'Finalizada', 'finalizada'),
(2, 2, 'Malísimo', '2026-09-24 06:13:28', '2026-09-24 06:16:23', 'Finalizada', 'finalizada'),
(3, 3, 'No tan bien', '2026-09-24 06:15:22', '2026-09-24 06:18:10', 'Finalizada', 'finalizada'),
(4, 4, 'Bien', '2026-09-24 06:15:43', '2026-09-24 06:21:56', 'Finalizada', 'finalizada'),
(5, 5, 'Bien', '2026-09-24 06:16:02', '2026-09-24 06:19:56', 'Finalizada', 'finalizada'),
(6, 6, 'No tan bien', '2026-09-24 06:20:56', NULL, 'Activa', 'en_conversacion');

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
(1, 1, 'estudiante', 'Me termino mi novio', '2026-09-24 06:13:17'),
(2, 1, 'ia', 'Lamento mucho escuchar eso, sé que las rupturas pueden ser momentos muy difíciles y dolorosos. ¿Esto pasó hace poco?', '2026-09-24 06:13:22'),
(3, 1, 'estudiante', 'Si hace poco', '2026-09-24 06:13:29'),
(4, 1, 'ia', 'Entiendo, los primeros días suelen ser los más difíciles. ¿Cómo te has estado sintiendo desde que pasó?', '2026-09-24 06:13:33'),
(5, 2, 'estudiante', 'La verdad me he sentido sin energía, me trasnocho jugando porque me aburro en la casa y luego no duermo', '2026-09-24 06:14:21'),
(6, 2, 'ia', 'Te entiendo, debe ser muy agotador sentirte sin energía durante el día. A veces los videojuegos parecen la mejor opción cuando estamos aburridos, aunque terminen quitándonos horas de sueño. ¿Esto te ha estado pasando desde hace mucho tiempo o empezó hace poco?', '2026-09-24 06:14:26'),
(7, 2, 'estudiante', 'Me ha estado pasando desde mitad de año', '2026-09-24 06:15:42'),
(8, 2, 'ia', 'Ya ha pasado un buen tiempo desde que comenzó esa rutina, debe ser muy agotador sentir ese cansancio acumulado. ¿Has notado si esto te afecta también en tus clases o en otras actividades del día?', '2026-09-24 06:15:47'),
(9, 3, 'estudiante', 'Me siento muy nervioso por el icfes, tengo nervios de que me vaya mal', '2026-09-24 06:15:58'),
(10, 3, 'ia', 'Es totalmente comprensible que te sientas así, el ICFES es un reto importante y es normal tener esos nervios. ¿Sientes que te preocupa alguna materia en específico o es más por el examen en general?', '2026-09-24 06:16:03'),
(11, 4, 'estudiante', 'Ps muy bien', '2026-09-24 06:16:13'),
(12, 2, 'estudiante', 'Así es, me duermo en clase o no hago trabajos por pura pereza', '2026-09-24 06:16:15'),
(13, 4, 'ia', '¡Qué bueno leer eso! Me alegra mucho que te sientas así. ¿Ha pasado algo especial hoy que te haga sentir de esa manera?', '2026-09-24 06:16:17'),
(14, 5, 'estudiante', 'Nose porque di', '2026-09-24 06:16:22'),
(15, 5, 'ia', 'Te escucho. A veces es difícil poner en palabras lo que sentimos o lo que nos pasa. ¿Qué es lo que te tiene dándole vueltas a la cabeza?', '2026-09-24 06:16:26'),
(16, 3, 'estudiante', 'Solo tengo miedo de que salga mal, pero no lo suficiente para preocuparme tanto', '2026-09-24 06:16:31'),
(17, 3, 'ia', 'Entiendo, es como una inquietud que te acompaña sin llegar a abrumarte del todo. ¿Cómo te has sentido con la preparación que has hecho hasta ahora?', '2026-09-24 06:16:36'),
(18, 4, 'estudiante', 'No nada raro solo amanecí bien', '2026-09-24 06:16:57'),
(19, 4, 'ia', 'Qué gusto que hayas despertado con esa buena energía. ¿Tienes algún plan para aprovechar este buen ánimo durante el día?', '2026-09-24 06:17:03'),
(20, 3, 'estudiante', 'Ya que mañana sale el resultado real, me tiene pensativo, solo es eso, espero tener un buen icfes', '2026-09-24 06:18:03'),
(21, 5, 'estudiante', 'Nose', '2026-09-24 06:19:28'),
(22, 5, 'ia', 'No te preocupes, es muy común no tenerlo claro al principio. ¿Ha pasado algo hoy, aunque parezca pequeño, que te haya hecho sentir así?', '2026-09-24 06:19:33'),
(23, 5, 'estudiante', 'Nose', '2026-09-24 06:19:46'),
(24, 1, 'estudiante', 'Mal', '2026-09-24 06:20:53'),
(25, 6, 'estudiante', 'Unas situaciones en la casa ??', '2026-09-24 06:21:41'),
(26, 6, 'ia', 'Lamento que estés pasando por momentos difíciles en casa; a veces el hogar puede ser un lugar complicado. ¿Qué es lo que ha estado sucediendo últimamente?', '2026-09-24 06:21:45'),
(27, 4, 'estudiante', 'No sé la vrd  pues que le recomiendas', '2026-09-24 06:21:51'),
(28, 6, 'estudiante', 'Me ponen a lavar la loza y no comprenden que yo no quiero', '2026-09-24 06:22:46'),
(29, 6, 'ia', 'Entiendo que te sientas así, puede ser frustrante sentir que no toman en cuenta lo que quieres o cómo te sientes. ¿Hay alguna razón específica por la que te molesta tanto esa tarea en este momento?', '2026-09-24 06:22:51'),
(30, 6, 'estudiante', 'Pq no me gusta lavarme las manos, iu', '2026-09-24 06:23:30');

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
(1, 'psicologo.prueba', 'psicologo', 'prueba', 'asdasdadasd@gmail.com', '12313123123123', 'SafeMind123!', 'Activo');

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
  MODIFY `id_analisis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT de la tabla `estudiante`
--
ALTER TABLE `estudiante`
  MODIFY `id_estudiante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT de la tabla `evaluacion`
--
ALTER TABLE `evaluacion`
  MODIFY `id_evaluacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  MODIFY `id_mensaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
--
-- AUTO_INCREMENT de la tabla `profesor`
--
ALTER TABLE `profesor`
  MODIFY `id_profesor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT de la tabla `psicologo`
--
ALTER TABLE `psicologo`
  MODIFY `id_psicologo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
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
