package com.djmeet.app;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;

public class NuevaPropuestaActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.enableEdgeToEdge(getWindow());
        setContentView(R.layout.activity_nueva_propuesta);

        EditText etYoutube = findViewById(R.id.etYoutube);
        EditText etTitulo = findViewById(R.id.etTitulo);
        EditText etArtista = findViewById(R.id.etArtista);
        Button btnGuardar = findViewById(R.id.btnGuardarPropuesta);

        btnGuardar.setOnClickListener(v -> {
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

            Intent resultado = new Intent();
            resultado.putExtra("youtube", youtube);
            resultado.putExtra("titulo", titulo);
            resultado.putExtra("artista", artista);

            setResult(RESULT_OK, resultado);
            finish();
        });

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });
    }
}
