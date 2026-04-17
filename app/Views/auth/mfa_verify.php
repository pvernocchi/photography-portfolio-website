<?php
declare(strict_types=1);

use App\Core\CSRF;
?>
<div class="auth-wrap">
    <section class="card auth-card">
        <h1>Multi-factor verification</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" id="mfa-error"><?= e($error) ?></div>
        <?php else: ?>
            <div class="alert alert-error" id="mfa-error" hidden></div>
        <?php endif; ?>

        <?php if (!empty($webauthn_enabled)): ?>
        <div class="tabs mfa-tabs">
            <button class="tab-btn active" data-tab="totp">Authenticator App</button>
            <button class="tab-btn" data-tab="webauthn">Security Key</button>
        </div>
        <?php endif; ?>

        <div class="tab-panel active" data-panel="totp" id="totp-panel">
            <p class="muted">Enter your 6-digit authentication code.</p>
            <form method="post" action="/admin/mfa/verify" class="form-stack">
                <?= CSRF::field() ?>
                <label>
                    Authentication code
                    <input type="text" name="code" required inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" autofocus>
                </label>
                <button type="submit" class="btn btn-primary">Verify</button>
            </form>
        </div>

        <?php if (!empty($webauthn_enabled)): ?>
        <div class="tab-panel" data-panel="webauthn" id="webauthn-panel">
            <p class="muted">Insert your security key and tap it when prompted.</p>
            <button type="button" class="btn btn-primary btn-block" id="webauthn-assert-btn">Use Security Key</button>
            <p class="muted mt-8 small" id="webauthn-status"></p>
        </div>
        <?php endif; ?>

        <p class="mt-16"><a href="/admin/login">Back to login</a></p>
    </section>
</div>

<?php if (!empty($webauthn_enabled)): ?>
<script>
(function () {
  'use strict';

  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

  // Tab switching
  document.querySelectorAll('.mfa-tabs .tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.mfa-tabs .tab-btn').forEach(function (b) {
        b.classList.remove('active');
      });
      document.querySelectorAll('.tab-panel').forEach(function (p) {
        p.classList.remove('active');
      });
      btn.classList.add('active');
      var panel = document.querySelector('[data-panel="' + btn.dataset.tab + '"]');
      if (panel) panel.classList.add('active');
    });
  });

  function showError(msg) {
    var el = document.getElementById('mfa-error');
    if (!el) return;
    el.textContent = msg;
    el.hidden = false;
  }

  function setStatus(msg) {
    var el = document.getElementById('webauthn-status');
    if (el) el.textContent = msg;
  }

  function b64urlToBuffer(b64url) {
    var padded = b64url.replace(/-/g, '+').replace(/_/g, '/');
    while (padded.length % 4) padded += '=';
    var binary = atob(padded);
    var buf = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) buf[i] = binary.charCodeAt(i);
    return buf.buffer;
  }

  function bufferToB64url(buffer) {
    var bytes = new Uint8Array(buffer);
    var binary = '';
    for (var i = 0; i < bytes.byteLength; i++) binary += String.fromCharCode(bytes[i]);
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  document.getElementById('webauthn-assert-btn').addEventListener('click', async function () {
    var btn = this;
    btn.disabled = true;
    setStatus('Requesting challenge\u2026');

    try {
      var optRes = await fetch('/admin/mfa/webauthn/assert/options', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: csrfToken })
      });
      var optData = await optRes.json();
      if (!optData.ok) throw new Error(optData.error || 'Failed to get options.');

      var opts = optData.options;
      opts.challenge = b64urlToBuffer(opts.challenge);
      if (opts.allowCredentials) {
        opts.allowCredentials = opts.allowCredentials.map(function (c) {
          return Object.assign({}, c, { id: b64urlToBuffer(c.id) });
        });
      }

      setStatus('Touch your security key\u2026');
      var credential = await navigator.credentials.get({ publicKey: opts });

      var assertionPayload = {
        csrf_token: csrfToken,
        id:         credential.id,
        rawId:      bufferToB64url(credential.rawId),
        type:       credential.type,
        response: {
          clientDataJSON:    bufferToB64url(credential.response.clientDataJSON),
          authenticatorData: bufferToB64url(credential.response.authenticatorData),
          signature:         bufferToB64url(credential.response.signature),
          userHandle: credential.response.userHandle
            ? bufferToB64url(credential.response.userHandle) : null
        }
      };

      setStatus('Verifying\u2026');
      var verifyRes = await fetch('/admin/mfa/webauthn/assert', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(assertionPayload)
      });
      var verifyData = await verifyRes.json();
      if (!verifyData.ok) throw new Error(verifyData.error || 'Verification failed.');

      setStatus('Verified! Redirecting\u2026');
      window.location.href = verifyData.redirect || '/admin/dashboard';
    } catch (err) {
      setStatus('');
      showError(err.message || 'Security key verification failed.');
      btn.disabled = false;
    }
  });
}());
</script>
<?php endif; ?>
