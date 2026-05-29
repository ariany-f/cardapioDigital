import { usePage } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';

export function useSeoAnalytics() {
    const page = usePage();

    const load = (id) => {
        if (!id || typeof window === 'undefined' || window.__seoAnalyticsLoaded === id) return;

        window.__seoAnalyticsLoaded = id;
        window.dataLayer = window.dataLayer || [];
        window.gtag =
            window.gtag ||
            function gtag() {
                window.dataLayer.push(arguments);
            };
        window.gtag('js', new Date());
        window.gtag('config', id);

        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${id}`;
        document.head.appendChild(script);
    };

    onMounted(() => load(page.props.seo?.analytics));

    watch(
        () => page.props.seo?.analytics,
        (id) => load(id),
    );
}
