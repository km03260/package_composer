const modal = document.getElementById('ssoModal');
const closeBtn = document.getElementById('closeModal');
const iframe = document.getElementById('ssoIframe');

// The SSO modal is shown by sso-client.js with an inline `display:flex`, which
// overrides the Tailwind `hidden` class — so it must be hidden via inline style.
closeBtn.addEventListener('click', () => {
modal.style.display = 'none';
});

// Optional: click outside modal to close
modal.addEventListener('click', (e) => {
if (e.target === modal) {
modal.style.display = 'none';
}
});
function openSsoModal() {
iframe.src = `${sso.ssoServerUrl}/sso/login/popup?...`;
modal.style.display = 'flex';
}


const sso = new SsoClient({
ssoServerUrl: '{{ config('sso.server_url') }}',
clientId: '{{ config('sso.client_id') }}',
secretKey: '{{ config('sso.client_secret') }}',
scopes: ['read', 'write']
});

document.getElementById('ssoLoginBtn').addEventListener('click', () => {
loginSSOModel();
});

document.getElementById('bypassSsoBtn').addEventListener('click', () => {
openLocalModal();
});

const qrModal = document.getElementById('qrModal');
const qrFallback = document.getElementById('qrFallback');
const qrScanStatus = document.getElementById('qrScanStatus');
let html5QrCode = null;

document.getElementById('qrLoginBtn').addEventListener('click', () => {
openQrModal();
});

document.getElementById('closeQrModal').addEventListener('click', () => {
closeQrModal();
});

qrModal.addEventListener('click', (e) => {
if (e.target === qrModal) closeQrModal();
});

function openQrModal() {
document.getElementById('qr-reader').innerHTML = '';
qrFallback.classList.add('hidden');
qrScanStatus.classList.add('hidden');
qrScanStatus.textContent = '';
qrModal.classList.remove('hidden');
html5QrCode = new Html5Qrcode('qr-reader');
html5QrCode.start(
{ facingMode: 'environment' },
{ fps: 10, qrbox: 250 },
(decodedText) => handleQrResult(decodedText),
() => {}
).catch((err) => {
console.error('QR scanner error:', err);
qrFallback.classList.remove('hidden');
});
}

function stopQrCamera() {
const instance = html5QrCode;
html5QrCode = null;
if (instance) {
try {
instance.stop()
.then(() => { try { instance.clear(); } catch(e) {} })
.catch(() => { try { instance.clear(); } catch(e) {} });
} catch(e) {}
}
}

function closeQrModal() {
qrModal.classList.add('hidden');
qrFallback.classList.add('hidden');
qrScanStatus.classList.add('hidden');
document.getElementById('qr-reader').innerHTML = '';
stopQrCamera();
}

let qrSubmitting = false;

// Show an error inside the QR modal (keeps it open) and reveal the
// fallback options (image upload / manual code) so the user can retry.
function showQrError(message) {
qrSubmitting = false;
qrScanStatus.textContent = message;
qrScanStatus.classList.remove('hidden');
qrFallback.classList.remove('hidden');
}

function scanQrFile(file) {
if (!file) return;
qrScanStatus.classList.add('hidden');
qrScanStatus.textContent = '';
let tempDiv = document.getElementById('qr-file-scan-tmp');
if (!tempDiv) {
tempDiv = document.createElement('div');
tempDiv.id = 'qr-file-scan-tmp';
tempDiv.style.display = 'none';
document.body.appendChild(tempDiv);
}
const scanner = new Html5Qrcode('qr-file-scan-tmp');
scanner.scanFile(file, false)
.then(decodedText => {
try { scanner.clear(); } catch(e) {}
handleQrResult(decodedText);
})
.catch(() => {
try { scanner.clear(); } catch(e) {}
qrScanStatus.textContent = "Aucun QR code trouvé dans cette image.";
qrScanStatus.classList.remove('hidden');
});
}

document.getElementById('qrFileInputMain').addEventListener('change', (e) => {
scanQrFile(e.target.files[0]);
e.target.value = '';
});

document.getElementById('qrFileInput').addEventListener('change', (e) => {
scanQrFile(e.target.files[0]);
e.target.value = '';
});

document.getElementById('qrManualSubmit').addEventListener('click', () => {
const val = document.getElementById('qrManualInput').value.trim();
if (val) handleQrResult(val);
});

document.getElementById('qrManualInput').addEventListener('keydown', (e) => {
if (e.key === 'Enter') document.getElementById('qrManualSubmit').click();
});

