// Global variables for auto-rotation
let currentEventIndex = 0;
let autoRotateInterval;
let events = [];

async function fetchEvents() {
    try {
        // Try to get cached events first
        const cachedEvents = localStorage.getItem('cachedEvents');
        const lastUpdate = localStorage.getItem('lastUpdate');
        const now = new Date();

        // Check if we have cached events and if they're from today
        if (cachedEvents && lastUpdate) {
            const lastUpdateDate = new Date(parseInt(lastUpdate));
            if (lastUpdateDate.toDateString() === now.toDateString()) {
                console.log('Using cached events');
                return JSON.parse(cachedEvents);
            }
        }

        // If no cache or outdated, fetch new data
        const response = await fetch('https://www.basislager.co/de', {
            headers: {
                'Accept': 'text/html',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            }
        });
        const html = await response.text();
        
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Create a Set to track unique event URLs
        const processedUrls = new Set();
        const events = [];
        let currentId = 1;

        // Get all event items
        const eventItems = doc.querySelectorAll('li.flex.flex-col.col-span-4');

        eventItems.forEach(item => {
            const link = item.querySelector('a');
            const eventUrl = link?.href || '';

            // Skip if we've already processed this event URL
            if (processedUrls.has(eventUrl)) {
                return;
            }

            const dateText = item.querySelector('.text-fs9.leading-lh9')?.textContent.trim();
            const title = item.querySelector('.text-bl-green')?.textContent.trim();
            const description = item.querySelector('.line-clamp-2')?.textContent.trim();
            
            // Get tags from the last .text-fs9.leading-lh9 element
            const allTextFs9Elements = item.querySelectorAll('.text-fs9.leading-lh9');
            const tagsText = allTextFs9Elements.length > 1 
                ? allTextFs9Elements[allTextFs9Elements.length - 1].textContent.trim() 
                : '';
            
            const imageUrl = item.querySelector('img')?.src || '';

            if (dateText && title) {
                events.push({
                    id: currentId++,
                    title,
                    description,
                    date: dateText,
                    place: 'Basislager Leipzig',
                    imageUrl,
                    eventUrl,
                    tags: tagsText.split(',').map(tag => tag.trim()).filter(tag => tag)
                });
                
                // Mark this URL as processed
                processedUrls.add(eventUrl);
            }
        });

        const eventsByDate = new Map();

        // Process events
        events.forEach(event => {
            // Parse date from the event
            const [date, time] = event.date.split(', ');
            const [day, month, year] = date.split('.');
            const eventDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
            const dateKey = eventDate.toISOString().split('T')[0];

            if (!eventsByDate.has(dateKey)) {
                eventsByDate.set(dateKey, []);
            }
            eventsByDate.get(dateKey).push(event);
        });

        // Find today's or nearest future events
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const todayKey = today.toISOString().split('T')[0];

        // If there are events today, return them
        if (eventsByDate.has(todayKey)) {
            return {
                events: eventsByDate.get(todayKey),
                isToday: true,
                date: 'Today'
            };
        }

        // Find the next closest date
        const futureDates = Array.from(eventsByDate.keys())
            .filter(date => date > todayKey)
            .sort();

        if (futureDates.length > 0) {
            const nearestDate = futureDates[0];
            const events = eventsByDate.get(nearestDate);
            const eventDate = new Date(nearestDate);
            const formattedDate = eventDate.toLocaleDateString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            return {
                events,
                isToday: false,
                date: formattedDate
            };
        }

        return {
            events: [],
            isToday: false,
            date: null
        };
    } catch (error) {
        console.error('Error processing events:', error);
        // Try to use cached data if available when fetch fails
        const cachedEvents = localStorage.getItem('cachedEvents');
        if (cachedEvents) {
            return JSON.parse(cachedEvents);
        }
        return {
            events: [],
            isToday: false,
            date: null
        };
    }
}

function createEventCard(event, index) {
    // Parse date for the date box
    const [date, time] = event.date.split(', ');
    const [day, month, year] = date.split('.');
    const eventDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
    const dayNum = eventDate.getDate();
    const monthName = eventDate.toLocaleString('de-DE', { month: 'short' });

    const tags = event.tags.map(tag => `<span class="tag">${tag}</span>`).join('');
    const isActive = index === currentEventIndex ? 'active' : '';
    
    return `
        <div class="event-card ${isActive}" data-index="${index}">
            <div class="event-date-box">
                <div class="day">${dayNum}</div>
                <div class="month">${monthName}</div>
            </div>
            <img src="${event.imageUrl || window.PLACEHOLDER_IMAGE}" alt="${event.title}" class="event-thumbnail"
                 onerror="this.src=window.PLACEHOLDER_IMAGE">
            <div class="event-card-content">
                <h3 class="event-title">${event.title}</h3>
                <p class="event-description">${event.description}</p>
                <div class="event-meta">
                    <span>${time}</span>
                    <span>•</span>
                    <span>${event.place}</span>
                </div>
                ${event.tags.length ? `<div class="tags-container">${tags}</div>` : ''}
            </div>
        </div>
    `;
}

