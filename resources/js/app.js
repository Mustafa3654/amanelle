import initAtmosphere from './atmosphere';

initAtmosphere();

// Livewire swaps DOM without a page load, so a navigated-to page would render
// an empty atmosphere container without this.
document.addEventListener('livewire:navigated', initAtmosphere);
