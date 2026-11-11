@extends('layouts.form')
@section('title', 'Respaldos | Portal Chincha')
@section('width', '1050px')
@section('content')
<a href="/#/admin/respaldo">Volver a la intranet</a><h1>Respaldos y restauración</h1>
@if ($error)<p class="notice" role="alert">{{ $error }}</p>@endif
@if ($message)<p class="notice success" role="status">{{ $message }}</p>@endif
@if ($restored)<p><a href="/#/admin">Iniciar sesión</a></p>
@elseif (isset($schedule, $backups))
<section><h2>Crear y descargar</h2><p>Las copias portables incluyen contenido, cuentas y adjuntos reales. Guárdalas en un lugar privado. La configuración de conexión del hosting (.env) se conserva por separado.</p>
<form method="post">@csrf<input type="hidden" name="action" value="create"><button>Crear respaldo completo</button></form>
<form method="post" action="{{ route('backups.sql') }}">@csrf<button style="margin-top:10px">Descargar también como SQL</button></form>
<ul>@foreach ($backups as $b)<li>{{ $b['created_at'] }} · {{ $b['kind'] }} · {{ number_format($b['bytes'] / 1048576, 2) }} MB · <a href="?download={{ $b['id'] }}">Descargar copia portable</a></li>@endforeach</ul></section>
<section><h2>Restaurar una copia</h2><p>Reemplaza el contenido y las cuentas por la copia elegida. Antes se crea automáticamente otra copia del estado actual. Se verifica el archivo completo y sus adjuntos; un error revierte la operación.</p>
<form method="post" enctype="multipart/form-data">@csrf<input type="hidden" name="action" value="restore">
<label>Respaldo del servidor<select name="backup_id"><option value="">Selecciona una copia</option>@foreach ($backups as $b)<option value="{{ $b['id'] }}">{{ $b['created_at'].' · '.$b['kind'] }}</option>@endforeach</select></label>
<p>Para copias que excedan el límite de subida de PHP, súbelas con hPanel a <code>storage/app/portal/backups</code> conservando su nombre y selecciónalas en la lista.</p>
<label>O sube una copia portable .piscobak<input type="file" name="backup" accept=".piscobak,application/gzip"></label>
<label>Tu contraseña actual<input type="password" name="password" required autocomplete="current-password"></label>
<label>Escribe RESTAURAR para confirmar<input name="confirmation" required pattern="RESTAURAR" autocomplete="off"></label>
<button>Restaurar contenido, cuentas y archivos</button></form></section>
<section><h2>Respaldos diarios</h2><form method="post">@csrf<input type="hidden" name="action" value="configure">
<label><input type="checkbox" name="enabled" @checked($schedule['enabled'])> Activar respaldos diarios</label>
<label>Hora local (0 a 23)<input type="number" name="hour" min="0" max="23" value="{{ (int) $schedule['hour'] }}" required></label>
<label>Copias programadas que se conservan (2 a 30)<input type="number" name="retention" min="2" max="30" value="{{ (int) $schedule['retention'] }}" required></label>
<button>Guardar programación</button></form>
<p>Zona horaria: {{ $timezone }}. Última copia programada: {{ $schedule['last_day'] ?: 'Todavía ninguna' }}.</p>
<p>En hPanel → Avanzado → Tareas cron configura una tarea que ejecute <strong>cada minuto</strong> el programador de Laravel:</p>
<code>php {{ base_path('artisan') }} schedule:run</code>
<p>El programador revisa cada hora este horario, evita duplicados del mismo día y elimina las copias programadas que excedan la retención. No necesita contraseñas en la URL.</p>
<form method="post">@csrf<input type="hidden" name="action" value="run"><button>Ejecutar programación ahora</button></form></section>
@endif
@endsection
