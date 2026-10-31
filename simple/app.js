"use strict";

// La interfaz usa la lista de lista.js. Los registros no se guardan en un array.
let lista = new ListaEnlazada();
let seleccionado = null;
let idEnEdicion = null;
let accionConfirmada = null;
const CLAVE = "estructuras-ii-lista-simple-v1";
const $ = (id) => document.getElementById(id);

function elemento(etiqueta, clase, texto) {
    const nuevo = document.createElement(etiqueta);
    if (clase) nuevo.className = clase;
    if (texto !== undefined) nuevo.textContent = texto;
    return nuevo;
}

function referencia(nodo) {
    return nodo == null ? "null" : "#" + nodo.dato.identificacion;
}

function nombre(persona) {
    return persona.nombres + " " + persona.apellidos;
}

function mensaje(texto, tipo = "") {
    $("mensaje").className = "message" + (tipo ? " " + tipo : "");
    $("mensaje").textContent = texto;
}

function explicar(texto, ...pasos) {
    $("operacion-texto").textContent = texto;
    $("pasos").replaceChildren();
    for (const paso of pasos) {
        $("pasos").append(elemento("li", "", paso));
    }
}

// Se convierte la lista a un objeto de registros para guardarla en el navegador.
// Al cargarla se reconstruyen objetos Nodo y sus conexiones reales.
function guardarLocal() {
    const copia = {
        version: 1,
        cabeza: lista.cabeza === null ? null : lista.cabeza.dato.identificacion,
        nodos: Object.create(null)
    };
    lista.recorrer((nodo) => {
        copia.nodos[nodo.dato.identificacion] = {
            dato: nodo.dato,
            siguiente: nodo.siguiente === null ? null : nodo.siguiente.dato.identificacion
        };
    });
    try {
        localStorage.setItem(CLAVE, JSON.stringify(copia));
        $("estado-almacenamiento").textContent = "Los datos se guardan en este navegador.";
    } catch {
        $("estado-almacenamiento").textContent = "Guardado local no disponible: los datos duran esta sesión.";
    }
}

function recuperarLocal(texto) {
    const copia = JSON.parse(texto);
    if (!copia || copia.version !== 1 || !copia.nodos ||
        (copia.cabeza !== null && typeof copia.cabeza !== "string")) {
        throw new Error("Los datos guardados tienen un formato distinto.");
    }
    const recuperada = new ListaEnlazada();
    const visitados = new Set();
    let id = copia.cabeza;
    while (id !== null) {
        if (visitados.has(id) || visitados.size >= 5000 || !Object.hasOwn(copia.nodos, id)) {
            throw new Error("Las conexiones guardadas no son válidas.");
        }
        visitados.add(id);
        const registro = copia.nodos[id];
        const dato = registro.dato;
        if (!dato || dato.identificacion !== id ||
            (registro.siguiente !== null && typeof registro.siguiente !== "string")) {
            throw new Error("Hay un registro guardado que no se puede recuperar.");
        }
        recuperada.agregar(new Persona(dato.identificacion, dato.nombres, dato.apellidos, dato.fecha, dato.correo));
        id = registro.siguiente;
    }
    lista = recuperada;
}

function cargarEjemplo() {
    lista.vaciar();
    lista.agregar(new Persona("101", "Ana", "Torres", "2005-04-12", "ana@ejemplo.com"));
    lista.agregar(new Persona("202", "Luis", "Rojas", "2004-09-23", "luis@ejemplo.com"));
    lista.agregar(new Persona("303", "Tamara", "Franco", "2006-01-18", "tamara@ejemplo.com"));
    seleccionado = null;
}

function conector() {
    const caja = elemento("span", "connector");
    caja.setAttribute("aria-hidden", "true");
    // Este SVG es fijo: no se incorpora HTML escrito por el usuario.
    caja.innerHTML = '<svg viewBox="0 0 44 44"><path d="M4 22h34m-5-5 5 5-5 5"/></svg>';
    return caja;
}

