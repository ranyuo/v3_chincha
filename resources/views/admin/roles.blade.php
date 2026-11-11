@extends('layouts.form')
@section('title', 'Roles y permisos | Portal Chincha')
@push('styles')
<style>
body{background:#f2f6fb;color:#102447}main{max-width:1050px;margin:auto;padding:24px}nav{display:flex;gap:8px;flex-wrap:wrap;margin:24px 0}nav a{padding:10px;border:1px solid #ccd6e5;border-radius:8px;background:white;text-decoration:none}nav a[aria-current]{background:#102447;color:white}.modules{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px}.modules label{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid #ccd6e5;border-radius:8px;margin:0}.modules input{width:20px;height:20px;flex:0 0 20px}.tools{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}button.secondary{background:#fff;color:#102447;border:1px solid #ccd6e5}fieldset{border:0;padding:0;min-width:0}legend{font-weight:bold;margin-bottom:16px}section{background:white;border:1px solid #d3dce8;border-radius:14px;padding:24px}input[type=text]{box-sizing:border-box;width:100%}
</style>
@endpush
@section('content')
<a href="/#/admin/usuarios">← Volver a Usuarios y roles</a><h1>Roles y permisos</h1>
<p>Edite el nombre y los módulos habilitados de cada perfil. Los permisos se guardan en MySQL y se verifican en el servidor.</p>
@if ($notice)<p class="notice success" role="status">{{ $notice }}</p>@endif
@if ($error)<p class="notice" role="alert">{{ $error }}</p>@endif
<nav aria-label="Seleccione un rol">@foreach ($roles as $id => $r)<a href="?role={{ $id }}" @if ($id === $key) aria-current="page" @endif>{{ $r['label'] }}</a>@endforeach</nav>
<section><h2>{{ $role['label'] }}</h2><p>Perfil institucional: {{ $definitions['roles'][$key]['label'] }}.</p>
@if ($protected)<p>El administrador conserva todos los permisos y la gestión de cuentas. Este perfil está protegido para evitar perder el acceso al sistema.</p>
@else
<p>Active o desactive los módulos del ámbito de este perfil. Los vínculos con alumno, docente, empresa o programa y las reglas de aprobación se mantienen.</p>
<form method="post">@csrf<input type="hidden" name="role" value="{{ $key }}"><input type="hidden" name="expected" value="{{ $expected }}">
<label>Nombre del rol<input type="text" name="label" maxlength="100" required value="{{ $role['label'] }}"></label>
<fieldset><legend>Módulos habilitados</legend><div class="modules">@foreach ($definitions['roles'][$key]['mods'] as $mod)
<label><input type="checkbox" name="mods[]" value="{{ $mod }}" @checked(in_array($mod, $role['mods'], true)) @disabled($mod === 'dashboard')>{{ $definitions['modules'][$mod] ?? $mod }}</label>
@endforeach</div></fieldset>
<p>Al guardar o restaurar, se cerrarán las sesiones de los usuarios de este rol. No se modifican sus cuentas ni sus contraseñas.</p>
<div class="tools"><button name="action" value="save">Guardar permisos</button><button class="secondary" name="action" value="reset" formnovalidate>Restaurar permisos originales</button></div>
</form>@endif</section>
@endsection
