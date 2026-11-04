<button type="button"
        aria-label="Zmeniť tému"
        onclick="var d = document.documentElement.dataset.theme !== 'dark';
                 document.documentElement.dataset.theme = d ? 'dark' : 'light';
                 try { localStorage.setItem('theme', d ? 'dark' : 'light'); } catch (e) {}"
        {{ $attributes->merge(['class' => 'w-9 h-9 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100']) }}>
    ◐
</button>