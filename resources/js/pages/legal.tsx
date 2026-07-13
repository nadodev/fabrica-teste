import { Head, Link, usePage } from '@inertiajs/react';

export default function Legal({ type }: { type: 'privacy' | 'terms' }) {
    const { commerceSettings, siteSettings } = usePage<{
        commerceSettings?: { policies?: Record<string, string | boolean> };
        siteSettings?: { storeName?: string; contactEmail?: string };
    }>().props;
    const policies = commerceSettings?.policies ?? {};
    const privacy = type === 'privacy';
    const title = privacy ? 'Politica de privacidade' : 'Termos de uso';

    return (
        <main className="mx-auto max-w-4xl px-4 py-12">
            <Head title={title} />
            <Link
                href="/"
                className="text-sm font-bold text-navy hover:underline"
            >
                Voltar para loja
            </Link>
            <h1 className="mt-6 font-display text-4xl font-black text-navy">
                {title}
            </h1>
            <div className="mt-6 space-y-5 rounded-xl border border-border bg-white p-6 leading-7 text-text-muted">
                {privacy ? (
                    <>
                        <p>
                            Coletamos os dados informados no carrinho e checkout
                            para atendimento, emissao e acompanhamento do
                            pedido.
                        </p>
                        <p>
                            Os dados podem incluir nome, e-mail, telefone,
                            documento, endereco de entrega e observacoes do
                            pedido.
                        </p>
                        <p>
                            Usamos essas informacoes apenas para contato
                            comercial, entrega, suporte e historico de compras.
                        </p>
                        <p>
                            Para solicitar alteração ou remoção de dados, entre
                            em contato
                            {siteSettings?.contactEmail ? (
                                <>
                                    {' '}
                                    pelo e-mail{' '}
                                    <a
                                        className="font-bold text-navy underline"
                                        href={`mailto:${siteSettings.contactEmail}`}
                                    >
                                        {siteSettings.contactEmail}
                                    </a>
                                </>
                            ) : (
                                ' pelos canais oficiais da loja'
                            )}
                            .
                        </p>
                        {policies.exchangePolicy && (
                            <Policy
                                title="Troca e devolução"
                                text={String(policies.exchangePolicy)}
                            />
                        )}
                        {policies.deliveryPolicy && (
                            <Policy
                                title="Política de entrega"
                                text={String(policies.deliveryPolicy)}
                            />
                        )}
                    </>
                ) : (
                    <>
                        <p>
                            Os produtos, precos e disponibilidade podem ser
                            confirmados pela equipe antes da conclusao
                            definitiva do pedido.
                        </p>
                        <p>
                            Pedidos feitos pelo site ainda nao possuem pagamento
                            online integrado. A forma de pagamento sera
                            combinada no atendimento.
                        </p>
                        <p>
                            Uniformes personalizados podem depender de aprovacao
                            de arte, grade, tamanhos e prazos de producao.
                        </p>
                        <p>
                            Ao finalizar um pedido, voce concorda com o contato
                            da loja para confirmacao das informacoes.
                        </p>
                        {policies.personalizationPolicy && (
                            <Policy
                                title="Produtos personalizados"
                                text={String(policies.personalizationPolicy)}
                            />
                        )}
                        {policies.warrantyInfo && (
                            <Policy
                                title="Garantia"
                                text={String(policies.warrantyInfo)}
                            />
                        )}
                        {policies.exchangePolicy && (
                            <Policy
                                title="Troca e devolução"
                                text={String(policies.exchangePolicy)}
                            />
                        )}
                        {policies.deliveryPolicy && (
                            <Policy
                                title="Entrega"
                                text={String(policies.deliveryPolicy)}
                            />
                        )}
                    </>
                )}
            </div>
        </main>
    );
}

function Policy({ title, text }: { title: string; text: string }) {
    return (
        <section>
            <h2 className="font-display text-lg font-black text-navy">
                {title}
            </h2>
            <p className="mt-2 whitespace-pre-line">{text}</p>
        </section>
    );
}