function updateEventDetail(event, index, total) {
    // Parse date for formatting
    const [date, time] = event.date.split(', ');
    const [day, month, year] = date.split('.');
    const eventDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
    const formattedDate = eventDate.toLocaleDateString('de-DE', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });

    const tags = event.tags.map(tag => `<span class="tag">${tag}</span>`).join('');
    const detailSection = document.querySelector('.event-detail');
    
    // Add counter display
    const counter = `<div class="event-counter">${index + 1} / ${total}</div>`;
    
    detailSection.innerHTML = `
        ${counter}
        <img src="${event.imageUrl || window.PLACEHOLDER_IMAGE}" alt="${event.title}" class="event-detail-image"
             onerror="this.src=window.PLACEHOLDER_IMAGE">
        <div class="event-detail-content">
            <div class="event-detail-header">
                <h2>${event.title}</h2>
                <span>${formattedDate}</span>
            </div>
            <div class="about-event">
                <h3>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="8"></line>
                    </svg>
                    About Event
                </h3>
                <p>${event.description}</p>
            </div>
            <div class="event-info">
                <div class="info-row">
                    <span class="info-label">Time</span>
                    <span>${time}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Location</span>
                    <span>${event.place}</span>
                </div>
                ${event.tags.length ? `
                <div class="info-row">
                    <span class="info-label">Tags</span>
                    <div class="tags-container">${tags}</div>
                </div>
                ` : ''}
            </div>
        </div>
    `;

    // Update active state of cards
    document.querySelectorAll('.event-card').forEach((card, i) => {
        card.classList.toggle('active', i === index);
    });
}

function startAutoRotate() {
    if (autoRotateInterval) {
        clearInterval(autoRotateInterval);
    }
    
    if (events.length <= 1) return;

    autoRotateInterval = setInterval(() => {
        currentEventIndex = (currentEventIndex + 1) % events.length;
        updateEventDetail(events[currentEventIndex], currentEventIndex, events.length);
    }, 5000); // Rotate every 5 seconds
}

function stopAutoRotate() {
    if (autoRotateInterval) {
        clearInterval(autoRotateInterval);
    }
}

async function initializeEvents() {
    const eventsList = document.querySelector('.events-list');
    const eventsContainer = eventsList.querySelector('.events-container');
    const titleElement = eventsList.querySelector('h2');

    try {
        const { events: fetchedEvents, isToday, date } = await fetchEvents();
        events = fetchedEvents; // Store in global variable

        // Update the title based on whether we're showing today's or future events
        if (date) {
            titleElement.textContent = isToday ? "Today's Events" : `Upcoming Events (${date})`;
        }

        // Clear loading content
        eventsContainer.innerHTML = '';

        if (events.length > 0) {
            // Add event cards
            events.forEach((event, index) => {
                const eventCard = createEventCard(event, index);
                eventsContainer.insertAdjacentHTML('beforeend', eventCard);
            });

            // Show first event in detail view
            currentEventIndex = 0;
            updateEventDetail(events[0], 0, events.length);

            // Start auto-rotation
            startAutoRotate();

            // Add click handlers to event cards
            document.querySelectorAll('.event-card').forEach((card, index) => {
                card.addEventListener('click', () => {
                    currentEventIndex = index;
                    updateEventDetail(events[index], index, events.length);
                    stopAutoRotate();
                    startAutoRotate(); // Restart rotation from this event
                });
            });
        } else {
            eventsContainer.innerHTML = '<p class="no-events">No upcoming events found</p>';
            document.querySelector('.event-detail').innerHTML = '<p class="no-events">No event selected</p>';
        }
    } catch (error) {
        console.error('Error initializing events:', error);
        eventsContainer.innerHTML = '<p class="no-events">Error loading events. Please try again later.</p>';
        document.querySelector('.event-detail').innerHTML = '<p class="no-events">Error loading event details</p>';
    }
}

// Initialize when the page loads
document.addEventListener('DOMContentLoaded', () => {
    initializeEvents();
    
    // Set up daily refresh
    setInterval(() => {
        const now = new Date();
        if (now.getHours() === 0 && now.getMinutes() === 0) {
            // Refresh at midnight
            initializeEvents();
        }
    }, 60000); // Check every minute
});

// Pause auto-rotation when user hovers over event detail
document.querySelector('.event-detail').addEventListener('mouseenter', stopAutoRotate);
document.querySelector('.event-detail').addEventListener('mouseleave', startAutoRotate); 