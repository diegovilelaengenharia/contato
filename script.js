(function () {
    const yearSpan = document.getElementById('year');
    if (yearSpan) {
        yearSpan.textContent = new Date().getFullYear();
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const enableAnimations = () => document.body.classList.add('animations-ready');
    if (!reducedMotion.matches) {
        window.requestAnimationFrame(enableAnimations);
    }

    function handleMotionPreference(event) {
        if (!event.matches) {
            window.requestAnimationFrame(enableAnimations);
        } else {
            document.body.classList.remove('animations-ready');
        }
    }

    if (typeof reducedMotion.addEventListener === 'function') {
        reducedMotion.addEventListener('change', handleMotionPreference);
    } else if (typeof reducedMotion.addListener === 'function') {
        reducedMotion.addListener(handleMotionPreference);
    }


    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('service-worker.js').catch(() => {
                // Ignora falhas de registro do service worker.
            });
        });
    }
    // Dados dos Serviços
    const servicesData = {
        'Prefeitura': [
            "Alvará de Construção (Obra Nova)",
            "Regularização de Obra (Anistia/Lei de Uso do Solo)",
            "Habite-se (Certificado de Conclusão de Obra)",
            "Alvará de Reforma e Ampliação",
            "Alvará e Certidão de Demolição",
            "Certificado de Averbação",
            "Certidão de Decadência",
            "Certidão de Número / Nome de Rua",
            "Certidão de Localização",
            "Renovação de Alvará de Construção",
            "Substituição de Projeto",
            "Desmembramento e Remembramento (Topografia)",
            "Certidão de Unificação ou Divisão",
            "Certidão de Retificação de Área",
            "Processo de Usucapião (Fase Municipal)"
        ],
        'Receita Federal': [
            "CNO (Cadastro Nacional de Obras)",
            "SERO (Serviço Eletrônico para Aferição de Obras)",
            "CND de Obra (Certidão Negativa de Débitos)",
            "Regularização de CPF/CNPJ vinculado à obra",
            "Retificação de dados cadastrais de imóveis"
        ],
        'Cartório de Imóveis': [
            "Averbação de Construção na Matrícula",
            "Instituição de Condomínio",
            "Retificação de Área (Georreferenciada)",
            "Registro de Usucapião Extrajudicial",
            "Unificação e Desmembramento de Lotes",
            "Averbação de Demolição"
        ],
        'Projetos de Engenharia': [
            "Projeto Arquitetônico",
            "Projeto Estrutural",
            "Projetos Complementares",
            "Laudo Técnico",
            "Consultoria"
        ]
    };

    const myPhoneNumber = "5535984529577";
    const modal = document.getElementById('serviceModal');
    const modalTitle = document.getElementById('modalTitle');
    const serviceListContainer = document.getElementById('serviceListContainer');
    const whatsappSubmitBtn = document.getElementById('whatsappSubmit');

    let currentCategory = '';

    // Função global para abrir o modal
    window.openModal = function (category) {
        currentCategory = category;
        modalTitle.textContent = `${category}`;
        serviceListContainer.innerHTML = '';

        if (servicesData[category]) {
            servicesData[category].forEach((service, index) => {
                const label = document.createElement('label');
                label.className = 'service-option';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = service;
                checkbox.id = `service-${index}`;
                // Adicionar classe para facilitar seleção
                checkbox.classList.add('service-item-checkbox');

                const span = document.createElement('span');
                span.textContent = service;

                label.appendChild(checkbox);
                label.appendChild(span);
                serviceListContainer.appendChild(label);
            });
        }

        modal.classList.add('is-open');
        document.body.classList.add('modal-open');
        modal.removeAttribute('aria-hidden');
    };

    // Fechar modal
    function closeModal() {
        modal.classList.remove('is-open');
        document.body.classList.remove('modal-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    // Event Listeners para o Modal
    document.querySelectorAll('[data-micromodal-close]').forEach(btn => {
        btn.addEventListener('click', closeModal);
    });

    // Enviar para WhatsApp
    if (whatsappSubmitBtn) {
        whatsappSubmitBtn.addEventListener('click', () => {
            const selectedServices = Array.from(serviceListContainer.querySelectorAll('input[type="checkbox"]:checked'))
                .map(cb => cb.value);

            let message = '';
            if (selectedServices.length > 0) {
                message = `Oi, Diego! Gostaria de mais detalhes sobre os serviços de *${currentCategory}*:\n\n- ${selectedServices.join('\n- ')}`;
            } else {
                message = `Oi, Diego! Gostaria de mais detalhes sobre os serviços de *${currentCategory}*.`;
            }

            const url = `https://wa.me/${myPhoneNumber}?text=${encodeURIComponent(message)}`;
            window.open(url, '_blank');
            closeModal();
        });
    }

    // ============================================
    // COMPORTAMENTO DINÂMICO NO SCROLL & REVEAL
    // ============================================
    const smartNav = document.getElementById('smartNav');
    const floatingButtons = document.getElementById('floatingButtons');
    let lastScrollY = window.scrollY;
    let scrollTimeout;

    if (smartNav) {
        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;

            // 1. Efeito de fundo sólido na Navbar ao rolar
            if (currentScrollY > 20) {
                smartNav.classList.add('smart-nav--scrolled');
            } else {
                smartNav.classList.remove('smart-nav--scrolled');
            }

            // 2. Esconder/Mostrar no Scroll (Navbar e FABs)
            if (currentScrollY > lastScrollY && currentScrollY > 100) {
                // Rolando para baixo -> Esconde
                smartNav.classList.add('smart-nav--hidden');
                if (floatingButtons) {
                    floatingButtons.classList.add('floating-buttons--hidden');
                }
            } else {
                // Rolando para cima -> Mostra
                smartNav.classList.remove('smart-nav--hidden');
                if (floatingButtons) {
                    floatingButtons.classList.remove('floating-buttons--hidden');
                }
            }

            lastScrollY = currentScrollY;

            // 3. Reaparecer botões flutuantes ao parar de rolar
            if (floatingButtons) {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    floatingButtons.classList.remove('floating-buttons--hidden');
                }, 1200); // 1.2 segundos de inatividade
            }
        }, { passive: true });
    }

    // Scroll Reveal Observer
    const revealElements = document.querySelectorAll('.reveal, .footer-premium');
    if (revealElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.08 });

        revealElements.forEach(el => revealObserver.observe(el));
    }
}());
