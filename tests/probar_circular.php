<?php
require_once __DIR__ . '/../src/cargar.php';
function personaCircular($id) { return new Persona((string)$id, 'Ana', 'Torres', '2005-04-12', 'ana@ejemplo.com'); }
function verificar($condicion) { if (!$condicion) throw new RuntimeException('Falló la comprobación circular.'); }
function comprobarCircular($l, $ids) {
    verificar($l->cantidad === count($ids));
    if (!$ids) { verificar($l->cabeza === null && $l->cola === null); return; }
    verificar($l->cabeza->antes === $l->cola && $l->cola->siguiente === $l->cabeza);
    $n = $l->cabeza;
    foreach ($ids as $id) {
        verificar($n->dato->identificacion === (string)$id);
        verificar($n->siguiente->antes === $n && $n->antes->siguiente === $n);
        $n = $n->siguiente;
    }
    verificar($n === $l->cabeza);
    $n = $l->cola;
    foreach (array_reverse($ids) as $id) { verificar($n->dato->identificacion === (string)$id); $n = $n->antes; }
    verificar($n === $l->cola);
    verificar(count(iterator_to_array($l->recorrer())) === count($ids));
}
$l = new ListaDobleCircular(); comprobarCircular($l, []);
verificar($l->buscar('99') === null);
$n=$l->agregar(personaCircular(2));comprobarCircular($l,[2]);
$l->agregar(personaCircular(4));$l->agregar(personaCircular(1),1);$l->agregar(personaCircular(3),3);comprobarCircular($l,[1,2,3,4]);
verificar($l->buscar('2') === $n && $l->buscar('99') === null);
$antes=$n->antes;$despues=$n->siguiente;
$l->editar('2',new Persona('9','Pedro','Torres','2005-04-12','a@ejemplo.com'));
verificar($n->dato->identificacion === '2' && $n->dato->nombres === 'Pedro' && $n->antes === $antes && $n->siguiente === $despues);
foreach ([0, -1, 6, 1.5, '1'] as $pos) {
    try { $l->agregar(personaCircular(5),$pos); throw new RuntimeException('Aceptó posición inválida'); } catch (InvalidArgumentException $e) {}
    comprobarCircular($l,[1,2,3,4]);
}
try { $l->agregar(personaCircular(2)); throw new RuntimeException('Aceptó duplicado'); } catch (InvalidArgumentException $e) {}
try { $l->eliminar('99'); throw new RuntimeException('Aceptó eliminación ausente'); } catch (InvalidArgumentException $e) {}
// La sesión de PHP serializa objetos: verificar que sus ciclos se recuperan.
$l=unserialize(serialize($l));comprobarCircular($l,[1,2,3,4]);
foreach ([[2,[1,3,4]],[1,[3,4]],[4,[3]],[3,[]]] as [$id,$ids]) { $l->eliminar((string)$id);comprobarCircular($l,$ids); }
$l->agregar(personaCircular(7));$l->vaciar();comprobarCircular($l,[]);$l->agregar(personaCircular(8));comprobarCircular($l,[8]);
echo "OK: inserción, búsqueda, edición, validaciones, eliminación, serialización y reinicio circular.\n";
