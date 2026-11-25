package com.djmeet.app;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import org.json.JSONObject;

import java.util.HashMap;
import java.util.Locale;
import java.util.Map;

public class InicioActivity extends AppCompatActivity {

    private EditText etNombre;
    private EditText etNombreSala;
    private EditText etCodigo;
    private Button btnCrearSala;
    private Button btnEntrarSala;
    private ProgressBar progreso;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        etNombre = findViewById(R.id.etNombre);
        etNombreSala = findViewById(R.id.etNombreSala);
        etCodigo = findViewById(R.id.etCodigo);
        btnCrearSala = findViewById(R.id.btnCrearSala);
        btnEntrarSala = findViewById(R.id.btnEntrarSala);
        progreso = findViewById(R.id.progreso);

        btnCrearSala.setOnClickListener(v -> crearSala());
        btnEntrarSala.setOnClickListener(v -> entrarSala());
    }

    private void crearSala() {
        String nombre = etNombre.getText().toString().trim();
        String nombreSala = etNombreSala.getText().toString().trim();

        if (nombre.isEmpty()) {
            etNombre.setError("Ingresá tu nombre");
            return;
        }

        if (nombreSala.isEmpty()) {
            etNombreSala.setError("Ingresá el nombre de la sala");
            return;
        }

        Map<String, String> datos = new HashMap<>();
        datos.put("nombre_anfitrion", nombre);
        datos.put("nombre_sala", nombreSala);

        cambiarEspera(true);

        ApiClient.enviar("salas.php", "POST", datos, (estado, contenido) -> {
            cambiarEspera(false);

            try {
                JSONObject respuesta = new JSONObject(contenido);

                if (estado == 201) {
                    abrirSala(
                            respuesta.getInt("id"),
                            respuesta.getString("nombreSala"),
                            respuesta.getString("codigo"),
                            "Anfitrión"
                    );
                } else {
                    mostrarError(respuesta.optString("error", "No se pudo crear la sala"));
                }
            } catch (Exception error) {
                mostrarError("La API devolvió una respuesta inválida");
            }
        });
    }

    private void entrarSala() {
        String nombre = etNombre.getText().toString().trim();
        String codigo = etCodigo.getText().toString().trim().toUpperCase(Locale.ROOT);

        if (nombre.isEmpty()) {
            etNombre.setError("Ingresá tu nombre");
            return;
        }

        if (codigo.length() != 8) {
            etCodigo.setError("Ingresá un código de 8 caracteres");
            return;
        }

        Map<String, String> datos = new HashMap<>();
        datos.put("nombre", nombre);
        datos.put("codigo", codigo);

        cambiarEspera(true);

        ApiClient.enviar("participantes.php", "POST", datos, (estado, contenido) -> {
            cambiarEspera(false);

            try {
                JSONObject respuesta = new JSONObject(contenido);

                if (estado == 200 || estado == 201) {
                    abrirSala(
                            respuesta.getInt("id"),
                            respuesta.getString("nombre"),
                            respuesta.getString("codigo"),
                            respuesta.getString("rol")
                    );
                } else {
                    mostrarError(respuesta.optString("error", "No se pudo entrar a la sala"));
                }
            } catch (Exception error) {
                mostrarError("La API devolvió una respuesta inválida");
            }
        });
    }

    private void abrirSala(int salaId, String nombreSala, String codigo, String rol) {
        Intent intent = new Intent(this, SalaOnlineActivity.class);
        intent.putExtra("salaId", salaId);
        intent.putExtra("nombreSala", nombreSala);
        intent.putExtra("codigo", codigo);
        intent.putExtra("rol", rol);
        startActivity(intent);
    }

    private void cambiarEspera(boolean esperando) {
        progreso.setVisibility(esperando ? View.VISIBLE : View.GONE);
        btnCrearSala.setEnabled(!esperando);
        btnEntrarSala.setEnabled(!esperando);
    }

    private void mostrarError(String mensaje) {
        Toast.makeText(this, mensaje, Toast.LENGTH_LONG).show();
    }
}
