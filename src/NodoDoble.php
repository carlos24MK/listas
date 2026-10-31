<?php

// La variante doble conserva dato y siguiente, y añade antes.
class NodoDoble extends NodoSimple
{
    public $antes;

    public function __construct(Persona $dato)
    {
        parent::__construct($dato);
        $this->antes = null;
    }
}
