package com.djmeet.app;

import android.os.Handler;
import android.os.Looper;

import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.CookieHandler;
import java.net.CookieManager;
import java.net.CookiePolicy;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.Map;

public class ApiClient {

    public static final String BASE_URL = "https://dj-meet-production-efe3c1e7.up.railway.app/api/";
    private static final Handler handler = new Handler(Looper.getMainLooper());

    static {
        CookieManager cookieManager = new CookieManager();
        cookieManager.setCookiePolicy(CookiePolicy.ACCEPT_ALL);
        CookieHandler.setDefault(cookieManager);
    }

    public interface Respuesta {
        void terminar(int estado, String contenido);
    }

    public static void enviar(String ruta, String metodo,
                              Map<String, String> datos, Respuesta respuesta) {
        new Thread(() -> {
            int estado = 0;
            String contenido;
            HttpURLConnection conexion = null;

            try {
                URL url = new URL(BASE_URL + ruta);
                conexion = (HttpURLConnection) url.openConnection();
                conexion.setRequestMethod(metodo);
                conexion.setConnectTimeout(10000);
                conexion.setReadTimeout(10000);
                conexion.setRequestProperty("Accept", "application/json");

                if (datos != null && !datos.isEmpty() && !metodo.equals("GET")) {
                    conexion.setDoOutput(true);
                    conexion.setRequestProperty(
                            "Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
                    String formulario = crearFormulario(datos);
                    try (OutputStream salida = conexion.getOutputStream()) {
                        salida.write(formulario.getBytes(StandardCharsets.UTF_8));
                    }
                }

                estado = conexion.getResponseCode();
                InputStream entrada = estado >= 400
                        ? conexion.getErrorStream()
                        : conexion.getInputStream();
                contenido = leer(entrada);
            } catch (Exception error) {
                contenido = "{\"error\":\"No se pudo conectar con la API\"}";
            } finally {
                if (conexion != null) {
                    conexion.disconnect();
                }
            }

            int estadoFinal = estado;
            String contenidoFinal = contenido;
            handler.post(() -> respuesta.terminar(estadoFinal, contenidoFinal));
        }).start();
    }

    private static String crearFormulario(Map<String, String> datos) throws Exception {
        StringBuilder formulario = new StringBuilder();
        for (Map.Entry<String, String> dato : datos.entrySet()) {
            if (formulario.length() > 0) {
                formulario.append('&');
            }
            formulario.append(URLEncoder.encode(dato.getKey(), "UTF-8"));
            formulario.append('=');
            formulario.append(URLEncoder.encode(dato.getValue(), "UTF-8"));
        }
        return formulario.toString();
    }

    private static String leer(InputStream entrada) throws Exception {
        if (entrada == null) {
            return "{}";
        }
        StringBuilder contenido = new StringBuilder();
        try (BufferedReader lector = new BufferedReader(new InputStreamReader(entrada))) {
            String linea;
            while ((linea = lector.readLine()) != null) {
                contenido.append(linea);
            }
        }
        return contenido.toString();
    }
}
