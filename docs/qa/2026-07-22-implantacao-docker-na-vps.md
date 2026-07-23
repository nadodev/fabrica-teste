# QA — Implantação Docker na VPS

## Objetivo

Validar construção, segurança, inicialização, persistência e operação da loja containerizada.

## Pré-condições

VPS com Docker Engine e Compose; portas 80/443 liberadas; DNS propagado; `.env.production` preenchido.

## Dados de teste

Cliente verificado, produto com estoque, frete válido, cartão de teste/produção controlada e endpoint Asaas configurado.

## Ambiente

Produção na VPS Hostinger.

## Casos de sucesso

- DEP-01: construir as imagens; esperado: targets `app` e `web` concluídos; pendente da VPS.
- DEP-02: subir composição; esperado: todos os serviços saudáveis; pendente da VPS.
- DEP-03: abrir hostname HTTPS; esperado: certificado válido e página 200; pendente de DNS/SSH.

## Casos de validação

- VAL-01: omitir senha; esperado: Compose ou entrypoint interrompe a inicialização; validado por configuração obrigatória.
- VAL-02: configuração completa; esperado: `docker compose config` aprovado; resultado: aprovado localmente.

## Casos de autorização

- AUT-01: tentar acessar MySQL/Redis pela Internet; esperado: portas inacessíveis; definido por rede interna sem `ports`.

## Casos de falha

- FAL-01: migration falha; esperado: web, worker e scheduler não iniciam.
- FAL-02: banco indisponível; esperado: healthcheck impede progressão.

## Casos de concorrência

- CON-01: duas inicializações; esperado: migration isolada sem execução concorrente.

## Casos de idempotência

- IDE-01: repetir deploy no mesmo commit; esperado: sem perda de volumes ou migrations duplicadas.

## Casos de regressão

- REG-01: checkout Pix e cartão.
- REG-02: cotação Melhor Envio.
- REG-03: webhook e reconciliação Asaas.
- REG-04: upload e leitura de arquivos no volume persistente.

## Casos responsivos

- RES-01: validar vitrine e checkout em celular após deploy.

## Casos de acessibilidade

- A11Y-01: executar navegação por teclado e inspeção automatizada nas páginas críticas.

## Evidências

- `docker compose config --quiet`: aprovado com valores de validação sem segredos reais.
- Suíte Laravel: 136 testes e 905 asserções aprovados.
- PHPStan, Pint, ESLint, TypeScript e Prettier: aprovados.
- Build frontend: aprovado, 2.311 módulos transformados.
- Build das imagens, URL, certificado e smoke tests aguardam acesso à VPS e hostname definitivo.

## Riscos conhecidos

Não há conector Hostinger VPS nesta sessão. O Docker local está instalado, mas o daemon não está em execução. A validação real deve ocorrer no servidor.
