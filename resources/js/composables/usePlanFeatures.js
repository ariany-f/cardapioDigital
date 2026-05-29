import { usePage } from '@inertiajs/vue3';

/** Ordem de exibição dos recursos no cardápio de planos. */
export const PLAN_FEATURE_KEYS = [
    'max_branches',
    'kds',
    'pos',
    'reports',
    'delivery_webhooks',
    'motoboys',
];

export function usePlanFeatures() {
    const page = usePage();
    const plansT = () => page.props.platformTranslations?.plans ?? {};

    const t = (key, fallback = key) => plansT()[key] ?? fallback;

    const featureLabel = (key) => plansT().features?.[key] ?? key;

    const formatFeatureValue = (key, value) => {
        if (key === 'max_branches') {
            return String(value);
        }
        if (typeof value === 'boolean') {
            return value ? t('yes', 'Sim') : t('no', 'Não');
        }

        return String(value);
    };

    const listFeatures = (featuresJson) => {
        const features = featuresJson ?? {};
        const known = new Set(PLAN_FEATURE_KEYS);

        const toRow = (key) => ({
            key,
            label: featureLabel(key),
            value: formatFeatureValue(key, features[key]),
        });

        const rows = PLAN_FEATURE_KEYS.filter((key) => key in features).map(toRow);
        const extra = Object.keys(features)
            .filter((key) => !known.has(key))
            .sort()
            .map(toRow);

        return [...rows, ...extra];
    };

    return { t, featureLabel, formatFeatureValue, listFeatures };
}
