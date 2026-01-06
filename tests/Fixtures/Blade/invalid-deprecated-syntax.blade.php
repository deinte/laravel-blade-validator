{{-- Invalid: Deprecated Blade syntax --}}
{{{ $variable }}}

{{ e($alreadyEscaped) }}

@if($a)
    A
@else if($b)
    B
@endif

{{ str_limit($text, 100) }}

<link href="{{ elixir('css/app.css') }}">