function pintarCadena() {
    const cadena = $("cadena");
    cadena.replaceChildren();
    cadena.hidden = lista.cantidad === 0;
    $("vacio").hidden = lista.cantidad !== 0;
    if (lista.cantidad === 0) return;
    const inicio = elemento("span", "null-cap", "null");
    inicio.setAttribute("aria-hidden", "true");
    cadena.append(inicio);
    lista.recorrer((nodo, posicion) => {
        if (posicion > 1) cadena.append(conector());
        const persona = nodo.dato;
        const tarjeta = elemento("button", "node" + (seleccionado === persona.identificacion ? " selected" : ""));
        tarjeta.type = "button";
        tarjeta.id = "nodo-" + persona.identificacion;
        tarjeta.setAttribute("aria-pressed", String(seleccionado === persona.identificacion));
        tarjeta.setAttribute("aria-label", `Seleccionar ${nombre(persona)}, identificación ${persona.identificacion}, posición ${posicion}`);
        const encabezado = elemento("span", "node-top");
        encabezado.append(elemento("span", "node-position", "POS. " + String(posicion).padStart(2, "0")));
        if (nodo === lista.cabeza || nodo === lista.cola) {
            const etiqueta = nodo === lista.cabeza && nodo === lista.cola ? "CABEZA / COLA" : nodo === lista.cabeza ? "CABEZA" : "COLA";
            encabezado.append(elemento("span", "node-endpoint", etiqueta));
        }
        const datos = elemento("span", "node-data");
        datos.append(elemento("span", "node-id", "ID " + persona.identificacion), elemento("span", "node-name", nombre(persona)));
        const enlaces = elemento("span", "node-links");
        const anterior = elemento("span");
        anterior.append(elemento("small", "", "antes"), elemento("b", "", "—"));
        const siguiente = elemento("span");
        siguiente.append(elemento("small", "", "siguiente"), elemento("b", "", referencia(nodo.siguiente)));
        enlaces.append(anterior, siguiente);
        tarjeta.append(encabezado, datos, enlaces);
        tarjeta.addEventListener("click", () => seleccionar(persona.identificacion, true));
        cadena.append(tarjeta);
    });
    const fin = elemento("span", "null-cap", "null");
    fin.setAttribute("aria-hidden", "true");
    cadena.append(fin);
}

function pintarDetalle() {
    const nodo = seleccionado === null ? null : lista.buscar(seleccionado);
    $("editar").disabled = nodo === null;
    $("eliminar").disabled = nodo === null;
    $("detalle").hidden = nodo === null;
    $("detalle-vacio").hidden = nodo !== null;
    $("titulo-seleccion").textContent = nodo ? nombre(nodo.dato) : "Elige un nodo de la lista";
    if (nodo === null) return;
    $("detalle-id").textContent = nodo.dato.identificacion;
    $("detalle-correo").textContent = nodo.dato.correo;
    $("detalle-fecha").textContent = new Date(nodo.dato.fecha + "T12:00:00").toLocaleDateString("es-CO");
    $("detalle-antes").textContent = "—";
    $("detalle-actual").textContent = referencia(nodo);
    $("detalle-siguiente").textContent = referencia(nodo.siguiente);
}

function pintarTabla() {
    const cuerpo = $("tabla-cuerpo");
    cuerpo.replaceChildren();
    if (lista.cantidad === 0) {
        const fila = elemento("tr");
        const celda = elemento("td", "table-empty", "Todavía no hay nodos para recorrer.");
        celda.colSpan = 7;
        fila.append(celda);
        cuerpo.append(fila);
    }
    lista.recorrer((nodo, posicion) => {
        const fila = elemento("tr", seleccionado === nodo.dato.identificacion ? "selected" : "");
        fila.append(elemento("td", "mono", posicion), elemento("td", "mono", nodo.dato.identificacion), elemento("td", "", nombre(nodo.dato)), elemento("td", "", nodo.dato.correo), elemento("td", "mono", "—"), elemento("td", "mono", referencia(nodo.siguiente)));
        const accion = elemento("td");
        const boton = elemento("button", "text-button", "Seleccionar");
        boton.type = "button";
        boton.setAttribute("aria-label", "Seleccionar a " + nombre(nodo.dato));
        boton.addEventListener("click", () => seleccionar(nodo.dato.identificacion, true));
        accion.append(boton);
        fila.append(accion);
        cuerpo.append(fila);
    });
    $("tabla-total").textContent = lista.cantidad + (lista.cantidad === 1 ? " registro" : " registros");
}

function actualizarPosicion() {
    const modo = $("modo-posicion").value;
    const campo = $("posicion");
    campo.max = lista.cantidad + 1;
    campo.disabled = modo !== "posicion" || idEnEdicion !== null;
    $("posicion-grupo").hidden = modo !== "posicion";
    if (modo === "inicio") campo.value = 1;
    if (modo === "final") campo.value = lista.cantidad + 1;
    $("rango-posicion").textContent = `Posiciones disponibles: 1 a ${lista.cantidad + 1}. La última agrega al final.`;
}

