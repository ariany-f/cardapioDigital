<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Mail\MarketingLeadMail;
use App\Models\MarketingLead;
use App\Services\Mail\MailDispatcher;
use App\Services\SeoService;
use App\Support\PlatformMarketing;
use App\Support\MarketingPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(SeoService $seo): Response|RedirectResponse
    {
        if (! PlatformMarketing::landingEnabled()) {
            return redirect()->route('login');
        }

        $marketing = MarketingPlans::forLanding();
        $featuredPlanModel = MarketingPlans::featuredPlanModel();

        return Inertia::render('Marketing/Landing', [
            'seo' => $seo->forMarketing($featuredPlanModel),
            'plans' => $marketing['plans'],
            'featuredPlan' => $marketing['featured'],
            'heroMock' => [
                'order_number' => '#1847',
                'branch' => 'Burger do Zé · Unidade Centro',
                'time' => 'Hoje · 19:42',
                'items' => [
                    ['qty' => 2, 'name' => 'X-Tudo especial', 'price' => '51,80'],
                    ['qty' => 1, 'name' => 'Batata rustica G', 'price' => '14,00'],
                    ['qty' => 1, 'name' => 'Refri 600ml', 'price' => '8,00'],
                ],
                'total' => '73,80',
                'status' => 'Em preparo',
                'status_detail' => 'Motoboy João · aguardando saída',
                'payment' => 'PIX · você confirma quando cair',
            ],
            'workflow' => [
                [
                    'title' => 'Acessa o cardápio',
                    'text' => 'Pelo link direto da unidade (Instagram, QR, WhatsApp) ou pelo link da loja, quando há várias filiais.',
                ],
                [
                    'title' => 'Monte o pedido',
                    'text' => 'Cardápio com variações, combos, observações e carrinho no celular.',
                ],
                [
                    'title' => 'Pague como quiser',
                    'text' => 'PIX, dinheiro ou cartão na entrega — você confirma no painel.',
                ],
                [
                    'title' => 'Acompanhe o pedido',
                    'text' => 'O cliente vê cada etapa no link — preparo, saída e código na entrega.',
                ],
            ],
            'categories' => [
                ['emoji' => '🍔', 'name' => 'Lanchonetes'],
                ['emoji' => '🍕', 'name' => 'Pizzarias'],
                ['emoji' => '🍦', 'name' => 'Açaiterias'],
                ['emoji' => '🛒', 'name' => 'Mercados'],
                ['emoji' => '💊', 'name' => 'Farmácias'],
                ['emoji' => '🐾', 'name' => 'Petshops'],
                ['emoji' => '🔥', 'name' => 'Churrascarias'],
                ['emoji' => '🍱', 'name' => 'Restaurantes'],
            ],
            'benefits' => [
                [
                    'title' => 'Seu cardápio, sua marca',
                    'text' => 'O cliente pede pelo link da sua loja — com logo, cores e descrição. Sem depender de app de terceiros para aparecer.',
                ],
                [
                    'title' => 'Cozinha e balcão no mesmo painel',
                    'text' => 'KDS na produção, PDV no balcão e pedidos online integrados. Tudo registrado, sem print de WhatsApp perdido.',
                ],
                [
                    'title' => 'Entrega organizada',
                    'text' => 'Painel web do entregador (no celular), atualização de status e código na porta — sem mapa ao vivo.',
                ],
            ],
            'audiences' => [
                [
                    'title' => 'Para quem quer vender mais',
                    'text' => 'Tenha cardápio digital, cupons e gestão de pedidos sem pagar comissão em cada venda.',
                    'cta' => 'Quero vender',
                    'href' => '#contato',
                ],
                [
                    'title' => 'Para quem já tem delivery',
                    'text' => 'Organize cozinha, motoboy e pagamentos num painel — e mantenha o relacionamento com o cliente.',
                    'cta' => 'Ver planos',
                    'href' => '#planos',
                ],
                [
                    'title' => 'Para quem quer independência',
                    'text' => 'Saia da dependência exclusiva de marketplaces e construa sua base de clientes diretos.',
                    'cta' => 'Falar com a gente',
                    'href' => '#contato',
                ],
            ],
            'stats' => [
                ['value' => '100%', 'label' => 'dos pedidos ficam com você — sem comissão por venda'],
                ['value' => '24/7', 'label' => 'cardápio online aceitando pedidos no horário da loja'],
                ['value' => '1', 'label' => 'painel para cozinha, balcão, entrega e relatórios'],
            ],
            'testimonials' => [
                [
                    'name' => 'Carla M.',
                    'role' => 'Pizzaria · SP',
                    'text' => 'Organizamos a cozinha com o KDS e o cliente acompanha o pedido pelo link. Parou a confusão no WhatsApp.',
                ],
                [
                    'name' => 'Ricardo L.',
                    'role' => 'Hamburgueria · PE',
                    'text' => 'O entregador abre o painel no celular, eu confirmo o PIX na hora. Acabou o caderno e o áudio perdido no grupo.',
                ],
                [
                    'name' => 'Fernanda S.',
                    'role' => 'Açaíteria · MG',
                    'text' => 'Cupom de desconto e combo no cardápio aumentaram o ticket médio. E pago só a mensalidade fixa.',
                ],
            ],
            'features' => [
                ['num' => '01', 'title' => 'Cardápio por unidade', 'text' => 'Cada filial com link, horário e cardápio próprio. Banner e destaque do dia inclusos.'],
                ['num' => '02', 'title' => 'KDS na cozinha', 'text' => 'Fila de produção na tela — sem gritar pedido ou reler mensagem apagada.'],
                ['num' => '03', 'title' => 'PDV no balcão', 'text' => 'Venda presencial com os mesmos produtos, combos e formas de pagamento do online.'],
                ['num' => '04', 'title' => 'PIX sem gateway', 'text' => 'Chave estática no checkout. Você marca como pago quando o valor cair.'],
                ['num' => '05', 'title' => 'Motoboy integrado', 'text' => 'Painel web do entregador no celular ou comanda impressa, com código na entrega.', 'wide' => true],
                ['num' => '06', 'title' => 'Chat no cardápio', 'text' => 'Cliente tira dúvida antes de fechar — histórico fica no pedido, não se perde no Zap.'],
            ],
        ]);
    }

    public function contact(Request $request, MailDispatcher $mail): RedirectResponse
    {
        if (! PlatformMarketing::landingEnabled()) {
            abort(404);
        }

        $data = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = MarketingLead::create([
            ...$data,
            'status' => MarketingLead::STATUS_PENDING,
        ]);

        $lead = [
            ...$data,
            'submitted_at' => $record->created_at
                ->timezone(config('app.timezone'))
                ->format('d/m/Y H:i'),
        ];

        $mail->send(config('marketing.contact_email'), new MarketingLeadMail($lead));

        return back()->with('success', 'Recebemos sua mensagem! Entraremos em contato em breve pelo e-mail informado.');
    }
}
