(function () {
    'use strict';

    /* Menu latéral (mobile) */
    var toggle = document.getElementById('menu-toggle');
    var scrim = document.getElementById('scrim');
    if (toggle) {
        toggle.addEventListener('click', function () { document.body.classList.toggle('nav-open'); });
    }
    if (scrim) {
        scrim.addEventListener('click', function () { document.body.classList.remove('nav-open'); });
    }

    /* Fenêtres modales : ouverture, préremplissage, fermeture */
    document.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-open]');
        if (opener) {
            var dialog = document.getElementById(opener.getAttribute('data-open'));
            if (!dialog) { return; }
            var form = dialog.querySelector('form');
            if (form) { form.reset(); }
            var raw = opener.getAttribute('data-fill');
            if (raw && form) {
                try {
                    var data = JSON.parse(raw);
                    Object.keys(data).forEach(function (key) {
                        if (form.elements[key]) { form.elements[key].value = data[key]; }
                    });
                } catch (e) { /* données illisibles : on ouvre le formulaire vide */ }
            }
            dialog.showModal();
            var first = dialog.querySelector('input[type=text], input[type=number], select');
            if (first) { first.focus(); }
            return;
        }
        var closer = event.target.closest('[data-close]');
        if (closer) {
            var parent = closer.closest('dialog');
            if (parent) { parent.close(); }
            return;
        }
        if (event.target.tagName === 'DIALOG') { event.target.close(); }

        var dismiss = event.target.closest('[data-dismiss]');
        if (dismiss) { dismiss.parentElement.remove(); }
    });

    /* Confirmation avant action sensible */
    document.addEventListener('submit', function (event) {
        var message = event.target.getAttribute('data-confirm');
        if (message && !window.confirm(message)) { event.preventDefault(); }
    });

    /* Messages de succès : disparition automatique */
    setTimeout(function () {
        document.querySelectorAll('.flash-success').forEach(function (el) { el.remove(); });
    }, 7000);

    /* Comptes de démonstration sur la page de connexion */
    document.querySelectorAll('[data-login]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('login').value = btn.getAttribute('data-login');
            document.getElementById('password').value = 'pass123';
            document.getElementById('password').focus();
        });
    });

    /* Demande de sang : le groupe du patient est proposé automatiquement */
    var patient = document.getElementById('demande-patient');
    var groupe = document.getElementById('demande-groupe');
    if (patient && groupe) {
        patient.addEventListener('change', function () {
            var option = patient.options[patient.selectedIndex];
            var g = option ? option.getAttribute('data-groupe') : '';
            if (g) { groupe.value = g; }
        });
    }
})();
