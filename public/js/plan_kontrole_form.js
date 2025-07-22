// Funkcija koja se poziva kada korisnik kuca naziv grupe da bi se ažurirao naslov harmonike
function updateAccordionHeader(inputElement) {
    const headerButton = inputElement.closest('.accordion-body').parentElement.previousElementSibling.querySelector('.accordion-button');
    if (headerButton) {
        headerButton.textContent = inputElement.value.trim() ? inputElement.value : 'Nova Grupa';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const dodajGrupuBtn = document.getElementById('dodaj-grupu');
    const grupeKontejner = document.getElementById('grupeAccordion');
    const placeholder = document.getElementById('prazna-lista-grupa');

    // Inicijalizacija modala za potvrdu brisanja DOM elemenata
    const domRemoveModalEl = document.getElementById('confirmDomRemoveModal');
    let domRemoveModal = domRemoveModalEl ? new bootstrap.Modal(domRemoveModalEl) : null;
    const domRemoveConfirmBtn = document.getElementById('confirmDomRemoveButton');
    const domRemoveModalBody = document.getElementById('confirmDomRemoveModalBody');
    let elementToRemove = null;

    // Funkcija za ažuriranje skrivenih polja za redosled
    function updateGroupOrder() {
        const items = grupeKontejner.querySelectorAll('.grupa-kartica');
        items.forEach((item, index) => {
            const redosledInput = item.querySelector('.redosled-grupe');
            if (redosledInput) {
                redosledInput.value = index;
            }
        });
    }

    // Funkcija koja se poziva kada se potvrdi brisanje u modalu
    if (domRemoveConfirmBtn) {
        domRemoveConfirmBtn.addEventListener('click', function() {
            if (elementToRemove) {
                const isGrupa = elementToRemove.classList.contains('grupa-kartica');
                const kontejnerKarakteristika = elementToRemove.parentElement;
                
                elementToRemove.remove(); // Ukloni element
                
                if (isGrupa) {
                    updateGroupOrder(); // Ažuriraj redosled ako je obrisana grupa
                } else if(kontejnerKarakteristika && kontejnerKarakteristika.classList.contains('karakteristike-kontejner')) {
                    // Ako je obrisana karakteristika, ponovo numerišemo preostale
                    kontejnerKarakteristika.querySelectorAll('.redni-broj-karakteristike').forEach((input, index) => {
                        input.value = index + 1;
                    });
                }
                
                checkPlaceholder(); // Proveri da li treba prikazati placeholder
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

    grupeKontejner.addEventListener('click', function(e) {
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
    
    if (dodajGrupuBtn) {
        dodajGrupuBtn.addEventListener('click', function () {
            if (placeholder) placeholder.style.display = 'none';

            const noviIndex = grupaIndexCounter++;
            const novaGrupaNode = grupaTemplate.content.cloneNode(true);
            const accordionItem = novaGrupaNode.querySelector('.accordion-item');

            // Ažuriranje jedinstvenih ID-jeva i atributa za novu grupu
            const header = accordionItem.querySelector('.accordion-header');
            const button = header.querySelector('button');
            const collapseDiv = accordionItem.querySelector('.accordion-collapse');
            
            header.id = `heading-${noviIndex}`;
            button.setAttribute('data-bs-target', `#collapse-${noviIndex}`);
            button.setAttribute('aria-controls', `collapse-${noviIndex}`);
            collapseDiv.id = `collapse-${noviIndex}`;
            collapseDiv.setAttribute('aria-labelledby', `heading-${noviIndex}`);
            
            // Ažuriranje 'name' atributa i 'value' za redosled
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
            updateGroupOrder(); // Ažuriraj redosled nakon dodavanja
        });
    }

    // ===== Inicijalizacija SortableJS =====
    if (grupeKontejner) {
        new Sortable(grupeKontejner, {
            animation: 150,
            handle: '.accordion-header', // Definišemo "ručku" za prevlačenje
            onEnd: function () {
                // Ažuriraj redosled u skrivenim poljima nakon prevlačenja
                updateGroupOrder();
            }
        });
    }

    // ===== Modal za potvrdu brisanja (opšti) =====
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    if (confirmDeleteModal) {
        const deleteButton = confirmDeleteModal.querySelector('#confirmDeleteButton');
        document.body.addEventListener('click', function (e) {
            const trigger = e.target.closest('[data-bs-toggle="modal"][data-bs-target="#confirmDeleteModal"]');
            if (trigger && trigger.hasAttribute('data-delete-url')) {
                const url = trigger.getAttribute('data-delete-url');
                if (deleteButton) {
                    deleteButton.setAttribute('href', url);
                }
            }
        });
    }
});