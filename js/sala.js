const parametros = new URLSearchParams(window.location.search);
const codigo = parametros.get('codigo');

let salaId = null;
let rol = 'invitado';
let estado = '';
let cancionSeleccionada = null;
let siguienteId = null;
let videoActual = null;
let ocupado = false;

const botonEnviarPropuesta = document.getElementById('boton-proponer');
const formularioPropuesta = document.getElementById('form-proponer-cancion');
const botonReproducir = document.getElementById('boton-reproducir');
const botonSiguiente = document.getElementById('boton-siguiente');
const reproductor = document.getElementById('reproductor-youtube');

formularioPropuesta.addEventListener('submit', async function(evento){
    evento.preventDefault();

    if(salaId === null){
        alert('Primero debe cargarse la sala');
        return;
    }

    if(ocupado){
        return;
    }

    ocupado = true;
    botonEnviarPropuesta.disabled = true;
    const datosFormulario = new FormData(formularioPropuesta);
    datosFormulario.set('salaId', salaId);

    try{
        const respuesta = await fetch(formularioPropuesta.action, {
            method: 'POST',
            body: datosFormulario
        });
        const datos = await respuesta.json();

        if(!respuesta.ok){
            alert(datos.error || 'No se pudo guardar la propuesta');
            return;
        }

        formularioPropuesta.reset();
        await cargarPropuestas(salaId);
    }
    catch(error){
        alert('No se pudo guardar la propuesta');
    }
    finally{
        botonEnviarPropuesta.disabled = false;
        ocupado = false;
    }
});

async function cargarSala(){
    const tituloSala = document.getElementById('titulo-sala');
    const strongSala = document.getElementById('sala-codigo-strong');
    const estadoSala = document.getElementById('estado-sala');
    const rolSala = document.getElementById('rol-sala');

    botonReproducir.disabled = true;
    botonSiguiente.disabled = true;

    if(!codigo){
        tituloSala.textContent = 'Falta el código de la sala';
        estadoSala.textContent = 'Volvé al inicio';
        return;
    }

    ocupado = true;

    try{
        const respuesta = await fetch('api/salas.php?codigo=' + encodeURIComponent(codigo));
        const datos = await respuesta.json();

        if(!respuesta.ok || !datos.codigo){
            tituloSala.textContent = 'No se pudo cargar la sala';
            estadoSala.textContent = 'No disponible';
            alert(datos.error || 'No se pudo obtener la sala');
            return;
        }

        salaId = datos.id;
        rol = datos.rol;
        estado = datos.estado;
        strongSala.textContent = datos.codigo;
        tituloSala.textContent = datos.nombre;
        estadoSala.textContent = estado === 'abierta' ? 'Sala abierta' : 'Sala cerrada';
        rolSala.textContent = rol === 'anfitrion' ? 'Anfitrión' : 'Invitado';

        await cargarPropuestas(salaId);
    }
    catch(error){
        tituloSala.textContent = 'No se pudo cargar la sala';
        estadoSala.textContent = 'No disponible';
        alert('No se pudo cargar la sala');
    }
    finally{
        ocupado = false;
    }
}

