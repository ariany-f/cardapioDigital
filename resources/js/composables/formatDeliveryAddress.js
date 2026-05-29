/**
 * @param {Record<string, unknown>|string|null|undefined} address
 * @returns {string|null}
 */
export function formatDeliveryAddress(address) {
    if (address == null || address === '') {
        return null;
    }

    if (typeof address === 'string') {
        const trimmed = address.trim();
        return trimmed !== '' ? trimmed : null;
    }

    if (typeof address !== 'object') {
        return null;
    }

    for (const key of ['formatted', 'full', 'label']) {
        const preset = String(address[key] ?? '').trim();
        if (preset) {
            return preset;
        }
    }

    const street = String(address.street ?? address.logradouro ?? '').trim();
    const number = String(address.number ?? address.numero ?? '').trim();
    const complement = String(address.complement ?? address.complemento ?? '').trim();
    const neighborhood = String(address.neighborhood ?? address.bairro ?? '').trim();
    const city = String(address.city ?? address.cidade ?? address.localidade ?? '').trim();
    const state = String(address.state ?? address.uf ?? '').trim();
    const postalCode = String(address.postal_code ?? address.cep ?? '').trim();

    const line1 = [street, number].filter(Boolean).join(', ');
    const cityState = [city, state].filter(Boolean).join(' / ');

    const parts = [
        line1 || null,
        complement || null,
        neighborhood || null,
        cityState || null,
        postalCode ? `CEP ${postalCode}` : null,
    ].filter(Boolean);

    return parts.length ? parts.join(' — ') : null;
}
