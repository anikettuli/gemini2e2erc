// Dynamic Content Loader
document.addEventListener('DOMContentLoaded', () => {
    loadBoardMembers();
    loadPartners();
    loadTestimonials();
});

// Load Board Members (split into Current and Previous sections by tag)
async function loadBoardMembers() {
    const currentContainer = document.getElementById('board-grid-current');
    const previousContainer = document.getElementById('board-grid-previous');
    if (!currentContainer && !previousContainer) return;

    try {
        const response = await fetch('data/board.json');
        const boardMembers = await response.json();

        const currentMembers = boardMembers.filter(m => !m.tag || m.tag === 'current');
        const previousMembers = boardMembers.filter(m => m.tag === 'previous');

        function renderMembers(members, container) {
            if (!container) return;
            container.innerHTML = ''; // Safely clear container
            
            if (members.length === 0) {
                const emptyMsg = document.createElement('p');
                emptyMsg.className = 'text-dim';
                emptyMsg.textContent = 'No members listed.';
                container.appendChild(emptyMsg);
                return;
            }

            members.forEach(member => {
                const memberCard = document.createElement('div');
                memberCard.className = 'board-member';
                
                const img = document.createElement('img');
                img.src = member.image || SITE_CONFIG.defaultImage;
                img.alt = member.name || 'Board Member';
                img.onerror = function() { this.src = SITE_CONFIG.defaultImage; };
                
                const h4 = document.createElement('h4');
                h4.textContent = member.name || '';
                
                memberCard.appendChild(img);
                memberCard.appendChild(h4);
                container.appendChild(memberCard);
            });
        }

        renderMembers(currentMembers, currentContainer);
        renderMembers(previousMembers, previousContainer);

    } catch (error) {
        console.error('Error loading board members:', error);
        if (currentContainer) currentContainer.innerHTML = '<p>Unable to load board members.</p>';
        if (previousContainer) previousContainer.innerHTML = '';
    }
}

// Load Partners
async function loadPartners() {
    const container = document.getElementById('partners-list');
    if (!container) return;

    try {
        const response = await fetch('data/partners.json');
        const partners = await response.json();

        let html = '';
        partners.forEach(partner => {
            html += `<li>${partner}</li>`;
        });
        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading partners:', error);
        container.innerHTML = '<li>Unable to load partners.</li>';
    }
}

// Load Testimonials
async function loadTestimonials() {
    const container = document.getElementById('testimonials-container');
    if (!container) return;

    try {
        const response = await fetch('data/testimonials.json');
        const testimonials = await response.json();

        let html = '';
        testimonials.forEach(t => {
            html += `
                <div class="testimonial">
                    <div class="testimonial-text">${t.text}</div>
                    <div class="testimonial-author">${t.author}</div>
                    <div class="testimonial-org">${t.organization}</div>
                </div>
            `;
        });
        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading testimonials:', error);
        container.innerHTML = '<p>Unable to load testimonials.</p>';
    }
}
