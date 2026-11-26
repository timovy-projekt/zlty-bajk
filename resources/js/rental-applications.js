export default function rentalApplications(endpoint) {
    let requestNumber = 0;

    return {
        status: 'submitted',
        perPage: 10,
        applications: [],
        meta: null,
        loading: false,
        error: '',

        init() {
            this.load();
        },

        async load(page = 1) {
            const currentRequest = ++requestNumber;
            const url = new URL(endpoint);
            url.searchParams.set('per_page', this.perPage);
            url.searchParams.set('page', page);

            if (this.status) {
                url.searchParams.set('status', this.status);
            }

            this.loading = true;
            this.error = '';
            this.applications = [];
            this.meta = null;

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(response.status === 401 || response.status === 403
                        ? 'Nemáte oprávnenie zobraziť žiadosti alebo vaše prihlásenie vypršalo.'
                        : 'Žiadosti sa nepodarilo načítať. Skúste to znova.');
                }

                const result = await response.json();

                if (!Array.isArray(result.data) || !result.meta || !Number.isInteger(result.meta.total)
                    || !Number.isInteger(result.meta.current_page) || !Number.isInteger(result.meta.last_page)) {
                    throw new Error('Server vrátil neplatné údaje o žiadostiach.');
                }

                if (currentRequest === requestNumber) {
                    this.applications = result.data;
                    this.meta = result.meta;
                }
            } catch (error) {
                if (currentRequest === requestNumber) {
                    this.error = error instanceof Error && !(error instanceof TypeError) && !(error instanceof SyntaxError) && error.message
                        ? error.message
                        : 'Žiadosti sa nepodarilo načítať. Skúste to znova.';
                }
            } finally {
                if (currentRequest === requestNumber) {
                    this.loading = false;
                }
            }
        },

        statusLabel(status) {
            return {
                submitted: 'Nová',
                approved: 'Schválená',
                rejected: 'Zamietnutá',
                cancelled: 'Zrušená',
            }[status] ?? status;
        },

        formatDate(value) {
            if (!value) {
                return 'Neuvedené';
            }

            const date = new Date(value);

            return Number.isNaN(date.getTime())
                ? 'Neplatný dátum'
                : new Intl.DateTimeFormat('sk-SK', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
        },
    };
}
