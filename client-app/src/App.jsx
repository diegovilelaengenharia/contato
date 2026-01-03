import { useState, useEffect } from 'react'
import { useAuth } from './contexts/AuthContext'
import { useTheme } from './contexts/ThemeContext'
import {
  LayoutDashboard,
  FolderOpen,
  User,
  Bell,
  Search,
  Menu,
  LogOut,
  FileText,
  DollarSign,
  AlertTriangle,
  HelpCircle,
  ChevronRight,
  Settings,
  Home,
  HardDrive,
  Briefcase,
  Phone,
  Mail,
  MapPin,
  Maximize2,
  FileDigit,
  Sun,
  Moon
} from 'lucide-react';

import Card from './components/Card';
import ProgressBar from './components/ProgressBar';
import Timeline from './components/Timeline';
import FinanceWidget from './components/FinanceWidget';
import PendencyWidget from './components/PendencyWidget';
import UploadArea from './components/UploadArea';
import KPICards from './components/KPICards';

// --- REAL DATA FETCHING ---
function App() {
  const { user, loading: authLoading } = useAuth();
  const { isDarkMode, toggleTheme } = useTheme();
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [activeTab, setActiveTab] = useState('inicial');

  const [clientData, setClientData] = useState(null);
  const [dataLoading, setDataLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    document.body.style.paddingBottom = window.innerWidth < 768 ? '80px' : '0px';
    return () => { document.body.style.paddingBottom = '0px'; };
  }, []);

  // Fetch Client Data when User is Authenticated
  useEffect(() => {
    if (user) {
      fetch('/area-cliente/api/get_client_data.php', { credentials: 'include' })
        .then(res => {
          if (!res.ok) throw new Error('Falha ao carregar dados');
          return res.json();
        })
        .then(data => {
          setClientData(data);
          setDataLoading(false);
        })
        .catch(err => {
          console.error(err);
          setError(err.message);
          setDataLoading(false);
        });
    } else if (!authLoading && !user) {
      setDataLoading(false);
    }
  }, [user, authLoading]);

  // Loading State
  if (authLoading || (user && dataLoading)) {
    return (
      <div className="flex h-screen items-center justify-center bg-[#f8f9fa] dark:bg-gray-900 text-vilela-primary transition-colors">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-vilela-primary"></div>
      </div>
    );
  }

  // Fallback / Error State
  if (!user) {
    const debugInfo = window.auth_debug ? JSON.stringify(window.auth_debug, null, 2) : "Sem detalhes de erro.";
    return (
      <div className="h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 p-4 transition-colors">
        <h2 className="text-xl font-bold text-gray-700 dark:text-gray-200 mb-2">Acesso Restrito</h2>
        <p className="text-gray-500 dark:text-gray-400 mb-4">Por favor, faça login para acessar.</p>

        {/* DEBUG BOX */}
        <div className="bg-red-50 text-red-700 p-4 rounded text-xs font-mono mb-4 max-w-md w-full overflow-auto">
          <strong>Debug Info (Envie para o Suporte):</strong>
          <pre>{debugInfo}</pre>
        </div>

        <a href="/area-cliente/index.php?logout=true" className="bg-vilela-primary text-white px-6 py-2 rounded-lg font-bold hover:bg-vilela-primary/90 transition-colors">
          Tentar Login Novamente
        </a>
      </div>
    )
  }

  // Use Real Data (or safe fallbacks)
  const DATA = clientData || {};
  const processDetails = DATA.processDetails || {};
  const timeline = DATA.timeline || [];
  const financeiro = DATA.financeiro || [];
  const financeiroKPIs = DATA.financeiro_kpis || null;
  const pendencias = DATA.pendencias || [];
  const engineer = DATA.engineer || {};

  return (
    <div className={`flex h-screen bg-[#f8f9fa] dark:bg-gray-900 font-sans text-vilela-text dark:text-gray-100 text-sm transition-colors duration-300`}>

      {/* --- SIDEBAR (DESKTOP) --- */}
      <aside
        className={`bg-white dark:bg-gray-800 border-r border-[#e9ecef] dark:border-gray-700 transition-all duration-300 flex flex-col z-20
          ${isSidebarOpen ? 'w-72' : 'w-24'} 
          hidden md:flex shadow-[4px_0_24px_rgba(0,0,0,0.02)]
        `}
      >
        <div className="h-24 flex items-center justify-center border-b border-[#f1f3f5] dark:border-gray-700 bg-white dark:bg-gray-800">
          <div className={`flex items-center gap-3 font-bold text-lg text-vilela-primary transition-all duration-300 ${!isSidebarOpen && 'justify-center'}`}>
            <img src="/logo.png" alt="Vilela" className="h-10 w-auto object-contain" />
          </div>
        </div>

        {isSidebarOpen && (
          <div className="p-6 border-b border-[#f1f3f5] bg-white">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-full border-2 border-vilela-primary/10 p-0.5 shadow-sm">
                <div className="w-full h-full rounded-full bg-gray-50 flex items-center justify-center overflow-hidden">
                  {user?.foto ? <img src={DATA.user_photo || user.foto} className="w-full h-full object-cover" /> :
                    <span className="text-vilela-primary font-bold text-lg">{user?.name?.[0]}</span>}
                </div>
              </div>
              <div className="overflow-hidden">
                <p className="font-bold text-gray-800 truncate text-base">{user?.name}</p>
                <div className="flex items-center gap-1.5 mt-1">
                  <span className="w-2 h-2 rounded-full bg-vilela-primary animate-pulse"></span>
                  <span className="text-xs text-gray-500 font-medium">Cliente Ativo</span>
                </div>
              </div>
            </div>
          </div>
        )}

        <nav className="flex-1 p-4 space-y-2 overflow-y-auto">
          <NavItem icon={<Home size={20} />} label="Visão Geral" active={activeTab === 'inicial'} onClick={() => setActiveTab('inicial')} expanded={isSidebarOpen} />
          <NavItem icon={<AlertTriangle size={20} />} label="Pendências" badge={pendencias.filter(p => p.status === 'pendente').length} active={activeTab === 'pendencias'} onClick={() => setActiveTab('pendencias')} expanded={isSidebarOpen} />
          <NavItem icon={<DollarSign size={20} />} label="Financeiro" active={activeTab === 'financeiro'} onClick={() => setActiveTab('financeiro')} expanded={isSidebarOpen} />
          <NavItem icon={<HardDrive size={20} />} label="Arquivos" active={activeTab === 'arquivos'} onClick={() => setActiveTab('arquivos')} expanded={isSidebarOpen} />

          <div className="my-6 border-t border-gray-100 dark:border-gray-700 mx-4"></div>

          <NavItem icon={<FileText size={20} />} label="Protocolar" active={activeTab === 'protocolo'} onClick={() => setActiveTab('protocolo')} expanded={isSidebarOpen} />
          <div className={`px-4 mb-3 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider ${!isSidebarOpen && 'hidden'}`}>Central de Ajuda</div>

          <NavItem icon={<HelpCircle size={20} />} label="Suporte Técnico" active={activeTab === 'suporte'} onClick={() => setActiveTab('suporte')} expanded={isSidebarOpen} />
        </nav>

        {isSidebarOpen && (
          <div className="p-5 bg-[#f8f9fa] dark:bg-gray-900 border-t border-[#e9ecef] dark:border-gray-700">
            <div className="flex items-start gap-3">
              <div className="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-vilela-primary dark:text-emerald-400 shadow-sm shrink-0">
                <Briefcase size={18} />
              </div>
              <div>
                <p className="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider mb-0.5">Responsável Técnico</p>
                <p className="font-bold text-gray-800 dark:text-gray-200 text-sm leading-tight">{engineer.name}</p>
                <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{engineer.crea}</p>
              </div>
            </div>
          </div>
        )}
      </aside>

      {/* --- MAIN CONTENT --- */}
      <div className="flex-1 flex flex-col h-screen overflow-hidden relative bg-[#f8f9fa] dark:bg-gray-900 transition-colors duration-300">

        {/* Mobile Header */}
        <header className="md:hidden h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 z-20 shrink-0 shadow-sm relative sticky top-0 transition-colors">
          <div className="flex items-center gap-2">
            <img src="/logo.png" alt="Vilela" className="h-8 w-auto object-contain" />
          </div>
          <div className="flex items-center gap-4">
            {/* Dark Toggle Mobile */}
            <button onClick={toggleTheme} className="p-2 rounded-full text-gray-400 hover:text-vilela-primary dark:hover:text-emerald-400 transition-colors">
              {isDarkMode ? <Sun size={20} /> : <Moon size={20} />}
            </button>
            <div className="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-vilela-primary dark:text-emerald-400 font-bold overflow-hidden shadow-sm">
              {user?.foto ? <img src={DATA.user_photo || user.foto} className="w-full h-full object-cover" /> : user?.name?.[0]}
            </div>
          </div>
        </header>

        {/* Desktop Header */}
        <header className="hidden md:flex h-20 bg-white dark:bg-gray-800 border-b border-[#f1f3f5] dark:border-gray-700 items-center justify-between px-8 z-10 shrink-0 transition-colors">
          <div className="flex items-center gap-2 text-gray-500 dark:text-gray-400 text-sm">
            <span className="hover:text-vilela-primary dark:hover:text-emerald-400 cursor-pointer font-medium transition-colors">Home</span>
            <ChevronRight size={14} className="text-gray-300 dark:text-gray-600" />
            <span className="font-bold text-vilela-primary dark:text-emerald-400 capitalize bg-vilela-light/30 dark:bg-emerald-900/30 px-3 py-1 rounded-full text-xs">
              {activeTab === 'inicial' ? 'Visão Geral' : activeTab}
            </span>
          </div>

          <div className="flex items-center gap-6">
            <div className="text-right">
              {/* Removed Process Info per user request */}
            </div>

            {/* Dark Mode Toggle */}
            <button
              onClick={toggleTheme}
              className="flex items-center justify-center w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-all focus:outline-none"
              title="Alternar Tema"
            >
              {isDarkMode ? <Sun size={20} /> : <Moon size={20} />}
            </button>

            <div className="h-8 w-px bg-gray-100 dark:bg-gray-700"></div>
            <a href='/area-cliente/logout.php' className="text-gray-400 hover:text-status-danger transition-colors flex items-center gap-2 text-xs font-bold uppercase tracking-wide group">
              <LogOut size={16} className="group-hover:-translate-x-1 transition-transform" />
              Sair
            </a>
          </div>
        </header>

        {/* SCROLLABLE AREA */}
        <main className="flex-1 overflow-auto p-4 md:p-8 space-y-6 pb-24 md:pb-12 max-w-7xl mx-auto w-full scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700">

          {/* Title for non-inicial pages */}
          {activeTab !== 'inicial' && (
            <div className="mb-2">
              <h1 className="text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100 capitalize tracking-tight flex items-center gap-2">
                {activeTab}
              </h1>
            </div>
          )}

          {/* === VIEW: INICIAL === */}
          {activeTab === 'inicial' && (
            <div className="flex flex-col items-center justify-center h-[50vh] text-center opacity-50">
              <div className="mb-4 p-4 rounded-full bg-gray-50 border border-gray-100 text-gray-300">
                <LayoutDashboard size={48} strokeWidth={1} />
              </div>
              <h2 className="text-xl font-medium text-gray-400">Área de Trabalho Limpa</h2>
              <p className="text-sm text-gray-300">Pronto para início da construção.</p>
            </div>
          )}

          {/* === VIEW: PENDENCIAS === */}
          {activeTab === 'pendencias' && (
            <div className="flex items-center justify-center h-[50vh] text-gray-300">Módulo Pendências (Vazio)</div>
          )}

          {/* === VIEW: FINANCEIRO === */}
          {activeTab === 'financeiro' && (
            <div className="flex items-center justify-center h-[50vh] text-gray-300">Módulo Financeiro (Vazio)</div>
          )}

          {/* === VIEW: ARQUIVOS (DRIVE) === */}
          {activeTab === 'arquivos' && (
            <div className="flex items-center justify-center h-[50vh] text-gray-300">Módulo Arquivos (Vazio)</div>
          )}

          {/* === VIEW: PROTOCOLO DIGITAL === */}
          {activeTab === 'protocolo' && (
            <UploadArea clientId={user?.id} />
          )}

        </main>

        {/* --- BOTTOM NAVIGATION (MOBILE ONLY - DOCK STYLE) --- */}
        <nav className="md:hidden fixed bottom-4 left-4 right-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur-lg border border-gray-200 dark:border-gray-700 rounded-2xl flex justify-around items-center h-16 shadow-[0_8px_30px_rgba(0,0,0,0.12)] z-50">
          <BottomNavItem icon={<Home size={20} />} label="Home" active={activeTab === 'inicial'} onClick={() => setActiveTab('inicial')} />
          <BottomNavItem icon={<AlertTriangle size={20} />} label="Pend." active={activeTab === 'pendencias'} onClick={() => setActiveTab('pendencias')} badge={pendencias.filter(p => p.status === 'pendente').length} />
          <BottomNavItem icon={<DollarSign size={20} />} label="Fin." active={activeTab === 'financeiro'} onClick={() => setActiveTab('financeiro')} />
          <BottomNavItem icon={<FileText size={20} />} label="Prot." active={activeTab === 'protocolo'} onClick={() => setActiveTab('protocolo')} />
          <BottomNavItem icon={<HardDrive size={20} />} label="Arq." active={activeTab === 'arquivos'} onClick={() => setActiveTab('arquivos')} />
        </nav>

      </div>
    </div>
  )
}

