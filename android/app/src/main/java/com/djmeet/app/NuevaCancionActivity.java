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
import java.util.Map;

public class NuevaCancionActivity extends AppCompatActivity {

    private EditText etYoutube;
    private EditText etTitulo;
    private EditText etArtista;
    private Button btnGuardar;
    private ProgressBar progreso;
    private int salaId;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_nueva_propuesta);

        etYoutube = findViewById(R.id.etYoutube);
        etTitulo = findViewById(R.id.etTitulo);
        etArtista = findViewById(R.id.etArtista);
        btnGuardar = findViewById(R.id.btnGuardarPropuesta);
        progreso = findViewById(R.id.progreso);
        salaId = getIntent().getIntExtra("salaId", 0);

        btnGuardar.setOnClickListener(v -> guardarPropuesta());
    }

    private void guardarPropuesta() {
        String youtube = etYoutube.getText().toString().trim();
        String titulo = etTitulo.getText().toString().trim();
        String artista = etArtista.getText().toString().trim();

        if (youtube.isEmpty()) {
            etYoutube.setError("Ingresá un enlace de YouTube");
            return;
        }

        if (titulo.isEmpty()) {
            etTitulo.setError("Ingresá el título");
            return;
        }

        if (artista.isEmpty()) {
            etArtista.setError("Ingresá el artista");
            return;
        }

        Map<String, String> datos = new HashMap<>();
        datos.put("salaId", String.valueOf(salaId));
        datos.put("youtube_url", youtube);
        datos.put("titulo", titulo);
        datos.put("artista", artista);

        cambiarEspera(true);

        ApiClient.enviar("propuestas.php", "POST", datos, (estado, contenido) -> {
            cambiarEspera(false);

            if (estado == 201) {
                setResult(RESULT_OK, new Intent());
                finish();
                return;
            }

            try {
                JSONObject respuesta = new JSONObject(contenido);
                Toast.makeText(
                        this,
                        respuesta.optString("error", "No se pudo guardar la propuesta"),
                        Toast.LENGTH_LONG
                ).show();
            } catch (Exception error) {
                Toast.makeText(this, "No se pudo guardar la propuesta", Toast.LENGTH_LONG).show();
            }
        });
    }

    private void cambiarEspera(boolean esperando) {
        progreso.setVisibility(esperando ? View.VISIBLE : View.GONE);
        btnGuardar.setEnabled(!esperando);
    }
}
