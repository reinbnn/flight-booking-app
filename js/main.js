// Main app functions
console.log('SKYJET app loaded');

// Store search params
function saveSearch(params) {
    sessionStorage.setItem('flightSearch', JSON.stringify(params));
}

// Get search params
function getSearch() {
    return JSON.parse(sessionStorage.getItem('flightSearch') || '{}');
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}
