<x-ssoauth-layout-main title="Connexion">

    <x-slot name="title">
        Se connecter
    </x-slot>

    <x-slot name="content">

        <div class="min-h-screen flex items-center justify-center bg-gray-100">

            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 text-center">

                <!-- Title -->
                <h3 class="text-2xl font-bold text-gray-900 mb-2">
                    Welcome to Client Module
                </h3>

                <p class="text-gray-500 mb-6">
                    Sign in with your SSO account
                </p>

                <!-- Login Section -->
                <div id="loginSection">

                    <button id="ssoLoginBtn"
                        class="w-full flex items-center justify-center gap-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition">

                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" />
                        </svg>

                        Sign in with SSO

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


                <div id="message" class="mt-4 text-sm"></div>

            </div>

        </div>

    </x-slot>


    <x-slot name="extraJs">

        const sso = new SsoClient({
        ssoServerUrl: '{{ config('sso.server_url') }}',
        clientId: '{{ config('sso.client_id') }}',
        scopes: ['read', 'write']
        });

        checkLoginStatus();

        document.getElementById('ssoLoginBtn').addEventListener('click', loginSSO);


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


        async function checkLoginStatus() {

        if (sso.isLoggedIn()) {

        const isValid = await sso.verifyToken();

        if (isValid) {

        showUserInfo(sso.getUser());

        } else {

        sso.logout();

        showLoginButton();

        }

        }

        }


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


        function showMessage(text, type) {

        const messageDiv = document.getElementById('message');

        messageDiv.textContent = text;

        messageDiv.className =
        `mt-4 px-4 py-2 rounded-xl font-medium ${type === 'error'
        ? 'bg-red-100 text-red-700'
        : 'bg-green-100 text-green-700'
        }`;

        setTimeout(() => {

        messageDiv.textContent = '';

        messageDiv.className = 'mt-4';

        }, 3000);

        }


        window.addEventListener('load', () => {
        console.log('aut clicked sign in with SSO')
        document.getElementById('ssoLoginBtn').click();
        });


    </x-slot>

</x-ssoauth-layout-main>