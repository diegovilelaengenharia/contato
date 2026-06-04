# CONTEXT 03 — Fase 3: QA, Homologação & Deploy Final (v2.1)

## 📌 Escopo e Objetivos
Esta fase tem como objetivo realizar a homologação final do portal da Vilela Engenharia na milestone v2.1. O foco está na auditoria dos fluxos integrados entre o Painel Admin e o Portal do Cliente, correção de caminhos no sub-app Louvor (evitando quebras de caminhos absolutos em subdomínios/subpastas), e na validação do pipeline de deploy via GitHub Actions.

---

## 🔒 Decisões e Correções

### 1. Caminhos Dinâmicos no Sub-App Louvor (OPS-02)
Para garantir que o sub-app Louvor funcione de forma robusta e independente de onde esteja hospedado (subpasta `/applouvor/` ou subdomínio dedicado `louvor.vilela.eng.br`), faremos as seguintes correções:
* **Layout (`src/layout/layout.php`):** O registro do Service Worker deve usar a constante `APP_URL` de forma dinâmica em vez do caminho absoluto `/sw.js`.
* **Notificações (`assets/js/notifications.js`):** O caminho do ícone de notificação deve usar `window.APP_URL` como prefixo dinâmico.
* **Service Worker (`sw.js`):** As rotas e arquivos do cache em `urlsToCache` devem ser convertidos de caminhos absolutos (com `/` no início) para caminhos relativos ao próprio service worker, permitindo que ele seja executado de forma portável em qualquer diretório/escopo.

### 2. Validação Fim a Fim (OPS-03)
* Validar a interação entre o Painel Admin e o Portal do Cliente:
  * Criação e edição de clientes pelo Admin.
  * Upload de comprovantes/documentos pelo cliente e visualização no Admin.
  * Atualização da timeline de etapas e financeiro pelo Admin, refletindo instantaneamente no simulador do portal.
  * Validação visual e de Dark Mode sob o Design System Unificado.

### 3. Deploy Automático para Hostinger (OPS-04)
* Validar se o arquivo `.github/workflows/deploy.yml` está cobrindo todas as exclusões necessárias de desenvolvimento (como `.planning`, `.vscode`, `Meu Drive`, etc.) para não poluir o servidor Hostinger.
* O deploy remoto deve rodar de forma automática a partir dos Secrets configurados no GitHub.
