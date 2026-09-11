<?php

header('Content-Type: application/json');

echo json_encode([

    "success" => true,

    "application" => [

        "name" => "Ameen GeoDatabase",
        "company" => "LIO",
        "version" => "1.0.0",

        "start_page" => "/public/index.php",
    ],

    "branding" => [

        "logo" => "/branding/logo.png",

        "animation" => "/branding/splash.json",

        "primary_color" => "#1565C0",

        "accent_color" => "#00BCD4"
    ],

    "shell" => [

        "minimum_version" => "1.0.0",

        "recommended_version" => "1.0.0",

        "latest_version" => "1.0.0",

        "apk" => "/download/WebViewShell.apk",

        "force_update" => false
    ],

    "server" => [

        "maintenance" => false,

        "message" => "Welcome"

    ]

], JSON_PRETTY_PRINT);