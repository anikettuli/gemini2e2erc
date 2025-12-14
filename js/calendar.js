// Calendar and Events Management
class EventCalendar {
    constructor() {
        this.events = [];
        this.currentMonth = new Date();
        this.init();
    }

    async init() {
        await this.loadEvents();
        this.renderCalendar();
        this.renderUpcomingEvents();
        this.addEventListeners();
    }

    addEventListeners() {
        const calendarContainer = document.getElementById('volunteerCalendar');
        if (!calendarContainer) return;

        calendarContainer.addEventListener('click', (e) => {
            const target = e.target;
            
            // Handle navigation buttons
            if (target.id === 'prevMonthBtn' || target.closest('#prevMonthBtn')) {
                this.prevMonth();
            } else if (target.id === 'nextMonthBtn' || target.closest('#nextMonthBtn')) {
                this.nextMonth();
            }
            
            // Handle day clicks
            const dayCell = target.closest('.calendar-day');
            if (dayCell && !dayCell.classList.contains('other-month')) {
                const date = dayCell.dataset.date;
                if (date) {
                    this.showDayEvents(date);
                }
            }
        });
    }

    async loadEvents() {
        try {
            const response = await fetch('data/events.json');
            this.events = await response.json();
            // Sort events by date
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

        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];

        let calendarHTML = `
            <div class="calendar-header">
                <button class="calendar-nav-btn" id="prevMonthBtn" aria-label="Previous Month">&lt;</button>
                <h3>${monthNames[month]} ${year}</h3>
                <button class="calendar-nav-btn" id="nextMonthBtn" aria-label="Next Month">&gt;</button>
            </div>
            <div class="calendar-weekdays">
                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div>
                <div>Thu</div><div>Fri</div><div>Sat</div>
            </div>
            <div class="calendar-days">
        `;

        // Previous month's days
        for (let i = firstDayOfWeek - 1; i >= 0; i--) {
            calendarHTML += `<div class="calendar-day other-month">${prevLastDate - i}</div>`;
        }

        // Current month's days
        for (let day = 1; day <= lastDate; day++) {
            const currentDate = new Date(year, month, day);
            const dateString = currentDate.toISOString().split('T')[0];
            const hasEvent = this.events.some(event => event.date === dateString);
            const isToday = this.isToday(currentDate);
            
            let dayClass = 'calendar-day';
            if (hasEvent) dayClass += ' has-event';
            if (isToday) dayClass += ' today';
            
            calendarHTML += `
                <div class="${dayClass}" data-date="${dateString}">
                    <span class="day-number">${day}</span>
                    ${hasEvent ? '<span class="event-dot"></span>' : ''}
                </div>
            `;
        }

        // Next month's days
        const totalCells = firstDayOfWeek + lastDate;
        const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
        for (let i = 1; i <= remainingCells; i++) {
            calendarHTML += `<div class="calendar-day other-month">${i}</div>`;
        }

        calendarHTML += '</div>';
        calendarContainer.innerHTML = calendarHTML;
    }

