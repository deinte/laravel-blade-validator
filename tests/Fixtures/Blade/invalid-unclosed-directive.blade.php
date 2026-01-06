{{-- Invalid: Unclosed directives --}}
@if($show)
    <div>This @if is not closed</div>

@foreach($items as $item)
    <p>{{ $item }}</p>
