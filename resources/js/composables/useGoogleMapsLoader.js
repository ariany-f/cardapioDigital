let loadPromise = null;

/**
 * Carrega Maps JavaScript API uma vez (uso em mapas interativos).
 * @param {string} apiKey
 */
export function loadGoogleMaps(apiKey) {
    if (!apiKey) {
        return Promise.reject(new Error('Google Maps API key missing'));
    }

    if (window.google?.maps) {
        return Promise.resolve(window.google.maps);
    }

    if (loadPromise) {
        return loadPromise;
    }

    loadPromise = new Promise((resolve, reject) => {
        const id = 'google-maps-js';
        if (document.getElementById(id)) {
            const check = setInterval(() => {
                if (window.google?.maps) {
                    clearInterval(check);
                    resolve(window.google.maps);
                }
            }, 50);
            setTimeout(() => {
                clearInterval(check);
                reject(new Error('Google Maps load timeout'));
            }, 15000);
            return;
        }

        window.__googleMapsInit = () => {
            if (window.google?.maps) {
                resolve(window.google.maps);
            } else {
                reject(new Error('Google Maps failed to initialize'));
            }
        };

        const script = document.createElement('script');
        script.id = id;
        script.async = true;
        script.defer = true;
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&callback=__googleMapsInit&language=pt-BR&region=BR`;
        script.onerror = () => reject(new Error('Failed to load Google Maps script'));
        document.head.appendChild(script);
    });

    return loadPromise;
}
