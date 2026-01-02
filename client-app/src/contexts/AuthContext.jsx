import { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const checkAuth = async () => {
            try {
                // Adjust path based on where React is served relative to PHP
                // Development (via Proxy): /area-cliente/api/check_auth.php
                // Production (built): ./api/check_auth.php (if app is inside area-cliente/app)
                // Let's rely on the relative path from the domain root if possible or use the proxy path.
                // During dev: http://localhost:5173/area-cliente/api/check_auth.php -> proxied

                const response = await fetch('/area-cliente/api/check_auth.php');

                if (response.ok) {
                    const data = await response.json();
                    setUser(data.user);
                } else {
                    // Not authenticated
                    window.location.href = '/area-cliente/index.php'; // Redirect to PHP login
                }
            } catch (error) {
                console.error('Auth check failed', error);
                // On error, maybe also redirect or show error?
                // window.location.href = '/area-cliente/index.php';
            } finally {
                setLoading(false);
            }
        };

        checkAuth();
    }, []);

    return (
        <AuthContext.Provider value={{ user, loading }}>
            {!loading ? children : <div className="flex h-screen items-center justify-center">Carregando...</div>}
        </AuthContext.Provider>
    );
};

export const useAuth = () => useContext(AuthContext);
