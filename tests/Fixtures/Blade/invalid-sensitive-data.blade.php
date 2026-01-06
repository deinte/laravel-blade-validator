{{-- Invalid: Sensitive data exposure --}}
<p>Password: {{ $user->password }}</p>
<p>API Key: {{ config('services.api.key') }}</p>
<p>Token: {{ $request->bearerToken() }}</p>
<p>Secret: {{ env('APP_SECRET') }}</p>
