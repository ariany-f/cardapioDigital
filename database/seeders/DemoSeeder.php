<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Combo;
use App\Models\ComboItem;
use App\Support\DemoProductImages;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Delivery;
use App\Models\DeliveryZone;
use App\Models\DiningTable;
use App\Models\Motoboy;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRating;
use App\Models\OrderStatusHistory;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductVariationGroup;
use App\Models\ProductVariationOption;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make(env('SEED_DEMO_PASSWORD', 'password'));
        $plan = Plan::where('slug', 'pro')->first() ?? Plan::where('slug', 'basico')->first();

        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'acme'],
            [
                'name' => 'ACME Lanches',
                'legal_name' => 'ACME Lanches e Alimentação Ltda',
                'document_type' => 'cnpj',
                'document_number' => '12.345.678/0001-90',
                'state_registration' => '123.456.789.012',
                'municipal_registration' => '987654-1',
                'phone' => '(11) 3000-0000',
                'email' => 'contato@acmelanches.com.br',
                'website' => 'https://acmelanches.com.br',
                'street' => 'Rua Augusta',
                'number' => '500',
                'complement' => 'Sala 12',
                'neighborhood' => 'Consolação',
                'city' => 'São Paulo',
                'state' => 'SP',
                'postal_code' => '01304-000',
                'status' => 'active',
                'default_locale' => 'pt_BR',
                'public_description' => 'Os melhores lanches da cidade desde 1990. Qualidade e sabor em cada pedido.',
                'whatsapp' => '5511300000000',
                'social_links' => ['instagram' => '@acmelanches'],
                'theme_primary_color' => '#f4003a',
                'theme_secondary_color' => '#1f2937',
                'settings_json' => [
                    'pix_enabled' => true,
                    'pix_key_type' => 'phone',
                    'pix_key' => '5511300000000',
                    'pix_beneficiary' => 'ACME Lanches',
                    'card_online_enabled' => true,
                    'card_online_instructions' => 'Envie o comprovante pelo WhatsApp do restaurante.',
                    'motoboy_auto_accept_assignments' => false,
                ],
            ]
        );

        $subscription = TenantSubscription::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'status' => 'active'],
            [
                'plan_id' => $plan->id,
                'current_period_start' => now()->startOfMonth(),
                'current_period_end' => now()->endOfMonth(),
                'payment_status' => 'paid',
            ]
        );

        TenantPayment::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'DEMO-001'],
            [
                'tenant_subscription_id' => $subscription->id,
                'amount' => $plan->price_monthly,
                'paid_at' => now(),
                'notes' => 'Pagamento demo',
            ]
        );

        $branch = Branch::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'centro'],
            [
                'name' => 'ACME — Centro',
                'phone' => '(11) 3000-0001',
                'street' => 'Avenida Paulista',
                'number' => '1000',
                'complement' => 'Loja 42 — Térreo',
                'neighborhood' => 'Bela Vista',
                'city' => 'São Paulo',
                'state' => 'SP',
                'postal_code' => '01310-100',
                'latitude' => -23.5614140,
                'longitude' => -46.6558810,
                'delivery_radius_km' => 8.00,
                'opening_hours' => [
                    'mon' => ['09:00', '23:59'],
                    'tue' => ['09:00', '23:59'],
                    'wed' => ['09:00', '23:59'],
                    'thu' => ['09:00', '23:59'],
                    'fri' => ['09:00', '23:59'],
                    'sat' => ['10:00', '23:59'],
                    'sun' => ['10:00', '23:59'],
                ],
                'public_description' => 'Nossa unidade no centro. Retirada e entrega.',
                'cover_image_path' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1600&q=80',
                'pickup_available' => true,
                'delivery_available' => true,
                'print_format' => 'thermal_80mm',
                'print_formats_enabled' => ['thermal_80mm', 'thermal_58mm', 'a4_summary'],
                'minimum_order_amount' => 25,
                'packaging_fee_default' => 3,
                'order_disposables' => [
                    ['key' => 'cutlery', 'label' => 'Talheres descartáveis', 'min_qty' => 0, 'max_qty' => 4, 'default_qty' => 0],
                    ['key' => 'napkin', 'label' => 'Guardanapos', 'min_qty' => 0, 'max_qty' => 10, 'default_qty' => 2],
                    ['key' => 'straw', 'label' => 'Canudo', 'min_qty' => 0, 'max_qty' => 6, 'default_qty' => 0],
                ],
                'default_prep_time_minutes' => 25,
                'delivery_time_minutes' => 20,
                'auto_accept_orders' => false,
                'allow_scheduled_orders' => true,
            ]
        );

        DeliveryZone::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'name' => 'Taxa padrão'],
            [
                'type' => 'flat',
                'rules' => [],
                'delivery_fee' => 8.90,
            ]
        );

        DeliveryZone::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'name' => 'Bela Vista'],
            [
                'type' => 'neighborhood',
                'rules' => ['neighborhoods' => ['Bela Vista', 'Centro']],
                'delivery_fee' => 6.90,
                'min_order_override' => 35,
            ]
        );

        DiningTable::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'name' => 'Mesa 01'],
            ['qr_token' => 'mesa01demo']
        );

        Banner::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'title' => 'Combo do dia'],
            [
                'image_path' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        Banner::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'title' => 'Frete grátis acima de R$ 60'],
            [
                'image_path' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        Coupon::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'ACME10'],
            [
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 40,
                'valid_until' => now()->addMonths(3),
                'is_active' => true,
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@acme.test'],
            [
                'name' => 'Admin ACME',
                'password' => $password,
                'tenant_id' => $tenant->id,
                'is_platform_user' => false,
                'is_protected_admin' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['tenant_admin']);

        User::query()->updateOrCreate(
            ['email' => 'gerente@acme.test'],
            ['name' => 'Gerente ACME', 'password' => $password, 'tenant_id' => $tenant->id, 'email_verified_at' => now()]
        )->syncRoles(['manager']);

        $this->seedAcmeMenu($tenant, $branch);

        $customer = Customer::query()->updateOrCreate(
            ['email' => 'cliente@demo.test'],
            ['name' => 'Cliente Demo', 'phone' => '11999999999', 'password' => $password]
        );

        CustomerAddress::withoutGlobalScopes()->firstOrCreate(
            ['customer_id' => $customer->id, 'street' => 'Rua Demo'],
            [
                'tenant_id' => $tenant->id,
                'number' => '100',
                'neighborhood' => 'Bela Vista',
                'city' => 'São Paulo',
                'state' => 'SP',
                'postal_code' => '01310-200',
                'is_default' => true,
            ]
        );

        $motoboy = Motoboy::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => '11988887777'],
            [
                'name' => 'João Silva',
                'cpf' => '123.456.789-00',
                'email' => 'joao.motoboy@acme.test',
                'password' => 'password',
                'document_rg' => '12.345.678-9',
                'birth_date' => '1990-05-15',
                'street' => 'Rua das Entregas',
                'number' => '50',
                'neighborhood' => 'Centro',
                'city' => 'São Paulo',
                'state' => 'SP',
                'postal_code' => '01310-100',
                'emergency_contact_name' => 'Maria Silva',
                'emergency_contact_phone' => '11977776666',
                'vehicle_type' => 'motorcycle',
                'vehicle' => 'Honda CG 160 — preta',
                'license_plate' => 'ABC-1D23',
                'cnh_number' => '12345678901',
                'cnh_category' => 'A',
                'cnh_expires_at' => now()->addYears(3)->toDateString(),
                'pix_key_type' => 'phone',
                'pix_key' => '11988887777',
                'employment_type' => 'freelancer',
                'employee_code' => 'ENT-001',
                'hired_at' => now()->subMonths(6)->toDateString(),
                'commission_percent' => 5,
                'operational_status' => 'busy',
                'max_active_deliveries' => 3,
                'notes' => 'Preferência: turno almoço e noite. Conhece bem a região da Bela Vista.',
                'is_active' => true,
                'uses_app' => true,
                'access_all_branches' => true,
            ]
        );
        $motoboy->branches()->sync([]);

        Motoboy::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => '11966665555'],
            [
                'name' => 'Carlos (parceiro impresso)',
                'vehicle_type' => 'motorcycle',
                'employment_type' => 'partner',
                'operational_status' => 'available',
                'max_active_deliveries' => 1,
                'notes' => 'Não usa o painel web — recebe comanda impressa; o restaurante atualiza o status.',
                'is_active' => true,
                'uses_app' => false,
                'access_all_branches' => false,
            ]
        )->branches()->sync([$branch->id]);

        $order = Order::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'order_number' => 'ACME-0001'],
            [
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'type' => 'delivery',
                'status' => 'preparing',
                'subtotal' => 28.90,
                'delivery_fee' => 8.90,
                'packaging_fee' => 3,
                'total' => 40.80,
                'payment_method' => 'on_delivery',
                'payment_channel' => 'pix',
                'guest_name' => $customer->name,
                'guest_phone' => $customer->phone,
            ]
        );

        OrderItem::withoutGlobalScopes()->firstOrCreate(
            ['order_id' => $order->id, 'name' => 'X-Burger ACME'],
            [
                'tenant_id' => $tenant->id,
                'quantity' => 1,
                'unit_price' => 28.90,
                'total_price' => 28.90,
                'notes' => 'Sem cebola',
            ]
        );

        OrderStatusHistory::withoutGlobalScopes()->firstOrCreate(
            ['order_id' => $order->id, 'status' => 'confirmed'],
            ['tenant_id' => $tenant->id, 'origin' => 'system']
        );

        Delivery::withoutGlobalScopes()->firstOrCreate(
            ['order_id' => $order->id],
            ['tenant_id' => $tenant->id, 'motoboy_id' => $motoboy->id, 'status' => 'on_route']
        );

        SupportRequest::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'subject' => 'Dúvida sobre pedido'],
            [
                'order_id' => $order->id,
                'type' => 'help',
                'message' => 'Quanto tempo para entrega?',
                'status' => 'open',
                'guest_name' => $customer->name,
                'guest_phone' => $customer->phone,
                'guest_email' => $customer->email,
            ]
        );

        $this->command->info('Demo ACME seeded at /acme');
    }

    protected function seedAcmeMenu(Tenant $tenant, Branch $branch): void
    {
        $categories = [
            'Burgers' => Category::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Burgers'],
                ['sort_order' => 1]
            ),
            'Porções' => Category::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Porções'],
                ['sort_order' => 2]
            ),
            'Bebidas' => Category::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Bebidas'],
                ['sort_order' => 3]
            ),
            'Sobremesas' => Category::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Sobremesas'],
                ['sort_order' => 4]
            ),
            'Combos' => Category::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Combos'],
                ['sort_order' => 5]
            ),
        ];

        $products = [
            ['cat' => 'Burgers', 'name' => 'X-Burger ACME', 'price' => 28.90, 'desc' => 'Hambúrguer 180g, queijo, alface, tomate', 'featured' => true, 'variations' => [
                ['type' => 'choice', 'group' => 'Tamanho', 'min' => 1, 'max' => 1, 'options' => [['name' => 'Normal', 'price' => 0], ['name' => 'Duplo', 'price' => 8]]],
                ['type' => 'choice', 'group' => 'Ponto da carne', 'min' => 1, 'max' => 1, 'options' => [['name' => 'Ao ponto', 'price' => 0], ['name' => 'Bem passado', 'price' => 0]]],
                ['type' => 'addon', 'group' => 'Adicionais', 'min' => 0, 'max' => 6, 'options' => [
                    ['name' => 'Bacon extra', 'price' => 5], ['name' => 'Cheddar extra', 'price' => 4],
                    ['name' => 'Ovo', 'price' => 3], ['name' => 'Cebola caramelizada', 'price' => 3],
                ]],
                ['type' => 'disposable', 'group' => 'Descartáveis deste item', 'min' => 0, 'max' => 3, 'allow_quantity' => true, 'options' => [
                    ['name' => 'Talheres', 'price' => 0, 'max_quantity' => 4],
                    ['name' => 'Guardanapo extra', 'price' => 0, 'max_quantity' => 6],
                    ['name' => 'Canudo', 'price' => 0, 'max_quantity' => 4],
                ]],
            ]],
            ['cat' => 'Burgers', 'name' => 'X-Bacon', 'price' => 32.90, 'desc' => 'Bacon crocante e cheddar', 'variations' => [
                ['type' => 'addon', 'group' => 'Adicionais', 'min' => 0, 'max' => 4, 'options' => [
                    ['name' => 'Queijo extra', 'price' => 4], ['name' => 'Bacon extra', 'price' => 5], ['name' => 'Molho especial', 'price' => 2],
                ]],
            ]],
            ['cat' => 'Burgers', 'name' => 'X-Tudo', 'price' => 36.90, 'desc' => 'O completo da casa', 'variations' => [
                ['type' => 'addon', 'group' => 'Adicionais', 'min' => 0, 'max' => 5, 'options' => [
                    ['name' => 'Ovo', 'price' => 3], ['name' => 'Catupiry', 'price' => 4], ['name' => 'Calabresa', 'price' => 5],
                ]],
            ]],
            ['cat' => 'Burgers', 'name' => 'X-Salada', 'price' => 26.90, 'desc' => 'Versão leve com salada fresca', 'variations' => [
                ['type' => 'choice', 'group' => 'Tamanho', 'min' => 1, 'max' => 1, 'options' => [['name' => 'Normal', 'price' => 0], ['name' => 'Grande', 'price' => 5]]],
                ['type' => 'addon', 'group' => 'Adicionais', 'min' => 0, 'max' => 3, 'options' => [['name' => 'Frango grelhado', 'price' => 6], ['name' => 'Queijo branco', 'price' => 4]]],
            ]],
            ['cat' => 'Burgers', 'name' => 'Cheeseburger', 'price' => 24.90, 'desc' => 'Clássico com queijo derretido'],
            ['cat' => 'Burgers', 'name' => 'Veggie Burger', 'price' => 29.90, 'desc' => 'Hambúrguer de grão-de-bico'],
            ['cat' => 'Porções', 'name' => 'Batata frita', 'price' => 18.90, 'desc' => 'Porção individual crocante', 'variations' => [
                ['type' => 'choice', 'group' => 'Tamanho', 'min' => 1, 'max' => 1, 'options' => [['name' => 'Média', 'price' => 0], ['name' => 'Grande', 'price' => 6]]],
                ['type' => 'addon', 'group' => 'Adicionais', 'min' => 0, 'max' => 3, 'options' => [
                    ['name' => 'Cheddar e bacon', 'price' => 7], ['name' => 'Molho especial', 'price' => 3],
                ]],
            ]],
            ['cat' => 'Porções', 'name' => 'Onion rings', 'price' => 19.90, 'desc' => 'Anéis de cebola empanados'],
            ['cat' => 'Porções', 'name' => 'Nuggets (8 un.)', 'price' => 16.90, 'desc' => 'Com molho barbecue'],
            ['cat' => 'Porções', 'name' => 'Frango a passarinho', 'price' => 27.90, 'desc' => 'Porção generosa'],
            ['cat' => 'Bebidas', 'name' => 'Refrigerante lata', 'price' => 6.90, 'desc' => '350ml', 'variations' => [
                ['type' => 'choice', 'group' => 'Sabor', 'min' => 1, 'max' => 1, 'options' => [['name' => 'Cola', 'price' => 0], ['name' => 'Guaraná', 'price' => 0], ['name' => 'Laranja', 'price' => 0]]],
                ['type' => 'disposable', 'group' => 'Descartáveis', 'min' => 0, 'max' => 2, 'allow_quantity' => true, 'options' => [
                    ['name' => 'Canudo', 'price' => 0, 'max_quantity' => 3],
                    ['name' => 'Copo extra', 'price' => 0, 'max_quantity' => 2],
                ]],
            ]],
            ['cat' => 'Bebidas', 'name' => 'Suco natural', 'price' => 9.90, 'desc' => 'Laranja ou limão'],
            ['cat' => 'Bebidas', 'name' => 'Água mineral', 'price' => 4.50, 'desc' => '500ml'],
            ['cat' => 'Bebidas', 'name' => 'Milkshake', 'price' => 14.90, 'desc' => '400ml cremoso', 'variations' => [
                ['type' => 'choice', 'group' => 'Sabor', 'min' => 1, 'max' => 1, 'options' => [['name' => 'Chocolate', 'price' => 0], ['name' => 'Morango', 'price' => 0], ['name' => 'Baunilha', 'price' => 0]]],
                ['type' => 'addon', 'group' => 'Adicionais', 'min' => 0, 'max' => 2, 'options' => [['name' => 'Chantilly', 'price' => 3], ['name' => 'Calda extra', 'price' => 2]]],
            ]],
            ['cat' => 'Sobremesas', 'name' => 'Brownie com sorvete', 'price' => 15.90, 'desc' => 'Quente com bola de creme'],
            ['cat' => 'Sobremesas', 'name' => 'Petit gateau', 'price' => 18.90, 'desc' => 'Chocolate belga'],
            ['cat' => 'Sobremesas', 'name' => 'Mousse de maracujá', 'price' => 12.90, 'desc' => 'Receita da casa'],
            ['cat' => 'Combos', 'name' => 'Combo Executivo', 'price' => 39.90, 'desc' => 'Burger + acompanhamento + bebida', 'featured' => true, 'variations' => [
                ['type' => 'choice', 'group' => 'Bebida', 'min' => 1, 'max' => 1, 'options' => [['name' => 'Refri lata', 'price' => 0], ['name' => 'Suco', 'price' => 2]]],
                ['type' => 'choice', 'group' => 'Acompanhamento', 'min' => 1, 'max' => 1, 'options' => [['name' => 'Batata média', 'price' => 0], ['name' => 'Onion rings', 'price' => 3]]],
                ['type' => 'addon', 'group' => 'Adicionais', 'min' => 0, 'max' => 3, 'options' => [['name' => 'Sobremesa mini', 'price' => 5], ['name' => 'Bacon no burger', 'price' => 5]]],
            ]],
            ['cat' => 'Combos', 'name' => 'Combo Família', 'price' => 89.90, 'desc' => '2 burgers + 2 porções + 2 refris'],
            ['cat' => 'Combos', 'name' => 'Combo Kids', 'price' => 22.90, 'desc' => 'Nuggets + suco + brinde'],
        ];

        foreach ($products as $data) {
            $product = Product::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'category_id' => $categories[$data['cat']]->id,
                    'name' => $data['name'],
                ],
                [
                    'description' => $data['desc'] ?? null,
                    'image_path' => DemoProductImages::pathFor($data['name']),
                    'base_price' => $data['price'],
                    'is_active' => true,
                    'is_featured' => $data['featured'] ?? false,
                    'prep_time_minutes' => 15,
                ]
            );

            $product->branches()->syncWithoutDetaching([
                $branch->id => ['tenant_id' => $tenant->id, 'is_available' => true],
            ]);

            foreach ($data['variations'] ?? [] as $v) {
                $group = ProductVariationGroup::withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'product_id' => $product->id,
                        'name' => $v['group'],
                    ],
                    [
                        'type' => $v['type'] ?? 'choice',
                        'min_select' => $v['min'],
                        'max_select' => $v['max'],
                        'allow_quantity' => $v['allow_quantity'] ?? (($v['type'] ?? '') === 'disposable'),
                    ]
                );

                foreach ($v['options'] as $i => $opt) {
                    ProductVariationOption::withoutGlobalScopes()->updateOrCreate(
                        [
                            'product_variation_group_id' => $group->id,
                            'name' => $opt['name'],
                        ],
                        [
                            'tenant_id' => $tenant->id,
                            'additional_price' => $opt['price'],
                            'max_quantity' => $opt['max_quantity'] ?? null,
                            'sort_order' => $i,
                        ]
                    );
                }
            }
        }

        $burger = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'X-Burger ACME')
            ->first();
        $fries = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Batata frita')
            ->first();
        $soda = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Refrigerante lata')
            ->first();

        if ($burger && $fries && $soda) {
            $combo = Combo::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'name' => 'Combo Clássico'],
                [
                    'description' => 'Burger + batata + refri',
                    'price' => 42.90,
                    'image_path' => DemoProductImages::pathFor('Combo Clássico'),
                    'is_active' => true,
                ]
            );

            ComboItem::withoutGlobalScopes()->updateOrCreate(
                ['combo_id' => $combo->id, 'product_id' => $burger->id],
                ['quantity' => 1]
            );
            ComboItem::withoutGlobalScopes()->updateOrCreate(
                ['combo_id' => $combo->id, 'product_id' => $fries->id],
                ['quantity' => 1]
            );
            ComboItem::withoutGlobalScopes()->updateOrCreate(
                ['combo_id' => $combo->id, 'product_id' => $soda->id],
                ['quantity' => 1]
            );
        }
    }
}
