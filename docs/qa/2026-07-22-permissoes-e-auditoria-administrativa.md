# QA — Permissões e auditoria administrativa

## Objetivo

Validar menor privilégio, proteção do proprietário, revogação imediata e auditoria sem dados sensíveis.

## Pré-condições

- migration administrativa aplicada;
- uma conta proprietária;
- clientes com e sem e-mail verificado;
- sessão persistida em banco.

## Dados de teste

- proprietário: `is_admin=true`, `is_super_admin=true`;
- operador: catálogo somente;
- candidato confirmado e candidato não confirmado.

## Ambiente

Automação em SQLite para casos funcionais. Validação de concorrência e deploy final ainda deve ser repetida em MySQL/InnoDB de homologação.

## Casos de sucesso

- **ADM-01** — proprietário promove cliente confirmado; esperado: acesso limitado e dashboard implícito; obtido: automatizado; **OK**.
- **ADM-02** — proprietário atualiza permissões; esperado: dependência de visualização normalizada; obtido: automatizado; **OK**.
- **ADM-03** — operador autorizado altera produto; esperado: tentativa e conclusão auditadas; obtido: automatizado; **OK**.

## Casos de validação

- **ADM-04** — promover e-mail não confirmado; esperado: rejeição sem persistência; obtido: automatizado; **OK**.
- **ADM-05** — conceder permissão inexistente; esperado: erro 422; obtido: validado pelo Form Request; **OK**.

## Casos de autorização

- **ADM-06** — operador de catálogo acessa pedidos/configurações; esperado: 403; obtido: automatizado; **OK**.
- **ADM-07** — operador altera o próprio acesso; esperado: rejeição; obtido: automatizado; **OK**.
- **ADM-08** — alterar ou revogar proprietário; esperado: rejeição; obtido: automatizado; **OK**.
- **ADM-09** — inserir permissão de proprietário diretamente no pivô; esperado: acesso negado; obtido: automatizado; **OK**.

## Casos de falha

- **ADM-10** — mutação não autorizada; esperado: auditoria `attempted` e `rejected`; obtido: automatizado; **OK**.
- **ADM-11** — falha ao gravar auditoria inicial; esperado: mutação não executada; obtido: garantido pela ordem síncrona; validação de indisponibilidade manual pendente.

## Casos de concorrência

- **ADM-12** — duas sincronizações simultâneas; esperado: estado final íntegro pela transação e chave composta; obtido: teste MySQL/InnoDB pendente.

## Casos de idempotência

- **ADM-13** — repetir promoção com a mesma chave e payload; esperado: uma promoção; obtido: middleware compartilhado coberto pela suíte de idempotência; **OK**.

## Casos de regressão

- **ADM-14** — proprietário existente após migration; esperado: conserva acesso integral; obtido: backfill implementado e teste de proprietário; **OK**.
- **ADM-15** — suítes de catálogo, pedidos, frete e autenticação; esperado: sem regressões; obtido: 120 testes e 766 asserções aprovadas; **OK**.

## Casos responsivos

- **ADM-16** — grade de permissões em celular, tablet e desktop; esperado: uma, duas e três colunas conforme espaço; obtido: classes responsivas implementadas; inspeção visual manual pendente.

## Casos de acessibilidade

- **ADM-17** — permissões associadas a `fieldset`, `legend` e `label`; esperado: navegação por teclado e nomes acessíveis; obtido: marcação semântica implementada; auditoria manual pendente.

## Evidências

- suíte administrativa: 10 testes e 66 asserções aprovadas;
- suíte completa: 120 testes e 766 asserções aprovadas;
- PHPStan: aprovado sem erros.
- Pint, TypeScript, ESLint e Prettier: aprovados;
- build Vite de produção: aprovado.
- navegador local: página inicial carregou somente JS/CSS de `public/build`, sem erros ou avisos no console.

## Riscos conhecidos

- segundo fator por e-mail implementado; TOTP ou WebAuthn permanecem como endurecimento contra phishing;
- concorrência precisa de evidência adicional em MySQL/InnoDB;
- QA visual e acessibilidade assistiva permanecem manuais.
