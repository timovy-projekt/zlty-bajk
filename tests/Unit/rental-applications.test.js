import assert from 'node:assert/strict';
import test from 'node:test';
import rentalApplications from '../../resources/js/rental-applications.js';

const endpoint = 'https://example.test/api/v1/admin/rental-applications';
const response = (data, meta) => ({
    ok: true,
    json: async () => ({ data, meta }),
});

test('loads new applications and supports filtering and pagination', async (t) => {
    const calls = [];
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        calls.push({ url, options });
        return response([{ id: calls.length }], { total: 25, current_page: calls.length, last_page: 3 });
    });

    const list = rentalApplications(endpoint);
    await list.load();
    assert.equal(calls[0].url.searchParams.get('status'), 'submitted');
    assert.equal(calls[0].url.searchParams.get('per_page'), '10');
    assert.equal(calls[0].options.credentials, 'same-origin');
    assert.equal(list.meta.total, 25);

    list.status = '';
    list.perPage = 20;
    await list.load(2);
    assert.equal(calls[1].url.searchParams.has('status'), false);
    assert.equal(calls[1].url.searchParams.get('page'), '2');
    assert.equal(calls[1].url.searchParams.get('per_page'), '20');
    assert.deepEqual(list.applications, [{ id: 2 }]);
});

test('shows access and malformed-response errors rather than an empty success state', async (t) => {
    const fetchMock = t.mock.method(globalThis, 'fetch', async () => ({ ok: false, status: 403 }));
    const list = rentalApplications(endpoint);

    await list.load();
    assert.match(list.error, /oprávnenie/);
    assert.equal(list.meta, null);
    assert.equal(list.loading, false);

    fetchMock.mock.mockImplementation(async () => response([], {}));
    await list.load();
    assert.match(list.error, /neplatné údaje/);
    assert.equal(list.meta, null);
});

test('ignores a previous request that finishes after the new filter', async (t) => {
    let finishFirst;
    const first = new Promise((resolve) => { finishFirst = resolve; });
    const fetchMock = t.mock.method(globalThis, 'fetch', () => first);
    const list = rentalApplications(endpoint);
    const oldRequest = list.load();

    fetchMock.mock.mockImplementation(async () => response([{ id: 2 }], { total: 1, current_page: 1, last_page: 1 }));
    list.status = 'approved';
    await list.load();
    finishFirst(response([{ id: 1 }], { total: 1, current_page: 1, last_page: 1 }));
    await oldRequest;

    assert.deepEqual(list.applications, [{ id: 2 }]);
    assert.equal(list.status, 'approved');
    assert.equal(list.loading, false);
});
