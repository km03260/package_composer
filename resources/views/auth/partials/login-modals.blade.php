{{-- Shared login modals (SSO iframe, QR, local "Connexion hors SSO"). --}}
@php($ssoLogo = asset(config('sso.logo_url')))

<div id="ssoModal" class="hidden fixed inset-0 bg-black/30 flex items-center justify-center rounded-none">
    <div
        class="relative bg-white w-[800px] h-[500px] rounded-none overflow-hidden shadow-[10px_8px_15px_6px_#ccc]">

        <button id="closeModal"
            class="absolute top-3 right-3 z-10 text-gray-600 hover:text-gray-900 text-xl font-bold">
            &times;
        </button>

        <iframe id="ssoIframe" src="" class="w-full h-full border-0"></iframe>
    </div>
</div>

<div id="qrModal" class="hidden fixed inset-0 bg-black/30 flex items-center justify-center z-50">
    <div class="relative bg-white w-[90%] max-w-md rounded-2xl shadow-xl p-6">

        <button id="closeQrModal"
            class="absolute top-3 right-3 z-10 text-gray-400 hover:text-gray-900 text-2xl font-bold leading-none">
            &times;
        </button>

        <div class="flex justify-center mb-3">
            <img src="{{ $ssoLogo }}" alt="Gedivepro" class="h-10" onerror="this.style.display='none'">
        </div>

        <h4 class="text-lg font-semibold text-gray-900 mb-4 text-center">
            Connexion par QR code
        </h4>

        <div id="qr-reader" class="w-full rounded-xl overflow-hidden"></div>

        <p class="text-xs text-gray-400 text-center mt-3">
            Placez le QR code devant la caméra ou importez une image.
        </p>

        <label class="mt-3 w-full flex items-center justify-center gap-2 cursor-pointer bg-indigo-50 hover:bg-indigo-100 border-2 border-dashed border-indigo-300 text-indigo-700 font-medium py-3 px-4 rounded-xl text-sm transition">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="8 12 12 8 16 12"/><line x1="12" y1="8" x2="12" y2="16"/></svg>
            Importer une image QR code
            <input id="qrFileInputMain" type="file" accept="image/*" class="hidden" />
        </label>

        <div id="qrFallback" class="hidden mt-4 space-y-3">

            <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-700 rounded-xl px-4 py-3 text-sm">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Caméra indisponible — utilisez les options ci-dessous.
            </div>

            <label class="w-full flex items-center justify-center gap-2 cursor-pointer bg-indigo-50 hover:bg-indigo-100 border-2 border-dashed border-indigo-300 text-indigo-700 font-medium py-3 px-4 rounded-xl text-sm transition">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="8 12 12 8 16 12"/><line x1="12" y1="8" x2="12" y2="16"/></svg>
                Scanner depuis une image
                <input id="qrFileInput" type="file" accept="image/*" class="hidden" />
            </label>

            <div class="flex items-center gap-2 text-gray-400 text-xs">
                <hr class="flex-1 border-gray-200" /><span>ou</span><hr class="flex-1 border-gray-200" />
            </div>

            <div class="flex gap-2">
                <input id="qrManualInput" type="text"
                    placeholder="Code utilisateur SSO"
                    class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                <button id="qrManualSubmit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">
                    OK
                </button>
            </div>

        </div>

        <p id="qrScanStatus" class="text-sm text-red-500 mt-3 text-center hidden"></p>

    </div>
</div>

<div id="localLoginModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
    <div class="relative bg-white w-[90%] max-w-md rounded-2xl shadow-xl p-6">

        <button id="closeLocalModal"
            class="absolute top-3 right-3 text-gray-400 hover:text-gray-900 text-2xl font-bold leading-none">
            &times;
        </button>

        <div class="flex justify-center mb-3">
            <img src="{{ $ssoLogo }}" alt="Gedivepro" class="h-10" onerror="this.style.display='none'">
        </div>

        <h4 class="text-lg font-semibold text-gray-900 mb-1 text-center">
            Connexion hors SSO
        </h4>
        <p class="text-sm text-gray-500 mb-4 text-center">
            Identifiez-vous avec vos identifiants Gedivepro
        </p>

        <div id="localError"
            class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700"></div>

        <div id="localSuccess"
            class="hidden mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
            Connexion réussie ! Redirection...
        </div>

        <form id="localLoginForm" class="space-y-4 text-left">
            @csrf

            <div id="localVerifyBox" class="hidden bg-blue-50 border border-blue-200 rounded-xl p-4">
                <p class="text-sm text-blue-800 mb-3">
                    Avant de continuer, merci de vérifier votre e-mail contenant un
                    <span class="font-semibold">code de vérification</span>.
                </p>
                <input type="text" name="code" placeholder="Code de vérification"
                    class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="button" id="localResendBtn"
                    class="mt-3 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    Renvoyer le code de vérification
                </button>
            </div>

            <div id="localLocatedBox"
                class="hidden p-4 rounded-xl border border-red-200 bg-red-50 text-sm text-red-700 text-left">
                <strong class="block mb-1">Accès bloqué en dehors des locaux de Gedivepro</strong>
                Votre profil ne vous permet pas d'accéder aux applications en dehors des locaux de Gedivepro.
            </div>

            <div id="localLoginFields" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email ou identifiant</label>
                    <input type="text" name="login" id="localLogin" required autocomplete="username"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                        placeholder="Email ou nom d'utilisateur">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                    <input type="password" name="password" id="localPassword" required
                        autocomplete="current-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                        placeholder="••••••••">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="localRemember"
                        class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    <label for="localRemember" class="ml-2 text-sm text-gray-700">
                        Se souvenir de cet appareil
                    </label>
                </div>
            </div>

            <button type="submit" id="localSubmitBtn"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl shadow-md transition">
                <span id="localBtnText">Se connecter</span>
                <span id="localLoader" class="hidden">Connexion...</span>
            </button>
        </form>

    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
