<div class="text-center py-12">
    <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
    </div>
    <h3 class="text-lg font-medium text-slate-900 mb-2">Ошибка поиска</h3>
    <p class="text-slate-600 mb-6">Произошла ошибка при выполнении поиска. Попробуйте еще раз через несколько секунд.</p>
    <div class="flex justify-center gap-4">
        <button onclick="retrySearch()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23,4 23,10 17,10"></polyline>
                <path d="M20.49,15a9,9,0,1,1-2.12-9.36L23,10"></path>
            </svg>
            Попробовать снова
        </button>
        <button onclick="reloadPage()" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1,4 1,10 7,10"></polyline>
                <path d="M3.51,15a9,9,0,0,0,13.48,0A9,9,0,0,0,17,1.51"></path>
            </svg>
            Перезагрузить страницу
        </button>
    </div>
</div>

<script>
function retrySearch() {
    // Повторить последний поиск
    if (typeof performSearch === 'function') {
        performSearch();
    } else {
        location.reload();
    }
}

function reloadPage() {
    // Перезагрузить страницу
    location.reload();
}
</script>
