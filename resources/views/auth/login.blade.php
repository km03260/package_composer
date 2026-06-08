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

        <div id="ssoModal" class="hidden fixed inset-0 bg-black/30 flex items-center justify-center rounded-none">
            <div
                class="relative bg-white w-[800px] h-[500px] rounded-none overflow-hidden shadow-[10px_8px_15px_6px_#ccc]">

                <button id="closeModal"
                    class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-xl font-bold">
                    &times;
                </button>

                <iframe id="ssoIframe" src="" class="w-full h-full border-1 border-white rounded-none"></iframe>
            </div>
        </div>

        <div id="qrModal" class="hidden fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="relative bg-white w-[90%] max-w-md rounded-2xl overflow-hidden shadow-xl p-6">

                <button id="closeQrModal"
                    class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-xl font-bold">
                    &times;
                </button>

                <h4 class="text-lg font-semibold text-gray-900 mb-4 text-center">
                    Scanner votre QR code
                </h4>

                <div id="qr-reader" class="w-full"></div>

                <p id="qrScanStatus" class="text-sm text-gray-500 mt-3 text-center"></p>

            </div>
        </div>

        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    </x-slot>


    <x-slot name="extraJs">
        const modal = document.getElementById('ssoModal');
        const closeBtn = document.getElementById('closeModal');
        const iframe = document.getElementById('ssoIframe');

        closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';

        });

        // Optional: click outside modal to close
        modal.addEventListener('click', (e) => {
        if (e.target === modal) {
        modal.classList.add('hidden');

        }
        });
        function openSsoModal() {
        iframe.src = `${sso.ssoServerUrl}/sso/login/popup?...`;
        modal.classList.remove('hidden');
        }


        const sso = new SsoClient({
        ssoServerUrl: '{{ config('sso.server_url') }}',
        clientId: '{{ config('sso.client_id') }}',
        secretKey: '{{ config('sso.client_secret') }}',
        scopes: ['read', 'write']
        });

        <!-- checkLoginStatus(); -->

        document.getElementById('ssoLoginBtn').addEventListener('click', () => {
        loginSSOModel();
        });

        document.getElementById('bypassSsoBtn').addEventListener('click', () => {
        window.location.href = '{{ url('/login') }}';
        });

        const qrModal = document.getElementById('qrModal');
        const closeQrModalBtn = document.getElementById('closeQrModal');
        const qrScanStatus = document.getElementById('qrScanStatus');
        let html5QrCode = null;

        document.getElementById('qrLoginBtn').addEventListener('click', () => {
        qrModal.classList.remove('hidden');
        startQrScanner();
        });

        closeQrModalBtn.addEventListener('click', () => {
        stopQrScanner();
        qrModal.classList.add('hidden');
        });

        qrModal.addEventListener('click', (e) => {
        if (e.target === qrModal) {
        stopQrScanner();
        qrModal.classList.add('hidden');
        }
        });

        function startQrScanner() {
        qrScanStatus.textContent = '';
        html5QrCode = new Html5Qrcode('qr-reader');
        html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: 250 },
        (decodedText) => handleQrResult(decodedText),
        () => {}
        ).catch((err) => {
        console.error('QR scanner start error:', err);
        qrScanStatus.textContent = "Impossible d'accéder à la caméra.";
        });
        }

        function stopQrScanner() {
        if (html5QrCode) {
        html5QrCode.stop().then(() => html5QrCode.clear()).catch(() => {});
        html5QrCode = null;
        }
        }

        function handleQrResult(decodedText) {
        stopQrScanner();
        qrModal.classList.add('hidden');

        let usersso = null;
        try {
        const parsed = JSON.parse(decodedText);
        usersso = parsed.usersso;
        } catch (e) {
        usersso = decodedText.trim();
        }

        if (!usersso) {
        showMessage('QR code invalide ou illisible', 'error');
        return;
        }

        window.location.href = `{{ url('/auth/qr-authentication') }}?usersso=${encodeURIComponent(usersso)}`;
        }

        function isServerNotFoundError(error) {
        const msg = (error || '').toString().toLowerCase();
        return msg.includes('network') ||
               msg.includes('fetch') ||
               msg.includes('failed to fetch') ||
               msg.includes('connection') ||
               msg.includes('unreachable') ||
               msg.includes('econnrefused') ||
               msg.includes('not found') ||
               msg.includes('err_name_not_resolved') ||
               msg.includes('err_connection_refused') ||
               msg.includes('timeout') ||
               msg.includes('net::');
        }

        function loginSSOModel() {
        sso.loginWithModal(
        (data) => {
        updateUserUI(data);
        },
        (error) => {
        console.error('Login error:', error);
        if (isServerNotFoundError(error)) {
            showServerNotFoundError();
        } else {
            showMessage('Échec de la connexion : ' + error, 'error');
        }
        }
        );
        }

        function updateUserUI(data) {
        window.location.href = `/auth/authentication?token=${data.token}`;

        const loginBtn = document.getElementById('ssoLoginBtn');
        loginBtn.textContent = `Welcome, ${data.user.Prenom || data.user.Email}`;
        loginBtn.disabled = true;
        }

        function loginSSO() {

        sso.loginWithPopup(
        (userData) => {
        showMessage('Login successful!', 'success');
        showUserInfo(userData);
        },
        (error) => {
        showMessage(error || 'Login failed', 'error');
        }
        );

        }

        document.getElementById('logoutBtn').addEventListener('click', () => {

        sso.logout();

        showMessage('Logged out successfully', 'success');

        showLoginButton();

        });


        function showUserInfo(user) {

        document.getElementById('loginSection').classList.add('hidden');

        document.getElementById('userSection').classList.remove('hidden');

        document.getElementById('userName').textContent = user.Prenom;

        document.getElementById('userEmail').textContent = user.Email;

        document.getElementById('userAvatar').textContent =
        user.Prenom.charAt(0).toUpperCase();

        }


        function showLoginButton() {

        document.getElementById('userSection').classList.add('hidden');

        document.getElementById('loginSection').classList.remove('hidden');

        }


        function showServerNotFoundError() {
        const messageDiv = document.getElementById('message');
        messageDiv.innerHTML = `
            <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-4 rounded-xl text-left shadow-sm">
                <div class="shrink-0 mt-0.5">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-sm">Serveur SSO inaccessible</p>
                    <p class="text-xs mt-1 text-red-600 leading-relaxed">
                        Impossible de joindre le serveur d'authentification.<br>
                        Veuillez contacter votre administrateur ou utiliser
                        <strong>Se connecter en dos de SSO</strong>.
                    </p>
                </div>
            </div>
        `;
        messageDiv.classList.remove('hidden');
        }

        function showMessage(text, type) {

        const messageDiv = document.getElementById('message');

        messageDiv.textContent = text;

        messageDiv.className =
        `mt-4 px-4 py-2 rounded-xl font-medium text-left ${type === 'error'
        ? 'bg-red-100 text-red-700'
        : 'bg-green-100 text-green-700'
        }`;

        messageDiv.classList.remove('hidden');

        setTimeout(() => {

        messageDiv.textContent = '';

        messageDiv.className = 'mt-4 hidden';

        }, 3000);

        }


        window.addEventListener('load', () => {
        document.getElementById('ssoLoginBtn').click();

        });


    </x-slot>

</x-ssoauth-layout-main>