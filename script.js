document.addEventListener('DOMContentLoaded', function () {
    fetchEvents();
});

function fetchEvents() {
    // Use a CORS proxy if needed for cross-origin requests
    const corsProxy = 'https://api.allorigins.win/raw?url=';
    const targetUrl = encodeURIComponent('https://www.basislager.co/de');

    fetch(corsProxy + targetUrl)
        .then(response => response.text())
        .then(html => {
            console.log("html", html)
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const events = parseEvents(doc);
            displayEvents(events);
        })
        .catch(error => {
            console.error('Error fetching events:', error);
            // Show fallback content or error message
            document.getElementById('events-container').innerHTML = `
               <div class="event-card">
                    <h2 class="event-title">⚠️ Error Loading Events</h2>
                    <p class="event-description">Could not fetch events. Please try again later.</p>
                </div>
            `;
        });
}

function parseEvents(doc) {
    const events = [];
    const eventElements = doc.querySelectorAll('li.flex.flex-col');

    eventElements.forEach((element, index) => {
        // Extract date and time
        const dateTimeEl = element.querySelector('span.text-fs9');
        const dateTimeText = dateTimeEl ? dateTimeEl.textContent.trim() : '';
        const dateTimeMatch = dateTimeText.match(/(\d{2}\.\d{2}\.\d{4}),\s*(\d{1,2})\s*Uhr/) || [];

        if (dateTimeMatch) {
            const [, date = '', time = ''] = dateTimeMatch;

            // Extract other event details
            const title = element.querySelector('span.text-fs7')?.textContent.trim() || '';
            const description = element.querySelectorAll('span.text-fs7')[1]?.textContent.trim() || '';
            const tagsText = element.querySelectorAll('span.text-fs9')[1]?.textContent.trim() || '';
            const tags = tagsText.split(',').map(tag => tag.trim()).filter(tag => tag);

            events.push({
                id: index + 1,
                title: title,
                description: description,
                date: `${time} Uhr, ${date}`,
                place: 'Basislager Leipzig',
                tags: tags
            });
        }
    });

    return filterTodayEvents(events);
}

function filterTodayEvents(events) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return events.filter(event => {
        const [day, month, year] = event.date.split(', ')[1].split('.');
        const eventDate = new Date(year, month - 1, day);
        eventDate.setHours(0, 0, 0, 0);
        return eventDate >= today;
    }).sort((a, b) => {
        const [dayA, monthA, yearA] = a.date.split(', ')[1].split('.');
        const [dayB, monthB, yearB] = b.date.split(', ')[1].split('.');
        return new Date(yearA, monthA - 1, dayA) - new Date(yearB, monthB - 1, dayB);
    });
}

function displayEvents(events) {
    const container = document.getElementById('events-container');
    container.innerHTML = ''; // Clear existing content

    if (events.length === 0) {
        container.innerHTML = `
            <div class="event-card">
                <h2 class="event-title">No upcoming events</h2>
                <p class="event-description">Check back later for new events.</p>
            </div>
        `;
        return;
    }

    events.forEach(event => {
        const eventCard = createEventCard(event);
        container.appendChild(eventCard);
    });
}

function createEventCard(event) {
    const card = document.createElement('div');
    card.className = 'event-card';

    card.innerHTML = `
        <h2 class="event-title">${escapeHtml(event.title)}</h2>
        <div class="event-date">${escapeHtml(event.date)}</div>
        <p class="event-description">${escapeHtml(event.description)}</p>
        <div class="event-tags">
            ${event.tags.map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join('')}
        </div>
    `;

    return card;
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;")
        .replace(/`/g, "&#x60;"); // Prevents backtick injection
}
