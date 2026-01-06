{{-- Invalid: Raw output that may be unsafe --}}
<div class="content">
    {!! $userContent !!}
</div>

<div class="dangerous">
    {!! request()->input('html') !!}
</div>
