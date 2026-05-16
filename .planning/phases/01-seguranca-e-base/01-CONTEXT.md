# Phase 1: Segurança e Base - Context

**Gathered:** 2026-05-16
**Status:** Ready for planning

<domain>
## Phase Boundary

Proteger credenciais hardcoded, bloquear acesso a arquivos sensíveis via .htaccess, e garantir deploy automático estável e 100% automatizado antes de qualquer reescrita de UI ou funcionalidades. Nenhuma mudança de UI ou feature nesta fase.

</domain>

<decisions>
## Implementation Decisions

### Mecanismo de Variáveis de Ambiente

- **D-01:** Parse PHP nativo do arquivo `area-cliente/.env` usando `parse_ini_file()` — sem dependências externas, sem Composer, funciona garantido em Hostinger shared hosting.
- **D-02:** Formato KEY=VALUE simples (INI). Ex: `DB_HOST=srv1074.hstgr.io`, `DB_PASS=...`, `ADMIN_PASSWORD=...`
- **D-03:** Localização do .env: `area-cliente/.env` (mesma pasta do db.php).
- **D-04:** Commitar `area-cliente/.env.example` como template público com todas as chaves e valores placeholder — mesmo padrão do `db.example.php` já existente.

### .htaccess — Proteção de Arquivos

- **D-05:** Escopo mínimo conforme SEC-03: bloquear acesso HTTP a `db.php`, `.env`, e `config/` apenas.
- **D-06:** Regras de bloqueio em `area-cliente/.htaccess` separado — não misturar com o .htaccess da raiz (que mantém HTTPS e redirect).
- **D-07:** Sem headers de segurança HTTP (X-Frame-Options, CSP, etc.) nesta fase — escopo exclusivo da Fase 1 é credenciais e arquivos perigosos.

### Deploy e Limpeza do Servidor

- **D-08:** Deploy 100% automatizado via GitHub Actions — nenhum arquivo criado ou editado manualmente no servidor Hostinger após esta fase.
- **D-09:** Credenciais reais armazenadas como GitHub Secrets: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `ADMIN_PASSWORD`. Diego os adiciona uma vez no painel do repositório GitHub.
- **D-10:** `deploy.yml` ganha um step que gera `area-cliente/.env` a partir dos GitHub Secrets ANTES do upload via FTP. O FTP Action sobe tudo incluindo o `.env` gerado.
- **D-11:** O novo `db.php` (sem credenciais hardcoded, lê do .env) é commitado ao git. Remover `area-cliente/db.php` do `.gitignore`.
- **D-12:** Limpeza do servidor de arquivos stale de uploads manuais anteriores: o fresh deploy via GitHub Actions sobrescreve tudo com o conteúdo atual do repositório + o `.env` gerado.

### Claude's Discretion

- Mecanismo exato de parse do .env: usar `parse_ini_file()` nativo. Usuário delegou esta decisão técnica.
- Sintaxe exata das regras .htaccess (Deny from all vs. FilesMatch vs. Order Deny,Allow): planner decide o mais compatível com Hostinger Apache.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requisitos da Fase
- `.planning/ROADMAP.md` — Meta e critérios de sucesso da Fase 1 (Segurança e Base)
- `.planning/REQUIREMENTS.md` — SEC-01, SEC-02, SEC-03, SEC-04, SEC-05 com spec completa

### Arquivos a Modificar
- `area-cliente/db.php` — Arquivo a ser reescrito para usar parse_ini_file(). Atualmente tem credenciais hardcoded.
- `.github/workflows/deploy.yml` — Workflow de deploy a ser atualizado: adicionar step de geração do .env, adicionar exclusões de segurança.
- `.gitignore` — Remover `area-cliente/db.php` da lista (o novo db.php limpo será commitado).
- `.htaccess` (raiz) — Não modificar — já tem HTTPS e anti-directory-listing corretos.

### Templates e Referências de Padrão
- `area-cliente/db.example.php` — Template existente de db.php. O `.env.example` seguirá o mesmo padrão.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `area-cliente/db.example.php`: Template de configuração já existe — o `.env.example` seguirá a mesma lógica de documentar as chaves sem valores reais.
- `.github/workflows/deploy.yml`: Já usa `SamKirkland/FTP-Deploy-Action@v4.3.5` com Secrets do GitHub (FTP_HOST, FTP_USER, FTP_PASSWORD) — modelo exato para os novos Secrets de banco.

### Established Patterns
- O projeto já usa `.gitignore` para excluir `area-cliente/db.php` e `.env` — padrão estabelecido de não commitar credenciais.
- O `db.php` usa PDO com `PDO::ERRMODE_EXCEPTION` — manter este padrão no novo arquivo.
- `define('ADMIN_PASSWORD', ...)` em `db.php` — esta constante precisa ser migrada para o .env e o código que a usa (`gestao_admin_99.php` e `includes/init.php`) precisará usar `getenv()` ou a variável carregada do .env.

### Integration Points
- `area-cliente/db.php` é incluído por quase todos os arquivos PHP do projeto via `require_once` — a interface (`$pdo`, `ADMIN_PASSWORD`) deve permanecer igual após a reescrita.
- `deploy.yml`: step de criação do `.env` deve ser inserido ANTES do step "Deploy via FTP para Hostinger".

</code_context>

<specifics>
## Specific Ideas

- O usuário quer zero ação manual no servidor após a configuração inicial dos GitHub Secrets.
- O modelo mental do usuário: "subir tudo, sem precisar mudar manualmente nada" — o deploy deve ser idempotente e completo.
- A limpeza de arquivos stale é alcançada pelo fresh deploy, não por scripts de limpeza.

</specifics>

<deferred>
## Deferred Ideas

- Headers de segurança HTTP (X-Frame-Options, Content-Security-Policy, X-Content-Type-Options) — mais adequados para a Fase 8 de polimento.
- Bloqueio .htaccess de `maintenance/` e `tools/` — usuário optou pelo mínimo agora; pode ser revisitado se necessário.
- Rotação de credenciais do banco (Diego@159753) — ação de segurança recomendada após implementar o .env, mas fora do escopo de código desta fase.

</deferred>

---

*Phase: 1-Segurança e Base*
*Context gathered: 2026-05-16*
