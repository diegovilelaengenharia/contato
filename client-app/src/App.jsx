import { useState } from 'react'
import { useAuth } from './contexts/AuthContext'

function App() {
  const { user } = useAuth();
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);

  return (
    <div className="flex h-screen bg-gray-100">
      {/* Sidebar */}
      <aside
        className={`bg-blue-900 text-white transition-all duration-300 ${isSidebarOpen ? 'w-64' : 'w-20'
          } flex flex-col`}
      >
        <div className="p-4 flex items-center justify-between font-bold text-xl border-b border-blue-800">
          <span className={!isSidebarOpen ? 'hidden' : ''}>Vilela Eng.</span>
          <button onClick={() => setIsSidebarOpen(!isSidebarOpen)} className="p-1 hover:bg-blue-800 rounded">
            {isSidebarOpen ? '«' : '»'}
          </button>
        </div>

        <nav className="flex-1 p-4 space-y-2">
          <a href="#" className="block p-3 rounded hover:bg-blue-800 bg-blue-800">
            📊 <span className={!isSidebarOpen ? 'hidden' : 'ml-2'}>Dashboard</span>
          </a>
          <a href="#" className="block p-3 rounded hover:bg-blue-800">
            📂 <span className={!isSidebarOpen ? 'hidden' : 'ml-2'}>Projetos</span>
          </a>
          <a href="#" className="block p-3 rounded hover:bg-blue-800">
            👤 <span className={!isSidebarOpen ? 'hidden' : 'ml-2'}>Perfil</span>
          </a>
        </nav>
      </aside>

      {/* Main Content */}
      <main className="flex-1 overflow-auto">
        <header className="bg-white shadow p-4 flex justify-between items-center">
          <h1 className="text-2xl font-semibold text-gray-800">Área do Cliente</h1>
          <div className="flex items-center space-x-4">
            <span className="text-gray-600">Olá, {user?.name || 'Cliente'}</span>
            <div className="w-10 h-10 bg-blue-200 rounded-full flex items-center justify-center text-blue-800 font-bold overflow-hidden">
              {user?.foto ? <img src={user.foto} alt="Avatar" className="w-full h-full object-cover" /> : (user?.name?.[0] || 'C')}
            </div>
          </div>
        </header>

        <div className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {/* Stats Cards */}
            <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
              <h3 className="text-gray-500 text-sm font-medium">Projetos Ativos</h3>
              <p className="text-3xl font-bold text-gray-800 mt-2">3</p>
            </div>
            <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
              <h3 className="text-gray-500 text-sm font-medium">Pendências</h3>
              <p className="text-3xl font-bold text-yellow-600 mt-2">1</p>
            </div>
            <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
              <h3 className="text-gray-500 text-sm font-medium">Próxima Reunião</h3>
              <p className="text-lg font-bold text-gray-800 mt-2">15/01/2026</p>
            </div>
          </div>

          <div className="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 className="text-lg font-semibold mb-4">Avisos Recentes</h2>
            <div className="space-y-4">
              <div className="border-l-4 border-blue-500 bg-blue-50 p-4">
                <p className="text-sm text-blue-900">
                  Bem-vindo à nova área do cliente! Estamos atualizando o sistema para melhor atendê-lo.
                </p>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  )
}

export default App
