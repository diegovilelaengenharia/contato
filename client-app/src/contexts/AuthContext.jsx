import { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const checkAuth = async () => {
      try {
        // Added timestamp to prevent caching and credentials include
        const response = await fetch('/area-cliente/api/check_auth.php?t=' + Date.now(), {
          credentials: 'include' // FORCE sending cookies
        });
        if (response.ok) {
          const data = await response.json();
          if (data.authenticated) {
            setUser(data.user);
          } else {
            console.warn("User not authenticated by API", data);
            // Save debug info to window for UI to display
            window.auth_debug = data;
            // Force update to show debug UI if needed (via state in App.jsx presumably, or just log here)
            // But since setUser(null) is default, App will show "Acesso Restrito".
          }
        } else {
          console.error("Auth check response verify failed", response.status);
          let errorDetail = { error: "HTTP " + response.status };
          try {
            // Try to parse the error explanation from the server
            const errData = await response.json();
            errorDetail = { ...errorDetail, ...errData };
          } catch (e) {
            console.warn("Could not parse error response", e);
          }
          window.auth_debug = errorDetail;
        }
      } catch (error) {
        console.error('Auth check failed', error);
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
