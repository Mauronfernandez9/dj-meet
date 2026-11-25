package com.djmeet.app;

import android.content.Intent;
import android.os.Bundle;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.ListView;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;

import java.util.ArrayList;

public class SalaActivity extends AppCompatActivity {

    private static final int PEDIDO_NUEVA_PROPUESTA = 100;
    private final ArrayList<String> propuestas = new ArrayList<>();
    private ArrayAdapter<String> adaptador;
    private TextView tvMensaje;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.enableEdgeToEdge(getWindow());
        setContentView(R.layout.activity_sala);

        TextView tvNombreSala = findViewById(R.id.tvNombreSala);
        TextView tvCodigoSala = findViewById(R.id.tvCodigoSala);
        TextView tvRol = findViewById(R.id.tvRol);
        tvMensaje = findViewById(R.id.tvMensaje);
        Button btnNuevaPropuesta = findViewById(R.id.btnNuevaPropuesta);
        ListView listaPropuestas = findViewById(R.id.listaPropuestas);

        String nombreSala = getIntent().getStringExtra("nombreSala");

        String codigo = getIntent().getStringExtra("codigo");

        String rol = getIntent().getStringExtra("rol");


        tvNombreSala.setText(nombreSala);
        tvCodigoSala.setText("Código: " + codigo);
        tvRol.setText("Rol: " + rol);








        adaptador = new ArrayAdapter<>(
                this,
                android.R.layout.simple_list_item_1,
                propuestas
        );
        listaPropuestas.setAdapter(adaptador);

        btnNuevaPropuesta.setOnClickListener(v -> {
            Intent intent = new Intent(SalaActivity.this, NuevaPropuestaActivity.class);
            startActivityForResult(intent, PEDIDO_NUEVA_PROPUESTA);
        });

        listaPropuestas.setOnItemLongClickListener((parent, view, posicion, id) -> {
            propuestas.remove(posicion);
            adaptador.notifyDataSetChanged();
            actualizarMensaje();

            return true;
        });

        actualizarMensaje();

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode == PEDIDO_NUEVA_PROPUESTA
                && resultCode == RESULT_OK
                && data != null) {
            String titulo = data.getStringExtra("titulo");
            String artista = data.getStringExtra("artista");
            propuestas.add(titulo + " - " + artista);
            adaptador.notifyDataSetChanged();
            actualizarMensaje();
        }
    }

    private void actualizarMensaje() {
        if (propuestas.isEmpty()) {
            tvMensaje.setText("Todavía no se cargaron canciones.");
        } else {
            tvMensaje.setText("Mantené presionada una canción para eliminarla.");
        }
    }
}