function NavItem({ icon, label, active, onClick, expanded, badge }) {
  return (
    <button
      onClick={onClick}
      className={`relative flex items-center gap-3 p-3 rounded-xl transition-all w-full group outline-none
       ${active
          ? 'bg-vilela-primary/10 text-vilela-primary font-bold shadow-sm ring-1 ring-vilela-primary/20'
          : 'text-gray-500 hover:bg-gray-50 hover:text-vilela-primary/80'}
    `}>
      <span className={`${active ? 'text-vilela-primary' : 'text-gray-400 group-hover:text-vilela-primary/70'} transition-colors`}>
        {icon}
      </span>

      {expanded && (
        <span className="text-sm whitespace-nowrap">
          {label}
        </span>
      )}

      {expanded && badge > 0 && (
        <span className={`ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded-md ${active ? 'bg-vilela-primary text-white' : 'bg-status-danger text-white'
          }`}>
          {badge}
        </span>
      )}

      {/* Active Indicator (Clean Dot instead of bar) */}
      {active && <div className="absolute right-2 w-1.5 h-1.5 bg-vilela-primary rounded-full"></div>}
    </button>
  )
}

function BottomNavItem({ icon, label, active, onClick, badge }) {
  // Mobile Dock Item Style
  return (
    <button onClick={onClick} className="flex flex-col items-center justify-center w-full h-full relative p-1 outline-none tap-highlight-transparent group">
      <div className={`p-1.5 rounded-xl transition-all duration-300 
          ${active
          ? 'text-vilela-primary dark:text-emerald-400 -translate-y-2 bg-white dark:bg-gray-700/50 shadow-sm ring-1 ring-gray-100 dark:ring-gray-600'
          : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300'}`}>
        {icon}
      </div>
      {/* Label only visible if active or minimal on mobile? kept minimal for dock feel */}
      {/* <span className={`text-[9px] font-bold mt-0.5 ${active ? 'text-vilela-primary' : 'text-gray-300'}`}>{label}</span> */}

      {badge > 0 && (
        <span className="absolute top-2 right-4 w-3.5 h-3.5 bg-status-danger text-white text-[8px] flex items-center justify-center rounded-full border border-white dark:border-gray-800 shadow-sm">
          {badge}
        </span>
      )}

      {active && <span className="absolute bottom-2 w-1 h-1 rounded-full bg-vilela-primary dark:bg-emerald-400"></span>}
    </button>
  )
}

import React from 'react';
export default App
