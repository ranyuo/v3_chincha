<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title')</title>
<link rel="stylesheet" href="{{ \App\Support\Asset::url('/forms.css') }}">
@stack('styles')
</head>
<body><main @hasSection('width') style="max-width:@yield('width')" @endif>
@yield('content')
</main></body>
</html>
