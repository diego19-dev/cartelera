(function () {
  var form = document.getElementById('user-form');
  if (!form) return;

  var pwd = form.querySelector('[name="password"]');
  var confirm = form.querySelector('[name="password_confirm"]');
  var idField = form.querySelector('[name="id"]');
  var isCreate = !idField || parseInt(idField.value, 10) <= 0;
  var reqs = form.querySelectorAll('[data-req]');
  var matchHint = document.getElementById('password-match-hint');

  function checks(password) {
    return {
      length: password.length >= 10,
      case: /[a-z]/.test(password) && /[A-Z]/.test(password),
      digit: /\d/.test(password),
      special: /[^a-zA-Z0-9]/.test(password),
    };
  }

  function allOk(c) {
    return c.length && c.case && c.digit && c.special;
  }

  function needsPassword() {
    return isCreate || (pwd && pwd.value.length > 0);
  }

  function update() {
    if (!pwd) return;
    var p = pwd.value;
    var active = needsPassword();
    var c = checks(p);

    reqs.forEach(function (li) {
      var key = li.getAttribute('data-req');
      if (!key) return;
      li.classList.remove('is-ok', 'is-fail', 'is-pending');
      if (!active || p.length === 0) {
        li.classList.add('is-pending');
        return;
      }
      li.classList.toggle('is-ok', !!c[key]);
      li.classList.toggle('is-fail', !c[key]);
    });

    if (!matchHint || !confirm) return;
    if (!active || (p.length === 0 && confirm.value.length === 0)) {
      matchHint.className = 'hint password-match-hint hidden';
      matchHint.textContent = '';
      return;
    }
    matchHint.classList.remove('hidden');
    if (confirm.value === p && p.length > 0) {
      matchHint.textContent = 'Las contraseñas coinciden';
      matchHint.className = 'hint password-match-hint is-ok';
    } else {
      matchHint.textContent = 'Las contraseñas no coinciden';
      matchHint.className = 'hint password-match-hint is-fail';
    }
  }

  function notify(message) {
    if (typeof toast === 'function') {
      toast(message, 'error');
      return;
    }
    alert(message);
  }

  if (pwd) pwd.addEventListener('input', update);
  if (confirm) confirm.addEventListener('input', update);
  update();

  form.addEventListener('submit', function (e) {
    if (!pwd || !needsPassword()) return;
    var p = pwd.value;
    var c = checks(p);

    if (!allOk(c)) {
      e.preventDefault();
      notify('La contraseña no cumple todos los requisitos de seguridad.');
      pwd.focus();
      update();
      return;
    }
    if (confirm && p !== confirm.value) {
      e.preventDefault();
      notify('Las contraseñas no coinciden.');
      confirm.focus();
      update();
    }
  });
})();
