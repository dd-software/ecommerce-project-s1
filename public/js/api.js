const api = {
    getToken: () => localStorage.getItem('jwt_token'),
    setToken: (token) => localStorage.setItem('jwt_token', token),
    clearToken: () => localStorage.removeItem('jwt_token'),
    getUser: () => JSON.parse(localStorage.getItem('user')),
    setUser: (user) => localStorage.setItem('user', JSON.stringify(user)),
    
    async request(endpoint, method = 'GET', body = null) {
        const headers = {
            'Content-Type': 'application/json'
        };
        const token = this.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        const config = { method, headers };
        if (body) {
            config.body = JSON.stringify(body);
        }
        
        try {
            // Resolución dinámica de URL para soportar subdirectorios de XAMPP transparentemente
            const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
            const apiUrl = basePath + '/api' + endpoint;
            
            const response = await fetch(apiUrl, config);
            const data = await response.json();
            
            if (!response.ok) {
                if (response.status === 401) {
                    this.clearToken();
                    localStorage.removeItem('user');
                }
                throw new Error(data.error || 'Error en la petición al servidor');
            }
            return data;
        } catch (error) {
            if(window.ui && typeof window.ui.showToast === 'function') {
                window.ui.showToast(error.message, 'danger');
            }
            throw error;
        }
    }
};