function pintar() {
    $("cantidad").textContent = lista.cantidad;
    $("cabeza").textContent = referencia(lista.cabeza);
    $("cola").textContent = referencia(lista.cola);
    $("vaciar").disabled = lista.cantidad === 0;
    pintarCadena();
    pintarDetalle();
    pintarTabla();
    actualizarPosicion();
}

function seleccionar(id, enfocar = false) {
    seleccionado = id;
    pintar();
    const tarjeta = $("nodo-" + id);
    if (tarjeta) {
        tarjeta.scrollIntoView({ behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "instant" : "smooth", block: "nearest", inline: "center" });
        if (enfocar) tarjeta.focus({ preventScroll: true });
    }
}

function cancelarEdicion() {
    idEnEdicion = null;
    $("form-persona").reset();
    $("identificacion").disabled = false;
    $("ubicacion").disabled = false;
    $("ubicacion").hidden = false;
    $("cancelar-edicion").hidden = true;
    $("titulo-formulario").textContent = "Agregar nodo";
    $("modo-formulario").textContent = "NUEVO REGISTRO";
    $("guardar").textContent = "Agregar nodo +";
    $("id-ayuda").textContent = "Debe ser única dentro de la lista.";
    $("nota-formulario").textContent = "Primero se conectan los vecinos. Después se actualiza cabeza o cola.";
    actualizarPosicion();
}

$("form-persona").addEventListener("submit", (evento) => {
    evento.preventDefault();
    try {
        const persona = new Persona($("identificacion").value, $("nombres").value, $("apellidos").value, $("fecha").value, $("correo").value);
        if (idEnEdicion !== null) {
            const id = idEnEdicion;
            const nodo = lista.editar(id, persona);
            explicar(`Se actualizó la información del nodo #${id}.`, "Se buscó desde la cabeza hasta encontrar la identificación.", "actual.dato recibió los nuevos datos, conservando el ID.", `El enlace sigue siendo siguiente: ${referencia(nodo.siguiente)}.`);
            mensaje(`Cambios guardados en ${nombre(nodo.dato)}. Su posición y sus conexiones se conservaron.`);
        } else {
            actualizarPosicion();
            const posicion = Number($("posicion").value);
            const nodo = lista.agregar(persona, posicion);
            seleccionado = persona.identificacion;
            explicar(`Se agregó a ${nombre(persona)} en la posición ${posicion}.`, `nuevo.siguiente = ${referencia(nodo.siguiente)}.`, false ? `El siguiente de ${"—"} ahora es ${referencia(nodo)}.` : `cabeza ahora apunta a ${referencia(nodo)}.`, nodo.siguiente ? `El nuevo apunta a ${referencia(nodo.siguiente)}.` : `cola ahora apunta a ${referencia(nodo)}.`);
            mensaje(`Nodo #${persona.identificacion} agregado en la posición ${posicion}.`);
        }
        cancelarEdicion();
        guardarLocal();
        pintar();
    } catch (error) {
        mensaje(error.message, "error");
    }
});

$("modo-posicion").addEventListener("change", actualizarPosicion);
$("cancelar-edicion").addEventListener("click", cancelarEdicion);
$("primer-nodo").addEventListener("click", () => {
    cancelarEdicion();
    $("identificacion").focus();
});

$("form-buscar").addEventListener("submit", (evento) => {
    evento.preventDefault();
    const id = $("buscar-id").value.trim();
    const nodo = lista.buscar(id);
    if (nodo === null) {
        seleccionado = null;
        pintar();
        mensaje(`No se encontró un nodo con identificación ${id}.`, "warning");
        explicar("La búsqueda llegó al final de la lista.", "actual comenzó en la cabeza.", "Se avanzó con actual = actual.siguiente.", "actual llegó a null sin encontrar esa identificación.");
        return;
    }
    let posicion = 0;
    lista.recorrer((actual, numero) => {
        if (actual === nodo) posicion = numero;
    });
    seleccionar(id, true);
    mensaje(`Encontrado: ${nombre(nodo.dato)}, posición ${posicion}.`);
    explicar(`La búsqueda encontró el nodo #${id} en la posición ${posicion}.`, "actual comenzó en la cabeza.", "Se compararon las identificaciones siguiendo el enlace siguiente.", "Al encontrar el ID se terminó la búsqueda.");
});

