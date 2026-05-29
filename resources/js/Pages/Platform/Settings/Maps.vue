<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    settings: Object,
});

const form = useForm({
    enabled: props.settings?.enabled ?? false,
    api_key: '',
});

const submit = () => form.put(route('platform.settings.maps.update'), { preserveScroll: true });
</script>

<template>
    <Head title="Google Maps" />

    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Google Maps</h1>
    <p class="mt-1 max-w-2xl text-sm text-slate-600">
        Chave única da plataforma para mapas no cardápio público e geocodificação de endereços (entrega por km).
        No Google Cloud, restrinja a chave por HTTP referrer do seu domínio.
    </p>

    <form class="platform-card mt-6 max-w-2xl space-y-4" @submit.prevent="submit">
        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-800">
            <input v-model="form.enabled" type="checkbox" class="rounded border-slate-300" />
            Ativar Google Maps na plataforma
        </label>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Chave da API (Maps JavaScript + Geocoding)</label>
            <input
                v-model="form.api_key"
                type="password"
                autocomplete="off"
                class="admin-input font-mono text-sm"
                :placeholder="settings.api_key_configured ? 'Deixe em branco para manter a chave atual' : 'AIza...'"
            />
            <p v-if="settings.api_key_configured" class="mt-1 text-xs text-emerald-700">Chave já configurada.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            <p class="font-semibold text-slate-800">APIs recomendadas (cobrança por uso, baixo volume típico)</p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
                <li><strong>Maps JavaScript API</strong> — mapa no cardápio e no painel do restaurante</li>
                <li><strong>Geocoding API</strong> — converter endereço ↔ coordenadas (taxa por km, raio de entrega)</li>
            </ul>
            <p class="mt-3 text-xs text-slate-500">
                Não é necessário Distance Matrix nem Places (autocomplete pago). A distância em km usa cálculo em linha reta no servidor.
                ViaCEP continua gratuito para CEP; sem chave, o sistema usa OpenStreetMap (Nominatim) como reserva.
            </p>
        </div>

        <button type="submit" class="platform-btn-primary" :disabled="form.processing">Salvar</button>
    </form>
</template>
