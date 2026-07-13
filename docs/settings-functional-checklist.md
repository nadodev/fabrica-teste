# Checklist funcional das configurações

Este arquivo registra se cada configuração do painel realmente produz efeito na loja.

Legenda:

- `[x]` implementada e refletida no site ou na regra correspondente.
- `[ ]` ainda pendente.
- `Removida` campo retirado do painel por não existir uma funcionalidade real correspondente.

## Geral

- [x] Nome da loja — título, cabeçalho, rodapé e e-mails.
- [x] Razão social — apresentação institucional e rodapé.
- [x] CNPJ/CPF — identificação pública no rodapé.
- [x] E-mail público — cabeçalho, rodapé, orçamento e contato LGPD.
- [x] Telefone público — cabeçalho, rodapé e orçamento.
- [x] WhatsApp público — cabeçalho, carrinho, orçamento, home e confirmação.
- [x] Horário de atendimento — cabeçalho, home, rodapé e orçamento.
- [x] Endereço da empresa — home e rodapé.
- [x] Redes sociais — rodapé.
- [x] Upload duplicado de logo removido desta aba.

## Aparência

- [x] Logo principal — fallback visual centralizado nesta aba.
- [x] Logo do cabeçalho.
- [x] Logo do rodapé.
- [x] Favicon aplicado no navegador.
- [x] Imagem de compartilhamento aplicada ao Open Graph.
- [x] Cor principal aplicada às variáveis visuais globais.
- [x] Cor secundária aplicada às variáveis visuais globais.
- [x] Produtos por página com paginação real no catálogo.
- [x] “Produtos em destaque” removido conforme solicitado.
- [x] “Categorias em destaque” removido conforme solicitado.
- [x] “Textos da página inicial” removido; os textos da home já possuem gestão estruturada em Conteúdo.

## Produtos

- [x] Permitir venda sem estoque.
- [x] Controle de estoque.
- [x] Quantidade mínima aplicada no produto, adição e carrinho.
- [x] Quantidade máxima aplicada no produto, adição e carrinho.
- Removida: aviso global de estoque baixo; cada variação já possui seu próprio limite.
- Removida: ativação global de variações; produtos já controlam suas variações individualmente.
- Removidas: unidades de peso/dimensões enquanto produtos não possuírem peso e dimensões reais.
- Removido: SKU automático sem gerador de SKU implementado.
- Removidas: tabela de medidas e avaliações enquanto não existirem conteúdo/modelo correspondentes.

## Pagamentos

- [x] Ativar Pix — checkout, produto, home e rodapé.
- [x] Ativar cartão — checkout, produto, home e rodapé.
- [x] Ativar boleto — checkout, produto, home e rodapé.
- Removidos: credenciais, ambiente e webhook até existir um gateway real e armazenamento seguro.
- Removidos: parcelas, juros, desconto Pix e vencimento até existir cálculo/cobrança real.

## Clientes

- [x] Cadastro obrigatório aplicado ao acesso ao checkout.
- [x] Compra como visitante aplicada ao acesso ao checkout.
- [x] Validar CPF/CNPJ aplicado à validação do checkout.
- [x] Exigir política de privacidade aplicado ao checkout.
- Removido: texto livre de campos obrigatórios; os campos possuem validação explícita.
- Removidos: tipos de pessoa, aprovação e bloqueio até existir cadastro empresarial e estado do cliente.

## Cupons e promoções

- [x] Permitir cupons — interface e validação do servidor.
- [x] Valor mínimo geral do pedido — carrinho e checkout.
- Removidas: regras promocionais que dependem de um motor de promoções inexistente.

## E-mails

- [x] Nome do remetente aplicado aos e-mails.
- [x] E-mail do remetente aplicado aos e-mails.
- [x] Notificação de novo pedido.
- [x] Notificação de novo orçamento.
- [x] Destinatários administrativos separados por vírgula.
- Removidos: SMTP incompleto e notificações sem eventos implementados.

## Políticas

- [x] URL dos termos — rodapé e checkout.
- [x] URL da política de privacidade — rodapé, checkout e cookies.
- [x] Política de troca e devolução.
- [x] Política de entrega.
- [x] Política de personalização.
- [x] Informações de garantia.
- [x] Aviso de cookies.
- [x] Consentimento LGPD no checkout.

## SEO

- [x] Título padrão usado como nome da aplicação nas páginas.
- [x] Descrição padrão.
- [x] Palavras-chave.
- [x] Imagem social.
- [x] Google Analytics.
- [x] Google Tag Manager.
- [x] Meta Pixel.
- [x] Sitemap dinâmico.
- [x] Conteúdo dinâmico do robots.txt.
- [x] Integração social/Open Graph.
- Removido: alternador de URLs amigáveis; as rotas públicas já são amigáveis e não podem ser desligadas sem quebrar links.

## Sistema

- [x] Importação e exportação de produtos — interface e rotas protegidas pela configuração.
- Removido: backup de produtos; a operação existente é backup integral do SQLite e pertence à tela Operação.
- Removidos: webhooks, chaves e integrações sem consumidores reais; dados sigilosos não ficam expostos em configurações inertes.
