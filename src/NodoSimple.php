<?php

class NodoSimple
{
    public $dato;
    public $siguiente;

    public function __construct(Persona $dato)
    {
        $this->dato = $dato;
        $this->siguiente = null;
    }
}
