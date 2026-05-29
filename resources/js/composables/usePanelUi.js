import { computed, unref } from 'vue';

export function usePanelUi(mode = 'tenant') {
    const cls = computed(() => {
        const isPlatform = unref(mode) === 'platform';
        const p = isPlatform ? 'platform' : 'admin';

        return {
            pageTitle: isPlatform ? 'text-2xl font-bold tracking-tight text-slate-900' : 'admin-page-title',
            card: `${p}-card`,
            input: `${p}-input`,
            btnPrimary: `${p}-btn-primary`,
            btnSecondary: `${p}-btn-secondary`,
            tableWrap: `${p}-table-wrap`,
            table: `${p}-table`,
            link: isPlatform ? 'text-indigo-600 hover:text-indigo-700' : 'text-orange-600 hover:text-orange-700',
            linkMuted: isPlatform ? 'text-slate-600 hover:text-indigo-600' : 'text-stone-600 hover:text-orange-600',
        };
    });

    return { cls };
}
