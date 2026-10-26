<?php

return [

    /*
    | URL del Document Server tal como la ve el NAVEGADOR (puerto publicado por Docker).
    */
    'url' => rtrim(env('ONLYOFFICE_URL', 'http://localhost:8080'), '/'),

    /*
    | URL de Laravel tal como la ve el DOCUMENT SERVER (desde dentro del contenedor).
    | En Docker Desktop (Windows/Mac) el host es http://host.docker.internal:PUERTO.
    | Con `php artisan serve` recuerda usar --host=0.0.0.0, si no el contenedor no llega.
    */
    'storage_url' => rtrim(env('ONLYOFFICE_STORAGE_URL', env('APP_URL', 'http://localhost')), '/'),

    /*
    | JWT: Laravel firma la configuración del editor y verifica los callbacks.
    | El secreto debe ser IDÉNTICO al JWT_SECRET del contenedor.
    */
    'jwt_enabled' => (bool) env('ONLYOFFICE_JWT_ENABLED', true),
    'jwt_secret' => env('ONLYOFFICE_JWT_SECRET'),
    'jwt_header' => env('ONLYOFFICE_JWT_HEADER', 'Authorization'),

    /*
    | Hosts desde los que se acepta descargar el archivo guardado (anti-SSRF).
    | Por defecto solo el host de 'url'. Agrega otros separados por coma si hace falta.
    */
    'callback_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('ONLYOFFICE_CALLBACK_HOSTS', ''))))),

    /* Disco de Laravel donde viven plantillas y documentos (privado). */
    'disk' => env('ONLYOFFICE_DISK', 'local'),

    /* Tamaño máximo de un .docx subido, en KB (20480 = 20 MB). */
    'max_upload_kb' => (int) env('ONLYOFFICE_MAX_UPLOAD_KB', 20480),

    /* Vigencia del enlace firmado con el que el Document Server descarga el archivo. */
    'file_url_ttl_hours' => 12,

    'lang' => env('ONLYOFFICE_LANG', 'es'),
];
