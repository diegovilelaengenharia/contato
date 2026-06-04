# Checklist de Rotação de Credenciais — Hardening de Segurança (SEC-10)

Olá, Diego! 

Como parte do processo de hardening de segurança (Fase 1 do v2.0), identificamos a necessidade de rotacionar todas as credenciais críticas de acesso aos servidores e banco de dados que estavam expostas em commits passados.

Siga os passos abaixo no painel de controle da **Hostinger** e no **GitHub** para concluir a segurança do ambiente:

- [ ] **1. Alterar a Senha do FTP**
  * Acesse o painel da Hostinger -> Gerenciar Site -> Contas FTP.
  * Altere a senha da conta principal de FTP.
  * Guarde a nova senha.

- [ ] **2. Alterar a Senha do SSH**
  * Acesse o painel da Hostinger -> Avançado -> Acesso SSH.
  * Altere a senha da sua conta de SSH.
  * Guarde a nova senha.

- [ ] **3. Alterar a Senha do MySQL**
  * Acesse o painel da Hostinger -> Banco de Dados -> Bancos de Dados MySQL.
  * Localize o banco `u884436813_cliente`.
  * Clique nos três pontos (...) e selecione "Alterar Senha".
  * Digite uma nova senha altamente segura e guarde-a.

- [ ] **4. Atualizar os Secrets no Repositório do GitHub**
  * Acesse o seu repositório no GitHub.
  * Vá em **Settings** (Configurações) -> **Secrets and variables** -> **Actions**.
  * Atualize os seguintes Secrets com os novos valores:
    * `DB_PASS` -> (Nova senha do MySQL que você gerou no passo 3)
    * `FTP_PASSWORD` -> (Nova senha do FTP que você gerou no passo 1)

- [ ] **5. Rodar o Deploy e Validar**
  * Faça um novo push de teste ou acione a action de deploy manualmente no GitHub.
  * A execução do workflow irá regenerar automaticamente o arquivo de credenciais do banco `db_credentials.php` com as senhas corretas.
  * Teste o acesso ao painel admin e ao site para confirmar que tudo está operando normalmente.

---
*Nota técnica: Esta tarefa é de caráter operacional e de responsabilidade do proprietário (Diego) no painel Hostinger, pois exige acessos com privilégios de proprietário das credenciais da conta.*
