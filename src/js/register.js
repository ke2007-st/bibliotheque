document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('register-form');
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        showFormError(form, '');

        const user = {
            nom: document.getElementById('nom').value.trim(),
            prenom: document.getElementById('prenom').value.trim(),
            email: document.getElementById('email').value.trim(),
            telephone: document.getElementById('telephone').value.trim(),
            mot_de_passe: document.getElementById('mot_de_passe').value,
            confirmer_mot_de_passe: document.getElementById('confirmer_mot_de_passe').value,
        };

        setFormLoading(form, true);

        try {
            const result = await AuthAPI.register(user);
            if (result.csrf_token) userCsrfToken = result.csrf_token;
            showToast('Inscription reussie !', 'success');
            window.location.href = 'index.html';
        } catch (err) {
            showFormError(form, err.message);
        } finally {
            setFormLoading(form, false);
        }
    });
});
