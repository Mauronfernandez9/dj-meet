const formulario = document.getElementById("form-crear-sala");

const boton = document.getElementById("boton-crear");

formulario.addEventListener('submit',async function(evento) {
    evento.preventDefault();
    boton.disabled = true;

    try{
        const respuesta = await fetch(formulario.action, {
            method:'POST',
            body:new FormData(formulario)
        });

        const datos = await respuesta.json();

        if(!respuesta.ok || !datos.codigo){
            alert(datos.error || "No se pudo crear la sala.");
            return;
        }

        window.location.href = 'sala.html?codigo=' + encodeURIComponent(datos.codigo);


    }
    catch (error){
        alert("No se pudo completar la operación.Intente nuevamente.");
    }
    finally{
        boton.disabled = false;
    }

    
});

// El segundo formulario registra al participante en una sala existente.
const formularioUnirse = document.getElementById('form-unirse-sala');
const botonUnirse = document.getElementById('boton-unirse');

formularioUnirse.addEventListener('submit', async function (evento) {
    evento.preventDefault();
    if (botonUnirse.disabled) {
        return;
    }
    botonUnirse.disabled = true;

    try {
        const respuesta = await fetch(formularioUnirse.action, {
            method: 'POST',
            body: new FormData(formularioUnirse)
        });
        const datos = await respuesta.json();

        if (!respuesta.ok || !datos || !datos.codigo) {
            alert((datos && datos.error) || 'No se pudo entrar a la sala.');
            return;
        }

        window.location.href = 'sala.html?codigo=' + encodeURIComponent(datos.codigo);
    } catch (error) {
        alert('No se pudo completar el ingreso. Intentá nuevamente.');
    } finally {
        botonUnirse.disabled = false;
    }
});
