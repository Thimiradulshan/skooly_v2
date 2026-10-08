@extends('layouts.app')

@section('title', 'Two-factor authentication')

@section('content')
    <x-page-header title="Two-factor authentication" description="Protect your account with an authenticator app." />

    @if (auth()->user()->two_factor_confirmed_at !== null)
        @if (is_array($recoveryCodes))
            <x-card>
                <h2>Recovery codes</h2>
                <p>Store these codes in a secure place. Each code can be used once and will not be shown again.</p>
                <ul>
                    @foreach ($recoveryCodes as $recoveryCode)
                        <li><code>{{ $recoveryCode }}</code></li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        <x-card>
            <h2>Enabled</h2>
            <p>Authenticator-app two-factor authentication is active for this account.</p>

            <form method="POST" action="{{ route('two-factor.destroy') }}" data-loading>
                @csrf
                @method('DELETE')

                <div class="form-field">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <div class="form-field">
                    <label class="form-label" for="code">Authenticator or recovery code</label>
                    <input class="form-control" type="text" id="code" name="code" required autocomplete="one-time-code" inputmode="numeric">
                </div>

                <button type="submit" class="btn btn-danger">Disable two-factor authentication</button>
            </form>
        </x-card>
    @else
        <x-card>
            <h2>Set up an authenticator app</h2>
            <p>Add this secret to an authenticator app, then enter the six-digit code it generates to finish setup.</p>

            <div class="form-field">
                <label class="form-label" for="manual-secret">Manual secret</label>
                <input class="form-control" id="manual-secret" type="text" value="{{ $manualSecret }}" readonly>
            </div>

            <div class="form-field">
                <label class="form-label" for="provisioning-uri">Setup URI</label>
                <textarea class="form-control" id="provisioning-uri" rows="3" readonly>{{ $provisioningUri }}</textarea>
            </div>

            <form method="POST" action="{{ route('two-factor.confirm') }}" data-loading>
                @csrf

                <div class="form-field">
                    <label class="form-label" for="code">Authenticator code</label>
                    <input class="form-control" type="text" id="code" name="code" required autocomplete="one-time-code" inputmode="numeric" autofocus>
                </div>

                <button type="submit" class="btn">Enable two-factor authentication</button>
            </form>
        </x-card>
    @endif
@endsection
