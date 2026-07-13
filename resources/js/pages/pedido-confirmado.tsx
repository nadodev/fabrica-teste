import { Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, Copy, MessageCircle, PackageCheck } from 'lucide-react';
import { useEffect, useState } from 'react';

type Instructions = {
    paymentUrl: string | null;
    pixPayload: string | null;
    pixEncodedImage: string | null;
    pixExpirationDate: string | null;
} | null;

export default function PedidoConfirmado({
    orderNumber,
    checkoutType,
    paymentMethod,
    paymentStatus,
    paymentFailureCode,
    instructions,
}: {
    orderNumber: string;
    checkoutType: string;
    paymentMethod: string;
    paymentStatus: string;
    paymentFailureCode: string | null;
    instructions: Instructions;
}) {
    const [copied, setCopied] = useState(false);
    const page = usePage<{
        siteSettings?: { whatsapp?: string };
        errors?: Record<string, string>;
    }>();
    const whatsapp = page.props.siteSettings?.whatsapp?.replace(/\D/g, '');
    const paymentError =
        page.props.errors?.payment ??
        (paymentFailureCode === 'declined'
            ? 'O cartao nao foi autorizado. Confira os dados ou fale com a loja.'
            : paymentFailureCode
              ? paymentMethod === 'credit_card'
                  ? 'O cartao nao pode ser processado por uma indisponibilidade temporaria. Fale com a loja antes de tentar novamente.'
                  : 'O pagamento ainda nao foi gerado por uma indisponibilidade temporaria. Uma nova tentativa sera feita automaticamente.'
              : null);
    const pixReady =
        paymentMethod === 'pix' && Boolean(instructions?.pixPayload);
    const qrImage = instructions?.pixEncodedImage
        ? instructions.pixEncodedImage.startsWith('data:image/')
            ? instructions.pixEncodedImage
            : `data:image/png;base64,${instructions.pixEncodedImage}`
        : null;

    useEffect(() => {
        if (
            checkoutType !== 'payment' ||
            !['pending', 'processing'].includes(paymentStatus)
        ) {
            return;
        }

        const timer = window.setInterval(() => {
            router.reload({
                only: ['paymentStatus', 'paymentFailureCode', 'instructions'],
            });
        }, 5000);

        return () => window.clearInterval(timer);
    }, [checkoutType, paymentStatus]);

    async function copyPix(): Promise<void> {
        if (!instructions?.pixPayload) {
            return;
        }

        await navigator.clipboard.writeText(instructions.pixPayload);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 2500);
    }

    return (
        <main className="min-h-[70vh] bg-bg-soft px-4 py-16">
            <div className="mx-auto max-w-2xl rounded-2xl border border-border bg-white p-8 text-center shadow-[var(--shadow-card)]">
                <div className="mx-auto grid h-20 w-20 place-items-center rounded-full bg-green-100 text-green-800">
                    <CheckCircle2 className="h-10 w-10" />
                </div>
                <h1 className="mt-6 font-display text-3xl font-black text-navy">
                    {checkoutType === 'quote'
                        ? 'Orcamento recebido'
                        : 'Pedido gerado com sucesso'}
                </h1>
                <p className="mt-3 text-text-muted">
                    Recebemos seu pedido{' '}
                    <strong className="text-navy">{orderNumber}</strong>. Nossa
                    {checkoutType === 'quote'
                        ? ' Nossa equipe entrara em contato para dar continuidade.'
                        : ' Confira abaixo as instrucoes para concluir o pagamento.'}
                </p>

                {paymentError && (
                    <div className="mt-6 rounded-xl bg-red-50 p-4 text-left text-sm font-semibold text-red-800">
                        {paymentError}
                    </div>
                )}

                {pixReady && (
                    <section className="mt-6 rounded-xl border border-border bg-bg-soft p-5 text-left">
                        <h2 className="text-center font-display text-xl font-black text-navy">
                            Pague com PIX
                        </h2>
                        {qrImage && (
                            <img
                                src={qrImage}
                                alt="QR Code PIX do pedido"
                                className="mx-auto mt-4 h-56 w-56 rounded-lg bg-white p-2"
                            />
                        )}
                        <label className="mt-4 block text-sm font-bold text-navy">
                            PIX Copia e Cola
                        </label>
                        <div className="mt-2 rounded-lg border border-border bg-white p-3 font-mono text-xs break-all text-text-muted">
                            {instructions?.pixPayload}
                        </div>
                        <button
                            type="button"
                            onClick={copyPix}
                            className="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-md bg-yellow px-5 py-3 font-black text-navy"
                        >
                            <Copy className="h-4 w-4" />
                            {copied ? 'Codigo copiado' : 'Copiar codigo PIX'}
                        </button>
                        {instructions?.pixExpirationDate && (
                            <p className="mt-2 text-center text-xs text-text-muted">
                                Valido ate{' '}
                                {new Date(
                                    instructions.pixExpirationDate,
                                ).toLocaleString('pt-BR')}
                                .
                            </p>
                        )}
                    </section>
                )}

                {!pixReady &&
                    instructions?.paymentUrl &&
                    checkoutType === 'payment' && (
                        <a
                            href={instructions.paymentUrl}
                            className="mt-6 inline-flex rounded-md bg-yellow px-6 py-3 font-black text-navy"
                        >
                            Abrir pagamento
                        </a>
                    )}

                {!pixReady &&
                    checkoutType === 'payment' &&
                    !instructions?.paymentUrl &&
                    !paymentFailureCode &&
                    ['pending', 'processing'].includes(paymentStatus) && (
                        <div className="mt-6 rounded-xl bg-bg-soft p-4 text-left">
                            <div className="flex gap-3">
                                <PackageCheck className="mt-0.5 h-5 w-5 text-navy" />
                                <div>
                                    <div className="font-bold text-navy">
                                        Gerando seu pagamento
                                    </div>
                                    <p className="mt-1 text-sm text-text-muted">
                                        Esta pagina atualiza automaticamente.
                                        Aguarde alguns segundos.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                {paymentStatus === 'paid' && checkoutType === 'payment' && (
                    <div className="mt-6 rounded-xl bg-green-50 p-4 font-bold text-green-800">
                        Pagamento confirmado.
                    </div>
                )}

                {checkoutType === 'quote' && (
                    <div className="mt-6 rounded-xl bg-bg-soft p-4 text-left">
                        <div className="flex gap-3">
                            <PackageCheck className="mt-0.5 h-5 w-5 text-navy" />
                            <div>
                                <div className="font-bold text-navy">
                                    Proxima etapa
                                </div>
                                <p className="mt-1 text-sm text-text-muted">
                                    Seu pedido de orcamento seguira para
                                    atendimento da nossa equipe.
                                </p>
                            </div>
                        </div>
                    </div>
                )}
                <div className="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    {whatsapp && (
                        <a
                            href={`https://wa.me/${whatsapp}`}
                            className="inline-flex items-center justify-center gap-2 rounded-md bg-yellow px-6 py-3 font-black text-navy"
                        >
                            <MessageCircle className="h-5 w-5" /> Chamar no
                            WhatsApp
                        </a>
                    )}
                    <Link
                        href="/produtos"
                        className="inline-flex items-center justify-center rounded-md border border-navy px-6 py-3 font-bold text-navy"
                    >
                        Continuar comprando
                    </Link>
                </div>
            </div>
        </main>
    );
}
