# Listas enlazadas simple y doble en PHP

Programa para estudiar las indicaciones del profesor Jimmy. Conserva dos listas independientes: una simple y otra doble. Incluye inserción en cualquier posición, búsqueda, consulta, edición y eliminación.

## Descargar desde GitHub

Para clonar el repositorio con el nombre que se usa en esta guía:

```bash
git clone https://github.com/carlos24MK/listas.git listas_php_codex
```

También puedes usar **Code → Download ZIP**, extraerlo y renombrar la carpeta como `listas_php_codex` antes de copiarla a WAMP.

## Abrirlo con WAMP

1. Extrae el ZIP completo.
2. Copia la carpeta `listas_php_codex` dentro de `C:\wamp64\www` si esa es la carpeta de instalación de tu WAMP.
3. Inicia WAMP y comprueba que Apache esté funcionando.
4. Abre `http://localhost/listas_php_codex/index.php` en el navegador. Si tu Apache usa otro puerto, inclúyelo en la dirección.
5. Cambia entre **Lista simple** y **Lista doble** con los dos botones superiores.

No necesita MySQL, migraciones, Composer ni paquetes externos. No se debe abrir index.php con doble clic ni mediante Live Server: PHP debe ejecutarse en Apache o en su servidor de desarrollo.

Si PHP está disponible en la terminal, también puedes abrir una terminal en la carpeta del programa y ejecutar:

```bash
php -S 127.0.0.1:8000
```

Después abre `http://127.0.0.1:8000`. El proyecto está pensado para PHP 8.1 o posterior.

## Lo que confirmó la transcripción

- Primero debe existir el programa de lista simplemente enlazada.
- Se conserva esa versión y se prepara la variante doble.
- El nodo doble añade un enlace anterior; aquí se llama `antes`, equivalente a `anterior`.
- Antes y siguiente empiezan en null al construir un nodo doble.
- Se conectan el nuevo nodo y sus vecinos antes de actualizar cabeza o cola.
- Antes de eliminar un nodo doble, se conectan sus vecinos entre sí.
- Buscar, consultar y editar conservan la misma lógica.

La inserción en cualquier posición se conserva por la indicación escrita compartida antes de la transcripción. En el audio el profesor explica especialmente la inserción por cabeza y por cola.

## Estructura de archivos

| Archivo | Función |
| --- | --- |
| `index.php` | Formularios, botones y presentación de los resultados. |
| `src/Persona.php` | Datos y validación de una persona. |
| `src/NodoSimple.php` | Nodo con dato y siguiente. |
| `src/NodoDoble.php` | Conserva dato y siguiente, y añade antes. |
| `src/ListaBase.php` | Buscar, consultar, editar y recorrer, compartidos por ambas listas. |
| `src/ListaSimple.php` | Agregar y eliminar en la lista simple. |
| `src/ListaDoble.php` | Agregar y eliminar en la lista doble. |
| `src/cargar.php` | Carga las clases en el orden necesario. |
| `assets/estilos.css` | Presentación visual. |
| `assets/interfaz.js` | Mostrar el campo de posición y pedir confirmación. No contiene los algoritmos de listas. |
| `tests/probar.php` | Pruebas de los nodos, enlaces y operaciones de las dos versiones. |

Las dos versiones comparten ListaBase para que buscar, consultar y editar sean literalmente los mismos métodos. ListaSimple y ListaDoble implementan agregar y eliminar de manera distinta.

Los registros se guardan como objetos Nodo enlazados. No se usa un array ni un ArrayList como estructura principal. Los arreglos que aparecen en la sesión para avisos, los formularios y las pruebas tienen funciones auxiliares.

## Conceptos para estudiar

### Persona y nodo

Persona contiene identificación, nombres, apellidos, fecha y correo. El nodo envuelve a esa persona y contiene los enlaces.

En la simple, el nodo tiene siguiente. En la doble, el nodo también tiene antes. La cabeza es el primero y la cola es el último; si hay un solo nodo, ambas referencias señalan el mismo objeto.

### Buscar y consultar

Buscar coloca actual en cabeza. Compara la identificación y avanza con `$actual = $actual->siguiente`. Devuelve el nodo o null si no lo encuentra. Consultar utiliza esa búsqueda para devolver los datos de la persona.

### Editar

