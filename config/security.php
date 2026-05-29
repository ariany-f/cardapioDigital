<?php

$isProduction = env('APP_ENV') === 'production';

return [

    /*
    |--------------------------------------------------------------------------
    | Registro público (/register — usuários do painel Laravel)
    |--------------------------------------------------------------------------
    */
    'allow_public_registration' => env('SECURITY_ALLOW_PUBLIC_REGISTRATION', ! $isProduction),

    /*
    |--------------------------------------------------------------------------
    | Cabeçalhos HTTP de segurança
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'enabled' => env('SECURITY_HEADERS', true),
        'hsts' => env('SECURITY_HSTS', $isProduction),
        'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bloqueio de paths comuns em varreduras automatizadas
    |--------------------------------------------------------------------------
    */
    'block_probe_paths' => env('SECURITY_BLOCK_PROBES', true),

    /*
    |--------------------------------------------------------------------------
    | Redirecionar HTTP → HTTPS em produção
    |--------------------------------------------------------------------------
    */
    'force_https' => env('SECURITY_FORCE_HTTPS', $isProduction),

    /*
    |--------------------------------------------------------------------------
    | Upload de imagens
    |--------------------------------------------------------------------------
    */
    'upload' => [
        'max_kb' => (int) env('SECURITY_UPLOAD_MAX_KB', 5120),
        'mimes' => ['jpeg', 'jpg', 'png', 'webp', 'gif'],
    ],

];
