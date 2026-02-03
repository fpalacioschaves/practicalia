<?php
// practicalia/config/env.php
// Si usas Bing (la más sencilla)
//putenv('BING_API_KEY=TU_CLAVE_BING');

// Si prefieres Google Custom Search:
putenv('GOOGLE_API_KEY=AIzaSyCBL5amMQYkd_O9_XB0c9HWCv2ZT5HVilA');
putenv('GOOGLE_CSE_ID=36006649f984d4da6');
// O si usas SerpAPI:
// putenv('SERPAPI_KEY=TU_CLAVE_SERPAPI');



// --- CONFIGURACIÓN SMTP ---

// Gmail Account
// Debes entrar en tu cuenta -> Seguridad -> Activar Verificación en 2 pasos -> Buscar "Contraseñas de aplicación"
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'gestion.practicas.safa@gmail.com');
define('SMTP_PASS', 'cjpm pxrt ydbi izum');
define('SMTP_FROM', 'gestion.practicas.safa@gmail.com');
define('SMTP_FROM_NAME', 'Practicalia');

// Fallback for codes using getenv()
putenv('SMTP_HOST=' . SMTP_HOST);
putenv('SMTP_PORT=' . SMTP_PORT);
putenv('SMTP_USER=' . SMTP_USER);
putenv('SMTP_PASS=' . SMTP_PASS);
putenv('SMTP_FROM=' . SMTP_FROM);
putenv('SMTP_FROM_NAME=' . SMTP_FROM_NAME);

// OPCIÓN A: Brevo (Gratis hasta 300 emails/día) - RECOMENDADO
// Regístrate en brevo.com, ve a "SMTP & API" y genera una "SMTP Key"
// putenv('SMTP_HOST=smtp-relay.brevo.com');
// putenv('SMTP_PORT=587');
// putenv('SMTP_USER=tu_email_de_brevo@gmail.com');
// OPCIÓN B: Gmail Personal (@gmail.com)
// Debes entrar en tu cuenta -> Seguridad -> Activar Verificación en 2 pasos -> Buscar "Contraseñas de aplicación"
putenv('SMTP_FROM_NAME=' . SMTP_FROM_NAME);
