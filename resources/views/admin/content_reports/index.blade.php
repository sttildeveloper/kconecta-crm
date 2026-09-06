@extends('layouts.backoffice')

@section('title', 'Kconecta - Denuncias')
@section('heading', 'Moderación de contenido')
@section('subheading', 'Revisa y resuelve denuncias de usuarios y perfiles')

@section('content')
<section class="page-card">
    <form method="GET" action="{{ route('admin.content-reports.index') }}">
        <label>Estado
            <select name="status">
                @foreach (array_merge(['all'], config('compliance.content_safety.report_statuses')) as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Filtrar</button>
    </form>

    @foreach ($reports as $report)
        <article style="border-top:1px solid #dbe5ee;padding:1rem 0">
            <p><strong>#{{ $report->id }} · {{ $report->reason }}</strong> — {{ $report->status }}</p>
            <p>Denunciante: {{ $report->reporter?->email }} · Usuario denunciado: {{ $report->reportedUser?->email }}</p>
            @if($report->details)<p>{{ $report->details }}</p>@endif
            <form method="POST" action="{{ route('admin.content-reports.update', $report) }}">
                @csrf @method('PATCH')
                <select name="status">
                    @foreach (config('compliance.content_safety.report_statuses') as $option)
                        <option value="{{ $option }}" @selected($report->status === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <input name="resolution_note" value="{{ $report->resolution_note }}" maxlength="3000" placeholder="Nota de moderación">
                <button type="submit">Guardar</button>
            </form>
        </article>
    @endforeach
    {{ $reports->links() }}
</section>
@endsection
