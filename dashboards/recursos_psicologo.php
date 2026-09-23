<?php

session_start();

/*
|--------------------------------------------------------------------------
| PROTECCIÓN DE ACCESO
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['rol']) ||
    $_SESSION['rol'] !== 'psicologo'
) {
    header("Location: ../login.php?rol=psicologo");
    exit();
}


/*
|--------------------------------------------------------------------------
| DATOS DEL PSICÓLOGO
|--------------------------------------------------------------------------
*/

$nombre = $_SESSION['nombre'] ?? 'Psicólogo';

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Recursos - SafeMind</title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,450;9..144,560;9..144,650&family=Karla:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        :root {

            --paper: #F7F4ED;
            --line: #E5DED0;

            --ink: #1E2B2F;
            --ink-soft: #68777B;
            --ink-faint: #9AA6A9;

            --deep: #17455A;
            --deep-dark: #0E2E3D;

            --sage: #4F7F62;
            --sage-soft: #DCE9DF;

            --orange: #C96D34;
            --orange-soft: #F5E1D2;

            --red: #B94A43;
            --red-soft: #F7E0DD;

            --blue-soft: #E2EDF1;

            --gold: #C8922F;
            --gold-soft: #F5E7C8;

        }


        body {

            font-family: 'Karla', sans-serif;

            background: var(--paper);

            color: var(--ink);

            min-height: 100vh;

        }


        .app {

            min-height: 100vh;

            display: flex;

        }


        /* =========================================================
           SIDEBAR
        ========================================================== */

        .sidebar {

            width: 263px;

            background: #fff;

            border-right: 1px solid var(--line);

            padding: 28px 20px;

            display: flex;

            flex-direction: column;

            position: fixed;

            left: 0;

            top: 0;

            bottom: 0;

        }


        .logo {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 0 8px;

            margin-bottom: 30px;

        }


        .logo-mark {

            width: 34px;

            height: 34px;

            border-radius: 10px;

            background: var(--blue-soft);

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--deep);

            font-weight: 700;

        }


        .logo span {

            font-family: 'Fraunces', serif;

            font-size: 23px;

            color: var(--deep-dark);

        }


        .perfil {

            background: var(--paper);

            border-radius: 12px;

            padding: 13px;

            margin-bottom: 20px;

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .avatar {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            background: var(--blue-soft);

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--deep);

            font-weight: 700;

        }


        .perfil-info strong {

            display: block;

            font-size: 14px;

        }


        .perfil-info span {

            font-size: 12px;

            color: var(--ink-soft);

        }


        .menu {

            display: flex;

            flex-direction: column;

            gap: 6px;

        }


        .menu a {

            text-decoration: none;

            color: var(--ink-soft);

            padding: 11px 13px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            gap: 10px;

            font-size: 14px;

            transition: 0.2s ease;

        }


        .menu a:hover {

            background: var(--paper);

            color: var(--deep);

        }


        .menu a.activo {

            background: var(--blue-soft);

            color: var(--deep);

            font-weight: 600;

        }


        .orientacion {

            margin-top: auto;

            background: var(--sage-soft);

            border-radius: 12px;

            padding: 15px;

        }


        .orientacion strong {

            display: block;

            font-size: 14px;

            margin-bottom: 5px;

        }


        .orientacion p {

            font-size: 12px;

            line-height: 1.5;

            color: var(--ink-soft);

            margin-bottom: 10px;

        }


        .orientacion span {

            color: var(--sage);

            font-size: 12px;

            font-weight: 700;

        }


        .cerrar {

            margin-top: 17px;

            padding: 10px 13px;

            color: var(--ink-soft);

            text-decoration: none;

            font-size: 13px;

        }


        .cerrar:hover {

            color: var(--red);

        }


        /* =========================================================
           MAIN
        ========================================================== */

        .main {

            margin-left: 263px;

            width: calc(100% - 263px);

            padding: 30px 38px 45px;

        }


        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 25px;

        }


        .titulo h1 {

            font-family: 'Fraunces', serif;

            font-size: 31px;

            color: var(--deep-dark);

            margin-bottom: 5px;

        }


        .titulo p {

            color: var(--ink-soft);

            font-size: 15px;

        }


        .fecha {

            text-align: right;

            font-size: 13px;

            color: var(--ink);

        }


        .fecha span {

            display: block;

            color: var(--ink-faint);

            margin-top: 3px;

        }


        /* =========================================================
           PANEL
        ========================================================== */

        .panel {

            background: #fff;

            border: 1px solid var(--line);

            border-radius: 15px;

            padding: 22px;

        }


        .panel-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;

        }


        .panel-header h2 {

            font-family: 'Fraunces', serif;

            font-size: 20px;

            color: var(--deep-dark);

        }


        .total {

            color: var(--ink-faint);

            font-size: 12px;

        }


        /* =========================================================
           BUSCADOR
        ========================================================== */

        .busqueda {

            margin-bottom: 22px;

        }


        .busqueda input {

            width: 100%;

            max-width: 500px;

            border: 1px solid var(--line);

            border-radius: 10px;

            padding: 12px 14px;

            font-family: 'Karla', sans-serif;

            font-size: 14px;

            color: var(--ink);

            background: #fff;

        }


        .busqueda input:focus {

            outline: none;

            border-color: var(--deep);

            box-shadow:
                0 0 0 3px
                rgba(23, 69, 90, 0.10);

        }


        /* =========================================================
           GRID RECURSOS
        ========================================================== */

        .recursos-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 16px;

        }


        .recurso-card {

            border: 1px solid var(--line);

            border-radius: 14px;

            padding: 18px;

            background: #fff;

            display: flex;

            flex-direction: column;

            min-height: 235px;

            transition: 0.2s ease;

        }


        .recurso-card:hover {

            transform: translateY(-2px);

            border-color: #D6CEC1;

        }


        .recurso-icono {

            width: 42px;

            height: 42px;

            border-radius: 11px;

            background: var(--sage-soft);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

            margin-bottom: 14px;

        }


        .recurso-titulo {

            font-family: 'Fraunces', serif;

            font-size: 18px;

            color: var(--deep-dark);

            margin-bottom: 8px;

            line-height: 1.25;

        }


        .recurso-tipo {

            display: inline-block;

            align-self: flex-start;

            padding: 5px 9px;

            border-radius: 20px;

            background: var(--blue-soft);

            color: var(--deep);

            font-size: 10px;

            font-weight: 700;

            margin-bottom: 12px;

        }


        .recurso-descripcion {

            color: var(--ink-soft);

            font-size: 13px;

            line-height: 1.6;

            flex-grow: 1;

        }


        .recurso-pie {

            margin-top: 18px;

            padding-top: 13px;

            border-top: 1px solid var(--line);

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 10px;

        }


        .veces {

            font-size: 12px;

            color: var(--ink-faint);

        }


        .veces strong {

            color: var(--deep);

        }


        .uso-alto {

            color: var(--sage);

            font-weight: 700;

        }


        .uso-medio {

            color: var(--orange);

            font-weight: 700;

        }


        .uso-bajo {

            color: var(--ink-faint);

        }


        /* =========================================================
           ESTADOS
        ========================================================== */

        .estado {

            min-height: 260px;

            display: flex;

            justify-content: center;

            align-items: center;

            text-align: center;

            color: var(--ink-faint);

            font-size: 14px;

        }


        .estado.error {

            color: var(--red);

        }


        /* =========================================================
           RESPONSIVE
        ========================================================== */

        @media (max-width: 1050px) {

            .recursos-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }

        }


        @media (max-width: 800px) {

            .sidebar {

                position: static;

                width: 100%;

            }


            .app {

                flex-direction: column;

            }


            .main {

                margin-left: 0;

                width: 100%;

                padding: 25px 20px;

            }


            .topbar {

                flex-direction: column;

            }


            .fecha {

                text-align: left;

            }

        }


        @media (max-width: 600px) {

            .recursos-grid {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body>

<div class="app">


    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside class="sidebar">


        <div class="logo">

            <div class="logo-mark">
                SM
            </div>

            <span>
                SafeMind
            </span>

        </div>


        <div class="perfil">

            <div class="avatar">

                <?php

                echo htmlspecialchars(
                    strtoupper(
                        substr(
                            $nombre,
                            0,
                            2
                        )
                    )
                );

                ?>

            </div>


            <div class="perfil-info">

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $nombre
                    );

                    ?>

                </strong>


                <span>
                    Psicólogo / Orientador
                </span>

            </div>

        </div>


        <nav class="menu">

            <a href="dashboard_psicologo.php">
                🏠 &nbsp; Inicio
            </a>


            <a href="dashboard_psicologo.php#alertas">
                🔔 &nbsp; Alertas
            </a>


            <a href="estudiantes_psicologo.php">
                👥 &nbsp; Estudiantes
            </a>


            <a href="seguimientos_psicologo.php">
                📋 &nbsp; Seguimientos
            </a>


            <a
                href="recursos_psicologo.php"
                class="activo"
            >
                🌿 &nbsp; Recursos
            </a>

        </nav>


        <div class="orientacion">

            <strong>
                SafeMind
            </strong>


            <p>
                Recursos de apoyo utilizados
                durante el acompañamiento.
            </p>


            <span>
                Panel protegido
            </span>

        </div>


        <a
            href="../logout.php"
            class="cerrar"
        >
            ↪ &nbsp; Cerrar sesión
        </a>

    </aside>


    <!-- =========================================================
         MAIN
    ========================================================== -->

    <main class="main">


        <div class="topbar">

            <div class="titulo">

                <h1>
                    Recursos de apoyo
                </h1>


                <p>
                    Material disponible para el acompañamiento
                    de los estudiantes
                </p>

            </div>


            <div class="fecha">

                <?php
                echo date('d/m/Y');
                ?>


                <span>
                    Información de SafeMind
                </span>

            </div>

        </div>


        <!-- =====================================================
             PANEL
        ====================================================== -->

        <section class="panel">


            <div class="panel-header">

                <h2>
                    Recursos disponibles
                </h2>


                <span
                    id="total-recursos"
                    class="total"
                >
                    Cargando...
                </span>

            </div>


            <div class="busqueda">

                <input
                    type="text"
                    id="buscador"
                    placeholder="Buscar recurso por nombre, descripción o tipo..."
                    autocomplete="off"
                >

            </div>


            <div
                id="recursos-container"
                class="recursos-grid"
            >

                <div
                    class="estado"
                    style="grid-column: 1 / -1;"
                >

                    Cargando recursos...

                </div>

            </div>


        </section>


    </main>

</div>


<script>

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const API_RECURSOS =
    '../API/seguimiento/obtener_recursos_psicologo.php';


/*
|--------------------------------------------------------------------------
| ELEMENTOS
|--------------------------------------------------------------------------
*/

const contenedor =
    document.getElementById(
        'recursos-container'
    );


const total =
    document.getElementById(
        'total-recursos'
    );


const buscador =
    document.getElementById(
        'buscador'
    );


/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

let recursos = [];


/*
|--------------------------------------------------------------------------
| ESCAPAR HTML
|--------------------------------------------------------------------------
*/

function escapar(texto) {

    const div =
        document.createElement('div');

    div.textContent =
        texto ?? '';

    return div.innerHTML;

}


/*
|--------------------------------------------------------------------------
| CLASE SEGÚN USO
|--------------------------------------------------------------------------
*/

function claseUso(cantidad) {

    const numero =
        Number(cantidad || 0);


    if (numero >= 5) {

        return 'uso-alto';

    }


    if (numero >= 1) {

        return 'uso-medio';

    }


    return 'uso-bajo';

}


/*
|--------------------------------------------------------------------------
| TEXTO DE USO
|--------------------------------------------------------------------------
*/

function textoUso(cantidad) {

    const numero =
        Number(cantidad || 0);


    if (numero === 0) {

        return 'Aún no recomendado';

    }


    if (numero === 1) {

        return 'Recomendado 1 vez';

    }


    return `Recomendado ${numero} veces`;

}


/*
|--------------------------------------------------------------------------
| RENDERIZAR
|--------------------------------------------------------------------------
*/

function renderizarRecursos(lista) {

    if (!Array.isArray(lista)) {

        lista = [];

    }


    if (lista.length === 0) {

        contenedor.innerHTML = `

            <div
                class="estado"
                style="grid-column: 1 / -1;"
            >

                No se encontraron recursos.

            </div>

        `;

        return;

    }


    contenedor.innerHTML =
        lista.map(
            recurso => {

                const titulo =
                    recurso.titulo ||
                    'Recurso de apoyo';


                const descripcion =
                    recurso.descripcion ||
                    'Sin descripción disponible.';


                const tipo =
                    recurso.tipo ||
                    'Apoyo';


                const veces =
                    Number(
                        recurso.veces_recomendado || 0
                    );


                return `

                    <article
                        class="recurso-card"
                    >

                        <div
                            class="recurso-icono"
                        >
                            🌿
                        </div>


                        <div
                            class="recurso-titulo"
                        >

                            ${escapar(
                                titulo
                            )}

                        </div>


                        <span
                            class="recurso-tipo"
                        >

                            ${escapar(
                                tipo
                            )}

                        </span>


                        <div
                            class="recurso-descripcion"
                        >

                            ${escapar(
                                descripcion
                            )}

                        </div>


                        <div
                            class="recurso-pie"
                        >

                            <span
                                class="veces ${claseUso(
                                    veces
                                )}"
                            >

                                ${escapar(
                                    textoUso(
                                        veces
                                    )
                                )}

                            </span>


                            ${
                                veces > 0
                                    ? `
                                        <strong
                                            style="
                                                color: var(--deep);
                                                font-size: 12px;
                                            "
                                        >
                                            ${veces}
                                        </strong>
                                      `
                                    : ''
                            }

                        </div>

                    </article>

                `;

            }
        ).join('');

}


/*
|--------------------------------------------------------------------------
| CARGAR RECURSOS
|--------------------------------------------------------------------------
*/

async function cargarRecursos() {

    try {

        const respuesta =
            await fetch(
                API_RECURSOS,
                {
                    method: 'GET',
                    cache: 'no-store',
                    credentials: 'same-origin'
                }
            );


        if (!respuesta.ok) {

            throw new Error(
                'No se pudo consultar la API de recursos.'
            );

        }


        const datos =
            await respuesta.json();


        if (!datos.success) {

            throw new Error(
                datos.mensaje ||
                'No se pudieron cargar los recursos.'
            );

        }


        recursos =
            Array.isArray(
                datos.recursos
            )
                ? datos.recursos
                : [];


        total.textContent =
            `${recursos.length} recurso(s)`;


        renderizarRecursos(
            recursos
        );


    } catch (error) {

        console.error(
            'Error al cargar recursos:',
            error
        );


        total.textContent =
            'Error';


        contenedor.innerHTML = `

            <div
                class="estado error"
                style="grid-column: 1 / -1;"
            >

                No se pudieron cargar
                los recursos.

            </div>

        `;

    }

}


/*
|--------------------------------------------------------------------------
| BUSCADOR
|--------------------------------------------------------------------------
*/

buscador.addEventListener(
    'input',
    function () {

        const texto =
            this.value
                .toLowerCase()
                .trim();


        if (texto === '') {

            renderizarRecursos(
                recursos
            );

            return;

        }


        const filtrados =
            recursos.filter(
                recurso => {

                    const titulo =
                        String(
                            recurso.titulo ?? ''
                        ).toLowerCase();


                    const descripcion =
                        String(
                            recurso.descripcion ?? ''
                        ).toLowerCase();


                    const tipo =
                        String(
                            recurso.tipo ?? ''
                        ).toLowerCase();


                    return (
                        titulo.includes(texto) ||
                        descripcion.includes(texto) ||
                        tipo.includes(texto)
                    );

                }
            );


        renderizarRecursos(
            filtrados
        );

    }
);


/*
|--------------------------------------------------------------------------
| INICIAR
|--------------------------------------------------------------------------
*/

cargarRecursos();

</script>


</body>

</html>