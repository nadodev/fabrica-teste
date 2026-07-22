# QA — Segundo fator administrativo

## Objetivo

Validar que senha isolada não autentica, que o código é descartável e que falhas não liberam o painel.

## Pré-condições

- migrations aplicadas;
- administrador com e-mail confirmado;
- transporte de e-mail configurado;
- scheduler ativo para retenção.

## Dados de teste

- administrador confirmado;
- cliente sem acesso;
- administrador não confirmado;
- códigos correto e incorreto.

## Ambiente

Automação em SQLite com mail fake. Concorrência final deve ser repetida em MySQL/InnoDB de homologação.

## Casos de sucesso

- **MFA-01** — senha válida; esperado: código enviado e usuário visitante; obtido: automatizado; **OK**.
- **MFA-02** — código correto; esperado: desafio consumido e sessão criada; obtido: automatizado; **OK**.
- **MFA-03** — manter conectado; esperado: cookie de lembrança somente após código; obtido: automatizado; **OK**.

## Casos de validação

- **MFA-04** — código fora do padrão de seis números; esperado: 422; obtido: Form Request coberto; **OK**.
- **MFA-05** — segundo fator sem desafio na sessão; esperado: retorno ao login; obtido: automatizado; **OK**.

## Casos de autorização

- **MFA-06** — cliente ou administrador não confirmado; esperado: rejeição genérica e nenhum e-mail; obtido: automatizado; **OK**.
- **MFA-07** — administrador revogado entre fatores; esperado: acesso negado; obtido: automatizado; **OK**.

## Casos de falha

- **MFA-08** — código incorreto; esperado: tentativa incrementada; obtido: automatizado; **OK**.
- **MFA-09** — quinta falha; esperado: bloqueio e retorno ao login; obtido: automatizado; **OK**.
- **MFA-10** — desafio expirado; esperado: consumo e novo login obrigatório; obtido: automatizado; **OK**.
- **MFA-11** — falha no transporte de e-mail; esperado: desafio invalidado e usuário visitante; obtido: automatizado; **OK**.

## Casos de concorrência

- **MFA-12** — duas requisições usando o mesmo código; esperado: lock e apenas um sucesso; obtido: regra e lock implementados; evidência MySQL/InnoDB pendente.

## Casos de idempotência

- **MFA-13** — reutilizar código consumido; esperado: rejeição; obtido: automatizado; **OK**.
- **MFA-14** — iniciar novo login; esperado: desafio anterior invalidado; obtido: automatizado; **OK**.

## Casos de regressão

- **MFA-15** — demais rotas administrativas via `actingAs`; esperado: sem alteração; obtido: suíte completa aprovada; **OK**.
- **MFA-16** — logout administrativo; esperado: sessão invalidada; coberto pela suíte existente.

## Casos responsivos

- **MFA-17** — 320 px até desktop; esperado: cartão sem overflow; obtido: padrões responsivos existentes; inspeção visual manual pendente.

## Casos de acessibilidade

- **MFA-18** — teclado e leitor; esperado: foco automático, label, `inputMode` e erro associado visualmente; obtido: marcação implementada; teste assistivo manual pendente.

## Evidências

- testes direcionados: 19 testes e 135 asserções aprovadas;
- suíte completa: 134 testes e 876 asserções aprovadas;
- PHPStan: aprovado sem erros.
- Pint, TypeScript, ESLint, Prettier e build Vite: aprovados;
- navegador local: login carregado pelo build de produção, sem erros no console; acesso direto ao desafio sem sessão redirecionou ao login.

## Riscos conhecidos

- segundo fator por e-mail não é resistente a phishing;
- indisponibilidade do e-mail bloqueia login por decisão segura;
- concorrência precisa de evidência MySQL/InnoDB.
