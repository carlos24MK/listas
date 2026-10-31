<?php
// Cargar las clases antes de recuperar los objetos guardados en la sesión.
require_once __DIR__ . '/src/cargar.php';

session_name('practica_listas_php');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function entrada($nombre, $valorPredeterminado = '')
{
    $valor = $_POST[$nombre] ?? $valorPredeterminado;
    if (!is_string($valor)) {
        throw new InvalidArgumentException('El formulario contiene un valor inválido.');
    }
    return trim($valor);
}

function cargarEjemplo(ListaBase $lista)
{
    $lista->vaciar();
    $lista->agregar(new Persona('101', 'Ana', 'Torres', '2005-04-12', 'ana@ejemplo.com'));
    $lista->agregar(new Persona('202', 'Luis', 'Rojas', '2004-09-23', 'luis@ejemplo.com'));
    $lista->agregar(new Persona('303', 'Tamara', 'Franco', '2006-01-18', 'tamara@ejemplo.com'));
}

function referencia($nodo)
{
    return $nodo === null ? 'null' : '#' . $nodo->dato->identificacion;
}

function nombre(Persona $persona)
{
    return $persona->nombres . ' ' . $persona->apellidos;
}

function urlNodo($tipo, $id, $editar = false)
{
    return 'index.php?' . http_build_query([
        'tipo' => $tipo,
        'seleccionado' => $id,
        'editar' => $editar ? '1' : '0'
    ]);
}

function ocultos($accion, $tipo, $token)
{
    echo '<input type="hidden" name="accion" value="' . e($accion) . '">';
    echo '<input type="hidden" name="tipo" value="' . e($tipo) . '">';
    echo '<input type="hidden" name="token" value="' . e($token) . '">';
}

