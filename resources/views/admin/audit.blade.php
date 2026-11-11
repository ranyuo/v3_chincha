@extends('layouts.form')
@section('title', 'Auditoría | Portal Chincha')
@section('width', '1450px')
@section('content')
<a href="/#/admin/auditoria">Volver a la intranet</a><h1>Historial completo de auditoría</h1><p>Los cambios de datos, sesiones, cuentas, archivos y respaldos se registran en el servidor. Las solicitudes de exportación desde el navegador se identifican como acciones de interfaz.</p>
<section><form method="get"><label>Usuario, acción, entidad, registro o detalle<input name="q" value="{{ $q }}"></label><label>Desde<input type="date" name="desde" value="{{ $dates['desde'] }}"></label><label>Hasta<input type="date" name="hasta" value="{{ $dates['hasta'] }}"></label><button>Filtrar historial</button></form>
<p>{{ $total }} registros. Página {{ $page }} de {{ $pages }}. <a href="{{ $link(['download' => 'csv']) }}">Exportar todos los resultados a CSV</a></p>
<div style="overflow:auto"><table style="width:100%;border-collapse:collapse"><thead><tr>@foreach ($headings as $h)<th style="padding:8px;text-align:left">{{ $h }}</th>@endforeach</tr></thead>
<tbody>@foreach ($rows as $r)<tr>@foreach ($columns as $key)<td style="padding:8px;vertical-align:top;border-top:1px solid #ddd;max-width:340px;overflow-wrap:anywhere">{{ (string) $r[$key] }}</td>@endforeach</tr>@endforeach</tbody></table></div>
<p>@if ($page > 1)<a href="{{ $link(['page' => $page - 1]) }}">Página anterior</a>@endif @if ($page < $pages)<a href="{{ $link(['page' => $page + 1]) }}">Página siguiente</a>@endif</p></section>
@endsection
