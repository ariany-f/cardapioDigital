import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Rotas do catálogo no admin do tenant ou no superadmin (platform).
 * @param {'tenant'|'platform'} mode
 * @param {object|null} platformTenant - { id, slug, name } quando mode === 'platform'
 */
export function useCatalogRoutes(mode = 'tenant', platformTenant = null) {
    const page = usePage();

    const tenant = computed(() =>
        mode === 'platform' ? platformTenant : page.props.tenant,
    );

    const tenantKey = computed(() =>
        mode === 'platform' ? tenant.value?.id : tenant.value?.slug,
    );

    const products = {
        index: () =>
            mode === 'platform'
                ? route('platform.tenants.products.index', tenantKey.value)
                : route('tenant.admin.products.index', { tenant: tenantKey.value }),
        create: () =>
            mode === 'platform'
                ? route('platform.tenants.products.create', tenantKey.value)
                : route('tenant.admin.products.create', { tenant: tenantKey.value }),
        store: () =>
            mode === 'platform'
                ? route('platform.tenants.products.store', tenantKey.value)
                : route('tenant.admin.products.store', { tenant: tenantKey.value }),
        edit: (productId) =>
            mode === 'platform'
                ? route('platform.tenants.products.edit', [tenantKey.value, productId])
                : route('tenant.admin.products.edit', { tenant: tenantKey.value, product: productId }),
        update: (productId) =>
            mode === 'platform'
                ? route('platform.tenants.products.update', [tenantKey.value, productId])
                : route('tenant.admin.products.update', { tenant: tenantKey.value, product: productId }),
        destroy: (productId) =>
            mode === 'platform'
                ? route('platform.tenants.products.destroy', [tenantKey.value, productId])
                : route('tenant.admin.products.destroy', { tenant: tenantKey.value, product: productId }),
    };

    const categories = {
        index: () =>
            mode === 'platform'
                ? route('platform.tenants.categories.index', tenantKey.value)
                : route('tenant.admin.categories.index', { tenant: tenantKey.value }),
        store: () =>
            mode === 'platform'
                ? route('platform.tenants.categories.store', tenantKey.value)
                : route('tenant.admin.categories.store', { tenant: tenantKey.value }),
        update: (categoryId) =>
            mode === 'platform'
                ? route('platform.tenants.categories.update', [tenantKey.value, categoryId])
                : route('tenant.admin.categories.update', { tenant: tenantKey.value, category: categoryId }),
        destroy: (categoryId) =>
            mode === 'platform'
                ? route('platform.tenants.categories.destroy', [tenantKey.value, categoryId])
                : route('tenant.admin.categories.destroy', { tenant: tenantKey.value, category: categoryId }),
    };

    const backToTenant = () =>
        mode === 'platform'
            ? route('platform.tenants.show', tenantKey.value)
            : route('tenant.admin.dashboard', { tenant: tenantKey.value });

    const fullAdminDashboard = () =>
        tenant.value?.slug
            ? route('tenant.admin.dashboard', { tenant: tenant.value.slug })
            : null;

    const publicMenu = (branchSlug) =>
        mode === 'platform'
            ? route('tenant.branch', { tenant: tenant.value.slug, branch: branchSlug })
            : route('tenant.branch', { tenant: tenant.value.slug, branch: branchSlug });

    return { mode, tenant, products, categories, backToTenant, fullAdminDashboard, publicMenu };
}
