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

  // Use Real Data (or safe fallbacks)
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
          <div style={{ height: 1, background: 'var(--color-border)', margin: '12px 0' }}></div>
          <NavItem icon={<DollarSign size={20} />} label="Financeiro" active={activeTab === 'financeiro'} onClick={() => setActiveTab('financeiro')} expanded={isSidebarOpen} />
          <NavItem icon={<HardDrive size={20} />} label="Documentos" active={activeTab === 'documentos'} onClick={() => setActiveTab('documentos')} expanded={isSidebarOpen} />
        </nav>
      </aside>

      {/* --- MAIN CONTENT --- */}
      <div className="main-content">

        {/* Desktop Header */}
        <header className="sidebar-header" style={{ justifyContent: 'space-between', padding: '0 30px', height: '70px', display: window.innerWidth < 768 ? 'none' : 'flex' }}>
          <h2 style={{ fontSize: 18, margin: 0, color: 'var(--color-text)' }}>
            {activeTab === 'inicial' ? 'Visão Geral' : activeTab.charAt(0).toUpperCase() + activeTab.slice(1)}
          </h2>

          {/* Mobile Menu Toggle (if needed) but we are using Sidebar for Desktop */}
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

              {/* Optional: Highlights if requested later */}
            </>
          )}

          {/* === VIEW: TIMELINE === */}
          {activeTab === 'timeline' && (
            <div className="timeline-container">
              {timeline.map((item, index) => (
                <div key={index} className="timeline-item">
                  <div className={`timeline-dot ${index === 0 ? 'active' : ''}`}>
                    {index + 1}
                  </div>
                  <div className="timeline-content">
                    <span className="timeline-date">{item.data}</span>
                    <h3 className="timeline-title">{item.titulo}</h3>
                    <p className="timeline-desc">{item.descricao}</p>
                  </div>
                </div>
              ))}
              {timeline.length === 0 && <p style={{ textAlign: 'center', color: '#999' }}>Nenhuma etapa registrada.</p>}
            </div>
          )}

          {/* === VIEW: PENDENCIAS === */}
          {activeTab === 'pendencias' && (
            <div className="pendency-grid">
              {pendencias.map((pend, index) => (
                <div key={index} className="pendency-card">
                  <div className="pendency-header">
                    <AlertTriangle size={24} color="#eab308" />
                    <span className={`status-badge ${pend.status}`}>{pend.status}</span>
                  </div>
                  <h3 style={{ margin: '10px 0', fontSize: 16 }}>{pend.titulo}</h3>
                  <p style={{ color: '#666', fontSize: 14 }}>{pend.descricao}</p>
                  <button className="btn-action">Resolver</button>
                </div>
              ))}
              {pendencias.length === 0 && <div className="vilela-card" style={{ gridColumn: '1/-1', textAlign: 'center' }}>Nenhuma pendência encontrada.</div>}
            </div>
          )}

          {/* === VIEW: FINANCEIRO === */}
          {activeTab === 'financeiro' && (
            <div>
              {financeiroKPIs && (
                <div className="kpi-row">
                  <div className="kpi-card">
                    <span className="kpi-label">Valor Total</span>
                    <span className="kpi-value">R$ {financeiroKPIs.total}</span>
                  </div>
                  <div className="kpi-card">
                    <span className="kpi-label">Pago</span>
                    <span className="kpi-value green">R$ {financeiroKPIs.pago}</span>
                  </div>
                  <div className="kpi-card">
                    <span className="kpi-label">A Pagar</span>
                    <span className="kpi-value red">R$ {financeiroKPIs.restante}</span>
                  </div>
                </div>
              )}

              <div className="vilela-card">
                <h2>Extrato Detalhado</h2>
                <table className="finance-table">
                  <thead>
                    <tr>
                      <th>Data</th>
                      <th>Descrição</th>
                      <th>Valor</th>
                    </tr>
                  </thead>
                  <tbody>
                    {financeiro.map((item, idx) => (
                      <tr key={idx}>
                        <td>{item.data}</td>
                        <td>{item.descricao}</td>
                        <td style={{ color: item.tipo === 'entrada' ? 'var(--color-primary)' : '#dc3545', fontWeight: 700 }}>
                          {item.tipo === 'entrada' ? '+' : '-'} R$ {item.valor}
                        </td>
                      </tr>
                    ))}
                    {financeiro.length === 0 && <tr><td colSpan="3" style={{ textAlign: 'center' }}>Sem registros financeiros.</td></tr>}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* === VIEW: DOCUMENTOS === */}
          {activeTab === 'documentos' && (
            <div className="hero-card" style={{ height: 'calc(100vh - 100px)', flexDirection: 'column', alignItems: 'stretch' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
                <h2 style={{ margin: 0, color: 'var(--color-primary)' }}>Arquivos do Projeto</h2>
                <button className="btn-action" style={{ width: 'auto', marginTop: 0 }} onClick={() => window.open(DATA.driveLink, '_blank')}>
                  Abrir no Drive <ChevronRight size={16} />
                </button>
              </div>
              {DATA.driveLink ?
                <iframe src={DATA.driveLink} style={{ flex: 1, width: '100%', border: 'none', borderRadius: 12, background: '#eee' }}></iframe> :
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

