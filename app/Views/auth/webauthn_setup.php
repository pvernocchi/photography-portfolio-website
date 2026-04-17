<?php
declare(strict_types=1);

use App\Core\CSRF;
?>
<header class="page-header">
    <h1>Security Keys</h1>
    <p class="muted">Register hardware security keys (YubiKey, etc.) as an alternative second factor alongside your authenticator app.</p>
</header>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>
<div class="alert alert-error" id="sk-error" hidden></div>
<div class="alert alert-success" id="sk-success" hidden></div>

<?php if (!empty($credentials)): ?>
<section class="card mb-24">
    <h2>Registered keys</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Registered</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($credentials as $cred): ?>
            <tr>
                <td><?= e($cred['name']) ?></td>
                <td><?= e($cred['created_at']) ?></td>
                <td>
                    <button type="button"
                            class="btn btn-danger btn-sm sk-delete-btn"
                            data-id="<?= (int) $cred['id'] ?>">Remove</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>

<section class="card">
    <h2>Register a new key</h2>
    <p class="muted">Give the key a name, then click <strong>Register</strong> and follow the browser prompt.</p>
    <div class="form-stack">
        <label>
            Key name
            <input type="text" id="sk-name" maxlength="255" placeholder="e.g. YubiKey 5C" value="Security Key">
        </label>
        <button type="button" class="btn btn-primary" id="sk-register-btn">Register Security Key</button>
        <p class="muted small" id="sk-status"></p>
    </div>
</section>

<script>
(function () {
  'use strict';

  var csrfToken = window.VernocchiAdmin ? window.VernocchiAdmin.csrfToken : '';

  function showError(msg) {
    var el = document.getElementById('sk-error');
    el.textContent = msg;
    el.hidden = false;
    document.getElementById('sk-success').hidden = true;
  }

  function showSuccess(msg) {
    var el = document.getElementById('sk-success');
    el.textContent = msg;
    el.hidden = false;
    document.getElementById('sk-error').hidden = true;
  }

  function setStatus(msg) {
    var el = document.getElementById('sk-status');
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

  document.getElementById('sk-register-btn').addEventListener('click', async function () {
    var btn = this;
    btn.disabled = true;
    setStatus('Requesting registration challenge\u2026');

    try {
      var optRes = await fetch('/admin/mfa/webauthn/register/options', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: csrfToken })
      });
      var optData = await optRes.json();
      if (!optData.ok) throw new Error(optData.error || 'Failed to get options.');

      var opts = optData.options;
      opts.challenge = b64urlToBuffer(opts.challenge);
      opts.user.id  = b64urlToBuffer(opts.user.id);

      setStatus('Touch your security key when prompted\u2026');
      var credential = await navigator.credentials.create({ publicKey: opts });

      var name = (document.getElementById('sk-name').value || 'Security Key').trim();
      var payload = {
        csrf_token: csrfToken,
        name:       name,
        id:         credential.id,
        rawId:      bufferToB64url(credential.rawId),
        type:       credential.type,
        response: {
          clientDataJSON:    bufferToB64url(credential.response.clientDataJSON),
          attestationObject: bufferToB64url(credential.response.attestationObject)
        }
      };

      setStatus('Saving key\u2026');
      var regRes = await fetch('/admin/mfa/webauthn/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      var regData = await regRes.json();
      if (!regData.ok) throw new Error(regData.error || 'Registration failed.');

      setStatus('');
      showSuccess('Security key registered successfully. Reloading\u2026');
      setTimeout(function () { window.location.reload(); }, 1200);
    } catch (err) {
      setStatus('');
      showError(err.message || 'Registration failed.');
      btn.disabled = false;
    }
  });

  // Delete buttons
  document.querySelectorAll('.sk-delete-btn').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      if (!confirm('Remove this security key?')) return;
      btn.disabled = true;
      var id = btn.dataset.id;
      try {
        var res = await fetch('/admin/mfa/webauthn/' + id + '/delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ csrf_token: csrfToken })
        });
        var data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Delete failed.');
        btn.closest('tr').remove();
        showSuccess('Security key removed.');
      } catch (err) {
        showError(err.message || 'Failed to remove key.');
        btn.disabled = false;
      }
    });
  });
}());
</script>
