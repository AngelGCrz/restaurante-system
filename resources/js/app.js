// Alpine component factories are loaded via public/js/alpine-components.js
// (non-module script that executes before Livewire/Alpine start)

// Re-init Alpine on Livewire SPA navigations (wire:navigate)
document.addEventListener('livewire:navigated', () => {
    Alpine.initTree(document.body)
})
