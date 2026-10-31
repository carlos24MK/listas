<?php

// Estos métodos se comparten: buscar, consultar y editar funcionan igual
// en las dos versiones. La lista se recorre con una variable auxiliar.
abstract class ListaBase
{
    public $cabeza = null;
    public $cola = null;
    public $cantidad = 0;

    public function buscar($identificacion)
    {
        $idBuscado = trim((string) $identificacion);
        $actual = $this->cabeza;

        while ($actual !== null) {
            if ($actual->dato->identificacion === $idBuscado) {
                return $actual;
            }
            $actual = $actual->siguiente;
        }
        return null;
    }

    public function consultar($identificacion)
    {
        $actual = $this->buscar($identificacion);
        return $actual === null ? null : $actual->dato;
    }

    public function editar($identificacion, Persona $nuevosDatos)
    {
        $actual = $this->buscar($identificacion);
        if ($actual === null) {
            throw new InvalidArgumentException('No se encontró el nodo que se quiere editar.');
        }

        // Cambiar el dato no cambia el nodo ni sus conexiones.
        $actual->dato = new Persona(
            $actual->dato->identificacion,
            $nuevosDatos->nombres,
            $nuevosDatos->apellidos,
            $nuevosDatos->fecha,
            $nuevosDatos->correo
        );
        return $actual;
    }

    public function nodoEnPosicion($posicion)
    {
        if (!is_int($posicion) || $posicion < 1 || $posicion > $this->cantidad) {
            throw new InvalidArgumentException('Esa posición no existe en la lista.');
        }
        $actual = $this->cabeza;
        $numero = 1;
        while ($numero < $posicion) {
            $actual = $actual->siguiente;
            $numero++;
        }
        return $actual;
    }

    // yield permite recorrer los nodos sin convertirlos a un arreglo.
    public function recorrer()
    {
        $actual = $this->cabeza;
        $posicion = 1;
        while ($actual !== null) {
            yield $posicion => $actual;
            $actual = $actual->siguiente;
            $posicion++;
        }
    }

    public function vaciar()
    {
        $this->cabeza = null;
        $this->cola = null;
        $this->cantidad = 0;
    }

    protected function validarInsercion(Persona $persona, $posicion)
    {
        if ($posicion === null) {
            $posicion = $this->cantidad + 1;
        }
        if (!is_int($posicion) || $posicion < 1 || $posicion > $this->cantidad + 1) {
            throw new InvalidArgumentException('La posición debe ser un entero entre 1 y ' . ($this->cantidad + 1) . '.');
        }
        if ($this->buscar($persona->identificacion) !== null) {
            throw new InvalidArgumentException('Ya existe un nodo con esa identificación.');
        }
        return $posicion;
    }

    abstract public function agregar(Persona $persona, $posicion = null);
    abstract public function eliminar($identificacion);
}
