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

public class MainActivity extends AppCompatActivity {
private EditText etNombre;
private EditText etNombreSala;
private EditText etCodigo;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.enableEdgeToEdge(getWindow());
        setContentView(R.layout.activity_main);

        etNombre = findViewById(R.id.etNombre);
        etNombreSala = findViewById(R.id.etNombreSala);
        etCodigo = findViewById(R.id.etCodigo);

        Button btnCrearSala = findViewById(R.id.btnCrearSala);

        Button btnEntrarSala = findViewById(R.id.btnEntrarSala);

        btnEntrarSala.setOnClickListener(v -> {
            String codigo = etCodigo.getText().toString().trim();
            String nombre = etNombre.getText().toString().trim();

            if(codigo.isEmpty() || codigo.length() != 8){
                etCodigo.setError("Debes ingresar un código");
                return;
            }

            if(nombre.isEmpty()){
                etNombre.setError("Ingresa tu nombre");
                return;
            }

            Intent intent = new Intent(MainActivity.this,SalaActivity.class);
            intent.putExtra("nombreSala", "Sala DJ Meet");
            intent.putExtra("codigo", codigo);
            intent.putExtra("rol", "Invitado");
            intent.putExtra("nombreParticipante", nombre);

            startActivity(intent);

        });


        btnCrearSala.setOnClickListener(v -> {
            String nombre = etNombre.getText().toString().trim();
            String nombreSala = etNombreSala.getText().toString().trim();

            if(nombre.isEmpty()){
                etNombre.setError("Ingresa tu nombre");
                return;
            }

            if(nombreSala.isEmpty()){
                etNombreSala.setError("Ingresa el nombre de la sala");
                return;
            }

            Intent intent = new Intent(MainActivity.this,SalaActivity.class);

            intent.putExtra("nombreSala",nombreSala);
            intent.putExtra("codigo","LOCAL001");
            intent.putExtra("nombreParticipante", nombre);

            intent.putExtra("rol","Anfitrión");

            startActivity(intent);



        });

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });
    }
}
