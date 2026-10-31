<?php

class Persona
{
    public $identificacion;
    public $nombres;
    public $apellidos;
    public $fecha;
    public $correo;

    public function __construct($identificacion, $nombres, $apellidos, $fecha, $correo)
    {
        $this->identificacion = trim((string) $identificacion);
        $this->nombres = trim((string) $nombres);
        $this->apellidos = trim((string) $apellidos);
        $this->fecha = trim((string) $fecha);
        $this->correo = trim((string) $correo);

        if (!preg_match('/^[0-9]{1,20}$/', $this->identificacion)) {
            throw new InvalidArgumentException('La identificación debe tener entre 1 y 20 dígitos.');
        }
        if (!preg_match('/^.{1,60}$/us', $this->nombres)) {
            throw new InvalidArgumentException('Escribe un nombre válido, de hasta 60 caracteres.');
        }
        if (!preg_match('/^.{1,60}$/us', $this->apellidos)) {
            throw new InvalidArgumentException('Escribe unos apellidos válidos, de hasta 60 caracteres.');
        }
        $fechaValida = DateTimeImmutable::createFromFormat('!Y-m-d', $this->fecha);
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $this->fecha) {
            throw new InvalidArgumentException('Selecciona una fecha válida.');
        }
        if (strlen($this->correo) > 254 || !filter_var($this->correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Escribe un correo válido.');
        }
    }
}
