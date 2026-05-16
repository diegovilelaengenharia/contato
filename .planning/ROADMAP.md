# Roadmap — Vilela Engenharia

**8 fases** | **32 requirements mapeados** | Cobertura v1 completa ✓

---

### Phase 1: Segurança e Base
**Goal:** Proteger credenciais, remover arquivos perigosos e garantir deploy estável antes de qualquer reescrita
**Mode:** standard
**Requirements:** SEC-01, SEC-02, SEC-03, SEC-04, SEC-05
**Plans:** 4 planos
**Success Criteria:**
1. `db.php` lê credenciais de variáveis de ambiente — nenhuma senha no código
2. `.htaccess` bloqueia acesso direto a `db.php`, `.env` e `config/`
3. Scripts de debug/reset removidos ou movidos para fora do public_html
4. Push para `main` dispara deploy automático e site atualiza sem erros no GitHub Actions
5. Senha do admin lida do `.env`, não hardcoded

Plans:
- [x] 01-01-PLAN.md — Reescrever db.php para parse_ini_file() + criar .env.example + atualizar .gitignore
- [x] 01-02-PLAN.md — Criar area-cliente/.htaccess com bloqueios FilesMatch para db.php, .env e config/
- [x] 01-03-PLAN.md — Atualizar deploy.yml com step de geração do .env e exclude list expandida
- [x] 01-04-PLAN.md — Corrigir admin_config.php para gravar senha no .env em vez de db.php

---

### Phase 2: Landing Page Mobile-First
**Goal:** Substituir a landing page atual (linktree) por uma página de apresentação completa, focada em mobile, com hero, serviços, sobre e links de contato
**Mode:** standard
**Requirements:** LAND-01, LAND-02, LAND-03, LAND-04, LAND-05, LAND-06, LAND-07, LAND-08
**UI hint:** yes
**Success Criteria:**
1. Página abre em celular sem scroll horizontal, todos os elementos legíveis sem zoom
2. Botão WhatsApp abre conversa com Diego no app
3. Links para redes sociais funcionando
4. Seções de serviços e sobre visíveis com conteúdo real de Diego Vilela
5. Botão "Área do Cliente" leva para o login do portal
6. Modal de orçamento funcional (ou link direto para WhatsApp com mensagem pré-formatada)

---

### Phase 3: Portal do Cliente — Autenticação e Dashboard
**Goal:** Reescrever o portal do cliente com base sólida — login seguro e dashboard com visão geral do processo
**Mode:** standard
**Requirements:** CLI-01, CLI-02, CLI-03, CLI-08, CLI-09
**UI hint:** yes
**Success Criteria:**
1. Cliente faz login com usuário/senha e é redirecionado para o dashboard
2. Dashboard exibe etapa atual do processo com barra de progresso visual
3. Dashboard exibe contagem de pendências abertas e pagamentos em aberto
4. Logout encerra sessão e redireciona para login
5. Interface legível e funcional em celular (iPhone SE e Android médio)

---

### Phase 4: Portal do Cliente — Funcionalidades Completas
**Goal:** Adicionar todas as abas funcionais do portal: pendências, financeiro, documentos e notificações
**Mode:** standard
**Requirements:** CLI-04, CLI-05, CLI-06, CLI-07, CLI-10
**UI hint:** yes
**Success Criteria:**
1. Aba de pendências lista itens com status visual (resolvido/pendente)
2. Aba financeiro lista lançamentos com badge de status colorido
3. Aba documentos lista arquivos com botão de download funcionando
4. Cliente faz upload de comprovante de pagamento
5. Notificação de novidade aparece quando há pendência nova ou etapa atualizada

---

### Phase 5: Painel Admin — Estrutura e Gestão de Clientes
**Goal:** Novo painel admin do zero — navegação limpa, lista de clientes e operações de CRUD básico
**Mode:** standard
**Requirements:** ADM-01, ADM-02, ADM-03, ADM-12, ADM-13, ADM-15
**UI hint:** yes
**Success Criteria:**
1. Diego faz login com credenciais do .env e acessa o painel
2. Lista de clientes exibe todos com busca por nome funcionando
3. Diego clica num cliente e vê todas as informações do processo
4. Diego cria novo cliente com usuário/senha
5. Diego exclui cliente com confirmação
6. Logout funcional

---

### Phase 6: Painel Admin — Processo e Timeline
**Goal:** Diego consegue atualizar etapas do processo e editar dados cadastrais do cliente pelo novo admin
**Mode:** standard
**Requirements:** ADM-04, ADM-05, ADM-11
**Success Criteria:**
1. Diego seleciona nova etapa e confirma — timeline do cliente atualiza automaticamente
2. Movimento de etapa aparece no histórico com data/hora
3. Diego edita dados pessoais, endereço e dados técnicos do imóvel
4. Diego gera PDF do resumo do processo (export funcional)
5. Todas as ações refletem imediatamente na visão do cliente

---

### Phase 7: Painel Admin — Financeiro, Pendências e Documentos
**Goal:** Diego consegue lançar cobranças, gerenciar pendências e enviar documentos pelo admin
**Mode:** standard
**Requirements:** ADM-06, ADM-07, ADM-08, ADM-09, ADM-10
**Success Criteria:**
1. Diego lança cobrança com descrição, valor, vencimento e status
2. Diego altera status de pagamento (pago/pendente/atrasado/isento)
3. Diego cria pendência para o cliente com descrição
4. Diego marca pendência como resolvida
5. Diego faz upload de documento entregável e cliente consegue baixar

---

### Phase 8: Polimento, Responsividade e Deploy Final
**Goal:** Revisão geral de UX/UI, garantir responsividade em todos os dispositivos e validar deploy end-to-end
**Mode:** standard
**Requirements:** ADM-14, CLI-09, LAND-07
**Success Criteria:**
1. Todas as três interfaces (landing, portal, admin) passam em inspeção mobile no Chrome DevTools (iPhone 12 e Pixel 7)
2. Nenhum elemento com overflow horizontal em tela < 390px
3. Formulários e botões atingíveis com polegar sem zoom
4. Deploy completo em produção — site ao vivo reflete todas as mudanças
5. Credenciais de produção no Hostinger configuradas via variáveis de ambiente no painel da hospedagem

---

## Dependências entre Fases

```
Fase 1 (Segurança) → Fase 2 (Landing) — independentes após Fase 1
Fase 1 → Fase 3 (Portal estrutura) → Fase 4 (Portal completo)
Fase 1 → Fase 5 (Admin estrutura) → Fase 6 (Admin processo) → Fase 7 (Admin financeiro)
Fase 7 → Fase 8 (Polimento)
```

**Execução paralela possível:** Fase 2 e Fase 3 podem rodar em paralelo após Fase 1.
Fases 5, 6, 7 devem rodar em sequência (admin se constrói em camadas).
