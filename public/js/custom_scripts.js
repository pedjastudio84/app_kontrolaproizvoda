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
        $(document.body).on('click', '[data-bs-toggle="modal"][data-bs-target="#confirmDeleteModal"]', function(e) {
            const trigger = e.currentTarget;
            if (trigger.hasAttribute('data-delete-url')) {
                const url = trigger.getAttribute('data-delete-url');
                if (deleteButton) {
                    deleteButton.setAttribute('href', url);
                }
            }
        });
    }

    $('.alert-dismissible, .login-form .alert').each(function() {
        const alertElement = $(this);
        setTimeout(function() {
            if (bootstrap && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
                if (bsAlert) {
                    bsAlert.close();
                }
            } else {
                alertElement.fadeOut();
            }
        }, 3000);
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
        
        // === NOVA, UNIVERZALNA FUNKCIJA ZA AŽURIRANJE REDOSLEDA I BROJEVA KARAKTERISTIKA ===
        function updateCharacteristicOrderAndNumbers(container) {
            const characteristicRows = container.querySelectorAll('.karakteristika-red');
            characteristicRows.forEach((row, index) => {
                // Ažuriranje skrivenog polja za poziciju (0, 1, 2...)
                const pozicijaInput = row.querySelector('.redosled-karakteristike');
                if (pozicijaInput) {
                    pozicijaInput.value = index;
                }
                // Ažuriranje vidljivog rednog broja (1, 2, 3...)
                const numberInput = row.querySelector('.redni-broj-karakteristike');
                if (numberInput) {
                    numberInput.value = index + 1;
                }
            });
        }
        // === KRAJ NOVE FUNKCIJE ===


        if (domRemoveConfirmBtn) {
            domRemoveConfirmBtn.addEventListener('click', function() {
                if (elementToRemove) {
                    const isGrupa = elementToRemove.classList.contains('grupa-kartica');
                    const kontejnerKarakteristika = elementToRemove.parentElement;
                    
                    elementToRemove.remove();
                    
                    if (isGrupa) {
                        updateGroupOrder();
                    } else if (kontejnerKarakteristika && kontejnerKarakteristika.classList.contains('karakteristike-kontejner')) {
                        // Pozivamo novu, univerzalnu funkciju nakon brisanja
                        updateCharacteristicOrderAndNumbers(kontejnerKarakteristika);
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

        const karakteristikaTemplate = document.getElementById('karakteristika-template');
        let grupaIndexCounter = grupeKontejner.querySelectorAll('.accordion-item').length;

        $('#dodaj-grupu').on('click', function() {
            if (placeholder) placeholder.style.display = 'none';
            const noviIndex = grupaIndexCounter++;
            
            // Koristimo jQuery za kloniranje templejta
            const novaGrupaNode = $('#grupa-template').contents().clone(true);

            // Ažuriramo atribute
            novaGrupaNode.find('.accordion-header').attr('id', `heading-${noviIndex}`);
            const button = novaGrupaNode.find('.accordion-button');
            button.attr('data-bs-target', `#collapse-${noviIndex}`);
            button.attr('aria-controls', `collapse-${noviIndex}`);
            const collapseDiv = novaGrupaNode.find('.accordion-collapse');
            collapseDiv.attr('id', `collapse-${noviIndex}`);
            collapseDiv.attr('aria-labelledby', `heading-${noviIndex}`);
            
            novaGrupaNode.find('[data-name]').each(function() {
                const nameTemplate = $(this).data('name').replace(/__G_INDEX__/g, noviIndex);
                $(this).attr('name', nameTemplate);
                if ($(this).hasClass('redosled-grupe')) {
                    $(this).val(noviIndex);
                }
            });

            novaGrupaNode.find('.dodaj-karakteristiku').data('grupa-index', noviIndex);
            
            // Dodajemo novu grupu koristeći jQuery
            $('#grupeAccordion').append(novaGrupaNode);
            updateGroupOrder();
        });

        $('#grupeAccordion').on('click', '.dodaj-karakteristiku, .ukloni-grupu, .ukloni-karakteristiku', function(e) {
            e.preventDefault();
            const clickedButton = $(this);

            if (clickedButton.hasClass('dodaj-karakteristiku')) {
                const karakteristikeKontejner = clickedButton.closest('.accordion-body').find('.karakteristike-kontejner');
                const trenutnaGrupaIndex = clickedButton.data('grupa-index');
                
                // Kloniramo templejt koristeći jQuery
                const novaKarakteristikaNode = $('#karakteristika-template').contents().clone(true);
                
                let karakteristikaIndex = karakteristikeKontejner.find('.karakteristika-red').length;

                novaKarakteristikaNode.find('[data-name]').each(function() {
                    const nameTemplate = $(this).data('name').replace('__K_INDEX__', karakteristikaIndex);
                    this.name = `grupe[${trenutnaGrupaIndex}]` + nameTemplate;
                });
                
                // Dodajemo novi element na kraj koristeći jQuery .append()
                karakteristikeKontejner.append(novaKarakteristikaNode);

                // Pozivamo novu, univerzalnu funkciju nakon dodavanja
                updateCharacteristicOrderAndNumbers(karakteristikeKontejner[0]);
            } 
            
            else if (clickedButton.hasClass('ukloni-grupu')) {
                elementToRemove = clickedButton.closest('.accordion-item')[0];
                if (domRemoveModalBody) domRemoveModalBody.textContent = 'Da li ste sigurni da želite da obrišete celu grupu sa svim njenim karakteristikama?';
                if (domRemoveModal) domRemoveModal.show();
            } 
            
            else if (clickedButton.hasClass('ukloni-karakteristiku')) {
                elementToRemove = clickedButton.closest('.karakteristika-red')[0];
                if (domRemoveModalBody) domRemoveModalBody.textContent = 'Da li ste sigurni da želite da obrišete ovu karakteristiku?';
                if (domRemoveModal) domRemoveModal.show();
            }
        });

        new Sortable(grupeKontejner, {
            animation: 150,
            handle: '.accordion-header',
            onEnd: function() {
                updateGroupOrder();
            }
        });

        function initializeSortableForKarakteristike() {
            document.querySelectorAll('.karakteristike-sortable-kontejner').forEach(function(kontejner) {
                if (!kontejner.classList.contains('sortable-initialized')) {
                    kontejner.classList.add('sortable-initialized');
                    new Sortable(kontejner, {
                        animation: 150,
                        handle: '.fa-grip-vertical',
                        onEnd: function(evt) {
                            // Pozivamo novu, univerzalnu funkciju nakon pomeranja
                            updateCharacteristicOrderAndNumbers(evt.target);
                        }
                    });
                }
            });
        }

        initializeSortableForKarakteristike();

        $('#dodaj-grupu').on('click', function() {
            setTimeout(initializeSortableForKarakteristike, 100);
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
    const canvas = document.getElementById('particle-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let particlesArray;
        const config = {
            particleColor: 'rgba(172, 22, 27, 0.7)',
            lineColor: 'rgba(172, 22, 27, 0.15)',
            particleAmount: 70,
            defaultSpeed: 0.5,
            variantSpeed: 0.5,
            defaultRadius: 2,
            variantRadius: 2,
            linkRadius: 180,
        };
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.radius = config.defaultRadius + Math.random() * config.variantRadius;
                this.speedX = (Math.random() * 2 - 1) * config.defaultSpeed;
                this.speedY = (Math.random() * 2 - 1) * config.defaultSpeed;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = config.particleColor;
                ctx.fill();
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
            }
        }

        function init() {
            particlesArray = [];
            for (let i = 0; i < config.particleAmount; i++) {
                particlesArray.push(new Particle());
            }
        }

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

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (const particle of particlesArray) {
                particle.update();
                particle.draw();
            }
            connectParticles();
            requestAnimationFrame(animate);
        }
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            init();
        });
        init();
        animate();
    }
});