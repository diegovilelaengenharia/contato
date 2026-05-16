# Requirements — Vilela Engenharia

## v1 Requirements

### Landing Page (Mobile-First)

- [ ] **LAND-01**: Usuário vê hero com foto, nome, título (Eng. Civil · CREA) e tagline em destaque
- [ ] **LAND-02**: Usuário clica em WhatsApp e abre conversa direta com Diego (link wa.me)
- [ ] **LAND-03**: Usuário acessa a Área do Cliente diretamente pela landing page
- [ ] **LAND-04**: Usuário vê seção de serviços com descrição do que a Vilela Engenharia faz (regularização, aprovação de projetos, CREA)
- [ ] **LAND-05**: Usuário vê seção "Sobre" com a apresentação de Diego Vilela
- [ ] **LAND-06**: Usuário acessa links de redes sociais (Instagram, LinkedIn ou outros)
- [ ] **LAND-07**: Página carrega e funciona perfeitamente em celular (layout mobile-first)
- [ ] **LAND-08**: Usuário pode iniciar um pedido de orçamento via modal ou link direto para WhatsApp

### Portal do Cliente

- [ ] **CLI-01**: Cliente faz login com usuário e senha (autenticação segura com session)
- [ ] **CLI-02**: Cliente vê dashboard com visão geral: etapa atual, pendências abertas, pagamentos em aberto
- [ ] **CLI-03**: Cliente acompanha timeline visual das 9 etapas do processo (com % de progresso)
- [ ] **CLI-04**: Cliente vê lista de pendências (documentos a entregar) com status resolvido/pendente
- [ ] **CLI-05**: Cliente vê lançamentos financeiros (taxas, multas) com status pago/pendente/atrasado/isento
- [ ] **CLI-06**: Cliente visualiza documentos entregues pelo escritório (download disponível)
- [ ] **CLI-07**: Cliente faz upload de comprovante de pagamento
- [ ] **CLI-08**: Cliente faz logout de forma segura
- [ ] **CLI-09**: Interface funciona bem em celular (responsiva)
- [ ] **CLI-10**: Cliente vê notificações de novidades (novas pendências, etapa atualizada, novo documento)

### Painel Administrativo

- [ ] **ADM-01**: Diego faz login como admin com senha segura
- [ ] **ADM-02**: Diego vê lista de todos os clientes com busca e filtros
- [ ] **ADM-03**: Diego seleciona um cliente e vê todas as informações do processo dele
- [ ] **ADM-04**: Diego atualiza a etapa do processo do cliente (com registro automático na timeline)
- [ ] **ADM-05**: Diego cadastra e edita dados do cliente (pessoais, imóvel, responsável técnico)
- [ ] **ADM-06**: Diego lança cobranças financeiras (descrição, valor, vencimento, status)
- [ ] **ADM-07**: Diego marca pagamento como pago/pendente/atrasado/isento
- [ ] **ADM-08**: Diego registra pendências para o cliente (documentos a entregar)
- [ ] **ADM-09**: Diego marca pendência como resolvida
- [ ] **ADM-10**: Diego faz upload de documentos entregáveis para o cliente
- [ ] **ADM-11**: Diego gera PDF do resumo do processo de um cliente
- [ ] **ADM-12**: Diego cria novo cliente (com definição de usuário e senha)
- [ ] **ADM-13**: Diego exclui cliente com confirmação
- [ ] **ADM-14**: Interface do admin funciona bem no desktop e celular
- [ ] **ADM-15**: Diego faz logout de forma segura

### Segurança e Infraestrutura

- [ ] **SEC-01**: Credenciais do banco de dados em variáveis de ambiente (.env), não no código
- [ ] **SEC-02**: Senha do admin em .env, não hardcoded no PHP
- [ ] **SEC-03**: .htaccess bloqueia acesso direto a db.php, .env e config/
- [ ] **SEC-04**: Scripts de debug/reset removidos do ambiente de produção
- [ ] **SEC-05**: Deploy automático funcional via GitHub Actions → FTPS → Hostinger a cada push em main

---

## v2 Requirements (Deferred)

- Portfólio de obras na landing page — decidir após v1
- Blog/artigos técnicos — decidir após v1
- Automação WhatsApp ao atualizar etapa — código já existe (comentado), ativar quando pronto
- Log de auditoria do admin (quem fez o quê e quando)
- Exportação financeira em Excel
- 2FA para admin

---

## Out of Scope

- Migração para Laravel, Node.js ou qualquer outro stack — Hostinger shared, PHP atual funciona
- App mobile nativo — site responsivo cobre o caso de uso
- Sistema de pagamento online (Stripe, PagSeguro) — pagamentos controlados manualmente
- CMS headless — complexidade desnecessária para esse porte

---

## Traceability

| Fase | Requirements Cobertos |
|------|----------------------|
| Fase 1 — Segurança e Base | SEC-01, SEC-02, SEC-03, SEC-04, SEC-05 |
| Fase 2 — Landing Page | LAND-01 a LAND-08 |
| Fase 3 — Portal do Cliente (estrutura) | CLI-01, CLI-02, CLI-03, CLI-08, CLI-09 |
| Fase 4 — Portal do Cliente (funcional) | CLI-04, CLI-05, CLI-06, CLI-07, CLI-10 |
| Fase 5 — Admin (estrutura e clientes) | ADM-01, ADM-02, ADM-03, ADM-12, ADM-13, ADM-15 |
| Fase 6 — Admin (processo e timeline) | ADM-04, ADM-05, ADM-11 |
| Fase 7 — Admin (financeiro e docs) | ADM-06, ADM-07, ADM-08, ADM-09, ADM-10 |
| Fase 8 — Polimento e responsividade | ADM-14, CLI-09, LAND-07 |
