@extends('layouts.form')
@section('title', 'Cuentas | Portal Chincha')
@section('content')
<a href="/#/admin/dashboard">← Volver al panel</a><h1>Cuentas del portal</h1>
@if ($notice)<p class="notice success" role="status">{{ $notice }}</p>@endif
@if ($error)<p class="notice" role="alert">{{ $error }}</p>@endif
@if (! empty($actor['cambiarClave']))<p class="notice" role="alert">Por seguridad, cambia la contraseña temporal que recibiste antes de usar la intranet.</p>@elseif ($notice === 'Contraseña actualizada.')<p><a href="/#/admin/dashboard">Ir a la intranet →</a></p>@endif
@if ($actor['rol'] === 'admin')
<section><h2>Usuarios del portal</h2><p>Cada cuenta accede a los módulos de su rol y a los registros con los que está vinculada.</p><ul>@foreach ($users as $user)<li>{{ $user['name'] }} — {{ $user['username'] }} ({{ $user['role'] }})</li>@endforeach</ul></section>
<section><h2>Crear usuario</h2><form method="post">
@csrf<input type="hidden" name="action" value="create">
<label>Nombre<input name="name" required maxlength="150" autocomplete="name"></label>
<label>Usuario<input name="username" required pattern="[a-z0-9._\-]{3,80}" autocomplete="off"></label>
<label>Correo<input type="email" name="email" required maxlength="254" autocomplete="email"></label>
<label>Rol<select name="role" style="display:block;width:100%;padding:10px">@foreach ($roles as $key => $role)<option value="{{ $key }}">{{ $role['label'] }}</option>@endforeach</select></label>
<label>Programa del coordinador<input name="programaId" placeholder="Ej.: apsti"></label><label>Vínculo académico<input name="vin" placeholder="doc:2, est:1 o emp:1"></label><label>Persona del docente<input name="personaId" placeholder="ID de la persona en Plana docente"></label><label>DNI / RUC<input name="dni"></label><label>Área<input name="area"></label>
<label>Contraseña nueva<input type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>
<label>Repetir contraseña<input type="password" name="password_confirmation" required minlength="12" maxlength="72" autocomplete="new-password"></label><button>Crear cuenta</button>
</form></section>
@endif
<section><h2>Cambiar mi contraseña</h2><form method="post">
@csrf<input type="hidden" name="action" value="password">
<label>Contraseña actual<input type="password" name="current_password" required maxlength="72" autocomplete="current-password"></label>
<label>Contraseña nueva<input type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>
<label>Repetir contraseña<input type="password" name="password_confirmation" required minlength="12" maxlength="72" autocomplete="new-password"></label><button>Cambiar contraseña</button>
</form></section>
@endsection
