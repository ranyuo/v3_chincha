@extends('layouts.form')
@section('title', 'Instalar portal Chincha')
@section('content')
<h1>Instalar portal Chincha</h1>
@if ($closed)
<section><p>El portal ya está instalado. El instalador quedó bloqueado.</p><a href="/">Ir al portal</a></section>
@elseif ($success)
<section><p class="notice success" role="status">Instalación completada. Las tablas y tu cuenta de administrador están listas.</p><p>Ya puedes entrar con el usuario y la contraseña que acabas de elegir.</p><a href="/#/admin">Abrir la intranet</a></section>
@else
<p>Configura primero los datos de MySQL y tu clave privada (<code>PORTAL_INSTALL_KEY</code>) en el archivo <code>.env</code>. Este paso crea las tablas y la primera cuenta.</p>
@if ($error)<p class="notice" role="alert">{{ $error }}</p>@endif
<section><form method="post" autocomplete="off">
@csrf
<label>Clave privada de instalación<input type="password" name="install_key" required minlength="32" autocomplete="off"></label>
<label>Tu nombre<input name="name" required maxlength="150" autocomplete="name"></label>
<label>Usuario de administrador<input name="username" required pattern="[a-zA-Z0-9._\-]{3,80}" maxlength="80" autocomplete="username"></label>
<label>Correo del administrador<input type="email" name="email" required maxlength="254" autocomplete="email"></label>
<label>Contraseña nueva (12 a 72 caracteres)<input type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>
<label>Repetir contraseña<input type="password" name="password_confirmation" required minlength="12" maxlength="72" autocomplete="new-password"></label>
<button type="submit">Instalar y crear mi cuenta</button>
</form></section>
@endif
@endsection
