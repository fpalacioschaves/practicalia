<?php
// practicalia/public/ayuda.php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/require_auth.php';
require_once __DIR__ . '/../lib/auth.php';

$pageTitle = 'Ayuda y Documentación';
require_once __DIR__ . '/partials/_header.php';
?>

<div class="mb-10">
    <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Centro de Ayuda Practicalia</h1>
    <p class="text-lg text-gray-600 max-w-3xl">Guía completa para el profesorado. Aprende a gestionar tus alumnos,
        empresas y automatizar comunicaciones de forma eficiente.</p>
</div>

<div class="space-y-12">

    <!-- Sección: Envío de Email Masivo (EXTENDIDA) -->
    <section id="email-masivo" class="bg-white rounded-3xl shadow-sm border p-8">
        <div class="flex items-center gap-4 mb-6">
            <div class="p-3 bg-purple-100 text-purple-700 rounded-2xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Guía Maestra: Envío Masivo de Emails</h2>
        </div>

        <div class="prose prose-blue max-w-none text-gray-600">
            <p class="mb-6">El sistema de envío masivo permite contactar con decenas de empresas en segundos,
                manteniendo un toque personal gracias a las variables dinámicas.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <span
                            class="w-6 h-6 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs">1</span>
                        Procedimiento Paso a Paso
                    </h3>
                    <ol class="space-y-3 text-sm list-decimal ml-4">
                        <li>Ve a la sección <strong>Empresas</strong>.</li>
                        <li>Usa los filtros (ciudad, sector) para encontrar tu público objetivo.</li>
                        <li>Marca las casillas individuales o usa el <strong>selector superior</strong> para marcarlas
                            todas.</li>
                        <li>Pulsa el botón <strong>✉ Enviar Email Masivo</strong>. Se abrirá un asistente.</li>
                        <li>Elige una plantilla guardada o escribe un mensaje nuevo.</li>
                        <li>Revisa el asunto y pulsa <strong>Enviar Correos</strong>.</li>
                    </ol>
                </div>

                <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
                    <h3 class="text-lg font-semibold text-blue-800 mb-3 flex items-center gap-2">
                        <span
                            class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs">2</span>
                        Uso de Variables Dinámicas
                    </h3>
                    <p class="text-sm mb-4 text-blue-900">Puedes insertar etiquetas que el sistema sustituirá
                        automáticamente por los datos reales de cada empresa:</p>
                    <ul class="space-y-2 text-sm">
                        <li><code
                                class="bg-white px-2 py-0.5 rounded border border-blue-200 text-blue-700 font-bold">{empresa}</code>:
                            Nombre de la empresa.</li>
                        <li><code
                                class="bg-white px-2 py-0.5 rounded border border-blue-200 text-blue-700 font-bold">{responsable}</code>:
                            Nombre del contacto (si existe).</li>
                        <li><code
                                class="bg-white px-2 py-0.5 rounded border border-blue-200 text-blue-700 font-bold">{ciudad}</code>:
                            Ciudad donde se ubica.</li>
                    </ul>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Ejemplos Prácticos de Plantillas</h3>

                <div class="space-y-4">
                    <!-- Ejemplo 1 -->
                    <div class="border rounded-2xl p-5 bg-white">
                        <div class="text-sm font-bold text-gray-500 mb-2 uppercase tracking-wide">Ejemplo A: Primer
                            Contacto (Prosperar)</div>
                        <div class="bg-gray-100 p-4 rounded-xl font-mono text-sm mb-3">
                            <strong>Asunto:</strong> Propuesta de colaboración FP Dual - {empresa}<br><br>
                            Hola, {responsable}:<br><br>
                            Soy profesor en el centro SAFA y le escribo porque estamos buscando plazas de prácticas en
                            {ciudad} para nuestros alumnos de Grado Superior.<br><br>
                            He visto que {empresa} tiene una trayectoria excelente y nos encantaría que algún alumno
                            pudiera aprender con ustedes...
                        </div>
                    </div>

                    <!-- Ejemplo 2 -->
                    <div class="border rounded-2xl p-5 bg-white">
                        <div class="text-sm font-bold text-gray-500 mb-2 uppercase tracking-wide">Ejemplo B: Seguimiento
                            de Convenio</div>
                        <div class="bg-gray-100 p-4 rounded-xl font-mono text-sm mb-3">
                            <strong>Asunto:</strong> Renovación de colaboración con {empresa}<br><br>
                            Estimados señores de {empresa}:<br><br>
                            Nos ponemos en contacto con su sede en {ciudad} para confirmar si este año disponen de
                            plazas para el periodo de marzo a junio...
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-amber-50 p-6 rounded-2xl border border-amber-200">
                <h4 class="font-bold text-amber-800 flex items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                    Información Crucial sobre Respuestas
                </h4>
                <p class="text-sm text-amber-900">
                    Aunque los emails se envían desde una cuenta técnica centralizada, el sistema configura
                    automáticamente el campo <span class="font-bold">"Responder a" (Reply-To)</span> con el email
                    personal del profesor que realiza el envío. <br><br>
                    <strong>¿Qué significa esto?</strong> Si la empresa responde al correo, ese mensaje llegará
                    directamente a <strong>tu buzón personal de profesor</strong>, facilitando la gestión directa de la
                    relación.
                </p>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Sección: Alumnos y Seguimiento -->
        <section class="bg-white rounded-3xl shadow-sm border p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="p-3 bg-red-100 text-red-700 rounded-2xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Alumnos y Evaluación</h2>
            </div>
            <div class="space-y-6 text-sm text-gray-600">
                <div>
                    <h3 class="font-bold text-gray-800 mb-1">Fichas de Alumno</h3>
                    <p>Cada alumno tiene un perfil donde puedes ver sus datos de contacto y a qué grado pertenece. Desde
                        su ficha puedes marcar si ya tiene una empresa asignada o si está en busca de una.</p>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 mb-1">Resultados de Aprendizaje (RAs)</h3>
                    <p>Para evaluar la formación dual, el sistema permite definir RAs por asignatura. Al editar una
                        Empresa, podrás seleccionar qué RAs específicos va a trabajar el alumno en ese centro de
                        trabajo.</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl border">
                    <p class="font-semibold text-gray-700 italic">"Personaliza la formación marcando individualmente los
                        RAs que cada empresa puede cubrir realmente."</p>
                </div>
            </div>
        </section>

        <!-- Sección: Gestión de Empresas -->
        <section class="bg-white rounded-3xl shadow-sm border p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="p-3 bg-blue-100 text-blue-700 rounded-2xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-10V4m0 10V4m-4 18V12a1 1 0 011-1h2a1 1 0 011 1v10" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Gestión de Empresas</h2>
            </div>
            <div class="space-y-6 text-sm text-gray-600">
                <div>
                    <h3 class="font-bold text-gray-800 mb-1">Empresas Públicas vs Privadas</h3>
                    <p>Al crear una empresa, puedes marcarla como <strong>"Compartida"</strong>. Esto permitirá que
                        profesores de otros centros de la red SAFA puedan ver sus datos básicos y colaborar con ella si
                        tú lo permites.</p>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 mb-1">Log de Contactos</h3>
                    <p>No pierdas el hilo de quién llamó a quién. Registra cada llamada, email o visita dentro de la
                        ficha de la empresa. Puedes añadir una "Próxima acción" para que no se te olvide volver a llamar
                        en una fecha concreta.</p>
                </div>
                <div class="flex items-center gap-2 text-blue-600 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    <span>Los contactos con candado solo son visibles por ti y administradores.</span>
                </div>
            </div>
        </section>
    </div>

</div>

<div class="mt-12 p-6 bg-gray-100 rounded-2xl text-center">
    <h3 class="font-semibold text-gray-800">¿Necesitas ayuda técnica adicional?</h3>
    <p class="text-sm text-gray-600 mt-2">Contacta con el administrador del sistema en tu centro para incidencias
        relacionadas con accesos o configuración de correo.</p>
</div>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>