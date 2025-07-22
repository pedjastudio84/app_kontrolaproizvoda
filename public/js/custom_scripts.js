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

    $('.alert-dismissible').each(function() {
        const alert = this;
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    /**********************************************************************************/
    /* DEO 2: LOGIKA ZA FORMU PLANA KONTROLE (centralizovano sa plan_kontrole_form.js)*/
    /**********************************************************************************/
    // Kod unutar ovog 'if' bloka će se izvršiti samo ako na stranici postoji element sa ID-jem 'grupeAccordion'
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

        // Provera za nesačuvane izmene na formi plana kontrole
        const formElement = $('#plan-kontrole-forma');
        if (formElement.length) {
            let initialFormData = formElement.serialize();
            formElement.on('submit', function() {
                $(window).off('beforeunload');
            });
            $('a.btn-secondary', formElement).on('click', function(event) {
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
        }
    }
});