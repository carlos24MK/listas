"use strict";

// Este archivo solo maneja los controles visuales. Las listas funcionan en PHP.
const modo = document.getElementById("modo-posicion");
const grupo = document.getElementById("posicion-grupo");
const posicion = document.getElementById("posicion");

function cambiarPosicion() {
    if (!modo) return;
    grupo.hidden = modo.value !== "posicion";
    posicion.disabled = modo.value !== "posicion";
}

if (modo) {
    modo.addEventListener("change", cambiarPosicion);
    cambiarPosicion();
}

const dialogo = document.getElementById("confirmacion");
let formularioPendiente = null;

document.querySelectorAll("form[data-confirmar]").forEach((formulario) => {
    formulario.addEventListener("submit", (evento) => {
        if (formulario.dataset.confirmado === "si" || !dialogo.showModal) return;
        evento.preventDefault();
        formularioPendiente = formulario;
        document.getElementById("confirmacion-texto").textContent = formulario.dataset.confirmar;
        dialogo.returnValue = "";
        dialogo.showModal();
        document.getElementById("confirmacion-cancelar").focus();
    });
});

dialogo.addEventListener("close", () => {
    if (dialogo.returnValue === "aceptar" && formularioPendiente) {
        formularioPendiente.dataset.confirmado = "si";
        formularioPendiente.requestSubmit();
    }
    formularioPendiente = null;
});
