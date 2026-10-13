const parametros = new URLSearchParams(window.location.search);
const codigo = parametros.get('codigo');

async function cargarSala() {
    const tituloSala = document.getElementById('titulo-sala');
    const strongSala = document.getElementById('sala-codigo-strong');
    const estadoSala = document.getElementById('estado-sala');
    const rolSala = document.getElementById('rol-sala');
    const botonReproducir = document.getElementById('boton-reproducir');
    const botonSiguiente = document.getElementById('boton-siguiente');

    botonReproducir.disabled = true;
    botonSiguiente.disabled = true;

    if (!codigo) {
        tituloSala.textContent = 'Falta el código de la sala';
        estadoSala.textContent = 'Volvé al inicio';
        alert('Falta el código de la sala. Entrá desde el inicio.');
        return;
    }

    try {
        const respuesta = await fetch('api/salas.php?codigo=' + encodeURIComponent(codigo));
        const datos = await respuesta.json();

        if (!respuesta.ok || !datos.codigo) {
            tituloSala.textContent = 'No se pudo cargar la sala';
            estadoSala.textContent = 'No disponible';
            alert(datos.error || 'No se pudo obtener la sala.');
            return;
        }

        // Mostrar los datos que llegaron desde PHP.
        strongSala.textContent = datos.codigo;
        tituloSala.textContent = datos.nombre;

        if (datos.estado === 'abierta') {
            estadoSala.textContent = 'Sala abierta';
        } else {
            estadoSala.textContent = 'Sala cerrada';
        }

        if (datos.rol === 'anfitrion') {
            rolSala.textContent = 'Anfitrión';
        } else {
            rolSala.textContent = 'Invitado';
        }

        // Los controles se habilitan para el anfitrión de una sala abierta.
        if (datos.rol === 'anfitrion' && datos.estado === 'abierta') {
            botonReproducir.disabled = false;
            botonSiguiente.disabled = false;
        }
    } catch (error) {
        tituloSala.textContent = 'No se pudo cargar la sala';
        estadoSala.textContent = 'No disponible';
        alert('No se pudo completar la operación. Intentá nuevamente.');
    }
}

cargarSala();
