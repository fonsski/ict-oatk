<div class="text-center py-12">
    <div class="mx-auto w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
        </svg>
    </div>
    <h3 class="text-lg font-medium text-slate-900 mb-2">{{ $message ?? 'Ничего не найдено' }}</h3>
    <p class="text-slate-600 mb-6">Попробуйте изменить параметры поиска или использовать другие ключевые слова</p>
    <button onclick="clearSearch()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1,4 1,10 7,10"></polyline>
            <path d="M3.51,15a9,9,0,0,0,13.48,0A9,9,0,0,0,17,1.51"></path>
        </svg>
        Очистить поиск
    </button>
</div>

<script>
function clearSearch() {
    // Очистить все поля поиска и фильтры
    const searchInputs = document.querySelectorAll('input[type="text"], input[type="search"]');
    const selectInputs = document.querySelectorAll('select');

    searchInputs.forEach(input => {
        if (input.name === 'search' || input.name === 'q') {
            input.value = '';
        }
    });

    selectInputs.forEach(select => {
        if (select.name !== '_token') {
            select.selectedIndex = 0;
        }
    });

    // Перезагрузить страницу или выполнить новый поиск
    if (typeof performSearch === 'function') {
        performSearch();
    } else {
        window.location.href = window.location.pathname;
    }
}
</script>
