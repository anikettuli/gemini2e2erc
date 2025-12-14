// Dynamic Content Loader
document.addEventListener('DOMContentLoaded', () => {
    loadBoardMembers();
    loadPartners();
    loadTestimonials();
});

// Load Board Members
async function loadBoardMembers() {
    const container = document.getElementById('board-grid');
    if (!container) return;

    try {
        const response = await fetch('data/board.json');
        const boardMembers = await response.json();

        let html = '';
        boardMembers.forEach(member => {
            html += `
                <div class="board-member">
                    <img src="${member.image}" alt="${member.name}" onerror="this.src='${SITE_CONFIG.defaultImage}'">
                    <h4>${member.name}</h4>
                </div>
            `;
        });
        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading board members:', error);
        container.innerHTML = '<p>Unable to load board members.</p>';
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