    renderUpcomingEvents() {
        const upcomingContainer = document.getElementById('upcomingEvents');
        if (!upcomingContainer) return;

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const upcoming = this.events.filter(event => {
            const eventDate = new Date(event.date);
            return eventDate >= today;
        }).slice(0, 6); // Show next 6 events

        if (upcoming.length === 0) {
            upcomingContainer.innerHTML = '<p style="text-align: center; padding: 2rem; color: var(--text-light);">No upcoming volunteer events scheduled. Check back soon!</p>';
            return;
        }

        let eventsHTML = '<div class="events-grid">';
        
        upcoming.forEach(event => {
            const spotsLeft = event.maxPeople - event.people;
            const percentFull = (event.people / event.maxPeople) * 100;
            const isFull = spotsLeft <= 0;
            
            eventsHTML += `
                <div class="event-card ${isFull ? 'event-full' : ''}">
                    <div class="event-image">
                        <img src="${event.image || 'images/placeholder-event.svg'}" 
                             alt="${event.title}"
                             onerror="this.src='images/placeholder-event.svg'">
                        ${isFull ? '<div class="event-full-badge">FULL</div>' : ''}
                    </div>
                    <div class="event-content">
                        <h4>${event.title}</h4>
                        <div class="event-details">
                            <div class="event-detail">
                                <strong>📅 Date:</strong> ${this.formatDate(event.date)}
                            </div>
                            <div class="event-detail">
                                <strong>🕐 Time:</strong> ${event.time}
                            </div>
                            <div class="event-detail">
                                <strong>📍 Location:</strong> ${event.location}
                            </div>
                            <div class="event-detail">
                                <strong>👥 Capacity:</strong> ${event.people}/${event.maxPeople} volunteers
                                <div class="capacity-bar">
                                    <div class="capacity-fill" style="width: ${percentFull}%"></div>
                                </div>
                                ${!isFull ? `<span class="spots-left">${spotsLeft} spots left</span>` : '<span class="spots-left full">Event is full</span>'}
                            </div>
                        </div>
                        <p class="event-description">${event.description}</p>
                        ${!isFull ? `
                            <button class="event-register-btn" onclick="openSignupModal(${event.id}, '${event.title.replace(/'/g, "\\'")}', '${this.formatDate(event.date)}', '${event.time}')">
                                Sign Up Now
                            </button>
                        ` : `
                            <button class="event-register-btn" disabled>Event Full</button>
                        `}
                    </div>
                </div>
            `;
        });
        
        eventsHTML += '</div>';
        upcomingContainer.innerHTML = eventsHTML;
    }

    showDayEvents(dateString) {
        const dayEvents = this.events.filter(event => event.date === dateString);
        const eventsDisplay = document.getElementById('eventsDisplay');
        if (!eventsDisplay) return;

        if (dayEvents.length === 0) {
            eventsDisplay.innerHTML = `
                <div class="events-display-empty">
                    <p style="font-size: 1.1rem; margin: 0;">📅</p>
                    <p>No events on ${this.formatDate(dateString)}</p>
                </div>
            `;
            return;
        }

        let eventsHTML = '';
        
        dayEvents.forEach(event => {
            const spotsLeft = event.maxPeople - event.people;
            const percentFull = (event.people / event.maxPeople) * 100;
            const isFull = spotsLeft <= 0;
            
            eventsHTML += `
                <div class="event-card ${isFull ? 'event-full' : ''}">
                    <div class="event-image">
                        <img src="${event.image || 'images/placeholder-event.svg'}" 
                             alt="${event.title}">
                        ${isFull ? '<span class="event-full-badge">Full</span>' : ''}
                    </div>
                    <div class="event-content">
                        <h4>${event.title}</h4>
                        <div class="event-details">
                            <div class="event-detail" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <strong>📅 Date:</strong> ${this.formatDate(event.date)}
                            </div>
                            <div class="event-detail">
                                <strong>🕒 Time:</strong> ${event.time}
                            </div>
                            <div class="event-detail">
                                <strong>📍 Location:</strong> ${event.location}
                            </div>
                            <div class="event-detail">
                                <strong>👥 Volunteers:</strong> ${event.people}/${event.maxPeople} 
                                ${!isFull ? `<span style="color: var(--success-color);">(${spotsLeft} spots left)</span>` : ''}
                            </div>
                        </div>
                        <div class="capacity-bar">
                            <div class="capacity-fill" style="width: ${Math.min(percentFull, 100)}%"></div>
                        </div>
                        <p class="event-description">${event.description}</p>
                        ${!isFull ? `
                            <button class="event-register-btn" onclick="openSignupModal(${event.id}, '${event.title.replace(/'/g, "\\'")}', '${this.formatDate(event.date)}', '${event.time}')">Sign Up Now</button>
                        ` : `
                            <button class="event-register-btn" disabled>Event Full</button>
                        `}
                    </div>
                </div>
            `;
        });
        
        eventsDisplay.innerHTML = eventsHTML;
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
}

// Initialize calendar when DOM is loaded
let eventCalendar;
document.addEventListener('DOMContentLoaded', () => {
    eventCalendar = new EventCalendar();
});
