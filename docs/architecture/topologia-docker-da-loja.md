# Topologia Docker da loja

## Decisão

Usar uma composição Docker autocontida com Caddy, Nginx, PHP-FPM, MySQL, Redis, worker e scheduler.

## Motivo

A loja possui pagamentos, webhooks, reservas e tarefas periódicas. Apenas um container web deixaria reconciliação, expiração e notificações sem execução confiável.

## Fronteiras

Caddy termina TLS. Nginx serve arquivos públicos e encaminha PHP. Aplicação, worker e scheduler compartilham código e storage, mas são processos separados. MySQL e Redis não aceitam conexões externas.

Laravel confia nos cabeçalhos do proxy porque o PHP-FPM só é alcançável pela rede interna, e Caddy/Nginx controlam os cabeçalhos encaminhados. Isso preserva URLs HTTPS e cookies seguros.

## Inicialização

O serviço `migrate` aguarda banco e Redis saudáveis e executa `migrate --force --isolated`. Os processos contínuos dependem de sua conclusão com sucesso.

## Persistência e recuperação

Dados mutáveis permanecem em volumes nomeados. O backup lógico do MySQL e o arquivo do storage são gravados fora dos volumes antes de atualizar o código.

## HTTPS e hostname

O ambiente suporta hostname dedicado, por exemplo `loja.dominio.com.br`. Hospedagem sob caminho `/loja` não é equivalente a subdomínio e exige uma decisão separada sobre base URL, assets, cookies e proxy.
