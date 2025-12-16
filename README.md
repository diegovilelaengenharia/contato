# Vilela Engenharia - Links oficiais

Esta e a versao 1.0 Completa (04/10/25) da landing page estatica, pronta para hospedagem em qualquer provedor.

## Conteudo

- `index.html`: Pagina principal com links oficiais, modal de orcamento validado e registro do service worker.
- `style.css`: Estilos responsivos, animacoes leves e tratamento dedicado para os icones dos cards.
- `manifest.json`: Manifesto PWA para instalacao em dispositivos moveis.
- `service-worker.js`: Cache offline basico para os principais assets.
- `assets/`: Logotipo e arquivos auxiliares (ex.: `diego-vilela.vcf`).

## Observacoes

- O botao "Area do Cliente" aponta para `/portal`, pronto para integrar com o futuro dashboard seguro dos clientes.
- O botao "Orcamento online" abre um modal acessivel, valida entradas e envia o briefing para o WhatsApp da equipe.
- O site pode ser instalado como atalho em navegadores compativeis, gracas ao manifesto e ao service worker.
- Para publicar atualizacoes, execute `git add`, `git commit` e `git push origin main`.
