const NOMINATIM_HEADERS = {
    Accept: 'application/json',
    'Accept-Language': 'pt-BR',
};

function onlyDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

export function formatCepInput(value) {
    const digits = onlyDigits(value).slice(0, 8);
    if (digits.length <= 5) {
        return digits;
    }

    return `${digits.slice(0, 5)}-${digits.slice(5)}`;
}

/** Formata CEP e recalcula posição do cursor após inserir o hífen. */
export function formatCepWithSelection(rawValue, selectionStart = String(rawValue ?? '').length) {
    const digits = onlyDigits(rawValue).slice(0, 8);
    const formatted = formatCepInput(digits);
    const digitsBefore = onlyDigits(String(rawValue).slice(0, selectionStart)).length;
    let cursor = digitsBefore;
    if (digitsBefore > 5) {
        cursor += 1;
    }
    cursor = Math.min(cursor, formatted.length);

    return { value: formatted, selectionStart: cursor, selectionEnd: cursor };
}

export function normalizeCep(value) {
    return onlyDigits(value).slice(0, 8);
}

export function isValidCep(value) {
    return normalizeCep(value).length === 8;
}

/**
 * @returns {Promise<{ ok: true, address: object } | { ok: false, error: string }>}
 */
export async function lookupViaCep(cep) {
    const digits = normalizeCep(cep);
    if (digits.length !== 8) {
        return { ok: false, error: 'Informe um CEP com 8 dígitos.' };
    }

    try {
        const res = await fetch(`https://viacep.com.br/ws/${digits}/json/`);
        if (!res.ok) {
            return { ok: false, error: 'Não foi possível consultar o CEP. Tente de novo.' };
        }

        const data = await res.json();
        if (data.erro) {
            return { ok: false, error: 'CEP não encontrado.' };
        }

        return {
            ok: true,
            address: {
                postal_code: formatCepInput(digits),
                street: data.logradouro || '',
                neighborhood: data.bairro || '',
                city: data.localidade || '',
                state: data.uf || '',
                complement: data.complemento || '',
            },
        };
    } catch {
        return { ok: false, error: 'Erro de conexão ao buscar o CEP.' };
    }
}

/**
 * @returns {Promise<{ ok: true, lat: number, lng: number } | { ok: false, error: string }>}
 */
export function getBrowserPosition() {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            resolve({ ok: false, error: 'Geolocalização não suportada neste navegador.' });
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) =>
                resolve({
                    ok: true,
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                }),
            () => resolve({ ok: false, error: 'Permita o acesso à localização ou preencha o endereço manualmente.' }),
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 },
        );
    });
}

/**
 * @returns {Promise<{ ok: true, address: object } | { ok: false, error: string }>}
 */
export async function reverseGeocode(lat, lng) {
    try {
        const url = new URL('https://nominatim.openstreetmap.org/reverse');
        url.searchParams.set('format', 'json');
        url.searchParams.set('lat', String(lat));
        url.searchParams.set('lon', String(lng));
        url.searchParams.set('addressdetails', '1');

        const res = await fetch(url, { headers: NOMINATIM_HEADERS });
        if (!res.ok) {
            return { ok: false, error: 'Não foi possível obter o endereço pela localização.' };
        }

        const data = await res.json();
        const a = data.address ?? {};

        const street = [a.road, a.pedestrian, a.footway, a.residential].filter(Boolean).join(' ') || '';

        return {
            ok: true,
            address: {
                postal_code: formatCepInput(a.postcode || ''),
                street,
                neighborhood: a.suburb || a.neighbourhood || a.quarter || '',
                city: a.city || a.town || a.village || a.municipality || '',
                state: (a['ISO3166-2-lvl4'] || '').replace(/^BR-/, '') || a.state || '',
                complement: '',
            },
        };
    } catch {
        return { ok: false, error: 'Erro ao buscar endereço pela localização.' };
    }
}

/**
 * Tenta obter coordenadas a partir do endereço preenchido (raio de entrega).
 */
export async function forwardGeocode(address) {
    const parts = [
        address.street,
        address.number,
        address.neighborhood,
        address.city,
        address.state,
        normalizeCep(address.postal_code),
        'Brasil',
    ].filter(Boolean);

    if (parts.length < 2) {
        return { ok: false, error: null };
    }

    try {
        const url = new URL('https://nominatim.openstreetmap.org/search');
        url.searchParams.set('format', 'json');
        url.searchParams.set('q', parts.join(', '));
        url.searchParams.set('limit', '1');
        url.searchParams.set('countrycodes', 'br');

        const res = await fetch(url, { headers: NOMINATIM_HEADERS });
        if (!res.ok) {
            return { ok: false, error: null };
        }

        const data = await res.json();
        if (!data?.length) {
            return { ok: false, error: null };
        }

        return {
            ok: true,
            lat: parseFloat(data[0].lat),
            lng: parseFloat(data[0].lon),
        };
    } catch {
        return { ok: false, error: null };
    }
}

/**
 * ViaCEP primeiro; se falhar, usa GPS + reverse geocode.
 */
export async function resolveDeliveryAddress(cep) {
    const viaCep = await lookupViaCep(cep);
    if (viaCep.ok) {
        return { ...viaCep, source: 'viacep' };
    }

    const position = await getBrowserPosition();
    if (!position.ok) {
        return { ok: false, error: viaCep.error, fallbackError: position.error };
    }

    const reversed = await reverseGeocode(position.lat, position.lng);
    if (!reversed.ok) {
        return {
            ok: false,
            error: viaCep.error,
            fallbackError: reversed.error,
            lat: position.lat,
            lng: position.lng,
        };
    }

    return {
        ok: true,
        source: 'geolocation',
        address: reversed.address,
        lat: position.lat,
        lng: position.lng,
    };
}
