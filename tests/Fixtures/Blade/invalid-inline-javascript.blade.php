{{-- Invalid: Inline JavaScript with Blade expressions --}}
<button onclick="{{ $action }}">Click me</button>

<a href="javascript:{{ $code }}">Link</a>

<script>
    var userData = {{ $userData }};
    eval("{{ $code }}");
</script>
