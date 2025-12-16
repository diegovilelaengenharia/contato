import { data } from './data.js';

// Icons SVG Map for reuse
const icons = {
    whatsapp: '<path d="M12 2a10 10 0 0 0-8.66 15.14L2 22l5-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.08-1.13l-.29-.18-3 .79.8-2.91-.19-.3A8 8 0 1 1 12 20zm4.37-5.73-.52-.26a1.32 1.32 0 0 0-1.15.04l-.4.21a.5.5 0 0 1-.49 0 8.14 8.14 0 0 1-2.95-2.58.5.5 0 0 1 0-.49l.21-.4a1.32 1.32 0 0 0 .04-1.15l-.26-.52a1.32 1.32 0 0 0-1.18-.73h-.37a1 1 0 0 0-1 .86 3.47 3.47 0 0 0 .18 1.52A10.2 10.2 0 0 0 13 15.58a3.47 3.47 0 0 0 1.52.18 1 1 0 0 0 .86-1v-.37a1.32 1.32 0 0 0-.73-1.18z"></path>',
    instagram: '<path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm5 3.5A3.5 3.5 0 1 1 8.5 12 3.5 3.5 0 0 1 12 8.5zm0 5A1.5 1.5 0 1 0 10.5 12 1.5 1.5 0 0 0 12 13.5zm4.25-6.75a1 1 0 1 1-1-1 1 1 0 0 1 1 1z"></path>',
    email: '<path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5z"></path>',
    phone: '<path d="M6.62 10.79a15.91 15.91 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1-.24 12.36 12.36 0 0 0 3.88.62 1 1 0 0 1 1 1v3.57a1 1 0 0 1-1 1A17 17 0 0 1 3 5a1 1 0 0 1 1-1h3.55a1 1 0 0 1 1 1 12.36 12.36 0 0 0 .62 3.88 1 1 0 0 1-.24 1z"></path>',
    doc: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"></path>',
    plan: '<path d="M20.71 5.63l-2.34-2.34a2 2 0 0 0-2.83 0l-12 12a1 1 0 0 0 0 1.41l2.34 2.34a1 1 0 0 0 1.41 0l12-12a1 1 0 0 0 0-1.41zM7.5 17.5L6.5 16.5l5-5 1 1-5 5zm9.9-9.9l-2 2-1-1 2-2 1 1z"></path>',
    bank: '<path d="M4 10v7h3v-7H4zm6 0v7h3v-7h-3zm6 0v7h3v-7h-3zM2 22h19v-3H2v3zm19-19H2v6h19V3z"></path>',
    helmet: '<path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7L12 2zm0 4c1.1 0 2 .9 2 2h2c0-2.21-1.79-4-4-4s-4 1.79-4 4h2c0-1.1.9-2 2-2z"></path>',
    check: '<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path>',
    chevRight: '<path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path>'
};

// --- Renderers ---

function renderLinks() {
    const container = document.getElementById('links-grid');
    if (!container) return;

    container.innerHTML = data.links.map(link => `
        <li>
            <a class="link-card link-card--${link.icon}" href="${link.url}" target="_blank" rel="noopener">
                <span class="icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">${icons[link.icon]}</svg>
                </span>
                <span class="text">
                    <span class="title">${link.title}</span>
                    <span class="subtitle">${link.subtitle}</span>
                </span>
            </a>
        </li>
    `).join('');
}

function renderServices() {
    const container = document.getElementById('services-grid');
    if (!container) return;

    container.innerHTML = data.services.map(service => `
        <button class="link-card link-card--service" onclick="openServiceModal('${service.id}')">
            <span class="icon">
                <svg viewBox="0 0 24 24">${icons[service.icon]}</svg>
            </span>
            <span class="text">
                <span class="title">${service.title}</span>
            </span>
            <span class="service-arrow" style="margin-left: auto; color: var(--color-text-subtle); display: flex; align-items: center;">
                 <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;">${icons.chevRight}</svg>
            </span>
        </button>
    `).join('');
}

function renderTips() {
    const container = document.getElementById('tips-grid');
    if (!container) return;

    container.innerHTML = data.tips.map(tip => `
        <article class="tip-card">
            <div class="tip-header">
                <span class="tip-badge">Dica</span>
                <h3>${tip.title}</h3>
            </div>
            <p>${tip.content}</p>
        </article>
    `).join('');
}

// --- Modal Logic ---

window.openServiceModal = (serviceId) => {
    const service = data.services.find(s => s.id === serviceId);
    if (!service) return;

    const modal = document.getElementById('service-modal');
    const modalContent = document.getElementById('modal-content-inject');

    // Populate content
    modalContent.innerHTML = `
        <div class="modal-service-header">
            <div class="modal-icon-wrapper">
                <svg viewBox="0 0 24 24">${icons[service.icon]}</svg>
            </div>
            <h2>${service.title}</h2>
        </div>
        
        <div class="modal-body">
            <p class="modal-intro">${service.shortDescription}</p>
            
            <ul class="feature-list">
                ${service.details.map(detail => `
                    <li>
                        <svg viewBox="0 0 24 24" class="check-icon">${icons.check}</svg>
                        ${detail}
                    </li>
                `).join('')}
            </ul>

            <a href="https://wa.me/${data.profile.whatsapp}?text=${encodeURIComponent(service.ctaMessage)}" 
               target="_blank" 
               class="modal-cta-button">
               Solicitar Orçamento no WhatsApp
            </a>
        </div>
    `;

    // Show modal
    modal.hidden = false;
    // Small delay to allow display:block to apply before adding opacity class for transition
    requestAnimationFrame(() => {
        modal.classList.add('is-open');
        document.body.classList.add('modal-open');
    });
};

window.closeModal = () => {
    const modal = document.getElementById('service-modal');
    modal.classList.remove('is-open');
    document.body.classList.remove('modal-open');

    // Wait for transition to finish before hiding
    setTimeout(() => {
        modal.hidden = true;
    }, 300);
};

// Close on click outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('service-modal');
    if (e.target === modal) {
        window.closeModal();
    }
});

// Close on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        window.closeModal();
    }
});


// --- Init ---
document.addEventListener('DOMContentLoaded', () => {
    renderLinks();
    renderServices();
    renderTips();

    // Register Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('./service-worker.js')
            .then(registration => {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            })
            .catch(err => {
                console.log('ServiceWorker registration failed: ', err);
            });
    }

    // Update year
    document.getElementById('year').textContent = new Date().getFullYear();
});
