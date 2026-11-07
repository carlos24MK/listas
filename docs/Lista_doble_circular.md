# Lista doble circular: explicación para la clase

Partimos de la lista doble y conservamos también las versiones simple y doble originales. No se invierte el orden: se conectan los extremos.

## Qué cambia

| Lista doble | Lista doble circular con nodos |
| --- | --- |
| El anterior de la cabeza es null. | El anterior de la cabeza es la cola. |
| El siguiente de la cola es null. | El siguiente de la cola es la cabeza. |
| Al recorrer se puede terminar en null. | Al recorrer se termina después de una vuelta. |

Ejemplo: Ana, Luis, Tamara. Desde Tamara, Siguiente lleva a Ana. Desde Ana, Anterior lleva a Tamara. Los botones de la página permiten comprobarlo. Los campos `antes` y `siguiente` guardan referencias a nodos reales.

## Agregar

1. Validar persona, ID único y posición. Con tres nodos se permite insertar desde 1 hasta 4.
2. Crear el nodo. Sus enlaces nacen en null, igual que en la doble original.
3. Si es el primero, hacer que sus dos enlaces apunten a él mismo. Es cabeza y cola.
4. Si ya hay nodos, localizar `antes` y `despues`. Para agregar al final, `despues` será la cabeza, porque el círculo se cierra allí.
5. Conectar los cuatro enlaces:

```javascript
nuevo.antes = antes;
nuevo.siguiente = despues;
antes.siguiente = nuevo;
despues.antes = nuevo;
```

6. Después actualizar cabeza si la posición es 1, o cola si se insertó al final. Finalmente aumentar cantidad.

Así siempre se conserva `cola.siguiente === cabeza` y `cabeza.antes === cola` cuando hay nodos.

## Eliminar

Primero buscar el nodo (`aux`). Si no existe, mostrar un aviso y conservar la lista.

Si es el único, cabeza y cola quedan en null. Si hay varios:

```javascript
aux.antes.siguiente = aux.siguiente;
aux.siguiente.antes = aux.antes;
```

Después, si era cabeza, la cabeza pasa al siguiente. Si era cola, la cola pasa al anterior. Por último se desconectan los enlaces del nodo retirado y se reduce cantidad. Con dos nodos, el que queda termina apuntándose a sí mismo en ambos sentidos.

## Buscar y recorrer

En la lista doble original se avanzaba mientras `actual` fuera distinto de null. En la circular, eso nunca ocurre mientras haya nodos. Por eso se revisan como máximo `cantidad` nodos.

Buscar devuelve el nodo al encontrar el ID; si completa una vuelta, devuelve null. Editar conserva el nodo, el ID y sus enlaces; utiliza la búsqueda adaptada. Consultar usa la misma búsqueda.

El guardado del navegador escribe datos e identificaciones de los enlaces; al cargar, reconstruye los nodos y comprueba que el recorrido guardado cierre sobre la cabeza. En PHP la sesión conserva los objetos enlazados, incluidos sus ciclos.

## Archivos que debes estudiar

- `circular/lista.js`: buscar, agregar, eliminar y recorrer de la versión web.
- `lista.js`: Persona, Nodo, editar y nodoEnPosicion, reutilizados por la circular.
- `src/ListaDobleCircular.php`: equivalente en PHP.
- `src/ListaBase.php`: consultar, editar y validaciones reutilizadas en PHP.

## Práctica para explicarlo

1. Carga el ejemplo y selecciona a Tamara (303). Pulsa Siguiente: vuelve a Ana (101).
2. Desde Ana pulsa Anterior: vuelve a Tamara.
3. Agrega un nodo en la posición 2 y revisa sus dos vecinos.
4. Busca un ID inexistente: debe dar un aviso y terminar.
5. Edita un nombre: el ID y los enlaces se conservan.
6. Elimina un nodo intermedio, la cabeza y la cola; revisa que el círculo se mantenga.
7. Deja un nodo: sus dos enlaces deben señalarlo a él mismo.
8. Elimina el último: cabeza y cola deben quedar en null. Agrega otro y comprueba que se forme un círculo nuevo.
9. Recarga la página: los enlaces deben reconstruirse correctamente.

Puedes explicarlo así: “La lista doble circular conecta el último con el primero en ambos sentidos. Al agregar conecto el nuevo con sus vecinos; al eliminar conecto los vecinos entre sí. Para buscar o mostrar la lista recorro una sola vuelta, porque dentro del círculo no llego a null”.
