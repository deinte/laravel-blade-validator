{{-- Invalid: Blade directive inside component attribute --}}
<x-button
    @if($isActive)
        color="primary"
    @else
        color="gray"
    @endif
>
    Click me
</x-button>