$("editar").addEventListener("click", () => {
    const nodo = lista.buscar(seleccionado);
    if (nodo === null) return;
    idEnEdicion = nodo.dato.identificacion;
    $("identificacion").value = nodo.dato.identificacion;
    $("identificacion").disabled = true;
    $("nombres").value = nodo.dato.nombres;
    $("apellidos").value = nodo.dato.apellidos;
    $("fecha").value = nodo.dato.fecha;
    $("correo").value = nodo.dato.correo;
    $("ubicacion").hidden = true;
    $("ubicacion").disabled = true;
    $("cancelar-edicion").hidden = false;
    $("titulo-formulario").textContent = "Editar persona";
    $("modo-formulario").textContent = "EDITANDO NODO #" + idEnEdicion;
    $("guardar").textContent = "Guardar cambios";
    $("id-ayuda").textContent = "La identificación se conserva al editar.";
    $("nota-formulario").textContent = "Editar cambia los datos de la persona y conserva sus enlaces.";
    $("nombres").focus();
});

function confirmar(titulo, texto, boton, accion) {
    $("confirmacion-titulo").textContent = titulo;
    $("confirmacion-texto").textContent = texto;
    $("confirmacion-aceptar").textContent = boton;
    accionConfirmada = accion;
    $("confirmacion").returnValue = "";
    $("confirmacion").showModal();
    $("confirmacion-cancelar").focus();
}

$("confirmacion").addEventListener("close", () => {
    const accion = accionConfirmada;
    accionConfirmada = null;
    if ($("confirmacion").returnValue === "aceptar" && accion) accion();
});

$("eliminar").addEventListener("click", () => {
    const nodo = lista.buscar(seleccionado);
    if (nodo === null) return;
    const id = nodo.dato.identificacion;
    confirmar("¿Eliminar este nodo?", `Se retirará a ${nombre(nodo.dato)} (ID ${id}). Primero se reconectarán sus vecinos.`, "Eliminar nodo", () => {
        const anterior = "—";
        const siguiente = referencia(nodo.siguiente);
        lista.eliminar(id);
        if (idEnEdicion === id) cancelarEdicion();
        seleccionado = null;
        guardarLocal();
        pintar();
        mensaje(`Nodo #${id} eliminado. Las conexiones se actualizaron correctamente.`);
        explicar(`Se retiró el nodo #${id} después de reconectar sus vecinos.`, anterior !== "null" ? `El siguiente de ${anterior} ahora es ${siguiente}.` : `cabeza ahora apunta a ${siguiente}.`, siguiente !== "null" ? `El nodo siguiente sigue en la lista.` : `cola ahora apunta a ${anterior}.`, "Por último, los enlaces del nodo retirado quedaron en null.");
    });
});

$("vaciar").addEventListener("click", () => {
    confirmar("¿Vaciar la lista?", "Se retirarán todos los nodos de esta práctica. Podrás volver a cargar los datos de ejemplo.", "Vaciar lista", () => {
        lista.vaciar();
        seleccionado = null;
        cancelarEdicion();
        guardarLocal();
        pintar();
        mensaje("Lista vacía. Puedes agregar el primer nodo.");
        explicar("Se dejó la lista sin elementos.", "cabeza = null.", "cola = null.", "cantidad = 0.");
    });
});

function usarEjemplo() {
    cargarEjemplo();
    cancelarEdicion();
    guardarLocal();
    pintar();
    mensaje("Ejemplo cargado con tres personas ficticias. Prueba insertar un nodo en la posición 2.");
    explicar("Se conectaron tres nodos de ejemplo.", "Cabeza: #101 (Ana).", "Nodo intermedio: #202 (Luis).", "Cola: #303 (Tamara).");
}

$("ejemplo").addEventListener("click", () => {
    if (lista.cantidad === 0) {
        usarEjemplo();
    } else {
        confirmar("¿Cargar el ejemplo?", "Los registros actuales se reemplazarán por tres personas ficticias.", "Cargar ejemplo", usarEjemplo);
    }
});

$("codigo-nodo").textContent = Nodo.toString();
$("codigo-agregar").textContent = ListaEnlazada.prototype.agregar.toString();
$("codigo-eliminar").textContent = ListaEnlazada.prototype.eliminar.toString();

try {
    const guardado = localStorage.getItem(CLAVE);
    if (guardado === null) {
        usarEjemplo();
    } else {
        recuperarLocal(guardado);
        pintar();
        mensaje(`Lista recuperada: ${lista.cantidad} ${lista.cantidad === 1 ? "nodo" : "nodos"}.`);
        explicar("Se recuperaron los datos guardados en este navegador.", "Se crearon nuevamente los nodos.", "Se reconstruyeron sus enlaces siguiente.");
    }
} catch {
    pintar();
    mensaje("No fue posible recuperar datos del navegador. Puedes agregar nodos o cargar el ejemplo.", "warning");
    $("estado-almacenamiento").textContent = "Los datos actuales están en memoria; se intentará guardarlos al realizar cambios.";
}
