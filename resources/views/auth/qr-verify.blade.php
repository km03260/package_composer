<x-ssoauth-layout-main title="Vérification">

    <x-slot name="title">
        Vérification
    </x-slot>

    <x-slot name="content">

        <div class="min-h-screen flex items-center justify-center bg-transparent">

            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">

                <div class="flex justify-center mb-4">
                    <img src="{{ asset(config('sso.logo_url')) }}" alt="Gedivepro" class="h-10"
                        onerror="this.style.display='none'">
                </div>

                <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">
                    Vérification de l'appareil
                </h3>

                <p class="text-sm text-gray-500 mb-6 text-center">
                    Un code de vérification a été envoyé à votre adresse e-mail.
                    Saisissez-le pour continuer.
                </p>

                @if (!empty($error))
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                        {{ $error }}
                    </div>
                @endif

                <div id="qrVerifyMsg" class="hidden mb-4 p-3 rounded-xl text-sm"></div>

                <form method="POST" action="{{ route('auth.qr.authentication.verify') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="usersso" value="{{ $usersso }}">
                    <input type="text" name="code" required autofocus placeholder="Code de vérification"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl shadow-md transition">
                        Vérifier
                    </button>
                </form>

                <button type="button" id="qrResendBtn"
                    class="mt-4 w-full text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    Renvoyer le code de vérification
                </button>

            </div>

        </div>

    </x-slot>

    <x-slot name="extraJs">
        document.getElementById('qrResendBtn').addEventListener('click', async function () {
        const btn = this;
        const msg = document.getElementById('qrVerifyMsg');
        const original = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Envoi...';
        try {
        const res = await fetch('{{ route('auth.qr.resend') }}', {
        method: 'POST',
        headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({ usersso: '{{ $usersso }}' })
        });
        const data = await res.json();
        msg.textContent = data.message || '';
        msg.className = 'mb-4 p-3 rounded-xl text-sm ' + (data.success
        ? 'bg-green-50 border border-green-200 text-green-700'
        : 'bg-red-50 border border-red-200 text-red-700');
        msg.classList.remove('hidden');
        } catch (e) {
        msg.textContent = 'Erreur réseau. Vérifiez votre connexion.';
        msg.className = 'mb-4 p-3 rounded-xl text-sm bg-red-50 border border-red-200 text-red-700';
        msg.classList.remove('hidden');
        } finally {
        btn.disabled = false;
        btn.textContent = original;
        }
        });
    </x-slot>

</x-ssoauth-layout-main>
