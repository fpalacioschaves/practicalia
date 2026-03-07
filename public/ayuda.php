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

<!-- Sección 1: Configuración Académica y Dualización -->
<section id="academic" class="bg-white rounded-3xl shadow-sm border p-8 mb-8">
    <div class="flex items-center gap-4 mb-6">
        <div class="p-3 bg-red-100 text-red-700 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Grados, Asignaturas y RAs</h2>
    </div>

    <div class="space-y-6 text-sm text-gray-600">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="border p-4 rounded-xl">
                <h3 class="font-bold text-gray-800 mb-2">1. Grados y Asignaturas</h3>
                <p>Cada Grado (ej: DAW) tiene sus propias asignaturas. Al crear una asignatura, puedes vincularla a uno
                    o varios grados de forma simultánea.</p>
            </div>
            <div class="border p-4 rounded-xl">
                <h3 class="font-bold text-gray-800 mb-2">2. Resultados de Aprendizaje</h3>
                <p>Dentro de cada asignatura se definen los <strong>RAs</strong>. Estos son los objetivos evaluables que
                    el alumno debe conseguir durante su formación.</p>
            </div>
            <div class="border p-4 rounded-xl">
                <h3 class="font-bold text-gray-800 mb-2">3. Dualización Individual</h3>
                <p>Al asignar un alumno a una empresa, puedes marcar <strong>qué RAs específicos</strong> se van a
                    trabajar en esa estancia concreta. ¡Evaluación personalizada!</p>
            </div>
        </div>

        <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200">
            <p class="font-medium text-amber-900 italic text-center">"Desde la ficha de la empresa o del alumno, verás
                el panel de RAs para marcar con un click los que se están dualizando realmente."</p>
        </div>
    </div>
</section>

<!-- Sección 2: Envío de Email Masivo -->
<section id="email-masivo" class="bg-white rounded-3xl shadow-sm border p-8 mb-8">
    <div class="flex items-center gap-4 mb-6">
        <div class="p-3 bg-purple-100 text-purple-700 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Automatización: Envío Masivo</h2>
    </div>

    <div class="prose prose-blue max-w-none text-gray-600">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">Variables Dinámicas</h3>
                <p class="text-sm mb-4">Usa estas etiquetas para que cada correo sea único:</p>
                <ul class="space-y-2 text-sm font-mono">
                    <li><span class="text-blue-700 font-bold">{empresa}</span>: Nombre de la compañía.</li>
                    <li><span class="text-blue-700 font-bold">{responsable}</span>: Tutor/a asignado/a.</li>
                    <li><span class="text-blue-700 font-bold">{ciudad}</span>: Municipio del centro.</li>
                </ul>
            </div>

            <div class="bg-purple-50 p-6 rounded-2xl border border-purple-100 text-purple-900">
                <h3 class="text-lg font-semibold mb-2">Gestión de Respuestas</h3>
                <p class="text-sm">Aunque uses la cuenta del centro para el envío masivo, las empresas recibirán el
                    correo con tu email personal configurado para responder. <strong>¡Las respuestas irán directas a tu
                        buzón!</strong></p>
            </div>
        </div>
    </div>
</section>

<!-- Sección 3: Gestión Avanzada de Empresas -->
<section id="empresas-pro" class="bg-white rounded-3xl shadow-sm border p-8">
    <div class="flex items-center gap-4 mb-6">
        <div class="p-3 bg-emerald-100 text-emerald-700 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-10V4m0 10V4m-4 18V12a1 1 0 011-1h2a1 1 0 011 1v10" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Colaboración y Datos</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm text-gray-600">
        <div>
            <h3 class="font-bold text-gray-800 mb-1">Empresas Compartidas (Publicas)</h3>
            <p>Al crear o editar una empresa, verás el check <span class="font-bold">"Compartir con todos los
                    centros"</span>. Esto permite que otros profesores de la red vean que esa empresa ya existe,
                evitando duplicados y facilitando convenios globales.</p>
        </div>
        <div>
            <h3 class="font-bold text-gray-800 mb-1">Histórico de Contactos</h3>
            <p>Dentro de cada ficha puedes registrar llamadas, emails o visitas. Si marcas un contacto como <span
                    class="font-bold">Confidencial</span>, solo tú y los administradores podréis ver el detalle de esa
                conversación.</p>
        </div>
        <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border flex items-center gap-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p><strong>Geolocalización:</strong> Si rellenas la dirección completa y código postal, se activará
                automáticamente el mapa de Google Maps en la ficha del centro para facilitar visitas de seguimiento.</p>
        </div>
    </div>
</section>

<!-- Sección 4: Gestión de Alumnos y Asignaciones -->
<section id="alumnos" class="bg-white rounded-3xl shadow-sm border p-8 mt-8">
    <div class="flex items-center gap-4 mb-6">
        <div class="p-3 bg-blue-100 text-blue-700 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Gestión de Alumnos y Asignaciones</h2>
    </div>

    <div class="space-y-6 text-sm text-gray-600">
        <p>Al editar un alumno, encontrarás la sección <strong>"Formación en empresa"</strong>. Aquí puedes vincular al
            alumno con una empresa para sus prácticas o formación dual.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="border border-green-200 bg-green-50 p-5 rounded-2xl">
                <h3 class="font-bold text-green-900 mb-2 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    Cerrar Asignación
                </h3>
                <p class="text-green-800">Utiliza el botón <strong>"Cerrar"</strong> cuando el alumno haya
                    <em>finalizado</em> su periodo de prácticas en la empresa. Podrás especificar la fecha exacta de
                    fin. Esta acción <strong>guarda la asignación en el histórico</strong> del alumno, permitiendo
                    llevar un registro de todas las empresas por las que ha pasado.</p>
            </div>

            <div class="border border-red-200 bg-red-50 p-5 rounded-2xl">
                <h3 class="font-bold text-red-900 mb-2 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Eliminar Asignación
                </h3>
                <p class="text-red-800">Utiliza el botón <strong>"Eliminar asignación"</strong> únicamente cuando te
                    hayas equivocado al asignar la empresa o el alumno finalmente no haya ido. Esto borrará por completo
                    el registro de la base de datos, <strong>no quedará rastro en el histórico</strong>.</p>
            </div>
        </div>
    </div>
</section>


</div>

<div class="mt-12 p-8 bg-gray-900 rounded-3xl text-center text-white">
    <h3 class="text-xl font-bold mb-2">¿Necesitas soporte técnico adicional?</h3>
    <p class="text-gray-400 mb-6">Contacta con el administrador del sistema de tu centro para incidencias de accesos o
        configuración SMPT.</p>
    <a href="mailto:admin@safa.es"
        class="inline-block bg-white text-black font-bold px-6 py-3 rounded-2xl hover:bg-gray-200 transition-colors">Contactar
        Soporte</a>
</div>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>