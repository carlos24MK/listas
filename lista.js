"use strict";

// Datos de una persona. La identificación se conserva al editar.
class Persona {
    constructor(identificacion, nombres, apellidos, fecha, correo) {
        this.identificacion = String(identificacion ?? "").trim();
        this.nombres = String(nombres ?? "").trim();
        this.apellidos = String(apellidos ?? "").trim();
        this.fecha = String(fecha ?? "").trim();
        this.correo = String(correo ?? "").trim();

        if (!/^\d{1,20}$/.test(this.identificacion)) {
            throw new Error("La identificación debe tener entre 1 y 20 dígitos.");
        }
        if (!this.nombres || this.nombres.length > 60) {
            throw new Error("Escribe un nombre de hasta 60 caracteres.");
        }
        if (!this.apellidos || this.apellidos.length > 60) {
            throw new Error("Escribe los apellidos, hasta 60 caracteres.");
        }
        const fechaValida = new Date(this.fecha + "T12:00:00Z");
        if (!/^\d{4}-\d{2}-\d{2}$/.test(this.fecha) ||
            Number.isNaN(fechaValida.getTime()) ||
            fechaValida.toISOString().slice(0, 10) !== this.fecha) {
            throw new Error("Selecciona una fecha válida.");
        }
        if (this.correo.length > 254 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.correo)) {
            throw new Error("Escribe un correo válido, por ejemplo ana@ejemplo.com.");
        }
    }
}

class Nodo {
    constructor(dato) {
        this.dato = dato;
        this.antes = null;
        this.siguiente = null;
    }
}

class ListaEnlazada {
    constructor() {
        this.cabeza = null;
        this.cola = null;
        this.cantidad = 0;
    }

    buscar(identificacion) {
        const idBuscado = String(identificacion).trim();
        let actual = this.cabeza;

        while (actual !== null) {
            if (actual.dato.identificacion === idBuscado) {
                return actual;
            }
            actual = actual.siguiente;
        }
        return null;
    }

    // Las posiciones se cuentan desde 1.
    nodoEnPosicion(posicion) {
        if (!Number.isInteger(posicion) || posicion < 1 || posicion > this.cantidad) {
            throw new Error("Esa posición no existe en la lista.");
        }
        let actual = this.cabeza;
        let contador = 1;
        while (contador < posicion) {
            actual = actual.siguiente;
            contador++;
        }
        return actual;
    }

    agregar(persona, posicion = this.cantidad + 1) {
        if (!(persona instanceof Persona)) {
            throw new Error("El nodo debe contener una persona válida.");
        }
        if (!Number.isInteger(posicion) || posicion < 1 || posicion > this.cantidad + 1) {
            throw new Error(`La posición debe ser un entero entre 1 y ${this.cantidad + 1}.`);
        }
        if (this.buscar(persona.identificacion) !== null) {
            throw new Error("Ya existe un nodo con esa identificación.");
        }

        const nuevo = new Nodo(persona);
        let antes = null;
        let siguiente = null;

        if (posicion === this.cantidad + 1) {
            // Al final, el vecino anterior es la cola actual.
            antes = this.cola;
        } else {
            siguiente = this.nodoEnPosicion(posicion);
            antes = siguiente.antes;
        }

        // Primero conectamos el nodo nuevo con los vecinos que ya existen.
        nuevo.antes = antes;
        nuevo.siguiente = siguiente;

        // Después actualizamos las conexiones de los vecinos.
        if (antes !== null) {
            antes.siguiente = nuevo;
        } else {
            this.cabeza = nuevo;
        }
        if (siguiente !== null) {
            siguiente.antes = nuevo;
        } else {
            this.cola = nuevo;
        }

        this.cantidad++;
        return nuevo;
    }

    editar(identificacion, nuevosDatos) {
        const actual = this.buscar(identificacion);
        if (actual === null) {
            throw new Error("No se encontró el nodo que se quiere editar.");
        }
        // Validamos antes de cambiar el dato; mantenemos el ID original.
        const persona = new Persona(
            actual.dato.identificacion,
            nuevosDatos.nombres,
            nuevosDatos.apellidos,
            nuevosDatos.fecha,
            nuevosDatos.correo
        );
        actual.dato = persona;
        return actual;
    }

    eliminar(identificacion) {
        const aux = this.buscar(identificacion);
        if (aux === null) {
            throw new Error("No se encontró el nodo que se quiere eliminar.");
        }
        const antes = aux.antes;
        const despues = aux.siguiente;

        // Reconectar los vecinos antes de desconectar el nodo.
        if (antes !== null) {
            antes.siguiente = despues;
        } else {
            this.cabeza = despues;
        }
        if (despues !== null) {
            despues.antes = antes;
        } else {
            this.cola = antes;
        }

        aux.antes = null;
        aux.siguiente = null;
        this.cantidad--;
        return aux.dato;
    }

    recorrer(visitar) {
        let actual = this.cabeza;
        let posicion = 1;
        while (actual !== null) {
            visitar(actual, posicion);
            actual = actual.siguiente;
            posicion++;
        }
    }

    vaciar() {
        this.cabeza = null;
        this.cola = null;
        this.cantidad = 0;
    }
}

// Permite probar las mismas clases con Node.js, sin dependencias.
if (typeof module !== "undefined" && module.exports) {
    module.exports = { Persona, Nodo, ListaEnlazada };
}
