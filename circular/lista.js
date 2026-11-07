"use strict";

// En Node reutilizamos las clases originales; en el navegador las carga ../lista.js.
const baseCircular = typeof module !== "undefined" && module.exports
    ? require("../lista.js") : { Persona, Nodo, ListaEnlazada };

class ListaDobleCircular extends baseCircular.ListaEnlazada {
    buscar(identificacion) {
        const id = String(identificacion).trim();
        let actual = this.cabeza;
        // Una vuelta completa: nunca esperamos a encontrar null dentro del círculo.
        for (let i = 0; i < this.cantidad; i++) {
            if (actual.dato.identificacion === id) return actual;
            actual = actual.siguiente;
        }
        return null;
    }

    consultar(identificacion) {
        const nodo = this.buscar(identificacion);
        return nodo === null ? null : nodo.dato;
    }

    agregar(persona, posicion = this.cantidad + 1) {
        if (!(persona instanceof baseCircular.Persona)) throw new Error("La persona no es válida.");
        if (!Number.isInteger(posicion) || posicion < 1 || posicion > this.cantidad + 1) {
            throw new Error(`La posición debe ser un entero entre 1 y ${this.cantidad + 1}.`);
        }
        if (this.buscar(persona.identificacion)) throw new Error("Ya existe un nodo con esa identificación.");
        const nuevo = new baseCircular.Nodo(persona);
        if (this.cantidad === 0) {
            // Con un solo nodo, ambos enlaces regresan al propio nodo.
            nuevo.antes = nuevo;
            nuevo.siguiente = nuevo;
            this.cabeza = nuevo;
            this.cola = nuevo;
        } else {
            const despues = posicion === this.cantidad + 1 ? this.cabeza : this.nodoEnPosicion(posicion);
            const antes = despues.antes;
            // Conectar los cuatro enlaces antes de actualizar los extremos.
            nuevo.antes = antes;
            nuevo.siguiente = despues;
            antes.siguiente = nuevo;
            despues.antes = nuevo;
            if (posicion === 1) this.cabeza = nuevo;
            if (posicion === this.cantidad + 1) this.cola = nuevo;
        }
        this.cantidad++;
        return nuevo;
    }

    eliminar(identificacion) {
        const aux = this.buscar(identificacion);
        if (aux === null) throw new Error("No se encontró el nodo que se quiere eliminar.");
        if (this.cantidad === 1) {
            this.cabeza = null;
            this.cola = null;
        } else {
            // Los vecinos se conectan entre sí, incluso al retirar un extremo.
            aux.antes.siguiente = aux.siguiente;
            aux.siguiente.antes = aux.antes;
            if (aux === this.cabeza) this.cabeza = aux.siguiente;
            if (aux === this.cola) this.cola = aux.antes;
        }
        aux.antes = null;
        aux.siguiente = null;
        this.cantidad--;
        return aux.dato;
    }

    recorrer(visitar) {
        let actual = this.cabeza;
        for (let posicion = 1; posicion <= this.cantidad; posicion++) {
            visitar(actual, posicion);
            actual = actual.siguiente;
        }
    }
}

if (typeof module !== "undefined" && module.exports) {
    module.exports = { ...baseCircular, ListaDobleCircular };
}
