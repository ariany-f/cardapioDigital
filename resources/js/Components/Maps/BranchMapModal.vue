<script setup>
import GoogleMapEmbed from '@/Components/Maps/GoogleMapEmbed.vue';
import { computed } from 'vue';

const props = defineProps({
    open: Boolean,
    branch: { type: Object, required: true },
    apiKey: { type: String, required: true },
});

const emit = defineEmits(['close']);

const hasCoords = computed(
    () => props.branch?.latitude != null && props.branch?.longitude != null,
);

const directionsUrl = computed(() => {
    if (!hasCoords.value) {
        return null;
    }
    const lat = props.branch.latitude;
    const lng = props.branch.longitude;
    return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[80] flex items-end justify-center bg-black/50 p-4 sm:items-center"
            @click.self="emit('close')"
        >
            <div class="max-h-[90dvh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-4 shadow-xl">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-stone-900">Localização</h2>
                        <p class="text-sm text-stone-500">{{ branch.name }}</p>
                        <p v-if="branch.full_address" class="mt-1 text-xs text-stone-400">{{ branch.full_address }}</p>
                    </div>
                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-100 text-stone-600"
                        aria-label="Fechar"
                        @click="emit('close')"
                    >
                        ✕
                    </button>
                </div>

                <GoogleMapEmbed
                    v-if="hasCoords"
                    :api-key="apiKey"
                    :center="{ lat: branch.latitude, lng: branch.longitude }"
                    :markers="[{ lat: branch.latitude, lng: branch.longitude, title: branch.name }]"
                    height="min(50vh, 320px)"
                />
                <p v-else class="rounded-xl bg-stone-50 px-3 py-4 text-sm text-stone-600">
                    Esta unidade ainda não tem coordenadas cadastradas no painel.
                </p>

                <a
                    v-if="directionsUrl"
                    :href="directionsUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="menu-btn-primary mt-4 block w-full text-center"
                >
                    Abrir rotas no Google Maps
                </a>
            </div>
        </div>
    </Teleport>
</template>
