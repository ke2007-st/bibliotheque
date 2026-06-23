document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('login-form');
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        showFormError(form, '');

        const credentials = {
            email: document.getElementById('email').value.trim(),
            mot_de_passe: document.getElementById('mot_de_passe').value,
        };

        setFormLoading(form, true);

        try {
            const result = await AuthAPI.login(credentials);
            if (result.csrf_token) userCsrfToken = result.csrf_token;
            showToast('Connexion reussie', 'success');
            window.location.href = 'index.html';
        } catch (err) {
            showFormError(form, err.message);
        } finally {
            setFormLoading(form, false);
        }
    });
});
