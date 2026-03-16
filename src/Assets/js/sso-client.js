// public/js/sso-client.js
class SsoClient {
    constructor(config = {}) {
        this.ssoServerUrl = config.ssoServerUrl || 'http://sso.gedivepro.test';
        this.clientId = config.clientId;
        this.redirectUri = config.redirectUri || window.location.origin + '/auth/sso/callback';
        this.scopes = config.scopes || ['read', 'write'];
        this.popupWidth = config.popupWidth || 800;
        this.popupHeight = config.popupHeight || 470;
        this.tokenKey = config.tokenKey || 'sso_access_token';
        this.stateKey = config.stateKey || 'sso_state';
    }

    // Open SSO login popup
    loginWithPopup(onSuccess, onError) {
        // Clear any old state
        localStorage.removeItem(this.stateKey);

        // Generate state
        const state = this.generateState();
        localStorage.setItem(this.stateKey, state);

        // Build popup URL
        const popupUrl = `${this.ssoServerUrl}/sso/login/popup?${new URLSearchParams({
            client_id: this.clientId,
            redirect_uri: this.redirectUri,
            state: state,
            scope: this.scopes.join(',')
        })}`;

        console.log('Opening SSO popup:', popupUrl);

        // Open popup
        const popup = window.open(
            popupUrl,
            'SSOLogin',
            `width=${this.popupWidth},height=${this.popupHeight},left=${(window.screen.width - this.popupWidth) / 2},top=${(window.screen.height - this.popupHeight) / 2}`
        );

        if (!popup) {
            alert('Please allow popups for this website');
            return;
        }

        // Message handler
        const messageHandler = (event) => {
            console.log("here we go ......", event);

            // Check message type
            if (event.data.type === 'SSO_POPUP_READY') {
                console.log('Popup is ready');
                // You can send a request for token if needed
                if (popup && !popup.closed) {
                    popup.postMessage({ type: 'SSO_REQUEST_TOKEN' }, '*');
                }
            }
            else if (event.data.type === 'SSO_LOGIN_SUCCESS') {
                console.log(event.data.access_token);
                console.log('Login success message received');

                setTimeout(() => {
                    window.location.href = `/auth/authentication?token=${event.data.access_token}`;
                }, 100);

                // Verify state
                const storedState = localStorage.getItem(this.stateKey);
                if (event.data.state !== storedState) {
                    console.error('State mismatch');
                    onError?.('Authentication failed');
                    cleanup();
                    return;
                }

                // Store token
                if (event.data.access_token) {

                    console.log(this.tokenKey);

                    localStorage.setItem(this.tokenKey, event.data.access_token);
                }

                // Store user data
                if (event.data.user) {
                    localStorage.setItem('sso_user', JSON.stringify(event.data.user));
                }

                // Cleanup
                localStorage.removeItem(this.stateKey);
                cleanup();

                // Close popup
                try {
                    if (popup && !popup.closed) {
                        setTimeout(() => popup.close(), 500);
                    }
                } catch (e) {
                    console.log('Popup already closed');
                }

                // Call success callback
                onSuccess?.({
                    token: event.data.access_token,
                    user: event.data.user
                });

            } else if (event.data.type === 'SSO_LOGIN_ERROR') {
                console.error('Login error:', event.data.message);
                onError?.(event.data.message || 'Login failed');
                cleanup();

            } else if (event.data.type === 'SSO_POPUP_CLOSED') {
                console.log('Popup closed by user');
                // Don't call onError here - let the interval handler do it
            }
        };

        // Cleanup function
        const cleanup = () => {
            window.removeEventListener('message', messageHandler);
            if (checkInterval) clearInterval(checkInterval);
        };

        // Add event listener
        window.addEventListener('message', messageHandler);

        // Check if popup was closed
        let checkInterval = setInterval(() => {
            if (popup.closed) {
                console.log('Popup closed');
                cleanup();

                // Only show error if we were expecting login (state still exists)
                if (localStorage.getItem(this.stateKey)) {
                    localStorage.removeItem(this.stateKey);
                    onError?.('Login cancelled or failed');
                }
            }
        }, 500);

        // Focus on popup
        popup.focus();
    }

    generateState() {
        return Math.random().toString(36).substring(2) + Date.now().toString(36);
    }

    // ... rest of the methods (verifyToken, logout, etc.)
}

// Initialize
window.SSO = new SsoClient({
    ssoServerUrl: 'http://sso.gedivepro.test',
    clientId: '14', // make it dynamique
    redirectUri: window.location.origin + '/auth/sso/callback',
    scopes: ['read', 'write']
});