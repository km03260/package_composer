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
        setTimeout(() => {
            if (!popup) {
                alert('Please allow popups for this website');
                return;
            }
        }, 1000);

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
                const storedState = localStorage.getItem(this.stateKey);

                if (event.data.state !== storedState) {
                    onError?.('Authentication failed');
                    cleanup();
                    return;
                }

                // store token
                localStorage.setItem(this.tokenKey, event.data.access_token);

                if (event.data.user) {
                    localStorage.setItem('sso_user', JSON.stringify(event.data.user));
                }

                localStorage.removeItem(this.stateKey);

                cleanup();

                // ✅ CALL SUCCESS FIRST
                onSuccess?.({
                    token: event.data.access_token,
                    user: event.data.user
                });

                // ✅ THEN redirect (optional)
                window.location.href = `/auth/authentication?token=${event.data.access_token}`;


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

    // Open SSO login Model
    loginWithModal(onSuccess, onError) {
        // Clear any old state
        localStorage.removeItem(this.stateKey);

        // Generate new state
        const state = this.generateState();
        localStorage.setItem(this.stateKey, state);

        // Build SSO login URL
        const loginUrl = `${this.ssoServerUrl}/sso/login/popup?${new URLSearchParams({
            client_id: this.clientId,
            redirect_uri: this.redirectUri,
            state: state,
            scope: this.scopes.join(',')
        })}`;

        console.log('2 Opening SSO modal:', loginUrl);

        // Create modal container if it doesn't exist
        let modal = document.getElementById('ssoModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'ssoModal';
            modal.style.cssText = `
                    position: fixed; top:0; left:0; width:100%; height:100%;
                    background: rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center;
                    z-index: 9999;
                `;
            modal.innerHTML = `
                    <div id="ssoModalContent" style="
                        width: ${this.popupWidth}px; 
                        height: ${this.popupHeight}px; 
                        background:#fff; border-radius:12px; overflow:hidden; position:relative;">
                        <iframe id="ssoIframe" src="" style="width:100%; height:100%; border:none;"></iframe>
                        <button id="ssoCloseBtn" style="
                            position:absolute; top:8px; right:8px; background:#f00; color:#fff; border:none; border-radius:4px; padding:2px 6px; cursor:pointer;">
                            ✕
                        </button>
                    </div>
                `;
            document.body.appendChild(modal);

            // Add close button event
            const closeBtn = document.getElementById('ssoCloseBtn');
            closeBtn.onclick = () => {
                modal.style.display = 'none';
                cleanup();
                onError?.('User closed modal');
            };
        }

        // Show modal
        modal.style.display = 'flex';

        const iframe = document.getElementById('ssoIframe');
        iframe.src = loginUrl;

        // Message handler from iframe
        const messageHandler = (event) => {
            console.log("🔥 MESSAGE RECEIVED:", event);
            console.log("event callback ", event);

            // IMPORTANT: Verify origin for security
            const allowedHost = new URL(this.ssoServerUrl).host;

            if (!event.origin.includes(allowedHost)) {
                console.warn('Blocked origin:', event.origin);
                return;
            }

            console.log('SSO message received:', event.data);

            if (event.data.type === 'SSO_LOGIN_SUCCESS') {
                // Verify state
                const storedState = localStorage.getItem(this.stateKey);
                if (event.data.state !== storedState) {
                    console.error('State mismatch');
                    onError?.('Authentication failed - state mismatch');
                    cleanup();
                    modal.style.display = 'none';
                    return;
                }

                // Store token and user
                if (event.data.access_token) {
                    localStorage.setItem(this.tokenKey, event.data.access_token);
                }
                if (event.data.user) {
                    localStorage.setItem('sso_user', JSON.stringify(event.data.user));
                }

                // Cleanup
                localStorage.removeItem(this.stateKey);
                cleanup();

                // Hide modal
                modal.style.display = 'none';

                // Call success callback
                onSuccess?.({
                    token: event.data.access_token,
                    user: event.data.user
                });


            } else if (event.data.type === 'SSO_LOGIN_ERROR') {
                onError?.(event.data.message || 'Login failed');
                modal.style.display = 'none';
                cleanup();
            }
        };

        // Cleanup function
        const cleanup = () => {
            window.removeEventListener('message', messageHandler);
            iframe.src = 'about:blank';
        };

        // Listen for messages from iframe
        window.addEventListener('message', messageHandler);
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