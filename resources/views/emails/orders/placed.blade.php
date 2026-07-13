@component('mail::message')
# {{ ($order['checkoutType'] ?? 'payment') === 'quote' ? ($adminCopy ? 'Novo orcamento recebido' : 'Orcamento recebido') : ($adminCopy ? 'Novo pedido recebido' : 'Pedido recebido') }}

{{ ($order['checkoutType'] ?? 'payment') === 'quote' ? 'Orcamento' : 'Pedido' }} **{{ $order['number'] }}**

Cliente: **{{ $order['customerName'] }}**

Total: **{{ $order['total'] }}**

@if(! empty($order['couponCode']))
Cupom: **{{ $order['couponCode'] }}**
@endif

@component('mail::table')
| Produto | Qtd | Subtotal |
| --- | ---: | ---: |
@foreach($order['items'] as $item)
| {{ $item['name'] }} @if($item['variationLabel'])<br><small>{{ $item['variationLabel'] }}</small>@endif @if(! empty($item['notes']))<br><small>Obs: {{ $item['notes'] }}</small>@endif | {{ $item['quantity'] }} | {{ $item['subtotal'] }} |
@endforeach
@endcomponent

@if(! $adminCopy)
Nossa equipe vai revisar as informacoes e entrar em contato para {{ ($order['checkoutType'] ?? 'payment') === 'quote' ? 'confirmar o orcamento.' : 'combinar entrega e pagamento.' }}
@endif

Obrigado,<br>
{{ app(\App\Support\StoreSettings::class)->storeName() }}
@endcomponent
