// Modern Tailwind Calendar Logic for District 2-E2 ERC
class EventCalendar {
  constructor() {
    this.events = [];
    this.currentMonth = new Date();
    this.init();
  }

  async init() {
    await this.loadEvents();
    this.setCurrentMonthToNextEvent();
    this.renderCalendar();
    this.renderUpcomingEvents();
    this.addEventListeners();
    this.autoSelectNextEvent();
  }

  setCurrentMonthToNextEvent() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const nextEvent = this.events.find(event => new Date(event.date) >= today);
    if (nextEvent) {
      const eventDate = new Date(nextEvent.date);
      this.currentMonth = new Date(eventDate.getFullYear(), eventDate.getMonth(), 1);
    }
  }

  addEventListeners() {
    const calendarContainer = document.getElementById('volunteerCalendar');
    if (!calendarContainer) return;

    calendarContainer.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (btn && btn.id === 'prevMonthBtn') this.prevMonth();
      if (btn && btn.id === 'nextMonthBtn') this.nextMonth();

      const dayCell = e.target.closest('[data-date]');
      if (dayCell && !dayCell.classList.contains('opacity-30')) { // Filter out previous/next month days if desired
        const date = dayCell.dataset.date;
        if (date) {
          document.querySelectorAll('[data-date]').forEach(c => c.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-900/50'));
          dayCell.classList.add('ring-2', 'ring-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-900/50');
          this.showDayEvents(date);
        }
      }
    });
  }

  async loadEvents() {
    try {
      const response = await fetch('data/events.json');
      this.events = await response.json();
      this.events.sort((a, b) => new Date(a.date) - new Date(b.date));
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

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    let html = `
            <div class="flex justify-between items-center mb-6">
                <button id="prevMonthBtn" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-full transition-colors text-slate-600 dark:text-slate-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">${monthNames[month]} ${year}</h3>
                <button id="nextMonthBtn" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-full transition-colors text-slate-600 dark:text-slate-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
            <div class="grid grid-cols-7 gap-1 mb-2 text-center text-xs font-bold text-slate-400 uppercase tracking-wide">
                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
            </div>
            <div class="grid grid-cols-7 gap-1">
        `;

    // Previous month days
    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
      html += `<div class="aspect-square flex items-center justify-center text-slate-300 dark:text-slate-600 text-sm cursor-default">${prevLastDate - i}</div>`;
    }

    // Current month days
    const todayStr = new Date().toISOString().split('T')[0];

    for (let day = 1; day <= lastDate; day++) {
      const currentDate = new Date(year, month, day);
      // Use local date string to avoid timezone offset issues simply by manually formatting
      const offset = currentDate.getTimezoneOffset();
      const localDate = new Date(currentDate.getTime() - (offset * 60 * 1000));
      const dateString = localDate.toISOString().split('T')[0];

      const dayEvents = this.events.filter(e => e.date === dateString);
      const hasEvent = dayEvents.length > 0;
      const isToday = dateString === todayStr;

      let bgClass = hasEvent ? 'font-bold text-slate-900 dark:text-white hover:bg-indigo-50 dark:hover:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800';
      if (isToday) bgClass = 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-md transform hover:scale-105 transition-all';

      html += `
                <div class="aspect-square flex flex-col items-center justify-center rounded-xl cursor-pointer transition-all relative ${bgClass}" data-date="${dateString}">
                    <span class="text-sm">${day}</span>
                    ${hasEvent && !isToday ? `<span class="w-1.5 h-1.5 bg-indigo-500 rounded-full mt-1"></span>` : ''}
                    ${hasEvent && isToday ? `<span class="w-1.5 h-1.5 bg-white rounded-full mt-1"></span>` : ''}
                </div>
             `;
    }

    html += '</div>';
    calendarContainer.innerHTML = html;
  }

  showDayEvents(dateString) {
    const eventsDisplay = document.getElementById('eventsDisplay');
    if (!eventsDisplay) return;

    const dayEvents = this.events.filter(event => event.date === dateString);

    if (dayEvents.length === 0) {
      const dateObj = new Date(dateString + 'T00:00:00');
      const datePretty = dateObj.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
      eventsDisplay.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-slate-400 text-center p-8">
                    <span class="text-4xl mb-4 opacity-50">📅</span>
                    <p class="text-lg">No events on<br><strong class="text-slate-600 dark:text-slate-300">${datePretty}</strong></p>
                </div>
             `;
      return;
    }

    let html = '';
    dayEvents.forEach(event => {
      html += this.createEventCard(event);
    });
    // Added snap-x snap-mandatory and scroll-px for behavior
    // Added trailing spacer for last-card snap fix
    eventsDisplay.innerHTML = `<div class="flex lg:flex-col gap-4 animate-fade-in w-full overflow-x-auto snap-x snap-mandatory lg:overflow-visible no-scrollbar pb-4 px-4 scroll-px-4">${html}<div class="w-4 shrink-0 lg:hidden" aria-hidden="true"></div></div>`;
  }

  renderUpcomingEvents() {
    // We might not need this if we rely on the main calendar view, but let's keep it if the DOM element exists
    const container = document.getElementById('upcomingEvents');
    if (!container) return;

    const today = new Date().toISOString().split('T')[0];
    const upcoming = this.events.filter(e => e.date >= today).slice(0, 4);

    if (upcoming.length === 0) {
      container.innerHTML = '<p class="text-center text-slate-500 py-8">No upcoming events scheduled.</p>';
      return;
    }

    let eventsHtml = '';
    upcoming.forEach(event => {
      eventsHtml += this.createEventCard(event);
    });

    container.innerHTML = `
      <h3 class="text-2xl font-bold mb-6 text-slate-900 dark:text-white px-1">Upcoming Opportunities</h3>
      <div class="flex md:grid md:grid-cols-2 gap-6 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-6 w-full px-4 scroll-px-4">
        ${eventsHtml}
        <div class="w-4 shrink-0 md:hidden" aria-hidden="true"></div>
      </div>`;
  }

  createEventCard(event) {
    const spotsLeft = event.maxPeople - event.people;
    const percentFull = Math.min((event.people / event.maxPeople) * 100, 100);
    const isFull = spotsLeft <= 0;
    const datePretty = new Date(event.date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    // Use event.image or fallback
    const imageSrc = event.image ? event.image : 'images/e2e2rc_LOGO.png';

    return `
            <div class="bg-white dark:bg-slate-700/50 rounded-2xl p-5 lg:p-6 shadow-sm border border-slate-100 dark:border-slate-700 hover:shadow-md transition-shadow relative overflow-hidden group flex flex-col sm:flex-row gap-5 lg:gap-6 w-[80vw] sm:w-auto sm:min-w-0 flex-shrink-0 lg:w-full snap-center lg:snap-none">
                ${isFull ? '<div class="absolute top-2 right-2 bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded-full z-10">FULL</div>' : ''}
                
                <div class="w-full sm:w-32 aspect-square shrink-0 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-600">
                    <img src="${imageSrc}" alt="${event.title}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" onerror="this.src='images/e2e2rc_LOGO.png'">
                </div>

                <div class="flex-1">
                    <h4 class="font-bold text-lg text-slate-900 dark:text-white mb-2 group-hover:text-indigo-600 transition-colors leading-tight">${event.title}</h4>
                    
                    <div class="text-sm text-slate-500 dark:text-slate-400 space-y-1 mb-4">
                        <div class="flex items-center gap-2">
                            <span>📅</span> <span>${datePretty}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>⏰</span> <span>${event.time}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>📍</span> <span>${event.location || 'Watauga Center'}</span>
                        </div>
                    </div>
                
                    <div class="mb-4">
                         <div class="flex justify-between text-xs text-slate-500 mb-1">
                            <span>${event.people} / ${event.maxPeople} Registered</span>
                            <span class="${isFull ? 'text-red-500' : 'text-green-500'} font-bold">${isFull ? 'Waitlist Only' : spotsLeft + ' spots left'}</span>
                         </div>
                         <div class="w-full bg-slate-200 dark:bg-slate-600 rounded-full h-1.5 overflow-hidden">
                            <div class="h-full ${isFull ? 'bg-red-500' : 'bg-indigo-500'} transition-all duration-500" style="width: ${percentFull}%"></div>
                         </div>
                    </div>

                    <button 
                        onclick="openSignupModal(${event.id}, '${event.title.replace(/'/g, "\\'")}', '${datePretty}', '${event.time}')"
                        class="w-full py-2 rounded-xl font-bold text-sm transition-colors ${isFull ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-500/30'}"
                        ${isFull ? 'disabled' : ''}>
                        ${isFull ? 'Event Full' : 'Sign Up Now'}
                    </button>
                </div>
            </div>
        `;
  }

  prevMonth() {
    this.currentMonth.setMonth(this.currentMonth.getMonth() - 1);
    this.renderCalendar();
  }

  nextMonth() {
    this.currentMonth.setMonth(this.currentMonth.getMonth() + 1);
    this.renderCalendar();
  }

  autoSelectNextEvent() {
    const todayStr = new Date().toISOString().split('T')[0];
    const nextEvent = this.events.find(e => e.date >= todayStr);
    if (nextEvent) {
      // Wait for DOM to update then click
      setTimeout(() => {
        const cell = document.querySelector(`[data-date="${nextEvent.date}"]`);
        if (cell) cell.click();
        else this.showDayEvents(nextEvent.date); // fallback
      }, 100);
    }
  }
}

let eventCalendar;
document.addEventListener('DOMContentLoaded', () => {
  eventCalendar = new EventCalendar();
  window.eventCalendar = eventCalendar; // Expose for modal interaction
});
