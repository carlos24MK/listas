<?php

class ListaDobleCircular extends ListaBase
{
    public function buscar($identificacion)
    {
        $id = trim((string) $identificacion);
        foreach ($this->recorrer() as $actual) {
            if ($actual->dato->identificacion === $id) {
                return $actual;
            }
        }
        return null;
    }

    public function recorrer()
    {
        $actual = $this->cabeza;
        // En el círculo no hay null entre nodos: detenerse después de una vuelta.
        for ($posicion = 1; $posicion <= $this->cantidad; $posicion++) {
            yield $posicion => $actual;
            $actual = $actual->siguiente;
        }
    }

    public function agregar(Persona $persona, $posicion = null)
    {
        $posicion = $this->validarInsercion($persona, $posicion);
        $nuevo = new NodoDoble($persona);
        if ($this->cantidad === 0) {
            $nuevo->antes = $nuevo;
            $nuevo->siguiente = $nuevo;
            $this->cabeza = $nuevo;
            $this->cola = $nuevo;
        } else {
            $despues = $posicion === $this->cantidad + 1
                ? $this->cabeza : $this->nodoEnPosicion($posicion);
            $antes = $despues->antes;
            // Primero conectar el nuevo y sus vecinos en ambos sentidos.
            $nuevo->antes = $antes;
            $nuevo->siguiente = $despues;
            $antes->siguiente = $nuevo;
            $despues->antes = $nuevo;
            // Después actualizar cabeza y cola cuando corresponda.
            if ($posicion === 1) $this->cabeza = $nuevo;
            if ($posicion === $this->cantidad + 1) $this->cola = $nuevo;
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
        if ($this->cantidad === 1) {
            $this->cabeza = null;
            $this->cola = null;
        } else {
            // Reconectar vecinos antes de retirar el nodo, también en los extremos.
            $aux->antes->siguiente = $aux->siguiente;
            $aux->siguiente->antes = $aux->antes;
            if ($aux === $this->cabeza) $this->cabeza = $aux->siguiente;
            if ($aux === $this->cola) $this->cola = $aux->antes;
        }
        $aux->antes = null;
        $aux->siguiente = null;
        $this->cantidad--;
        return $aux->dato;
    }
}
