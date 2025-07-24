// Funkcija koja se poziva kada korisnik kuca naziv grupe da bi se ažurirao naslov harmonike
function updateAccordionHeader(inputElement) {
    const headerButton = inputElement.closest('.accordion-body').parentElement.previousElementSibling.querySelector('.accordion-button');
    if (headerButton) {
        headerButton.textContent = inputElement.value.trim() ? inputElement.value : 'Nova Grupa';
    }
}

$(document).ready(function() {
    /*****************************************************************/
    /* DEO 1: OPŠTE SKRIPTE (Modali i automatsko zatvaranje poruka) */
    /*****************************************************************/
    const imageModalEl = document.getElementById('imageModal');
    if (imageModalEl) {
        const imageModal = new bootstrap.Modal(imageModalEl);
        const modalImage = document.getElementById('modalImage');
        $(document.body).on('click', '.view-image-link', function(event) {
            event.preventDefault();
            const imageUrl = $(this).data('image-url');
            if (modalImage && imageUrl) {
                modalImage.src = imageUrl;
                imageModal.show();
            }
        });
    }

    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    if (confirmDeleteModal) {
        const deleteButton = confirmDeleteModal.querySelector('#confirmDeleteButton');
        $(document.body).on('click', '[data-bs-toggle="modal"][data-bs-target="#confirmDeleteModal"]', function (e) {
            const trigger = e.currentTarget;
            if (trigger.hasAttribute('data-delete-url')) {
                const url = trigger.getAttribute('data-delete-url');
                if (deleteButton) {
                    deleteButton.setAttribute('href', url);
                }
            }
        });
    }

    // IZMENA: Selektor sada obuhvata i .alert unutar .login-form
    // kao i sve .alert-dismissible poruke na drugim stranicama.
    $('.alert-dismissible, .login-form .alert').each(function() {
        const alertElement = $(this);
        setTimeout(function() {
            // Proveravamo da li Bootstrap Alert postoji pre nego što ga zatvorimo
            if (bootstrap && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
                if (bsAlert) {
                    bsAlert.close();
                }
            } else {
                 // Ako Bootstrap nije dostupan, samo sakrivamo element
                alertElement.fadeOut();
            }
        }, 3000); // 3 sekunde
    });


    /**********************************************************************************/
    /* DEO 2: LOGIKA ZA FORMU PLANA KONTROLE (centralizovano sa plan_kontrole_form.js)*/
    /**********************************************************************************/
    if ($('#grupeAccordion').length) {
        const grupeKontejner = document.getElementById('grupeAccordion');
        const placeholder = document.getElementById('prazna-lista-grupa');
        const domRemoveModalEl = document.getElementById('confirmDomRemoveModal');
        let domRemoveModal = domRemoveModalEl ? new bootstrap.Modal(domRemoveModalEl) : null;
        const domRemoveConfirmBtn = document.getElementById('confirmDomRemoveButton');
        const domRemoveModalBody = document.getElementById('confirmDomRemoveModalBody');
        let elementToRemove = null;

        function updateGroupOrder() {
            grupeKontejner.querySelectorAll('.grupa-kartica').forEach((item, index) => {
                const redosledInput = item.querySelector('.redosled-grupe');
                if (redosledInput) {
                    redosledInput.value = index;
                }
            });
        }

        if (domRemoveConfirmBtn) {
            domRemoveConfirmBtn.addEventListener('click', function() {
                if (elementToRemove) {
                    const isGrupa = elementToRemove.classList.contains('grupa-kartica');
                    const kontejnerKarakteristika = elementToRemove.parentElement;
                    elementToRemove.remove();
                    if (isGrupa) {
                        updateGroupOrder();
                    } else if (kontejnerKarakteristika && kontejnerKarakteristika.classList.contains('karakteristike-kontejner')) {
                        kontejnerKarakteristika.querySelectorAll('.redni-broj-karakteristike').forEach((input, index) => {
                            input.value = index + 1;
                        });
                    }
                    checkPlaceholder();
                    elementToRemove = null;
                    domRemoveModal.hide();
                }
            });
        }

        function checkPlaceholder() {
            if (placeholder) {
                placeholder.style.display = grupeKontejner.querySelectorAll('.accordion-item').length === 0 ? 'block' : 'none';
            }
        }
        checkPlaceholder();

        const grupaTemplate = document.getElementById('grupa-template');
        const karakteristikaTemplate = document.getElementById('karakteristika-template');
        let grupaIndexCounter = grupeKontejner.querySelectorAll('.accordion-item').length;

        $('#dodaj-grupu').on('click', function () {
            if (placeholder) placeholder.style.display = 'none';
            const noviIndex = grupaIndexCounter++;
            const novaGrupaNode = grupaTemplate.content.cloneNode(true);
            const accordionItem = novaGrupaNode.querySelector('.accordion-item');
            const header = accordionItem.querySelector('.accordion-header');
            const button = header.querySelector('button');
            const collapseDiv = accordionItem.querySelector('.accordion-collapse');
            header.id = `heading-${noviIndex}`;
            button.setAttribute('data-bs-target', `#collapse-${noviIndex}`);
            button.setAttribute('aria-controls', `collapse-${noviIndex}`);
            collapseDiv.id = `collapse-${noviIndex}`;
            collapseDiv.setAttribute('aria-labelledby', `heading-${noviIndex}`);
            novaGrupaNode.querySelectorAll('[data-name]').forEach(input => {
                const nameTemplate = input.dataset.name.replace(/__G_INDEX__/g, noviIndex);
                input.name = nameTemplate;
                if(input.classList.contains('redosled-grupe')){
                    input.value = noviIndex;
                }
            });
            const dodajKarakteristikuBtn = novaGrupaNode.querySelector('.dodaj-karakteristiku');
            if (dodajKarakteristikuBtn) {
                dodajKarakteristikuBtn.dataset.grupaIndex = noviIndex;
            }
            grupeKontejner.appendChild(accordionItem);
            updateGroupOrder();
        });

        $(grupeKontejner).on('click', function(e) {
            if (e.target && e.target.classList.contains('dodaj-karakteristiku')) {
                const karakteristikeKontejner = e.target.closest('.accordion-body').querySelector('.karakteristike-kontejner');
                const trenutnaGrupaIndex = e.target.dataset.grupaIndex;
                let karakteristikaIndex = 0;
                while (karakteristikeKontejner.querySelector(`[name*="[karakteristike][${karakteristikaIndex}]"]`)) {
                    karakteristikaIndex++;
                }
                const novaKarakteristikaNode = karakteristikaTemplate.content.cloneNode(true);
                novaKarakteristikaNode.querySelectorAll('[data-name]').forEach(input => {
                    const nameTemplate = input.dataset.name.replace('__K_INDEX__', karakteristikaIndex);
                    input.name = `grupe[${trenutnaGrupaIndex}]` + nameTemplate;
                });
                novaKarakteristikaNode.querySelector('.redni-broj-karakteristike').value = karakteristikeKontejner.querySelectorAll('.karakteristika-red').length + 1;
                karakteristikeKontejner.appendChild(novaKarakteristikaNode);
            }
            if (e.target && e.target.classList.contains('ukloni-grupu')) {
                e.preventDefault();
                elementToRemove = e.target.closest('.accordion-item');
                if (domRemoveModalBody) domRemoveModalBody.textContent = 'Da li ste sigurni da želite da obrišete celu grupu sa svim njenim karakteristikama?';
                if (domRemoveModal) domRemoveModal.show();
            }
            if (e.target && e.target.classList.contains('ukloni-karakteristiku')) {
                e.preventDefault();
                elementToRemove = e.target.closest('.karakteristika-red');
                if (domRemoveModalBody) domRemoveModalBody.textContent = 'Da li ste sigurni da želite da obrišete ovu karakteristiku?';
                if (domRemoveModal) domRemoveModal.show();
            }
        });

        new Sortable(grupeKontejner, {
            animation: 150,
            handle: '.accordion-header',
            onEnd: function () {
                updateGroupOrder();
            }
        });
    }

    /**************************************************************************/
    /* DEO 3: UNIVERZALNA PROVERA ZA NESAČUVANE IZMENE NA BILO KOJOJ FORMI   */
    /**************************************************************************/
    $('.form-with-unsaved-check').each(function() {
        const formElement = $(this);
        let initialFormData = formElement.serialize();

        formElement.on('submit', function() {
            $(window).off('beforeunload');
        });

        // Tražimo dugme za odustajanje unutar forme koje ima klasu .cancel-link
        $('.cancel-link', formElement).on('click', function(event) {
            let currentFormData = formElement.serialize();
            if (initialFormData !== currentFormData) {
                if (!confirm('Imate nesačuvane izmene. Da li ste sigurni da želite da odustanete?')) {
                    event.preventDefault();
                }
            }
        });

        $(window).on('beforeunload', function(e) {
            let currentFormData = formElement.serialize();
            if (initialFormData !== currentFormData) {
                const confirmationMessage = 'Imate nesačuvane izmene. Da li ste sigurni da želite da napustite stranicu?';
                (e || window.event).returnValue = confirmationMessage;
                return confirmationMessage;
            }
        });
    });
    
    /* ================================================== */
    /* === NOVI JAVASCRIPT KOD ZA ANIMACIJU POZADINE === */
    /* ================================================== */

    // Pokreni animaciju samo ako postoji canvas na stranici (tj. na login stranici)
    const canvas = document.getElementById('particle-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let particlesArray;

        // Prilagodljive vrednosti
        const config = {
            particleColor: 'rgba(172, 22, 27, 0.7)',  /* Ažurirana boja čestica */
            lineColor: 'rgba(172, 22, 27, 0.15)', /* Ažurirana boja linija */
            particleAmount: 70, // Malo više čestica za svetlu pozadinu
            defaultSpeed: 0.5,
            variantSpeed: 0.5,
            defaultRadius: 2,
            variantRadius: 2,
            linkRadius: 180,
        };

        // Postavljanje dimenzija canvasa
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        // Klasa za čestice
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.radius = config.defaultRadius + Math.random() * config.variantRadius;
                this.speedX = (Math.random() * 2 - 1) * config.defaultSpeed;
                this.speedY = (Math.random() * 2 - 1) * config.defaultSpeed;
            }

            // Crtanje čestice
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = config.particleColor;
                ctx.fill();
            }

            // Ažuriranje pozicije čestice
            update() {
                this.x += this.speedX;
                this.y += this.speedY;

                if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
            }
        }

        // Inicijalizacija
        function init() {
            particlesArray = [];
            for (let i = 0; i < config.particleAmount; i++) {
                particlesArray.push(new Particle());
            }
        }

        // Crtanje linija između bliskih čestica
        function connectParticles() {
            for (let i = 0; i < particlesArray.length; i++) {
                for (let j = i; j < particlesArray.length; j++) {
                    const dx = particlesArray[i].x - particlesArray[j].x;
                    const dy = particlesArray[i].y - particlesArray[j].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < config.linkRadius) {
                        ctx.beginPath();
                        ctx.strokeStyle = config.lineColor;
                        ctx.lineWidth = 0.5;
                        ctx.moveTo(particlesArray[i].x, particlesArray[i].y);
                        ctx.lineTo(particlesArray[j].x, particlesArray[j].y);
                        ctx.stroke();
                    }
                }
            }
        }

        // Glavna petlja animacije
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (const particle of particlesArray) {
                particle.update();
                particle.draw();
            }
            connectParticles();
            requestAnimationFrame(animate);
        }

        // Event listener za promenu veličine prozora
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            init();
        });

        init();
        animate();
    }
});
