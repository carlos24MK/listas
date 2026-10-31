<?php
require_once __DIR__ . '/../src/cargar.php';

function personaDePrueba($id)
{
    return new Persona($id, 'Nombre ' . $id, 'Apellido', '2005-04-12', 'persona' . $id . '@ejemplo.com');
}

function exigir($condicion, $mensaje)
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function comprobarLista(ListaBase $lista, array $esperados)
{
    exigir($lista->cantidad === count($esperados), 'Cantidad incorrecta.');
    $actual = $lista->cabeza;
    $antes = null;
    foreach ($esperados as $id) {
        exigir($actual instanceof NodoSimple, 'Falta un nodo.');
        exigir($actual->dato->identificacion === $id, 'Orden incorrecto.');
        if ($lista instanceof ListaDoble) {
            exigir($actual instanceof NodoDoble, 'El nodo doble perdió su tipo.');
            exigir($actual->antes === $antes, 'Enlace antes incorrecto.');
        } else {
            exigir(!property_exists($actual, 'antes'), 'Un nodo simple no debe tener antes.');
        }
        $antes = $actual;
        $actual = $actual->siguiente;
    }
    exigir($actual === null, 'El final debe apuntar a null.');
    exigir($lista->cola === $antes, 'Cola incorrecta.');
    if ($lista instanceof ListaDoble) {
        $actual = $lista->cola;
        $siguiente = null;
        for ($i = count($esperados) - 1; $i >= 0; $i--) {
            exigir($actual->dato->identificacion === $esperados[$i], 'Recorrido inverso incorrecto.');
            exigir($actual->siguiente === $siguiente, 'Enlace siguiente incorrecto al retroceder.');
            $siguiente = $actual;
            $actual = $actual->antes;
        }
        exigir($actual === null && $lista->cabeza === $siguiente, 'Cabeza incorrecta.');
    }
}

function debeFallar(callable $operacion)
{
    try {
        $operacion();
    } catch (InvalidArgumentException $error) {
        return;
    }
    throw new RuntimeException('La operación inválida fue aceptada.');
}

$correctas = 0;
$fallidas = 0;
function prueba($nombre, callable $operacion)
{
    global $correctas, $fallidas;
    try {
        $operacion();
        $correctas++;
        echo 'OK  ' . $nombre . PHP_EOL;
    } catch (Throwable $error) {
        $fallidas++;
        echo 'ERROR  ' . $nombre . ': ' . $error->getMessage() . PHP_EOL;
    }
}

