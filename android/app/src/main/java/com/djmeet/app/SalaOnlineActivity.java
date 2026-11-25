package com.djmeet.app;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.ListView;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;

public class SalaOnlineActivity extends AppCompatActivity {

    private static final int PEDIDO_NUEVA_PROPUESTA = 100;
    private final ArrayList<String> propuestas = new ArrayList<>();
    private final ArrayList<Integer> propuestaIds = new ArrayList<>();
    private final ArrayList<Boolean> puedeEliminar = new ArrayList<>();
    private ArrayAdapter<String> adaptador;
    private TextView tvMensaje;
    private ProgressBar progreso;
    private int salaId;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_sala);

        TextView tvNombreSala = findViewById(R.id.tvNombreSala);
        TextView tvCodigoSala = findViewById(R.id.tvCodigoSala);
        TextView tvRol = findViewById(R.id.tvRol);
        tvMensaje = findViewById(R.id.tvMensaje);
        progreso = findViewById(R.id.progreso);
        Button btnNuevaPropuesta = findViewById(R.id.btnNuevaPropuesta);
        ListView listaPropuestas = findViewById(R.id.listaPropuestas);

        salaId = getIntent().getIntExtra("salaId", 0);
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
            Intent intent = new Intent(this, NuevaCancionActivity.class);
            intent.putExtra("salaId", salaId);
            startActivityForResult(intent, PEDIDO_NUEVA_PROPUESTA);
        });

        listaPropuestas.setOnItemLongClickListener((parent, view, posicion, id) -> {
            if (!puedeEliminar.get(posicion)) {
                Toast.makeText(this, "Solo podés eliminar tus propuestas", Toast.LENGTH_SHORT).show();
                return true;
            }

            eliminarPropuesta(propuestaIds.get(posicion));
            return true;
        });

        cargarPropuestas();
    }

    private void cargarPropuestas() {
        cambiarEspera(true);

        ApiClient.enviar(
                "propuestas.php?salaId=" + salaId,
                "GET",
                null,
                (estado, contenido) -> {
                    cambiarEspera(false);

                    if (estado != 200) {
                        mostrarError(contenido, "No se pudieron cargar las propuestas");
                        return;
                    }

                    try {
                        JSONArray respuesta = new JSONArray(contenido);
                        propuestas.clear();
                        propuestaIds.clear();
                        puedeEliminar.clear();

                        for (int i = 0; i < respuesta.length(); i++) {
                            JSONObject propuesta = respuesta.getJSONObject(i);
                            String titulo = propuesta.getString("titulo");
                            String artista = propuesta.getString("artista");
                            int votos = propuesta.optInt("votos", 0);
                            String autor = propuesta.optString("propuesto_por", "");

                            propuestas.add(titulo + " - " + artista
                                    + "\n" + votos + " votos · " + autor);
                            propuestaIds.add(propuesta.getInt("id"));
                            puedeEliminar.add(propuesta.optBoolean("puede_eliminar", false));
                        }

                        adaptador.notifyDataSetChanged();
                        actualizarMensaje();
                    } catch (Exception error) {
                        Toast.makeText(this, "Respuesta inválida de la API", Toast.LENGTH_LONG).show();
                    }
                }
        );
    }

    private void eliminarPropuesta(int propuestaId) {
        cambiarEspera(true);

        ApiClient.enviar(
                "propuestas.php?id=" + propuestaId,
                "DELETE",
                null,
                (estado, contenido) -> {
                    cambiarEspera(false);

                    if (estado == 200) {
                        cargarPropuestas();
                    } else {
                        mostrarError(contenido, "No se pudo eliminar la propuesta");
                    }
                }
        );
    }

    private void mostrarError(String contenido, String mensajePredeterminado) {
        try {
            JSONObject respuesta = new JSONObject(contenido);
            Toast.makeText(
                    this,
                    respuesta.optString("error", mensajePredeterminado),
                    Toast.LENGTH_LONG
            ).show();
        } catch (Exception error) {
            Toast.makeText(this, mensajePredeterminado, Toast.LENGTH_LONG).show();
        }
    }

    private void cambiarEspera(boolean esperando) {
        progreso.setVisibility(esperando ? View.VISIBLE : View.GONE);
    }

    private void actualizarMensaje() {
        if (propuestas.isEmpty()) {
            tvMensaje.setText("Todavía no hay propuestas.");
        } else {
            tvMensaje.setText("Mantené presionada una propuesta propia para eliminarla.");
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode == PEDIDO_NUEVA_PROPUESTA && resultCode == RESULT_OK) {
            cargarPropuestas();
        }
    }
}