async function handleQrResult(decodedText) {
if (qrSubmitting) return;

let usersso = null;
try {
const parsed = JSON.parse(decodedText);
usersso = parsed.usersso;
} catch (e) {
usersso = decodedText.trim();
}

if (!usersso) {
showQrError('QR code invalide ou illisible.');
return;
}

// Stop the camera so the continuous scanner doesn't fire repeatedly while
// we authenticate, but keep the modal open in case we need to show an error.
qrSubmitting = true;
stopQrCamera();
qrScanStatus.classList.add('hidden');

try {
const res = await fetch(`{{ route('auth.qr.authentication') }}?usersso=${encodeURIComponent(usersso)}`, {
headers: {
'Accept': 'application/json',
'X-Requested-With': 'XMLHttpRequest'
}
});

let data = {};
try { data = await res.json(); } catch (e) {}

// Success, or dfa verification page: navigate.
if (data.redirect) {
window.location.href = data.redirect;
return;
}

showQrError(data.message || 'QR code invalide ou utilisateur introuvable.');
} catch (err) {
console.error('QR authentication error:', err);
showQrError('Erreur réseau. Vérifiez votre connexion.');
}
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
window.location.href = `{{ route('auth.sso.authentication') }}?token=${data.token}`;

const loginBtn = document.getElementById('ssoLoginBtn');
loginBtn.textContent = `Welcome, ${data.user.Prenom || data.user.Email}`;
loginBtn.disabled = true;
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
document.getElementById('userAvatar').textContent = user.Prenom.charAt(0).toUpperCase();
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
                <strong>Connexion hors SSO</strong>.
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


// --- Connexion hors SSO (local login) ---
const localModal = document.getElementById('localLoginModal');

document.getElementById('closeLocalModal').addEventListener('click', () => {
localModal.classList.add('hidden');
});

localModal.addEventListener('click', (e) => {
if (e.target === localModal) localModal.classList.add('hidden');
});

function openLocalModal() {
resetLocalModal();
localModal.classList.remove('hidden');
setTimeout(() => document.getElementById('localLogin').focus(), 100);
}

function resetLocalModal() {
document.getElementById('localError').classList.add('hidden');
document.getElementById('localSuccess').classList.add('hidden');
document.getElementById('localVerifyBox').classList.add('hidden');
document.getElementById('localLocatedBox').classList.add('hidden');
document.getElementById('localLoginFields').classList.remove('hidden');
}

function localSetBusy(busy) {
const btnText = document.getElementById('localBtnText');
const loader = document.getElementById('localLoader');
const submitBtn = document.getElementById('localSubmitBtn');
btnText.classList.toggle('hidden', busy);
loader.classList.toggle('hidden', !busy);
submitBtn.disabled = busy;
}

document.getElementById('localLoginForm').addEventListener('submit', async function (e) {
e.preventDefault();

const form = this;
const errorDiv = document.getElementById('localError');
const successDiv = document.getElementById('localSuccess');

errorDiv.classList.add('hidden');
successDiv.classList.add('hidden');
localSetBusy(true);

try {
const response = await fetch('{{ route('auth.local.login') }}', {
method: 'POST',
headers: {
'Accept': 'application/json',
'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
'X-Requested-With': 'XMLHttpRequest'
},
body: new FormData(form)
});

const data = await response.json();

localSetBusy(false);

if (data.success) {
successDiv.classList.remove('hidden');
setTimeout(() => {
window.location.href = data.redirect_url || '/';
}, 1200);
return;
}

const verifyBox = document.getElementById('localVerifyBox');
const locatedBox = document.getElementById('localLocatedBox');
const loginFields = document.getElementById('localLoginFields');

if (data.located) {
locatedBox.classList.remove('hidden');
verifyBox.classList.add('hidden');
loginFields.classList.add('hidden');
} else if (data.verify) {
// Keep login/password in the form (hidden) so they are resubmitted with the code.
verifyBox.classList.remove('hidden');
locatedBox.classList.add('hidden');
loginFields.classList.add('hidden');
errorDiv.textContent = data.message || '';
errorDiv.classList.toggle('hidden', !data.message);
document.querySelector('#localVerifyBox input[name="code"]').focus();
} else {
errorDiv.textContent = data.message || 'Échec de la connexion. Veuillez réessayer.';
errorDiv.classList.remove('hidden');
}
} catch (err) {
console.error('Local login error:', err);
localSetBusy(false);
errorDiv.textContent = 'Erreur réseau. Vérifiez votre connexion.';
errorDiv.classList.remove('hidden');
}
});

document.getElementById('localResendBtn').addEventListener('click', async function () {
const form = document.getElementById('localLoginForm');
const errorDiv = document.getElementById('localError');
const successDiv = document.getElementById('localSuccess');
const btn = this;

errorDiv.classList.add('hidden');
successDiv.classList.add('hidden');
btn.disabled = true;
const original = btn.textContent;
btn.textContent = 'Envoi...';

const payload = new FormData();
payload.append('login', form.querySelector('input[name="login"]').value);
payload.append('password', form.querySelector('input[name="password"]').value);

try {
const response = await fetch('{{ route('auth.local.resend') }}', {
method: 'POST',
headers: {
'Accept': 'application/json',
'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
'X-Requested-With': 'XMLHttpRequest'
},
body: payload
});

const data = await response.json();

if (data.success) {
successDiv.textContent = data.message || 'Un nouveau code a été envoyé.';
successDiv.classList.remove('hidden');
} else {
errorDiv.textContent = data.message || "Impossible d'envoyer le code.";
errorDiv.classList.remove('hidden');
}
} catch (err) {
console.error('Resend code error:', err);
errorDiv.textContent = 'Erreur réseau. Vérifiez votre connexion.';
errorDiv.classList.remove('hidden');
} finally {
btn.disabled = false;
btn.textContent = original;
}
});

@if (($autoLoginSso ?? false))
// Auto-open the SSO authentication modal (login page only — not after logout/prelogin).
window.addEventListener('load', () => {
document.getElementById('ssoLoginBtn').click();
});
@endif