// Se mantienen dos listas independientes: la original y su variante doble.
if (!isset($_SESSION['lista_simple'])) {
    $_SESSION['lista_simple'] = new ListaSimple();
    cargarEjemplo($_SESSION['lista_simple']);
}
if (!isset($_SESSION['lista_doble'])) {
    $_SESSION['lista_doble'] = new ListaDoble();
    cargarEjemplo($_SESSION['lista_doble']);
}
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$tipoPedido = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['tipo'] ?? 'simple') : ($_GET['tipo'] ?? 'simple');
$tipo = $tipoPedido === 'doble' ? 'doble' : 'simple';
$esDoble = $tipo === 'doble';
$lista = $_SESSION['lista_' . $tipo];
$token = $_SESSION['token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seleccion = '';
    $seguirEditando = false;
    try {
        if (!hash_equals($token, entrada('token'))) {
            throw new InvalidArgumentException('El formulario venció. Vuelve a intentarlo desde esta página.');
        }
        $accion = entrada('accion');
        $pasos = [];

        if ($accion === 'agregar') {
            $persona = new Persona(entrada('identificacion'), entrada('nombres'), entrada('apellidos'), entrada('fecha'), entrada('correo'));
            $modo = entrada('modo', 'final');
            if ($modo === 'inicio') {
                $posicion = 1;
            } elseif ($modo === 'final') {
                $posicion = $lista->cantidad + 1;
            } elseif ($modo === 'posicion') {
                $posicion = filter_var(entrada('posicion'), FILTER_VALIDATE_INT);
            } else {
                throw new InvalidArgumentException('Selecciona una forma de insertar válida.');
            }
            $nuevo = $lista->agregar($persona, $posicion);
            $seleccion = $persona->identificacion;
            $texto = 'Nodo #' . $seleccion . ' agregado en la posición ' . $posicion . '.';
            if ($esDoble) {
                $pasos[] = 'nuevo->antes = ' . referencia($nuevo->antes) . '; nuevo->siguiente = ' . referencia($nuevo->siguiente) . '.';
                $pasos[] = 'Se conectaron los vecinos con el nodo nuevo.';
                $pasos[] = 'Después se actualizaron cabeza o cola cuando correspondía.';
            } else {
                $pasos[] = 'Se encontró la posición desde la cabeza.';
                $pasos[] = 'nuevo->siguiente = ' . referencia($nuevo->siguiente) . '.';
                $pasos[] = 'Se conectó el anterior con el nuevo, o se actualizó la cabeza.';
            }
        } elseif ($accion === 'editar') {
            $seleccion = entrada('id_original');
            $seguirEditando = true;
            $persona = new Persona($seleccion, entrada('nombres'), entrada('apellidos'), entrada('fecha'), entrada('correo'));
            $lista->editar($seleccion, $persona);
            $seguirEditando = false;
            $texto = 'Datos del nodo #' . $seleccion . ' actualizados. El ID y sus enlaces se conservaron.';
            $pasos[] = 'Se buscó el nodo por identificación.';
            $pasos[] = 'Se cambiaron únicamente los datos de la persona.';
            $pasos[] = 'editar es el mismo método en las listas simple y doble.';
        } elseif ($accion === 'eliminar') {
            $id = entrada('identificacion');
            $aux = $lista->buscar($id);
            if ($aux === null) {
                throw new InvalidArgumentException('No se encontró el nodo que se quiere eliminar.');
            }
            $siguiente = referencia($aux->siguiente);
            $antes = $esDoble ? referencia($aux->antes) : '';
            $lista->eliminar($id);
            $texto = 'Nodo #' . $id . ' eliminado después de reconectar sus vecinos.';
            $pasos[] = $esDoble
                ? 'El siguiente de ' . $antes . ' se reconectó con ' . $siguiente . ', si existía el vecino anterior.'
                : 'El nodo anterior se conectó con ' . $siguiente . ', o se actualizó la cabeza.';
            if ($esDoble) {
                $pasos[] = 'El antes de ' . $siguiente . ' se reconectó con ' . $antes . ', si existía el vecino posterior.';
            }
            $pasos[] = 'Se actualizaron los extremos y se desconectó el nodo retirado.';
        } elseif ($accion === 'vaciar') {
            $lista->vaciar();
            $texto = 'Lista ' . $tipo . ' vacía. La otra versión se conserva.';
            $pasos = ['cabeza = null.', 'cola = null.', 'cantidad = 0.'];
        } elseif ($accion === 'ejemplo') {
            cargarEjemplo($lista);
            $texto = 'Ejemplo cargado en la lista ' . $tipo . ' con tres personas ficticias.';
            $pasos = ['Cabeza: #101.', 'Nodo intermedio: #202.', 'Cola: #303.'];
        } else {
            throw new InvalidArgumentException('La operación solicitada no existe.');
        }
        $_SESSION['aviso'] = ['texto' => $texto, 'error' => false, 'pasos' => $pasos];
    } catch (InvalidArgumentException $error) {
        $_SESSION['aviso'] = ['texto' => $error->getMessage(), 'error' => true, 'pasos' => []];
        if (isset($accion) && ($accion === 'agregar' || $accion === 'editar')) {
            $_SESSION['formulario'] = $_POST;
        }
    }
    $destino = 'index.php?tipo=' . $tipo;
    if ($seleccion !== '' && $lista->buscar($seleccion) !== null) {
        $destino .= '&seleccionado=' . rawurlencode($seleccion);
        if ($seguirEditando) {
            $destino .= '&editar=1';
        }
    }
    header('Location: ' . $destino, true, 303);
    exit;
}