foreach (['ListaSimple', 'ListaDoble'] as $clase) {
    prueba($clase . ' - insertar en lista vacía, cabeza y cola', function () use ($clase) {
        $lista = new $clase();
        comprobarLista($lista, []);
        $original = $lista->agregar(personaDePrueba('2'), 1);
        exigir($lista->cabeza === $original && $lista->cola === $original, 'El único nodo debe ser ambos extremos.');
        $lista->agregar(personaDePrueba('1'), 1);
        $lista->agregar(personaDePrueba('3'), 3);
        comprobarLista($lista, ['1', '2', '3']);
        exigir($lista->buscar('2') === $original, 'Se reemplazó un nodo que debía conservarse.');
    });

    prueba($clase . ' - inserción intermedia', function () use ($clase) {
        $lista = new $clase();
        $uno = $lista->agregar(personaDePrueba('1'));
        $cuatro = $lista->agregar(personaDePrueba('4'));
        $lista->agregar(personaDePrueba('2'), 2);
        $lista->agregar(personaDePrueba('3'), 3);
        comprobarLista($lista, ['1', '2', '3', '4']);
        exigir($lista->cabeza === $uno && $lista->cola === $cuatro, 'Se alteraron los extremos.');
    });

    prueba($clase . ' - posiciones inválidas e ID duplicado', function () use ($clase) {
        $lista = new $clase();
        $lista->agregar(personaDePrueba('1'));
        foreach ([0, -1, 3, 1.5, '1', false] as $posicion) {
            debeFallar(function () use ($lista, $posicion) { $lista->agregar(personaDePrueba('2'), $posicion); });
            comprobarLista($lista, ['1']);
        }
        debeFallar(function () use ($lista) { $lista->agregar(personaDePrueba('1')); });
        comprobarLista($lista, ['1']);
    });

    prueba($clase . ' - eliminar en medio y desconectar el retirado', function () use ($clase) {
        $lista = new $clase();
        $lista->agregar(personaDePrueba('1'));
        $retirado = $lista->agregar(personaDePrueba('2'));
        $lista->agregar(personaDePrueba('3'));
        $dato = $lista->eliminar('2');
        exigir($dato->identificacion === '2' && $retirado->siguiente === null, 'No se desconectó el retirado.');
        if ($retirado instanceof NodoDoble) exigir($retirado->antes === null, 'Falta desconectar antes.');
        comprobarLista($lista, ['1', '3']);
    });

    prueba($clase . ' - eliminar cabeza, cola y único nodo', function () use ($clase) {
        $lista = new $clase();
        $lista->agregar(personaDePrueba('1'));
        $lista->agregar(personaDePrueba('2'));
        $lista->agregar(personaDePrueba('3'));
        $lista->eliminar('1');
        comprobarLista($lista, ['2', '3']);
        $lista->eliminar('3');
        comprobarLista($lista, ['2']);
        $lista->eliminar('2');
        comprobarLista($lista, []);
        $lista->agregar(personaDePrueba('4'), 1);
        comprobarLista($lista, ['4']);
    });

    prueba($clase . ' - editar conserva el ID, el nodo y sus enlaces', function () use ($clase) {
        $lista = new $clase();
        $lista->agregar(personaDePrueba('1'));
        $original = $lista->agregar(personaDePrueba('2'));
        $lista->agregar(personaDePrueba('3'));
        $siguiente = $original->siguiente;
        $lista->editar('2', new Persona('999', 'Tamara', 'Franco', '2006-01-18', 'tamara@ejemplo.com'));
        exigir($lista->buscar('2') === $original, 'Cambió el objeto del nodo.');
        exigir($original->dato->identificacion === '2' && $original->dato->nombres === 'Tamara', 'Edición incorrecta.');
        exigir($original->siguiente === $siguiente, 'Cambió el siguiente al editar.');
        comprobarLista($lista, ['1', '2', '3']);
    });

    prueba($clase . ' - consultar, buscar y recorrer sin mover cabeza', function () use ($clase) {
        $lista = new $clase();
        $primero = $lista->agregar(personaDePrueba('001'));
        $lista->agregar(personaDePrueba('2'));
        exigir($lista->consultar('001') === $primero->dato, 'Consulta incorrecta.');
        exigir($lista->buscar('9') === null && $lista->consultar('9') === null, 'La ausencia debe devolver null.');
        debeFallar(function () use ($lista) { $lista->eliminar('9'); });
        debeFallar(function () use ($lista) { $lista->editar('9', personaDePrueba('9')); });
        $recorrido = '';
        foreach ($lista->recorrer() as $posicion => $nodo) $recorrido .= $posicion . ':' . $nodo->dato->identificacion . ';';
        exigir($recorrido === '1:001;2:2;' && $lista->cabeza === $primero, 'El recorrido alteró la lista.');
        comprobarLista($lista, ['001', '2']);
    });

    prueba($clase . ' - guardar y recuperar objetos como en una sesión PHP', function () use ($clase) {
        $lista = new $clase();
        $lista->agregar(personaDePrueba('1'));
        $lista->agregar(personaDePrueba('2'));
        $recuperada = unserialize(serialize($lista));
        comprobarLista($recuperada, ['1', '2']);
        $recuperada->eliminar('1');
        comprobarLista($recuperada, ['2']);
        comprobarLista($lista, ['1', '2']);
    });
}

prueba('los métodos buscar, consultar y editar se heredan sin cambios', function () {
    foreach (['buscar', 'consultar', 'editar'] as $metodo) {
        $simple = new ReflectionMethod('ListaSimple', $metodo);
        $doble = new ReflectionMethod('ListaDoble', $metodo);
        exigir($simple->getDeclaringClass()->getName() === 'ListaBase', 'La simple cambió un método compartido.');
        exigir($simple->getFileName() === $doble->getFileName() && $simple->getStartLine() === $doble->getStartLine(), 'No comparten el mismo método.');
    }
});

prueba('los enlaces de los nodos recién creados son null', function () {
    $simple = new NodoSimple(personaDePrueba('1'));
    $doble = new NodoDoble(personaDePrueba('2'));
    exigir($simple->siguiente === null && !property_exists($simple, 'antes'), 'Nodo simple incorrecto.');
    exigir($doble->antes === null && $doble->siguiente === null, 'Nodo doble incorrecto.');
});

prueba('las dos listas permanecen independientes', function () {
    $simple = new ListaSimple();
    $doble = new ListaDoble();
    $simple->agregar(personaDePrueba('1'));
    $doble->agregar(personaDePrueba('2'));
    $simple->vaciar();
    comprobarLista($simple, []);
    comprobarLista($doble, ['2']);
});

prueba('los datos inválidos y fechas imposibles se rechazan', function () {
    debeFallar(function () { new Persona('a', 'Ana', 'Torres', '2005-01-01', 'a@ejemplo.com'); });
    debeFallar(function () { new Persona('1', ' ', 'Torres', '2005-01-01', 'a@ejemplo.com'); });
    debeFallar(function () { new Persona('1', 'Ana', 'Torres', '2025-02-30', 'a@ejemplo.com'); });
    debeFallar(function () { new Persona('1', 'Ana', 'Torres', '2005-01-01', 'sin-correo'); });
});

echo PHP_EOL . $correctas . ' pruebas correctas; ' . $fallidas . ' fallidas.' . PHP_EOL;
exit($fallidas === 0 ? 0 : 1);
