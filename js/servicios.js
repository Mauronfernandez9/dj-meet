const formularioBusqueda = document.getElementById('form-buscar-cancion');
const botonBuscar = document.getElementById('boton-buscar-cancion');
const mensajeBusqueda = document.getElementById('mensaje-busqueda');
const resultadosBusqueda = document.getElementById('resultados-busqueda');

formularioBusqueda.addEventListener('submit', async function(evento){
    evento.preventDefault();

    if(salaId === null){
        mensajeBusqueda.textContent = 'Primero debe cargarse la sala.';
        return;
    }

    botonBuscar.disabled = true;
    resultadosBusqueda.textContent = '';
    mensajeBusqueda.textContent = 'Buscando canciones...';
    const busqueda = document.getElementById('busqueda-cancion').value.trim();

    try{
        const respuesta = await fetch('api/servicios.php?salaId=' + encodeURIComponent(salaId) +
            '&busqueda=' + encodeURIComponent(busqueda));
        const datos = await respuesta.json();

        if(!respuesta.ok){
            mensajeBusqueda.textContent = datos.error || 'No se pudo buscar. Podés completar los datos a mano.';
            return;
        }

        mensajeBusqueda.textContent = datos.resultados.length === 0 ?
            'No encontramos resultados. Probá otra búsqueda o completá los datos a mano.' :
            'Elegí el resultado que coincide con tu video.';

        for(const cancion of datos.resultados){
            const elemento = document.createElement('li');
            const nombre = document.createElement('p');
            nombre.textContent = cancion.titulo + ' — ' + cancion.artista;

            const botonUsar = document.createElement('button');
            botonUsar.type = 'button';
            botonUsar.className = 'sala-elegir';
            botonUsar.textContent = 'Usar datos';
            botonUsar.addEventListener('click', function(){
                document.getElementById('titulo-cancion').value = cancion.titulo;
                document.getElementById('artista-cancion').value = cancion.artista;
                mensajeBusqueda.textContent = 'Datos completados. Revisalos y pegá el enlace de YouTube.';
                document.getElementById('enlace-cancion').focus();
            });

            elemento.appendChild(nombre);
            elemento.appendChild(botonUsar);

            if(cancion.enlace){
                const enlace = document.createElement('a');
                enlace.href = cancion.enlace;
                enlace.target = '_blank';
                enlace.rel = 'noopener noreferrer';
                enlace.className = 'sala-enlace';
                enlace.textContent = 'Ver en Apple Music';
                elemento.appendChild(enlace);
            }

            resultadosBusqueda.appendChild(elemento);
        }
    }
    catch(error){
        mensajeBusqueda.textContent = 'No se pudo buscar. Podés escribir título y artista a mano.';
    }
    finally{
        botonBuscar.disabled = false;
    }
});