async function cargarPropuestas(salaId){
    const mensaje = document.getElementById('mensaje-cola');

    try{
        const respuesta = await fetch('api/propuestas.php?salaId=' + encodeURIComponent(salaId));
        const datos = await respuesta.json();

        if(!respuesta.ok){
            mensaje.textContent = datos.error || 'No se pudieron cargar las propuestas';
            return false;
        }

        const lista = document.getElementById('lista-propuesta');
        const cantidad = document.getElementById('cantidad-propuestas');
        lista.textContent = '';
        mensaje.textContent = '';
        siguienteId = null;
        let seleccionada = null;
        let pendientes = 0;

        for(const propuesta of datos){
            if(propuesta.estado === 'reproduciendo'){
                seleccionada = propuesta;
                continue;
            }

            if(propuesta.estado !== 'pendiente'){
                continue;
            }

            pendientes++;
            if(siguienteId === null){
                siguienteId = propuesta.id;
            }

            const elemento = document.createElement('li');
            elemento.className = 'sala-cancion';

            const informacion = document.createElement('div');
            informacion.className = 'sala-datos-cancion';

            const titulo = document.createElement('h3');
            titulo.textContent = propuesta.titulo;

            const artista = document.createElement('p');
            artista.textContent = propuesta.artista;

            const autor = document.createElement('small');
            autor.textContent = 'La propone ' + propuesta.propuesto_por;

            informacion.appendChild(titulo);
            informacion.appendChild(artista);
            informacion.appendChild(autor);
            elemento.appendChild(informacion);

            const acciones = document.createElement('div');
            acciones.className = 'sala-acciones';

            const botonVotar = document.createElement('button');
            botonVotar.type = 'button';
            botonVotar.className = 'sala-votar';
            botonVotar.textContent = (propuesta.votada ? 'Votado · ' : 'Votar · ') + propuesta.votos;
            botonVotar.disabled = !propuesta.puede_votar;
            botonVotar.setAttribute('aria-pressed', propuesta.votada ? 'true' : 'false');
            botonVotar.addEventListener('click', function(){
                votarPropuesta(propuesta.id, botonVotar);
            });
            acciones.appendChild(botonVotar);

            if(propuesta.puede_seleccionar){
                const botonElegir = document.createElement('button');
                botonElegir.type = 'button';
                botonElegir.className = 'sala-elegir';
                botonElegir.textContent = 'Elegir';
                botonElegir.addEventListener('click', function(){
                    seleccionarPropuesta(propuesta.id);
                });
                acciones.appendChild(botonElegir);
            }

            if(propuesta.puede_eliminar){
                const botonEliminar = document.createElement('button');
                botonEliminar.type = 'button';
                botonEliminar.className = 'sala-eliminar';
                botonEliminar.textContent = 'Eliminar';

                botonEliminar.addEventListener('click', async function(){
                    if(ocupado){
                        return;
                    }

                    ocupado = true;
                    botonEliminar.disabled = true;

                    try{
                        const respuesta = await fetch('api/propuestas.php?id=' + encodeURIComponent(propuesta.id), {
                            method: 'DELETE'
                        });
                        const datos = await respuesta.json();

                        if(!respuesta.ok){
                            alert(datos.error || 'No se pudo eliminar la propuesta');
                            return;
                        }

                        await cargarPropuestas(salaId);
                    }
                    catch(error){
                        alert('No se pudo eliminar la propuesta');
                    }
                    finally{
                        botonEliminar.disabled = false;
                        ocupado = false;
                    }
                });

                acciones.appendChild(botonEliminar);
            }

            elemento.appendChild(acciones);
            lista.appendChild(elemento);
        }

        cantidad.textContent = 'Propuestas: ' + pendientes;
        if(pendientes === 0){
            mensaje.textContent = 'Todavía no hay canciones en espera. ¡Sumá un tema!';
        }

        mostrarSeleccionada(seleccionada);
        return true;
    }
    catch(error){
        console.error(error);
        mensaje.textContent = 'No se pudo actualizar la cola. Se volverá a intentar.';
        return false;
    }
}

async function votarPropuesta(propuestaId, boton){
    if(ocupado){
        return;
    }

    ocupado = true;
    boton.disabled = true;
    const datosFormulario = new FormData();
    datosFormulario.set('propuesta_id', propuestaId);

    try{
        const respuesta = await fetch('api/votos.php', {
            method: 'POST',
            body: datosFormulario
        });
        const datos = await respuesta.json();

        if(!respuesta.ok){
            alert(datos.error || 'No se pudo guardar el voto');
        }

        await cargarPropuestas(salaId);
    }
    catch(error){
        alert('No se pudo guardar el voto');
    }
    finally{
        boton.disabled = false;
        ocupado = false;
    }
}

