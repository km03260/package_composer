<x-slot name="extraJs">
    const sso = window.SSO;

    checkLoginStatus();

    document.getElementById('ssoLoginBtn').addEventListener('click', () => {
        sso.loginWithPopup(
            (userData) => {
                showMessage('Login successful!', 'success');
                showUserInfo(userData);
            },
            (error) => {
                showMessage(error || 'Login failed', 'error');
            }
        );
    });

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
            `mt-4 px-4 py-2 rounded-xl font-medium ${
                type === 'error'
                ? 'bg-red-100 text-red-700'
                : 'bg-green-100 text-green-700'
            }`;

        setTimeout(() => {
            messageDiv.textContent = '';
            messageDiv.className = 'mt-4';
        }, 3000);
    }
</x-slot>