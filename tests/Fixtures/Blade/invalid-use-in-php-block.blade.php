{{-- Invalid: Use statement inside @php block --}}
@php
    use App\Models\User;
    $users = User::all();
@endphp

<ul>
    @foreach($users as $user)
        <li>{{ $user->name }}</li>
    @endforeach
</ul>
