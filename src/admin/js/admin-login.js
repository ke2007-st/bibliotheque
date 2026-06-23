document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('admin-login-form');
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        showFormError(form, '');
        setFormLoading(form, true);

        try {
            await AdminAuthAPI.login({
                email: document.getElementById('email').value.trim(),
                mot_de_passe: document.getElementById('mot_de_passe').value,
            });
            window.location.href = 'index.html';
        } catch (err) {
            showFormError(form, err.message);
        } finally {
            setFormLoading(form, false);
        }
    });
});
