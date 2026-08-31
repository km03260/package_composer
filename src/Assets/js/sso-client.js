class SsoClient {
    constructor(config = {}) {
        this.ssoServerUrl = config.ssoServerUrl;
        this.clientId = config.clientId;
        this.redirectUri = config.redirectUri || window.location.origin + '/auth/sso/callback';
        this.scopes = config.scopes || ['read', 'write'];
        this.popupWidth = config.popupWidth || 800;
        this.popupHeight = config.popupHeight || 470;
        this.tokenKey = config.tokenKey || 'sso_access_token';
        this.stateKey = config.stateKey || 'sso_state';
        this.secretKey = config.secretKey || 'sso_secret_key'
        this.redirectGuardKey = config.redirectGuardKey || 'sso_redirect_attempt'
    }

    /**
     * Navigation top-level vers le SSO.
     *
     * Contrairement a l'iframe et a la popup, la page entiere se rend sur le
     * domaine du SSO : ses cookies y sont first-party, donc poses et renvoyes.
     * C'est la seule forme qui partage reellement la session entre des
     * domaines distincts, et la seule qui n'exige aucun geste utilisateur.
     */
    loginWithRedirect(automatic = false) {
        // Garde-fou : si le SSO renvoie ici juste apres un retour infructueux
        // (jeton refuse, par exemple), une redirection automatique repartirait
        // en boucle. Une tentative automatique ne se rejoue donc pas dans les
        // 10 secondes ; un clic de l'utilisateur, lui, passe toujours.
        const lastAttempt = Number(sessionStorage.getItem(this.redirectGuardKey) || 0);

        if (automatic && Date.now() - lastAttempt < 10000) {
            return false;
        }

        sessionStorage.setItem(this.redirectGuardKey, String(Date.now()));

        const state = this.generateState();
        localStorage.setItem(this.stateKey, state);

        window.location.href = `${this.ssoServerUrl}/sso/login/popup?${new URLSearchParams({
            client_id: this.clientId,
            redirect_uri: this.redirectUri,
            state: state,
            secret_key: this.secretKey,
            scope: this.scopes.join(',')
        })}`;

        return true;
    }

    /**
     * Maintient la fenetre de connexion au premier plan, autant que le
     * navigateur l'autorise.
     *
     * Aucune API web ne permet d'epingler une fenetre au-dessus des autres ni
     * d'empecher un changement d'onglet : cela releve du systeme, pas de la
     * page. Ce qui est realisable est applique ici :
     *   - la page du module est recouverte d'un voile qui bloque toute
     *     interaction tant que la connexion n'est pas terminee ;
     *   - des que la fenetre parente reprend le focus, la popup est ramenee
     *     devant.
     */
    _holdPopupInFront(popup) {
        const overlay = document.createElement('div');
        overlay.setAttribute('data-sso-overlay', '');
        overlay.style.cssText = [
            'position:fixed', 'inset:0', 'z-index:2147483647',
            'background:rgba(15,23,42,.72)',
            'display:flex', 'align-items:center', 'justify-content:center',
            'font-family:system-ui,sans-serif', 'color:#fff', 'text-align:center'
        ].join(';');

        const panel = document.createElement('div');
        panel.style.cssText = 'max-width:22rem;padding:1.5rem;line-height:1.5';
        panel.innerHTML =
            '<p style="font-size:1rem;font-weight:600;margin:0 0 .5rem">Connexion en cours…</p>' +
            '<p style="font-size:.875rem;opacity:.8;margin:0 0 1rem">Terminez la connexion dans la fenêtre du SSO.</p>';

        const back = document.createElement('button');
        back.type = 'button';
        back.textContent = 'Revenir à la fenêtre de connexion';
        back.style.cssText = 'padding:.6rem 1rem;border-radius:.5rem;border:1px solid rgba(255,255,255,.3);background:transparent;color:#fff;cursor:pointer;font:inherit';
        back.addEventListener('click', () => {
            try { popup.focus(); } catch (e) {}
        });

        panel.appendChild(back);
        overlay.appendChild(panel);
        document.body.appendChild(overlay);

        // Un leger delai evite une lutte de focus avec le navigateur.
        let pending = null;
        const refocus = () => {
            if (pending) return;

            pending = setTimeout(() => {
                pending = null;
                try {
                    if (popup && !popup.closed) popup.focus();
                } catch (e) {}
            }, 120);
        };

        window.addEventListener('focus', refocus);

        return {
            release() {
                window.removeEventListener('focus', refocus);
                if (pending) clearTimeout(pending);
                overlay.remove();
            }
        };
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
            secret_key: this.secretKey,
            scope: this.scopes.join(',')
        })}`;

        // Open popup
        const popup = window.open(
            popupUrl,
            'SSOLogin',
            `width=${this.popupWidth},height=${this.popupHeight},left=${(window.screen.width - this.popupWidth) / 2},top=${(window.screen.height - this.popupHeight) / 2}`
        );
        // Un blocage se voit immediatement : window.open renvoie null. Plutot
        // que d'alerter et de s'arreter la, on rend la main a l'appelant, qui
        // bascule sur la redirection top-level.
        if (!popup || popup.closed || typeof popup.closed === 'undefined') {
            return false;
        }

        popup.focus();

        const guard = this._holdPopupInFront(popup);

        // Message handler
        const messageHandler = (event) => {
            // Check message type
            if (event.data.type === 'SSO_POPUP_READY') {

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

                onSuccess?.({
                    token: event.data.access_token,
                    user: event.data.user
                });

                // Le retour se fait sur l'URL de callback configuree. Un
                // chemin en dur casse des que l'application prefixe les
                // routes du package : la navigation aboutit alors sur une
                // 404 renvoyee vers /login, et la boucle recommence.
                const separator = this.redirectUri.indexOf('?') === -1 ? '?' : '&';

                window.location.href = this.redirectUri + separator +
                    'token=' + encodeURIComponent(event.data.access_token);


            } else if (event.data.type === 'SSO_LOGIN_ERROR') {
                console.error('Login error:', event.data.message);
                onError?.(event.data.message || 'Login failed');
                cleanup();

            } else if (event.data.type === 'SSO_POPUP_CLOSED') {
                // Don't call onError here - let the interval handler do it
            }
        };

        // Cleanup function
        const cleanup = () => {
            window.removeEventListener('message', messageHandler);
            if (checkInterval) clearInterval(checkInterval);
            guard.release();
        };

        // Add event listener
        window.addEventListener('message', messageHandler);

        // Check if popup was closed
        let checkInterval = setInterval(() => {
            if (popup.closed) {
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

        return true;
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
            secret_key: this.secretKey,
            scope: this.scopes.join(',')
        })}`;

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

            // IMPORTANT: Verify origin for security
            const allowedHost = new URL(this.ssoServerUrl).host;

            if (!event.origin.includes(allowedHost)) {
                console.warn('Blocked origin:', event.origin);
                return;
            }

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
