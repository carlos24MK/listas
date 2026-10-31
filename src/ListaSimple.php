<?php

class ListaSimple extends ListaBase
{
    public function agregar(Persona $persona, $posicion = null)
    {
        $posicion = $this->validarInsercion($persona, $posicion);
        $nuevo = new NodoSimple($persona);

        if ($this->cabeza === null) {
            $this->cabeza = $nuevo;
            $this->cola = $nuevo;
        } elseif ($posicion === 1) {
            $nuevo->siguiente = $this->cabeza;
            $this->cabeza = $nuevo;
        } elseif ($posicion === $this->cantidad + 1) {
            $this->cola->siguiente = $nuevo;
            $this->cola = $nuevo;
        } else {
            $antes = $this->nodoEnPosicion($posicion - 1);
            $nuevo->siguiente = $antes->siguiente;
            $antes->siguiente = $nuevo;
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

        if ($aux === $this->cabeza) {
            $this->cabeza = $aux->siguiente;
            if ($aux === $this->cola) {
                $this->cola = null;
            }
        } else {
            // antes es una variable auxiliar; no es un campo del nodo simple.
            $antes = $this->cabeza;
            while ($antes->siguiente !== $aux) {
                $antes = $antes->siguiente;
            }
            $antes->siguiente = $aux->siguiente;
            if ($aux === $this->cola) {
                $this->cola = $antes;
            }
        }

        $aux->siguiente = null;
        $this->cantidad--;
        return $aux->dato;
    }
}
