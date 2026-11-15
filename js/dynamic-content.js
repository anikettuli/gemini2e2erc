// Lazy, XSS-safe Dynamic Content Loaders (loaded per active tab)
let boardLoaded = false;
let partnersLoaded = false;
let testimonialsLoaded = false;

async function loadBoardMembers() {
  if (boardLoaded) return;
  const currentContainer = document.getElementById('board-grid-current');
  const previousContainer = document.getElementById('board-grid-previous');
  if (!currentContainer && !previousContainer) return;
  boardLoaded = true;

  try {
    const response = await fetch('data/board.json', { cache: 'no-cache' });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const boardMembers = await response.json();

    const list = Array.isArray(boardMembers) ? boardMembers : [];
    const currentMembers = list.filter(m => !m.tag || m.tag === 'current');
    const previousMembers = list.filter(m => m.tag === 'previous');

    function renderMembers(members, container) {
      if (!container) return;
      container.replaceChildren();

      if (members.length === 0) {
        const emptyMsg = document.createElement('p');
        emptyMsg.className = 'text-dim';
        emptyMsg.textContent = 'No members listed.';
        container.appendChild(emptyMsg);
        return;
      }

      const fragment = document.createDocumentFragment();
      members.forEach(member => {
        const memberCard = document.createElement('div');
        memberCard.className = 'board-member';

        const img = document.createElement('img');
        const fallbackSrc = 'images/placeholder-volunteer.svg';
        const rawSrc = member.image && member.image !== 'images/board/placeholder.png'
          ? member.image
          : fallbackSrc;
        img.src = rawSrc;
        img.alt = member.name ? String(member.name) : 'Board Member';
        img.width = 120;
        img.height = 120;
        img.loading = 'lazy';
        img.decoding = 'async';
        img.addEventListener('error', () => {
          if (!img.dataset.fallbackApplied) {
            img.dataset.fallbackApplied = 'true';
            img.src = fallbackSrc;
          }
        });

        const h4 = document.createElement('h4');
        h4.textContent = member.name ? String(member.name) : '';

        memberCard.appendChild(img);
        memberCard.appendChild(h4);
        fragment.appendChild(memberCard);
      });
      container.appendChild(fragment);
    }

    renderMembers(currentMembers, currentContainer);
    renderMembers(previousMembers, previousContainer);
  } catch (error) {
    boardLoaded = false;
    console.error('Error loading board members:', error);
    if (currentContainer) {
      const p = document.createElement('p');
      p.textContent = 'Unable to load board members.';
      currentContainer.replaceChildren(p);
    }
    if (previousContainer) {
      previousContainer.replaceChildren();
    }
  }
}

async function loadPartners() {
  if (partnersLoaded) return;
  const container = document.getElementById('partners-list');
  if (!container) return;
  partnersLoaded = true;

  try {
    const response = await fetch('data/partners.json', { cache: 'no-cache' });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const partners = await response.json();

    container.replaceChildren();
    const fragment = document.createDocumentFragment();
    (Array.isArray(partners) ? partners : []).forEach(partner => {
      const li = document.createElement('li');
      li.textContent = String(partner);
      fragment.appendChild(li);
    });
    container.appendChild(fragment);
  } catch (error) {
    partnersLoaded = false;
    console.error('Error loading partners:', error);
    const li = document.createElement('li');
    li.textContent = 'Unable to load partners.';
    container.replaceChildren(li);
  }
}

async function loadTestimonials() {
  if (testimonialsLoaded) return;
  const container = document.getElementById('testimonials-container');
  if (!container) return;
  testimonialsLoaded = true;

  try {
    const response = await fetch('data/testimonials.json', { cache: 'no-cache' });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const testimonials = await response.json();

    container.replaceChildren();
    const fragment = document.createDocumentFragment();
    (Array.isArray(testimonials) ? testimonials : []).forEach(t => {
      const card = document.createElement('div');
      card.className = 'testimonial';

      const textEl = document.createElement('div');
      textEl.className = 'testimonial-text';
      textEl.textContent = String(t.text || '');

      const authorEl = document.createElement('div');
      authorEl.className = 'testimonial-author';
      authorEl.textContent = String(t.author || '');

      const orgEl = document.createElement('div');
      orgEl.className = 'testimonial-org';
      orgEl.textContent = String(t.organization || '');

      card.append(textEl, authorEl, orgEl);
      fragment.appendChild(card);
    });
    container.appendChild(fragment);
  } catch (error) {
    testimonialsLoaded = false;
    console.error('Error loading testimonials:', error);
    const p = document.createElement('p');
    p.textContent = 'Unable to load testimonials.';
    container.replaceChildren(p);
  }
}

window.ensureBoardLoaded = loadBoardMembers;
window.ensurePartnersLoaded = loadPartners;
window.ensureTestimonialsLoaded = loadTestimonials;
