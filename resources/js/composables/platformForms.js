function tenantFormBase() {
    return {
        name: '',
        slug: '',
        legal_name: '',
        document_type: 'cnpj',
        document_number: '',
        state_registration: '',
        municipal_registration: '',
        phone: '',
        email: '',
        whatsapp: '',
        website: '',
        instagram: '',
        street: '',
        number: '',
        complement: '',
        neighborhood: '',
        city: '',
        state: '',
        postal_code: '',
        default_locale: 'pt_BR',
        currency: 'BRL',
        timezone: 'America/Sao_Paulo',
        public_description: '',
        theme_primary_color: '#ea580c',
        theme_secondary_color: '#1f2937',
        motoboys_enabled: true,
        pos_enabled: true,
        kds_enabled: true,
    };
}

/** Formulário de criação (inclui plano). */
export function emptyTenantForm(plans = [], languages = []) {
    const defaultLocale =
        languages.find((l) => l.is_default)?.code ?? languages[0]?.code ?? 'pt_BR';

    return {
        ...tenantFormBase(),
        default_locale: defaultLocale,
        plan_id: plans[0]?.id ?? '',
    };
}

/** Formulário de edição — sem plan_id (evita validação exists no PUT). */
export function emptyTenantEditForm() {
    return tenantFormBase();
}

function featureEnabledFromSettings(settings, key) {
    return (settings?.[key] ?? true) !== false;
}

export function tenantFormFromModel(t) {
    return {
        name: t.name ?? '',
        slug: t.slug ?? '',
        legal_name: t.legal_name ?? '',
        document_type: t.document_type ?? 'cnpj',
        document_number: t.document_number ?? '',
        state_registration: t.state_registration ?? '',
        municipal_registration: t.municipal_registration ?? '',
        phone: t.phone ?? '',
        email: t.email ?? '',
        whatsapp: t.whatsapp ?? '',
        website: t.website ?? '',
        instagram: t.social_links?.instagram ?? '',
        street: t.street ?? '',
        number: t.number ?? '',
        complement: t.complement ?? '',
        neighborhood: t.neighborhood ?? '',
        city: t.city ?? '',
        state: t.state ?? '',
        postal_code: t.postal_code ?? '',
        default_locale: t.default_locale ?? 'pt_BR',
        currency: t.currency ?? 'BRL',
        timezone: t.timezone ?? 'America/Sao_Paulo',
        public_description: t.public_description ?? '',
        theme_primary_color: t.theme_primary_color ?? '#ea580c',
        theme_secondary_color: t.theme_secondary_color ?? '#1f2937',
        motoboys_enabled: featureEnabledFromSettings(t.settings_json, 'motoboys_enabled'),
        pos_enabled: featureEnabledFromSettings(t.settings_json, 'pos_enabled'),
        kds_enabled: featureEnabledFromSettings(t.settings_json, 'kds_enabled'),
    };
}

export function emptyBranchForm() {
    return {
        name: '',
        slug: '',
        phone: '',
        instagram: '',
        notification_email: '',
        public_description: '',
        street: '',
        number: '',
        complement: '',
        neighborhood: '',
        city: '',
        state: '',
        postal_code: '',
        latitude: '',
        longitude: '',
        opening_hours: {},
        is_active: true,
        pickup_available: true,
        delivery_available: true,
        delivery_radius_km: '',
        minimum_order_amount: '',
        packaging_fee_default: '',
        default_prep_time_minutes: 30,
        delivery_time_minutes: 25,
        auto_accept_orders: false,
        allow_scheduled_orders: false,
        auto_print_on_new_order: false,
        password: '',
        print_format: 'thermal_80mm',
        print_copies_default: 1,
        order_disposables: [],
        cover_image: null,
    };
}

export function branchFormFromModel(b) {
    return {
        id: b.id,
        name: b.name ?? '',
        slug: b.slug ?? '',
        phone: b.phone ?? '',
        instagram: b.instagram ?? '',
        notification_email: b.notification_email ?? '',
        public_description: b.public_description ?? '',
        street: b.street ?? '',
        number: b.number ?? '',
        complement: b.complement ?? '',
        neighborhood: b.neighborhood ?? '',
        city: b.city ?? '',
        state: b.state ?? '',
        postal_code: b.postal_code ?? '',
        latitude: b.latitude ?? '',
        longitude: b.longitude ?? '',
        opening_hours: b.opening_hours ?? {},
        is_active: b.is_active ?? true,
        pickup_available: b.pickup_available ?? true,
        delivery_available: b.delivery_available ?? true,
        delivery_radius_km: b.delivery_radius_km ?? '',
        minimum_order_amount: b.minimum_order_amount ?? '',
        packaging_fee_default: b.packaging_fee_default ?? '',
        default_prep_time_minutes: b.default_prep_time_minutes ?? 30,
        delivery_time_minutes: b.delivery_time_minutes ?? 25,
        auto_accept_orders: b.auto_accept_orders ?? false,
        allow_scheduled_orders: b.allow_scheduled_orders ?? false,
        auto_print_on_new_order: b.auto_print_on_new_order ?? false,
        print_format: b.print_format ?? 'thermal_80mm',
        print_copies_default: b.print_copies_default ?? 1,
        order_disposables: b.order_disposables ?? [],
        cover_image: null,
    };
}
