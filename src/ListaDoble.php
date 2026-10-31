<?php

class ListaDoble extends ListaBase
{
    public function agregar(Persona $persona, $posicion = null)
    {
        $posicion = $this->validarInsercion($persona, $posicion);
        $nuevo = new NodoDoble($persona);
        $antes = null;
        $despues = null;

        if ($posicion === $this->cantidad + 1) {
            $antes = $this->cola;
        } else {
            $despues = $this->nodoEnPosicion($posicion);
            $antes = $despues->antes;
        }

        // 1. El nuevo apunta a sus vecinos.
        $nuevo->antes = $antes;
        $nuevo->siguiente = $despues;

        // 2. Los vecinos apuntan al nuevo, si existen.
        if ($antes !== null) {
            $antes->siguiente = $nuevo;
        }
        if ($despues !== null) {
            $despues->antes = $nuevo;
        }

        // 3. Solo después de conectar se actualizan cabeza y cola.
        if ($antes === null) {
            $this->cabeza = $nuevo;
        }
        if ($despues === null) {
            $this->cola = $nuevo;
        }

        $this->cantidad++;
        return $nuevo;
    }

    public function eliminar($identificacion)
    {
        $aux = $this->buscar($identificacion);
        if ($aux === null) {
            throw new InvalidArgumentException('No se encontró el nodo que se quiere eliminar.');
        }
        $antes = $aux->antes;
        $despues = $aux->siguiente;

        // 1. Conectar los vecinos entre sí antes de retirar aux.
        if ($antes !== null) {
            $antes->siguiente = $aux->siguiente;
        }
        if ($despues !== null) {
            $despues->antes = $aux->antes;
        }

        // 2. Actualizar los extremos cuando se elimina cabeza o cola.
        if ($aux === $this->cabeza) {
            $this->cabeza = $despues;
        }
        if ($aux === $this->cola) {
            $this->cola = $antes;
        }

        // 3. Desconectar el nodo retirado. No se usa delete en PHP.
        $aux->antes = null;
        $aux->siguiente = null;
        $this->cantidad--;
        return $aux->dato;
    }
}
