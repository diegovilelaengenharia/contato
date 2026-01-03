import { useState, useEffect } from 'react'
import { useAuth } from './contexts/AuthContext'
import { useTheme } from './contexts/ThemeContext'
import './App.css' // Import Standard CSS

import {
  LayoutDashboard,
  LayoutList,
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
    // Basic viewport fix for mobile
    document.body.style.paddingBottom = window.innerWidth < 768 ? '80px' : '0px';
    return () => { document.body.style.paddingBottom = '0px'; };
  }, []);

  // Fetch Client Data
  useEffect(() => {
    if (user) {
      if (window.DATA) { // Dev Mode Mock
        console.log("DEV MODE: Mock Data Loaded");
        setClientData(window.DATA);
        setDataLoading(false);
        return;
      }

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

  // States
  if (authLoading || (user && dataLoading)) return <div className="flex-center" style={{ height: '100vh', color: 'var(--color-primary)' }}>Carregando...</div>;

  if (!user) {
    return (
      <div className="flex-center flex-col" style={{ height: '100vh', padding: 20 }}>
        <h2>Acesso Restrito</h2>
        <p>Faça login para continuar.</p>
        <a href="/area-cliente/" className="nav-item active" style={{ display: 'inline-flex', width: 'auto', marginTop: 20 }}>Login</a>
      </div>
    )
  }

  const DATA = clientData || {};
  const processDetails = DATA.processDetails || {};
  const timeline = DATA.timeline || [];
  const financeiro = DATA.financeiro || [];
  const financeiroKPIs = DATA.financeiro_kpis || null;
  const pendencias = DATA.pendencias || [];
  const engineer = DATA.engineer || {};

  return (
    <div className="app-container">

      {/* --- DESKTOP SIDEBAR --- */}
      <aside className={`sidebar ${!isSidebarOpen ? 'closed' : ''}`}>
        <div className="sidebar-header">
          <img src="/logo.png" alt="Vilela" className="logo-img" />
        </div>

        {/* User Snippet */}
        <div className="sidebar-profile">
          <div className="user-snippet">
            {user?.foto ? <img src={DATA.user_photo || user.foto} className="user-avatar-small" /> :
              <div className="user-avatar-small flex-center" style={{ background: '#eee', color: '#999' }}>{user?.name?.[0]}</div>}

            {isSidebarOpen && <div className="user-meta">
              <h3>{user?.name?.split(' ')[0]}</h3>
              <span>Cliente Vilela</span>
            </div>}
          </div>
        </div>

        {/* Navigation */}
        <nav className="nav-list">
          <NavItem icon={<Home size={20} />} label="Início" active={activeTab === 'inicial'} onClick={() => setActiveTab('inicial')} expanded={isSidebarOpen} />
          <NavItem icon={<LayoutList size={20} />} label="Timeline" active={activeTab === 'timeline'} onClick={() => setActiveTab('timeline')} expanded={isSidebarOpen} />
          <NavItem icon={<AlertTriangle size={20} />} label="Pendências" active={activeTab === 'pendencias'} onClick={() => setActiveTab('pendencias')} expanded={isSidebarOpen} badge={pendencias.length} />
          <div style={{ height: 1, background: 'var(--ios-separator)', margin: '12px 0' }}></div>
          <NavItem icon={<DollarSign size={20} />} label="Financeiro" active={activeTab === 'financeiro'} onClick={() => setActiveTab('financeiro')} expanded={isSidebarOpen} />
          <NavItem icon={<HardDrive size={20} />} label="Documentos" active={activeTab === 'documentos'} onClick={() => setActiveTab('documentos')} expanded={isSidebarOpen} />
        </nav>
      </aside>

      {/* --- MAIN CONTENT --- */}
      <div className="main-content">

        {/* Desktop Header */}
        <header className="desktop-header">
          <div className="breadcrumb">
            {activeTab === 'inicial' ? 'Visão Geral' : activeTab.charAt(0).toUpperCase() + activeTab.slice(1)}
          </div>
          {/* Engineer Info or Actions */}
          <div style={{ display: 'flex', gap: 16 }}>
            <button onClick={toggleTheme} style={{ background: 'none', border: 'none', cursor: 'pointer' }}>
              {isDarkMode ? <Sun size={20} /> : <Moon size={20} />}
            </button>
          </div>
        </header>

        {/* Scrollable Area */}
        <main className="content-scrollable animate-in">

          {/* === VIEW: HERO (INICIAL) === */}
          {activeTab === 'inicial' && (
            <>
              <div className="hero-card">
                <div className="hero-avatar-container">
                  {DATA.user_photo ?
                    <img src={DATA.user_photo} className="hero-avatar" /> :
                    <div className="hero-avatar flex-center" style={{ background: '#eee', fontSize: 32 }}>{user?.name?.[0]}</div>
                  }
                </div>
                <div className="hero-info">
                  <h1>{user?.name}</h1>
                  <div className="info-pills">
                    <div className="info-pill"><User size={14} /> {DATA.client_info?.cpf || 'CPF --'}</div>
                    <div className="info-pill"><MapPin size={14} /> {processDetails.address || 'Sem endereço'}</div>
                  </div>
                </div>
              </div>
              {/* Additional Content Here */}
            </>
          )}

          {/* === VIEW: TIMELINE === */}
          {activeTab === 'timeline' && (
            <div className="ios-section">
              <h2>Acompanhamento</h2>
              <Timeline movements={timeline} />
            </div>
          )}

          {/* === VIEW: PENDENCIAS === */}
          {activeTab === 'pendencias' && (
            <div className="ios-section">
              <h2>Pendências do Processo</h2>
              <PendencyWidget pendencias={pendencias} />
            </div>
          )}

          {/* === VIEW: FINANCEIRO === */}
          {activeTab === 'financeiro' && (
            <div className="ios-section">
              <KPICards kpis={financeiroKPIs} />
              <div style={{ marginTop: 24 }}>
                <FinanceWidget financeiro={financeiro} />
              </div>
            </div>
          )}

          {/* === VIEW: DOCUMENTOS === */}
          {activeTab === 'documentos' && (
            <div className="hero-card" style={{ height: '100%' }}>
              {DATA.driveLink ?
                <iframe src={DATA.driveLink} style={{ width: '100%', height: '500px', border: 'none' }}></iframe> :
                <p>Drive não conectado.</p>
              }
            </div>
          )}

        </main>

        {/* --- MOBILE DOCK --- */}
        <nav className="mobile-dock">
          <DockItem icon={<Home size={24} />} active={activeTab === 'inicial'} onClick={() => setActiveTab('inicial')} />
          <DockItem icon={<LayoutList size={24} />} active={activeTab === 'timeline'} onClick={() => setActiveTab('timeline')} />
          <DockItem icon={<AlertTriangle size={24} />} active={activeTab === 'pendencias'} onClick={() => setActiveTab('pendencias')} badge={pendencias.length} />
          <DockItem icon={<DollarSign size={24} />} active={activeTab === 'financeiro'} onClick={() => setActiveTab('financeiro')} />
          <DockItem icon={<HardDrive size={24} />} active={activeTab === 'documentos'} onClick={() => setActiveTab('documentos')} />
        </nav>
      </div>
    </div>
  )
}

// --- SUBCOMPONENTS ---

function NavItem({ icon, label, active, onClick, expanded, badge }) {
  return (
    <button className={`nav-item ${active ? 'active' : ''}`} onClick={onClick} title={label}>
      {icon}
      {expanded && <span>{label}</span>}
      {expanded && badge > 0 && <span className="nav-badge">{badge}</span>}
    </button>
  )
}

function DockItem({ icon, active, onClick, badge }) {
  return (
    <button className={`dock-item ${active ? 'active' : ''}`} onClick={onClick}>
      {icon}
      {badge > 0 && <span style={{ position: 'absolute', top: 12, right: 12, width: 8, height: 8, background: 'red', borderRadius: '50%' }}></span>}
    </button>
  )
}

export default App
import { useAuth } from './contexts/AuthContext'
import { useTheme } from './contexts/ThemeContext'
import {
  LayoutDashboard,
  LayoutList,
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
      // DEV MODE: Use Mock Data if available
      if (window.DATA) {
        console.log("DEV MODE: Mock Data Loaded");
        setClientData(window.DATA);
        setDataLoading(false);
        return;
      }

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
    <div className={`flex h-screen bg-gray-50/50 dark:bg-black font-sans text-vilela-text dark:text-gray-100 text-sm transition-colors duration-300`}>

      {/* --- SIDEBAR (DESKTOP) --- */}
      <aside
        className={`bg-white dark:bg-gray-900 border-r border-vilela-border dark:border-gray-800 transition-all duration-300 flex flex-col z-20
          ${isSidebarOpen ? 'w-72' : 'w-24'} 
          hidden md:flex shadow-[4px_0_24px_rgba(0,0,0,0.01)]
        `}
      >
        <div className="h-24 flex items-center justify-center border-b border-vilela-border dark:border-gray-800 bg-white dark:bg-gray-900">
          <div className={`flex items-center gap-3 font-bold text-lg text-vilela-dark transition-all duration-300 ${!isSidebarOpen && 'justify-center'}`}>
            <img src="/logo.png" alt="Vilela" className="h-10 w-auto object-contain" />
          </div>
        </div>

        {isSidebarOpen && (
          <div className="p-6 border-b border-[#f1f3f5] bg-white">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-full border-2 border-vilela-primary/20 p-0.5 shadow-sm bg-white">
                <div className="w-full h-full rounded-full bg-gray-50 flex items-center justify-center overflow-hidden">
                  {user?.foto ? <img src={DATA.user_photo || user.foto} className="w-full h-full object-cover" /> :
                    <span className="text-vilela-primary font-bold text-lg">{user?.name?.[0]}</span>}
                </div>
              </div>
              <div className="overflow-hidden">
                <p className="font-bold text-vilela-dark truncate text-base">{user?.name}</p>
                <div className="flex items-center gap-1.5 mt-0.5">
                  <span className="w-1.5 h-1.5 rounded-full bg-vilela-primary/80 animate-pulse"></span>
                  <span className="text-xs text-vilela-subtle font-medium">Online</span>
                </div>
              </div>
            </div>
            {/* Navigation Items */}
            <nav className="flex-1 px-4 space-y-2 overflow-y-auto custom-scrollbar relative z-30">
              <div className="pt-2"> {/* Spacer */}
                <NavItem
                  icon={<Home size={20} />}
                  label="Página Inicial"
                  active={activeTab === 'inicial'}
                  onClick={() => setActiveTab('inicial')}
                  expanded={isSidebarOpen}
                />

                <NavItem
                  icon={<LayoutList size={20} />}
                  label="Timeline"
                  active={activeTab === 'timeline'}
                  onClick={() => setActiveTab('timeline')}
                  expanded={isSidebarOpen}
                />

                <NavItem
                  icon={<AlertTriangle size={20} />}
                  label="Pendências"
                  active={activeTab === 'pendencias'}
                  onClick={() => setActiveTab('pendencias')}
                  expanded={isSidebarOpen}
                  badge={pendencias.filter(p => p.status !== 'resolvido' && p.status !== 'anexado').length}
                />

                {/* Separator / Group Label */}
                {isSidebarOpen && <div className="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-3 mt-6 mb-2">Gestão</div>}

                <NavItem
                  icon={<DollarSign size={20} />}
                  label="Financeiro"
                  active={activeTab === 'financeiro'}
                  onClick={() => setActiveTab('financeiro')}
                  expanded={isSidebarOpen}
                />

                <NavItem
                  icon={<FileText size={20} />}
                  label="Documentos"
                  active={activeTab === 'documentos'}
                  onClick={() => setActiveTab('documentos')}
                  expanded={isSidebarOpen}
                />
              </div>
            </nav>
          </div>
        )}

        <nav className="flex-1 p-4 space-y-2 overflow-y-auto">
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
            <div className="animate-fade-in-up">
              {/* HERO CARD v2 (Clean & Detailed) */}
              <div className="bg-white dark:bg-gray-800 rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col md:flex-row items-center gap-6 md:gap-8 mb-8 relative overflow-hidden group">
                {/* Decorative Background Blur */}
                <div className="absolute top-0 right-0 w-64 h-64 bg-vilela-primary/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>

                {/* Avatar */}
                <div className="relative shrink-0">
                  <div className="w-24 h-24 md:w-32 md:h-32 rounded-full border-4 border-white dark:border-gray-700 shadow-lg overflow-hidden relative z-10 group-hover:scale-105 transition-transform duration-500">
                    {DATA.user_photo ? (
                      <img src={DATA.user_photo} alt="Avatar" className="w-full h-full object-cover" />
                    ) : (
                      <div className="w-full h-full bg-gray-100 dark:bg-gray-600 flex items-center justify-center text-gray-400 text-3xl font-bold">
                        {user?.name?.charAt(0)}
                      </div>
                    )}
                  </div>
                  {/* Status Indicator */}
                  <div className="absolute bottom-2 right-2 w-6 h-6 bg-vilela-primary border-4 border-white dark:border-gray-800 rounded-full z-20"></div>
                </div>

                {/* Client Info */}
                <div className="flex-1 text-center md:text-left z-10">
                  <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    {user?.name}
                  </h1>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    {/* CPF */}
                    <div className="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 px-4 py-2 rounded-xl">
                      <div className="bg-white dark:bg-gray-600 p-1.5 rounded-lg text-vilela-primary dark:text-emerald-400 shadow-sm">
                        <User size={16} strokeWidth={2.5} />
                      </div>
                      <span className="font-medium text-sm">{DATA.client_info?.cpf || 'CPF não informado'}</span>
                    </div>

                    {/* Address */}
                    <div className="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 px-4 py-2 rounded-xl">
                      <div className="bg-white dark:bg-gray-600 p-1.5 rounded-lg text-vilela-primary dark:text-emerald-400 shadow-sm">
                        <MapPin size={16} strokeWidth={2.5} />
                      </div>
                      <span className="font-medium text-sm truncate max-w-[200px]" title={processDetails.address}>
                        {processDetails.address || 'Endereço não cadastrado'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Optional: Simple Process Status Overview Component to fill space? */}
              {/* Keeping it simple for now as requested "Button by Button" - just the Hero Card first */}
            </div>
          )}

          {/* === VIEW: TIMELINE === */}
          {activeTab === 'timeline' && (
            <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
              <div className="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50 dark:border-gray-700">
                <h2 className="text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                  <LayoutList className="text-vilela-primary" /> Linha do Tempo
                </h2>
              </div>
              <Timeline movements={timeline} />
            </div>
          )}

          {/* === VIEW: PENDENCIAS === */}
          {activeTab === 'pendencias' && (
            <div className="space-y-6">
              <div className="bg-white dark:bg-gray-800 rounded-2xl p-6 md:p-8 border border-gray-100 dark:border-gray-700 shadow-sm transition-colors">
                <div className="flex items-center gap-3 mb-6 pb-6 border-b border-gray-50 dark:border-gray-700">
                  <h2 className="text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <AlertTriangle className="text-orange-500" /> Pendências
                  </h2>
                </div>
                <PendencyWidget pendencias={pendencias} />
              </div>
            </div>
          )}

          {/* === VIEW: FINANCEIRO === */}
          {activeTab === 'financeiro' && (
            <div className="space-y-6">
              <KPICards kpis={financeiroKPIs} />
              <div className="bg-white dark:bg-gray-800 rounded-2xl p-6 md:p-8 border border-gray-100 dark:border-gray-700 shadow-sm transition-colors">
                <div className="flex items-center gap-3 mb-6 pb-6 border-b border-gray-50 dark:border-gray-700">
                  <h2 className="text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <DollarSign className="text-vilela-primary" /> Histórico Financeiro
                  </h2>
                </div>
                <FinanceWidget financeiro={financeiro} />
              </div>
            </div>
          )}

          {/* === VIEW: DOCUMENTOS (DRIVE) === */}
          {activeTab === 'documentos' && (
            <div className="h-full flex flex-col">
              <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex-1 flex flex-col overflow-hidden min-h-[500px] transition-colors">
                <div className="p-4 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 flex justify-between items-center">
                  <h3 className="font-bold text-gray-700 dark:text-gray-200 flex items-center gap-2"><FileText size={18} /> Acervo Digital</h3>
                  <button onClick={() => window.open(DATA.driveLink, '_blank')} className="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-3 py-1.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 font-bold flex items-center gap-1 border border-gray-200 dark:border-gray-600 transition-colors">
                    Abrir Externamente <ChevronRight size={12} />
                  </button>
                </div>
                <div className="flex-1 bg-gray-50 dark:bg-gray-900 relative">
                  {DATA.driveLink ?
                    <iframe
                      src={DATA.driveLink}
                      className="w-full h-full border-0 absolute inset-0"
                      title="Google Drive"
                    ></iframe>
                    :
                    <div className="flex items-center justify-center h-full text-gray-400 dark:text-gray-600">Pasta do Drive não vinculada.</div>
                  }
                </div>
              </div>
            </div>
          )}

          {/* === VIEW: PROTOCOLO DIGITAL === */}
          {activeTab === 'protocolo' && (
            <UploadArea clientId={user?.id} />
          )}

        </main>

        {/* --- BOTTOM NAVIGATION (MOBILE ONLY - DOCK STYLE) --- */}
        <nav className="md:hidden fixed bottom-4 left-4 right-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur-lg border border-gray-200 dark:border-gray-700 rounded-2xl flex justify-around items-center h-16 shadow-[0_8px_30px_rgba(0,0,0,0.12)] z-50">
          <BottomNavItem icon={<Home size={20} />} label="Home" active={activeTab === 'inicial'} onClick={() => setActiveTab('inicial')} />
          <BottomNavItem icon={<LayoutList size={20} />} label="Tempo." active={activeTab === 'timeline'} onClick={() => setActiveTab('timeline')} />
          <BottomNavItem icon={<AlertTriangle size={20} />} label="Pend." active={activeTab === 'pendencias'} onClick={() => setActiveTab('pendencias')} badge={pendencias.filter(p => p.status === 'pendente').length} />
          <BottomNavItem icon={<DollarSign size={20} />} label="Fin." active={activeTab === 'financeiro'} onClick={() => setActiveTab('financeiro')} />
          <BottomNavItem icon={<HardDrive size={20} />} label="Doc." active={activeTab === 'documentos'} onClick={() => setActiveTab('documentos')} />
        </nav>

      </div>
    </div>
  )
}

function NavItem({ icon, label, active, onClick, expanded, badge }) {
  return (
    <button
      onClick={onClick}
      className={`relative flex items-center gap-3 px-4 py-3 my-1 rounded-2xl transition-all w-full group outline-none select-none
       ${active
          ? 'bg-vilela-primary text-white shadow-md shadow-vilela-primary/20 scale-[1.02]'
          : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900 active:scale-95'}
    `}>
      <span className={`transition-colors duration-300 ${active ? 'text-white' : 'text-gray-400 group-hover:text-vilela-primary'}`}>
        {React.cloneElement(icon, { strokeWidth: active ? 2.5 : 2 })}
      </span>

      {expanded && (
        <span className={`text-[15px] font-medium tracking-tight ${active ? 'font-semibold' : ''}`}>
          {label}
        </span>
      )}

      {expanded && badge > 0 && (
        <span className={`ml-auto text-[11px] font-bold px-2 py-0.5 rounded-full ${active ? 'bg-white/20 text-white' : 'bg-red-500 text-white'
          }`}>
          {badge}
        </span>
      )}
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
