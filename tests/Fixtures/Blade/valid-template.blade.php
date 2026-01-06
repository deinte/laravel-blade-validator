{{-- A valid Blade template for testing --}}
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $title }}</h1>

        @if($showWelcome)
            <p>Welcome, {{ $user->name }}!</p>
        @endif

        <ul>
            @foreach($items as $item)
                <li>{{ $item->name }}</li>
            @endforeach
        </ul>

        <button class="btn {{ $isPrimary ? 'btn-primary' : '' }}">Click</button>

        @auth
            <p>You are logged in.</p>
        @endauth
    </div>
@endsection
