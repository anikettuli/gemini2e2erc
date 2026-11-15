// Accessible, XSS-Safe Calendar and Events Management
class EventCalendar {
  constructor() {
    this.events = [];
    this.currentMonth = new Date();
    this.selectedDate = null;
    this.initialized = false;
  }

  async init() {
    if (this.initialized) return;
    this.initialized = true;
    await this.loadEvents();
    this.setCurrentMonthToNextEvent();
    this.addEventListeners();
    this.renderCalendar();
    this.renderUpcomingEvents();
    this.autoSelectNextEvent();
  }

  setCurrentMonthToNextEvent() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const nextEvent = this.events.find(event => {
      const eventDate = new Date(event.date + 'T00:00:00');
      return eventDate >= today;
    });

    if (nextEvent) {
      const eventDate = new Date(nextEvent.date + 'T00:00:00');
      this.currentMonth = new Date(eventDate.getFullYear(), eventDate.getMonth(), 1);
    } else {
      this.currentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    }
  }

  addEventListeners() {
    const calendarContainer = document.getElementById('volunteerCalendar');
    if (calendarContainer) {
      calendarContainer.addEventListener('click', (e) => {
        const target = e.target;

        if (target.closest('#prevMonthBtn')) {
          this.prevMonth();
          return;
        }
        if (target.closest('#nextMonthBtn')) {
          this.nextMonth();
          return;
        }

        const dayCell = target.closest('.calendar-day[data-date]');
        if (dayCell && !dayCell.classList.contains('other-month')) {
          const date = dayCell.dataset.date;
          if (date) {
            this.selectedDate = date;
            calendarContainer.querySelectorAll('.calendar-day[data-date]').forEach(cell => {
              const isMatch = cell === dayCell;
              cell.classList.toggle('selected', isMatch);
              cell.setAttribute('aria-pressed', isMatch ? 'true' : 'false');
            });
            this.showDayEvents(date);
          }
        }
      });
    }

    // Delegated listener for Sign Up buttons in both day-view and upcoming-events grid
    const handleSignupClick = (e) => {
      const btn = e.target.closest('.event-register-btn[data-event-id]');
      if (!btn || btn.disabled) return;
      const eventId = Number(btn.dataset.eventId);
      const ev = this.events.find(item => Number(item.id) === eventId);
      if (ev && typeof window.openSignupModal === 'function') {
        window.openSignupModal(ev.id, String(ev.title || ''), this.formatDate(ev.date), String(ev.time || ''));
      }
    };

    const upcomingContainer = document.getElementById('upcomingEvents');
    const eventsDisplay = document.getElementById('eventsDisplay');
    if (upcomingContainer) upcomingContainer.addEventListener('click', handleSignupClick);
    if (eventsDisplay) eventsDisplay.addEventListener('click', handleSignupClick);
  }

  async loadEvents() {
    try {
      const response = await fetch('data/events.json', { cache: 'no-cache' });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      this.events = Array.isArray(data) ? data : [];
      this.events.sort((a, b) => new Date(a.date + 'T00:00:00') - new Date(b.date + 'T00:00:00'));
    } catch (error) {
      console.error('Error loading events:', error);
      this.events = [];
    }
  }

  renderCalendar() {
    const calendarContainer = document.getElementById('volunteerCalendar');
    if (!calendarContainer) return;

    const year = this.currentMonth.getFullYear();
    const month = this.currentMonth.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const prevLastDay = new Date(year, month, 0);

    const firstDayOfWeek = firstDay.getDay();
    const lastDate = lastDay.getDate();
    const prevLastDate = prevLastDay.getDate();

    const monthNames = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];

    calendarContainer.replaceChildren();

    const header = document.createElement('div');
    header.className = 'calendar-header';

    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'calendar-nav-btn';
    prevBtn.id = 'prevMonthBtn';
    prevBtn.setAttribute('aria-label', 'Previous Month');
    prevBtn.textContent = '<';

    const title = document.createElement('h3');
    title.textContent = `${monthNames[month]} ${year}`;

    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'calendar-nav-btn';
    nextBtn.id = 'nextMonthBtn';
    nextBtn.setAttribute('aria-label', 'Next Month');
    nextBtn.textContent = '>';

    header.append(prevBtn, title, nextBtn);

    const weekdays = document.createElement('div');
    weekdays.className = 'calendar-weekdays';
    weekdays.setAttribute('aria-hidden', 'true');
    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(dayLabel => {
      const cell = document.createElement('div');
      cell.textContent = dayLabel;
      weekdays.appendChild(cell);
    });

    const daysGrid = document.createElement('div');
    daysGrid.className = 'calendar-days';

    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
      const filler = document.createElement('div');
      filler.className = 'calendar-day other-month';
      filler.setAttribute('aria-hidden', 'true');
      filler.textContent = String(prevLastDate - i);
      daysGrid.appendChild(filler);
    }

    for (let day = 1; day <= lastDate; day++) {
      const currentDate = new Date(year, month, day);
      const y = currentDate.getFullYear();
      const m = String(currentDate.getMonth() + 1).padStart(2, '0');
      const d = String(currentDate.getDate()).padStart(2, '0');
      const dateString = `${y}-${m}-${d}`;

      const matchingCount = this.events.filter(event => event.date === dateString).length;
      const hasEvent = matchingCount > 0;
      const isToday = this.isToday(currentDate);
      const isSelected = this.selectedDate === dateString;

      const dayBtn = document.createElement('button');
      dayBtn.type = 'button';
      dayBtn.className = 'calendar-day';
      if (hasEvent) dayBtn.classList.add('has-event');
      if (isToday) dayBtn.classList.add('today');
      if (isSelected) dayBtn.classList.add('selected');
      dayBtn.dataset.date = dateString;
      dayBtn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      dayBtn.setAttribute(
        'aria-label',
        `${monthNames[month]} ${day}, ${year}${hasEvent ? `, ${matchingCount} volunteer event${matchingCount > 1 ? 's' : ''}` : ''}`
      );

      const numSpan = document.createElement('span');
      numSpan.className = 'day-number';
      numSpan.textContent = String(day);
      dayBtn.appendChild(numSpan);

      if (hasEvent) {
        const dot = document.createElement('span');
        dot.className = 'event-dot';
        dot.setAttribute('aria-hidden', 'true');
        dayBtn.appendChild(dot);
      }

      daysGrid.appendChild(dayBtn);
    }

    const totalCells = firstDayOfWeek + lastDate;
    const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
    for (let i = 1; i <= remainingCells; i++) {
      const filler = document.createElement('div');
      filler.className = 'calendar-day other-month';
      filler.setAttribute('aria-hidden', 'true');
      filler.textContent = String(i);
      daysGrid.appendChild(filler);
    }

    calendarContainer.append(header, weekdays, daysGrid);
  }

  createEventCardElement(event, isDayView = false) {
    const people = Math.max(0, Number(event.people) || 0);
    const maxPeople = Math.max(1, Number(event.maxPeople) || 1);
    const spotsLeft = Math.max(0, maxPeople - people);
    const percentFull = Math.min(100, (people / maxPeople) * 100);
    const isFull = spotsLeft <= 0;

    const card = document.createElement('div');
    card.className = `event-card${isFull ? ' event-full' : ''}`;

    const imageWrap = document.createElement('div');
    imageWrap.className = 'event-image';

    const img = document.createElement('img');
    const fallbackSrc = 'images/placeholder-event.svg';
    img.src = event.image || fallbackSrc;
    img.alt = String(event.title || 'Volunteer event');
    img.loading = 'lazy';
    img.decoding = 'async';
    img.addEventListener('error', () => {
      if (!img.dataset.fallbackApplied) {
        img.dataset.fallbackApplied = 'true';
        img.src = fallbackSrc;
      }
    });
    imageWrap.appendChild(img);

    if (isFull) {
      const badge = document.createElement('div');
      badge.className = 'event-full-badge';
      badge.textContent = 'FULL';
      imageWrap.appendChild(badge);
    }

    const content = document.createElement('div');
    content.className = 'event-content';

    const heading = document.createElement('h4');
    heading.textContent = String(event.title || '');

    const details = document.createElement('div');
    details.className = 'event-details';

    const addDetail = (label, value, extraNode = null) => {
      const row = document.createElement('div');
      row.className = 'event-detail';
      const strong = document.createElement('strong');
      strong.textContent = label + ' ';
      row.append(strong, document.createTextNode(String(value || '')));
      if (extraNode) row.appendChild(extraNode);
      details.appendChild(row);
      return row;
    };

    addDetail('📅 Date:', this.formatDate(event.date));
    addDetail('🕐 Time:', event.time);
    addDetail('📍 Location:', event.location);

    const capWrap = document.createDocumentFragment();
    const bar = document.createElement('div');
    bar.className = 'capacity-bar';
    const fill = document.createElement('div');
    fill.className = 'capacity-fill';
    fill.style.setProperty('--capacity-pct', `${percentFull}%`);
    fill.style.width = `${percentFull}%`;
    bar.appendChild(fill);

    const spotsSpan = document.createElement('span');
    spotsSpan.className = isFull ? 'spots-left full' : 'spots-left';
    spotsSpan.textContent = isFull ? 'Event is full' : `${spotsLeft} spot${spotsLeft === 1 ? '' : 's'} left`;

    if (!isDayView) {
      capWrap.append(bar, spotsSpan);
      addDetail('👥 Capacity:', `${people}/${maxPeople} volunteers`, capWrap);
    } else {
      const inlineSpots = document.createElement('span');
      inlineSpots.className = isFull ? 'spots-left full' : 'spots-left';
      inlineSpots.textContent = isFull ? ' (Full)' : ` (${spotsLeft} spot${spotsLeft === 1 ? '' : 's'} left)`;
      addDetail('👥 Volunteers:', `${people}/${maxPeople}`, inlineSpots);
    }

    const desc = document.createElement('p');
    desc.className = 'event-description';
    desc.textContent = String(event.description || '');

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'event-register-btn';
    if (isFull) {
      btn.disabled = true;
      btn.textContent = 'Event Full';
    } else {
      btn.dataset.eventId = String(event.id);
      btn.textContent = 'Sign Up Now';
    }

    if (isDayView) {
      content.append(heading, details, bar, desc, btn);
    } else {
      content.append(heading, details, desc, btn);
    }

    card.append(imageWrap, content);
    return card;
  }

  renderUpcomingEvents() {
    const upcomingContainer = document.getElementById('upcomingEvents');
    if (!upcomingContainer) return;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const upcoming = this.events.filter(event => {
      const eventDate = new Date(event.date + 'T00:00:00');
      return eventDate >= today;
    }).slice(0, 6);

    upcomingContainer.replaceChildren();

    if (upcoming.length === 0) {
      const empty = document.createElement('p');
      empty.className = 'text-center p-2 text-dim';
      empty.textContent = 'No upcoming volunteer events scheduled. Check back soon!';
      upcomingContainer.appendChild(empty);
      return;
    }

    const grid = document.createElement('div');
    grid.className = 'events-grid';
    upcoming.forEach(event => {
      grid.appendChild(this.createEventCardElement(event, false));
    });
    upcomingContainer.appendChild(grid);
  }

  showDayEvents(dateString) {
    const dayEvents = this.events.filter(event => event.date === dateString);
    const eventsDisplay = document.getElementById('eventsDisplay');
    if (!eventsDisplay) return;

    eventsDisplay.replaceChildren();

    if (dayEvents.length === 0) {
      const emptyWrap = document.createElement('div');
      emptyWrap.className = 'events-display-empty';
      const icon = document.createElement('p');
      icon.className = 'text-lg m-0';
      icon.textContent = '📅';
      const text = document.createElement('p');
      text.textContent = `No events on ${this.formatDate(dateString)}`;
      emptyWrap.append(icon, text);
      eventsDisplay.appendChild(emptyWrap);
      return;
    }

    const fragment = document.createDocumentFragment();
    dayEvents.forEach(event => {
      fragment.appendChild(this.createEventCardElement(event, true));
    });
    eventsDisplay.appendChild(fragment);
  }

  prevMonth() {
    this.currentMonth.setMonth(this.currentMonth.getMonth() - 1);
    this.renderCalendar();
  }

  nextMonth() {
    this.currentMonth.setMonth(this.currentMonth.getMonth() + 1);
    this.renderCalendar();
  }

  isToday(date) {
    const today = new Date();
    return date.getDate() === today.getDate() &&
      date.getMonth() === today.getMonth() &&
      date.getFullYear() === today.getFullYear();
  }

  formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
  }

  autoSelectNextEvent() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const nextEvent = this.events.find(event => {
      const eventDate = new Date(event.date + 'T00:00:00');
      return eventDate >= today;
    });

    if (nextEvent) {
      this.selectedDate = nextEvent.date;
      this.showDayEvents(nextEvent.date);
      const dayCell = document.querySelector(`.calendar-day[data-date="${nextEvent.date}"]`);
      if (dayCell) {
        dayCell.classList.add('selected');
        dayCell.setAttribute('aria-pressed', 'true');
      }
    }
  }
}

window.ensureCalendarLoaded = function () {
  if (!window.eventCalendar) {
    window.eventCalendar = new EventCalendar();
  }
  return window.eventCalendar.init();
};