Editar busca el nodo y cambia únicamente su dato. Conserva el ID original y sus enlaces. Está implementado una sola vez en ListaBase.

### Agregar en la lista doble

1. Validar los datos, la identificación única y la posición.
2. Crear el nodo con antes y siguiente en null.
3. Localizar a sus vecinos.
4. Asignar nuevo->antes y nuevo->siguiente.
5. Hacer que los vecinos apunten al nuevo.
6. Después actualizar cabeza y cola, cuando corresponda.

Al agregar por la cabeza, el anterior del nuevo es null y el siguiente es la antigua cabeza. La antigua cabeza pasa a tener al nuevo como anterior. Después cabeza se actualiza al nuevo.

Al agregar por la cola, el anterior del nuevo es la antigua cola y su siguiente es null. La antigua cola pasa a tener al nuevo como siguiente. Después cola se actualiza al nuevo.

### Eliminar en la lista doble

Después de encontrar aux, se guardan sus vecinos:

```php
$antes = $aux->antes;
$despues = $aux->siguiente;
```

Se conectan entre sí si existen:

```php
if ($antes !== null) {
    $antes->siguiente = $aux->siguiente;
}
if ($despues !== null) {
    $despues->antes = $aux->antes;
}
```

Después se actualizan cabeza o cola cuando se elimina uno de los extremos. Por último se desconectan los enlaces del nodo retirado. Si era el único nodo, cabeza y cola quedan en null.

En PHP no se utiliza delete para destruir un nodo. Al perder las referencias que lo mantienen accesible, PHP puede recuperar su memoria; también dispone de recolección de ciclos. Asignar null a una variable auxiliar no destruye un objeto al que todavía apuntan otras variables o nodos.

## Uso de posiciones

Las posiciones comienzan en 1. Si hay 3 nodos, se puede insertar en las posiciones 1, 2, 3 o 4. La posición 1 inserta al inicio y la 4 al final. No se aceptan posiciones 0, negativas, decimales o superiores a cantidad + 1.

## Práctica guiada

1. En Lista simple, conserva los tres registros de ejemplo y agrega un ID 104 en la posición 2.
2. Comprueba que el recorrido sea 101, 104, 202, 303 y que cada nodo tenga solo siguiente.
3. Cambia a Lista doble. Sus datos son independientes: sigue teniendo los tres registros iniciales.
4. Agrega el ID 104 en la posición 2. Su antes debe ser 101 y su siguiente 202.
5. Edita el nombre del 104. Su ID y sus conexiones deben conservarse.
6. Elimina el 104. El siguiente del 101 vuelve a ser 202 y el antes del 202 vuelve a ser 101.
7. Elimina cabeza, cola y el único nodo restante. Ambos extremos deben quedar en null.
8. Comprueba que vaciar o cambiar una versión no altere la otra.

## Guardado de datos

Cada lista se conserva en la sesión de PHP del navegador. Las clases se cargan antes de session_start para recuperar correctamente los objetos y sus referencias. No se utiliza una base de datos. Los datos pueden desaparecer al vencer o eliminarse la sesión, o al cambiar de servidor o navegador.

## Pruebas

Desde la carpeta del programa, con PHP disponible en la terminal:

```bash
php tests/probar.php
```

No se requiere PHPUnit ni instalar paquetes. Las pruebas verifican las dos versiones, inserción intermedia y en extremos, identificaciones duplicadas, posiciones inválidas, eliminación de cabeza/cola/único nodo, edición sin cambiar referencias, búsqueda, recuperación de objetos y métodos compartidos.

## Trabajar con Codex

Abre la carpeta listas_php_codex en Codex o VS Code. Lee primero Persona.php, NodoSimple.php y ListaSimple.php. Después compara NodoDoble.php y ListaDoble.php.

Puedes usar esta instrucción:

> Conserva la lista simple y la doble como versiones independientes. Estudia src/ListaBase.php antes de cambiar nada. Buscar, consultar y editar deben seguir siendo compartidos. Cada nodo doble tiene antes y siguiente, inicialmente null. En agregar se conectan los vecinos antes de actualizar cabeza y cola. En eliminar se reconectan los vecinos antes de desconectar el nodo. Mantén la inserción por posición y ejecuta php tests/probar.php después de modificar los algoritmos. Explica cada cambio con palabras sencillas.