async function seleccionarPropuesta(propuestaId){
    if(ocupado || propuestaId === null){
        return false;
    }

    ocupado = true;

    try{
        const respuesta = await fetch('api/propuestas.php?salaId=' + encodeURIComponent(salaId) + '&id=' + encodeURIComponent(propuestaId), {
            method: 'PUT'
        });
        const datos = await respuesta.json();

        if(!respuesta.ok){
            alert(datos.error || 'No se pudo seleccionar la canción');
            await cargarPropuestas(salaId);
            return false;
        }

        const actualizada = await cargarPropuestas(salaId);
        return actualizada;
    }
    catch(error){
        alert('No se pudo seleccionar la canción');
        return false;
    }
    finally{
        ocupado = false;
    }
}

function mostrarSeleccionada(propuesta){
    const titulo = document.getElementById('titulo-seleccionada');
    const artista = document.getElementById('artista-seleccionada');
    const autor = document.getElementById('autor-seleccionada');
    const espera = document.getElementById('vista-espera');
    const mensaje = document.getElementById('mensaje-reproductor');
    const enlace = document.getElementById('enlace-youtube');

    cancionSeleccionada = propuesta;
    botonReproducir.disabled = rol !== 'anfitrion' || estado !== 'abierta' || (propuesta === null && siguienteId === null);
    botonSiguiente.disabled = rol !== 'anfitrion' || estado !== 'abierta' || siguienteId === null;

    if(propuesta === null){
        titulo.textContent = 'Todavía no hay una canción seleccionada';
        artista.textContent = 'El anfitrión puede elegir una de la cola.';
        autor.textContent = '';
    }else{
        titulo.textContent = propuesta.titulo;
        artista.textContent = propuesta.artista;
        autor.textContent = 'Propuesta por ' + propuesta.propuesto_por;
    }

    if(propuesta === null || rol !== 'anfitrion'){
        reproductor.hidden = true;
        reproductor.removeAttribute('src');
        videoActual = null;
        espera.hidden = false;
        enlace.hidden = true;
        mensaje.textContent = rol === 'anfitrion' ? 'Elegí un tema de la cola para comenzar.' : 'La música suena en el equipo del anfitrión.';
        return;
    }

    espera.hidden = true;
    reproductor.hidden = false;
    enlace.hidden = false;
    enlace.href = 'https://www.youtube.com/watch?v=' + encodeURIComponent(propuesta.youtube_id);
    mensaje.textContent = 'Usá los controles del video. Si no está disponible acá, podés abrirlo en YouTube.';

    if(videoActual !== propuesta.id){
        reproductor.src = 'https://www.youtube.com/embed/' + encodeURIComponent(propuesta.youtube_id) + '?playsinline=1';
        videoActual = propuesta.id;
    }
}

botonReproducir.addEventListener('click', async function(){
    if(ocupado || rol !== 'anfitrion' || estado !== 'abierta'){
        return;
    }

    if(cancionSeleccionada === null){
        const seleccionada = await seleccionarPropuesta(siguienteId);
        if(!seleccionada){
            return;
        }
    }

    if(cancionSeleccionada !== null){
        reproductor.src = 'https://www.youtube.com/embed/' + encodeURIComponent(cancionSeleccionada.youtube_id) + '?autoplay=1&playsinline=1';
    }
});

botonSiguiente.addEventListener('click', async function(){
    if(rol !== 'anfitrion' || estado !== 'abierta'){
        return;
    }

    const seleccionada = await seleccionarPropuesta(siguienteId);
    if(seleccionada && cancionSeleccionada !== null){
        reproductor.src = 'https://www.youtube.com/embed/' + encodeURIComponent(cancionSeleccionada.youtube_id) + '?autoplay=1&playsinline=1';
    }
});

cargarSala();

setInterval(async function(){
    if(salaId !== null && !ocupado && !document.hidden){
        ocupado = true;
        try{
            await cargarPropuestas(salaId);
        }
        finally{
            ocupado = false;
        }
    }
}, 5000);
