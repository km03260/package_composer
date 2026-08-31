<x-ssoauth-layout-main title="Connexion">

    <x-slot name="title">
        Se connecter
    </x-slot>

    <x-slot name="content">

        <div class="min-h-screen flex items-center justify-center bg-transparent">

            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 text-center">

                <h3 class="text-2xl font-bold text-gray-900 mb-2">
                    Welcome to Client Module
                </h3>

                <p class="text-gray-500 mb-6">
                    Sign in with your SSO account
                </p>

                @if ($errors->any())
                    {{-- Un echec d'authentification revient ici. Le bandeau, et
                         surtout le fait de ne PAS rouvrir la popup ci-dessous,
                         cassent la boucle /login -> popup -> /login. --}}
                    <div class="mb-6 flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-4 rounded-xl text-left shadow-sm">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 mt-0.5">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <div>
                            <p class="font-semibold text-sm">Connexion impossible</p>
                            <p class="text-xs mt-1 text-red-600 leading-relaxed">{{ $errors->first() }}</p>
                        </div>
                    </div>
                @endif

                @if (!empty($ssoDiagnostic))
                    {{-- Visible uniquement avec SSO_DEBUG=true, apres une tentative
                         de connexion : dit ce qui n'a pas survecu entre la requete
                         qui a connecte l'utilisateur et celle qui le renvoie ici. --}}
                    <div class="mb-6 bg-amber-50 border border-amber-300 rounded-xl p-4 text-left">
                        <p class="font-semibold text-sm text-amber-900">
                            Diagnostic : {{ $ssoDiagnostic['verdict'] }}
                        </p>
                        <p class="text-xs mt-2 text-amber-800 leading-relaxed">
                            {{ $ssoDiagnostic['detail'] }}
                        </p>
                        <table class="mt-3 w-full text-[11px] text-amber-900/80">
                            @foreach ($ssoDiagnostic['contexte'] as $cle => $valeur)
                                <tr class="border-t border-amber-200/60">
                                    <td class="py-1 pr-3 font-mono align-top">{{ $cle }}</td>
                                    <td class="py-1 font-mono break-all">{{ var_export($valeur, true) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif

                <div id="loginSection" class="flex flex-col gap-3">

                    <button id="ssoLoginBtn"
                        class="w-full flex items-center justify-center gap-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition">

                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" />
                        </svg>

                        Sign in with SSO

                    </button>

                    <button id="bypassSsoBtn"
                        class="w-full flex items-center justify-center gap-3 bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3 px-4 rounded-xl shadow-sm border border-gray-300 transition">

                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="text-gray-500">
                            <path
                                d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
                        </svg>

                        Connexion hors SSO

                    </button>

                    <button id="qrLoginBtn"
                        class="w-full flex items-center justify-center gap-3 bg-gray-900 hover:bg-gray-800 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition">

                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M3 11h8V3H3v8zm2-6h4v4H5V5zM3 21h8v-8H3v8zm2-6h4v4H5v-4zM13 3v8h8V3h-8zm6 6h-4V5h4v4zM19 19h2v2h-2zM13 13h2v2h-2zM15 15h2v2h-2zM13 17h2v2h-2zM17 17h2v2h-2zM15 19h2v2h-2zM17 13h2v2h-2zM19 15h2v2h-2z" />
                        </svg>

                        Connexion par QR code

                    </button>

                </div>


                <!-- User Section -->
                <div id="userSection" class="hidden">

                    <div class="flex items-center justify-center gap-4 mb-4">

                        <div id="userAvatar"
                            class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-lg">
                            JD
                        </div>

                        <div class="text-left">
                            <h5 id="userName" class="font-semibold text-gray-900">
                                John Doe
                            </h5>

                            <p id="userEmail" class="text-sm text-gray-500">
                                john@example.com
                            </p>
                        </div>

                    </div>

                    <button id="logoutBtn"
                        class="w-full border border-red-500 text-red-500 hover:bg-red-50 font-semibold py-2 rounded-xl transition">
                        Logout
                    </button>

                </div>


                <div id="message" class="mt-4 text-sm hidden"></div>

            </div>

        </div>

        @include('ssoauth::auth.partials.login-modals')

    </x-slot>


    <x-slot name="extraJs">
        @include('ssoauth::auth.partials.login-scripts', ['autoLoginSso' => !$errors->any()])
    </x-slot>

</x-ssoauth-layout-main>
