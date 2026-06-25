async function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPass').value;
    
    try {
        const res = await api.request('/auth/login', 'POST', { email, password });
        api.setToken(res.token);
        api.setUser(res.user);
        ui.showToast(`Bienvenido de nuevo, ${res.user.first_name}!`, 'success');
        setTimeout(() => window.location.href = 'index.html', 1000);
    } catch (e) {}
}

async function handleRegister(e) {
    e.preventDefault();
    const first_name = document.getElementById('regFirst').value;
    const last_name = document.getElementById('regLast').value;
    const email = document.getElementById('regEmail').value;
    const password = document.getElementById('regPass').value;
    
    try {
        await api.request('/auth/register', 'POST', { first_name, last_name, email, password });
        ui.showToast('Registro exitoso. Por favor, inicia sesión ahora.', 'success');
        document.getElementById('regForm').reset();
    } catch(e) {}
}
