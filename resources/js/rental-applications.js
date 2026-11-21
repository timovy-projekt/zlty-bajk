/**
 * Zoznam žiadostí o prenájom načítaný z JSON API.
 *
 * Používa sa pre zákazníka (/api/v1/rental-applications)
 * aj pre admina (/api/v1/admin/rental-applications).
 *
 * Polia `rental` (bicykel, termín, úhrada) API zatiaľ neposiela.
 * UI pre ne zobrazuje „—“ a začne ich ukazovať automaticky,
 * keď ich backend doplní v tvare:
 *
 * rental: {
 *     starts_at, ends_at, status,
 *     bike: { inventory_number, model },
 *     payment: { amount, currency, due_date, variable_symbol, paid },
 * }
 */
export default function rentalApplications(endpoint) {
    return {
        endpoint,
        applications: [],
        meta: null,
        status: '',
        perPage: 20,
        loading: false,
        error: null,

        init() {
            this.load(1);
        },

        async load(page = 1) {
            this.loading = true;
            this.error = null;

            const params = new URLSearchParams({ page: String(page), per_page: String(this.perPage) });

            if (this.status !== '') {
                params.set('status', this.status);
            }

            try {
                const response = await fetch(`${this.endpoint}?${params.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(
                        response.status === 401 || response.status === 403
                            ? 'Na zobrazenie žiadostí nemáte oprávnenie.'
                            : 'Žiadosti sa nepodarilo načítať.',
                    );
                }

                const payload = await response.json();

                this.applications = payload.data ?? [];
                this.meta = payload.meta ?? null;
            } catch (exception) {
                this.applications = [];
                this.meta = null;
                this.error = exception instanceof Error && exception.message
                    ? exception.message
                    : 'Žiadosti sa nepodarilo načítať.';
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.status = '';
            this.perPage = 20;
            this.load(1);
        },

        get currentPage() {
            return this.meta?.current_page ?? 1;
        },

        get lastPage() {
            return this.meta?.last_page ?? 1;
        },

        get total() {
            return this.meta?.total ?? 0;
        },

        formatDate(value, withTime = false) {
            if (!value) {
                return '—';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return '—';
            }

            const pad = (number) => String(number).padStart(2, '0');
            const formatted = `${pad(date.getDate())}.${pad(date.getMonth() + 1)}.${date.getFullYear()}`;

            return withTime ? `${formatted} ${pad(date.getHours())}:${pad(date.getMinutes())}` : formatted;
        },

        bikeLabel(application) {
            const bike = application.rental?.bike;

            if (!bike) {
                return '—';
            }

            return bike.model ? `${bike.inventory_number} (${bike.model})` : bike.inventory_number;
        },

        termLabel(application) {
            const rental = application.rental;

            if (!rental?.starts_at) {
                return '—';
            }

            return `${this.formatDate(rental.starts_at)} – ${this.formatDate(rental.ends_at)}`;
        },

        paymentLabel(application) {
            const payment = application.rental?.payment;

            if (!payment) {
                return '—';
            }

            const amount = payment.amount ? `${payment.amount} ${payment.currency ?? 'EUR'}` : '';
            const state = payment.paid ? 'Uhradené' : 'Neuhradené';

            return amount ? `${amount} · ${state}` : state;
        },

        customerLabel(application) {
            const customer = application.customer;

            if (!customer) {
                return '—';
            }

            return `${customer.first_name} ${customer.last_name}`.trim();
        },
    };
}