$aviso = $_SESSION['aviso'] ?? ['texto' => 'Lista ' . $tipo . ' preparada. Agrega un nodo o selecciona uno para consultarlo.', 'error' => false, 'pasos' => []];
unset($_SESSION['aviso']);
$datosForm = $_SESSION['formulario'] ?? [];
unset($_SESSION['formulario']);
$idSeleccionado = isset($_GET['seleccionado']) && is_string($_GET['seleccionado']) ? $_GET['seleccionado'] : '';
$busqueda = isset($_GET['busqueda']) && is_string($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
if ($busqueda !== '') {
    $idSeleccionado = $busqueda;
    $encontrado = $lista->consultar($busqueda);
    $aviso = [
        'texto' => $encontrado ? 'Encontrado: ' . nombre($encontrado) . ' (ID ' . $busqueda . ').' : 'No se encontró la identificación ' . $busqueda . '.',
        'error' => $encontrado === null,
        'pasos' => ['actual comienza en cabeza.', 'Se compara cada identificación y se avanza con actual = actual->siguiente.', 'La búsqueda termina al encontrar el nodo o llegar a null.']
    ];
}
$seleccionado = $lista->buscar($idSeleccionado);
$editando = $seleccionado !== null && ($_GET['editar'] ?? '') === '1';
if ($editando && $datosForm === []) {
    $datosForm = get_object_vars($seleccionado->dato);
}
$valor = function ($campo, $predeterminado = '') use ($datosForm) {
    return isset($datosForm[$campo]) && is_string($datosForm[$campo]) ? $datosForm[$campo] : $predeterminado;
};
$modo = $valor('modo', 'final');
$archivoLista = __DIR__ . '/src/' . ($esDoble ? 'ListaDoble.php' : 'ListaSimple.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Listas enlazadas simple y doble en PHP con inserción por posición, consulta, edición y eliminación.">
    <title>Lista <?= e($tipo) ?> · Estructuras II</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Crect width='40' height='40' rx='10' fill='%230f766e'/%3E%3Cpath d='M9 15h9v10H9zm13 0h9v10h-9zM18 20h4' fill='none' stroke='white' stroke-width='2'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="assets/estilos.css">
    <script src="assets/interfaz.js" defer></script>
</head>
<body>
<a class="skip-link" href="#programa">Saltar al programa</a>
<header class="header"><a class="brand" href="index.php"><span class="brand-icon" aria-hidden="true">↔</span><span>Listas enlazadas en PHP<small>ESTRUCTURAS DE INFORMACIÓN II</small></span></a><a class="header-link" href="#logica">Ver el código ↗</a></header>
<main class="main" id="programa">
    <div class="intro"><div><span class="eyebrow">PRÁCTICA DEL PROFESOR JIMMY</span><h1>Lista simple y su variante doble.</h1><p>Conserva la primera versión y compara cómo cambian las conexiones.</p></div><span class="type-badge"><?= $esDoble ? '2 enlaces por nodo' : '1 enlace por nodo' ?></span></div>
    <nav class="version-tabs" aria-label="Versión del programa"><a href="index.php?tipo=simple" <?= !$esDoble ? 'aria-current="page"' : '' ?>>Lista simple <span>dato + siguiente</span></a><a href="index.php?tipo=doble" <?= $esDoble ? 'aria-current="page"' : '' ?>>Lista doble <span>dato + antes + siguiente</span></a></nav>
    <section class="stats" aria-label="Estado de la lista"><div class="stat"><span>Total de nodos</span><strong id="cantidad"><?= $lista->cantidad ?></strong><small>Versión <?= e($tipo) ?></small></div><div class="stat"><span>Cabeza</span><strong id="cabeza"><?= e(referencia($lista->cabeza)) ?></strong><small>Primer nodo de la lista</small></div><div class="stat"><span>Cola</span><strong id="cola"><?= e(referencia($lista->cola)) ?></strong><small>Último nodo de la lista</small></div></section>
    <div class="message <?= $aviso['error'] ? 'error' : '' ?>" id="mensaje" role="status"><?= e($aviso['texto']) ?></div>
    <div class="workspace"><div class="list-column">
        <section class="panel" aria-labelledby="titulo-lista"><div class="panel-heading"><div><h2 id="titulo-lista">Tu lista <?= e($tipo) ?></h2><p>Selecciona un nodo para consultar, editar o eliminar.</p></div><span class="live-label"><i></i> PHP</span></div>
            <form method="get" class="search-bar"><input type="hidden" name="tipo" value="<?= e($tipo) ?>"><label for="busqueda" class="sr-only">Buscar por identificación</label><svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="10" r="6"/><path d="m15 15 5 5"/></svg><input id="busqueda" name="busqueda" inputmode="numeric" maxlength="20" placeholder="Buscar por identificación" value="<?= e($busqueda) ?>" required><button class="button small" type="submit">Buscar</button></form>
            <div class="canvas" tabindex="0" aria-label="Diagrama de nodos. Puedes desplazarlo horizontalmente.">
                <?php if ($lista->cantidad === 0): ?>
                    <div class="empty-state"><span class="empty-symbol" aria-hidden="true">∅</span><h3>La lista está vacía</h3><p>El primer nodo será cabeza y cola al mismo tiempo.</p><a href="#formulario" class="button secondary">Agregar primer nodo</a></div>
                <?php else: ?>
                    <div id="cadena" class="chain"><?php if ($esDoble): ?><span class="null-cap" aria-hidden="true">null</span><?php endif; ?>
                        <?php foreach ($lista->recorrer() as $posicion => $nodo): ?>
                            <?php if ($posicion > 1): ?><span class="connector" aria-hidden="true"><svg viewBox="0 0 44 44"><path d="M4 16h34m-5-5 5 5-5 5"/><?php if ($esDoble): ?><path d="M40 29H6m5-5-5 5 5 5"/><?php endif; ?></svg></span><?php endif; ?>
                            <a class="node <?= $seleccionado === $nodo ? 'selected' : '' ?>" id="nodo-<?= e($nodo->dato->identificacion) ?>" href="<?= e(urlNodo($tipo, $nodo->dato->identificacion)) ?>" aria-label="Seleccionar <?= e(nombre($nodo->dato)) ?>, identificación <?= e($nodo->dato->identificacion) ?>, posición <?= $posicion ?>">
                                <span class="node-top"><span class="node-position">POS. <?= str_pad((string) $posicion, 2, '0', STR_PAD_LEFT) ?></span><?php if ($nodo === $lista->cabeza || $nodo === $lista->cola): ?><span class="node-endpoint"><?= $nodo === $lista->cabeza && $nodo === $lista->cola ? 'CABEZA / COLA' : ($nodo === $lista->cabeza ? 'CABEZA' : 'COLA') ?></span><?php endif; ?></span>
                                <span class="node-data"><span class="node-id">ID <?= e($nodo->dato->identificacion) ?></span><span class="node-name"><?= e(nombre($nodo->dato)) ?></span></span>
                                <span class="node-links"><?php if ($esDoble): ?><span><small>antes</small><b><?= e(referencia($nodo->antes)) ?></b></span><?php endif; ?><span><small>siguiente</small><b><?= e(referencia($nodo->siguiente)) ?></b></span></span>
                            </a>
                        <?php endforeach; ?><span class="null-cap" aria-hidden="true">null</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="canvas-footer"><span><span class="legend-dot"></span> Nodo seleccionado · <?= $esDoble ? 'Conexión en ambos sentidos' : 'Conexión hacia el siguiente' ?></span><div class="inline-forms"><form method="post" data-confirmar="Los datos de esta versión se reemplazarán por tres personas ficticias."><?php ocultos('ejemplo', $tipo, $token); ?><button class="text-button" type="submit">Cargar ejemplo</button></form><form method="post" data-confirmar="Se retirarán los nodos de esta versión. La otra lista se conserva."><?php ocultos('vaciar', $tipo, $token); ?><button class="text-button muted" type="submit" <?= $lista->cantidad === 0 ? 'disabled' : '' ?>>Vaciar lista</button></form></div></div>
        </section>
        <section class="panel selection-panel" aria-labelledby="titulo-seleccion"><div class="panel-heading"><div><span class="eyebrow">NODO SELECCIONADO</span><h2 id="titulo-seleccion"><?= $seleccionado ? e(nombre($seleccionado->dato)) : 'Elige un nodo de la lista' ?></h2></div><div class="action-buttons">
            <?php if ($seleccionado): ?><a class="button secondary small" href="<?= e(urlNodo($tipo, $idSeleccionado, true)) ?>#formulario">Editar</a><form method="post" data-confirmar="<?= e('Se eliminará a ' . nombre($seleccionado->dato) . '. Primero se reconectarán sus vecinos.') ?>"><?php ocultos('eliminar', $tipo, $token); ?><input type="hidden" name="identificacion" value="<?= e($idSeleccionado) ?>"><button type="submit" class="button danger-outline small">Eliminar</button></form><?php else: ?><button class="button secondary small" disabled>Editar</button><button class="button danger-outline small" disabled>Eliminar</button><?php endif; ?>
        </div></div>
        <?php if ($seleccionado): ?>
            <dl class="person-details"><div><dt>Identificación</dt><dd id="detalle-id"><?= e($seleccionado->dato->identificacion) ?></dd></div><div><dt>Correo</dt><dd><?= e($seleccionado->dato->correo) ?></dd></div><div><dt>Fecha</dt><dd><?= e($seleccionado->dato->fecha) ?></dd></div></dl>
            <div class="connections"><?php if ($esDoble): ?><div><span>antes</span><strong id="detalle-antes"><?= e(referencia($seleccionado->antes)) ?></strong></div><span aria-hidden="true">←</span><?php endif; ?><div class="connection-current"><span>actual</span><strong><?= e(referencia($seleccionado)) ?></strong></div><span aria-hidden="true">→</span><div><span>siguiente</span><strong id="detalle-siguiente"><?= e(referencia($seleccionado->siguiente)) ?></strong></div></div>
        <?php else: ?><div class="selection-empty">Aquí podrás ver sus datos y sus conexiones.</div><?php endif; ?>
        </section>
        <section class="operation" aria-label="Explicación de la operación"><span class="operation-icon" aria-hidden="true">⌘</span><div><h2>Lo que ocurrió</h2><p><?= e($aviso['texto']) ?></p><ol><?php foreach ($aviso['pasos'] as $paso): ?><li><?= e($paso) ?></li><?php endforeach; ?></ol></div></section>
    </div>
    <aside class="panel form-panel" id="formulario" aria-labelledby="titulo-formulario"><div class="panel-heading"><div><span class="eyebrow"><?= $editando ? 'EDITANDO NODO #' . e($idSeleccionado) : 'NUEVO REGISTRO' ?></span><h2 id="titulo-formulario"><?= $editando ? 'Editar persona' : 'Agregar nodo' ?></h2></div><span class="form-icon" aria-hidden="true">+</span></div>
        <form method="post"><?php ocultos($editando ? 'editar' : 'agregar', $tipo, $token); ?><?php if ($editando): ?><input type="hidden" name="id_original" value="<?= e($idSeleccionado) ?>"><?php endif; ?>
            <div class="field"><label for="identificacion">Identificación <span>*</span></label><input id="identificacion" name="identificacion" inputmode="numeric" pattern="[0-9]{1,20}" maxlength="20" placeholder="Ej. 104" value="<?= e($editando ? $idSeleccionado : $valor('identificacion')) ?>" required <?= $editando ? 'readonly' : '' ?>><small><?= $editando ? 'La identificación se conserva al editar.' : 'Debe ser única dentro de esta lista.' ?></small></div>
            <div class="field"><label for="nombres">Nombres <span>*</span></label><input id="nombres" name="nombres" maxlength="60" placeholder="Ej. Sofía" value="<?= e($valor('nombres')) ?>" required></div>
            <div class="field"><label for="apellidos">Apellidos <span>*</span></label><input id="apellidos" name="apellidos" maxlength="60" placeholder="Ej. Ramírez" value="<?= e($valor('apellidos')) ?>" required></div>
            <div class="field"><label for="fecha">Fecha <span>*</span></label><input id="fecha" name="fecha" type="date" value="<?= e($valor('fecha')) ?>" required></div>
            <div class="field"><label for="correo">Correo <span>*</span></label><input id="correo" name="correo" type="email" maxlength="254" placeholder="sofia@ejemplo.com" value="<?= e($valor('correo')) ?>" required></div>
            <?php if (!$editando): ?><fieldset class="location"><legend>¿Dónde lo agregamos?</legend><label class="sr-only" for="modo-posicion">Tipo de inserción</label><select name="modo" id="modo-posicion"><option value="final" <?= $modo === 'final' ? 'selected' : '' ?>>Al final de la lista</option><option value="inicio" <?= $modo === 'inicio' ? 'selected' : '' ?>>Al inicio de la lista</option><option value="posicion" <?= $modo === 'posicion' ? 'selected' : '' ?>>En una posición específica</option></select><div class="field" id="posicion-grupo"><label for="posicion">Posición</label><input type="number" id="posicion" name="posicion" min="1" max="<?= $lista->cantidad + 1 ?>" step="1" value="<?= e($valor('posicion', (string) ($lista->cantidad + 1))) ?>" required></div><p>Posiciones disponibles: 1 a <?= $lista->cantidad + 1 ?>. La última agrega al final.</p></fieldset><?php endif; ?>
            <button class="button primary full" type="submit"><?= $editando ? 'Guardar cambios' : 'Agregar nodo' ?> <span aria-hidden="true">+</span></button><?php if ($editando): ?><a class="button secondary full" href="<?= e(urlNodo($tipo, $idSeleccionado)) ?>">Cancelar edición</a><?php endif; ?>
            <p class="form-note"><?= $editando ? 'Editar cambia los datos y conserva las conexiones.' : 'Las conexiones se resuelven en PHP antes de actualizar los extremos.' ?></p>
        </form>
    </aside></div>
    <section class="panel table-panel" aria-labelledby="titulo-tabla"><div class="panel-heading"><div><h2 id="titulo-tabla">Recorrido de la lista <?= e($tipo) ?></h2><p>Se avanza desde la cabeza con actual = actual-&gt;siguiente.</p></div><span class="count-badge"><?= $lista->cantidad ?> registros</span></div><div class="table-scroll"><table><thead><tr><th>Posición</th><th>Identificación</th><th>Persona</th><th>Correo</th><?php if ($esDoble): ?><th>Antes</th><?php endif; ?><th>Siguiente</th><th>Acción</th></tr></thead><tbody id="tabla-cuerpo">
        <?php foreach ($lista->recorrer() as $posicion => $nodo): ?><tr class="<?= $seleccionado === $nodo ? 'selected' : '' ?>"><td class="mono"><?= $posicion ?></td><td class="mono"><?= e($nodo->dato->identificacion) ?></td><td><?= e(nombre($nodo->dato)) ?></td><td><?= e($nodo->dato->correo) ?></td><?php if ($esDoble): ?><td class="mono"><?= e(referencia($nodo->antes)) ?></td><?php endif; ?><td class="mono"><?= e(referencia($nodo->siguiente)) ?></td><td><a class="text-button" href="<?= e(urlNodo($tipo, $nodo->dato->identificacion)) ?>">Seleccionar</a></td></tr><?php endforeach; ?>
        <?php if ($lista->cantidad === 0): ?><tr><td class="table-empty" colspan="<?= $esDoble ? 7 : 6 ?>">Todavía no hay nodos para recorrer.</td></tr><?php endif; ?>
    </tbody></table></div></section>
    <section id="logica" class="logic-section"><div><span class="eyebrow">LO QUE CAMBIA ENTRE VERSIONES</span><h2>Nodo, agregar y eliminar.</h2><p>La lista simple tiene un enlace por nodo: <code>siguiente</code>. La doble añade <code>antes</code>, equivalente al <code>anterior</code> que menciona el profesor.</p><p>Buscar, consultar y editar se comparten en <code>ListaBase.php</code>. La inserción por posición conserva el cambio solicitado en el mensaje escrito.</p><p>Primero se conectan los vecinos y después se actualizan cabeza y cola. Al eliminar, los vecinos se reconectan antes de retirar el nodo.</p></div><details class="panel code-details"><summary>Ver el código de esta versión <span aria-hidden="true">+</span></summary><div class="code-grid"><h3><?= $esDoble ? 'ListaDoble.php' : 'ListaSimple.php' ?></h3><pre><?= e(file_get_contents($archivoLista)) ?></pre></div></details></section>
    <footer class="footer"><span>PHP · HTML · CSS</span><span>Dos listas independientes, guardadas en la sesión de PHP.</span></footer>
</main>
<dialog id="confirmacion" aria-labelledby="confirmacion-titulo"><form method="dialog"><span class="dialog-symbol" aria-hidden="true">!</span><h2 id="confirmacion-titulo">Confirmar operación</h2><p id="confirmacion-texto"></p><div class="dialog-actions"><button id="confirmacion-cancelar" class="button secondary" value="cancelar">Cancelar</button><button class="button danger" value="aceptar">Confirmar</button></div></form></dialog>
</body>
</html>
