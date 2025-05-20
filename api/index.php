<?php
function fetchEvents()
{
    $url = 'https://www.basislager.co/de';
    $html = file_get_contents($url);

    if (!$html) {
        return [];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    
    // Update selector to match the div structure
    $eventElements = $xpath->query("//div[contains(@class, 'flex') and contains(@class, 'flex-col') and contains(@class, 'gap-1')]");

    $events = [];
    foreach ($eventElements as $index => $element) {
        // Update selectors to match the actual HTML structure
        $dateTimeNode = $xpath->query(".//span[contains(@class, 'text-fs9')][1]", $element)->item(0);
        $titleNode = $xpath->query(".//span[contains(@class, 'text-fs7')][1]", $element)->item(0);
        $descNode = $xpath->query(".//span[contains(@class, 'text-fs7')][2]", $element)->item(0);
        $tagsNode = $xpath->query(".//span[contains(@class, 'text-fs9')][2]", $element)->item(0);

        $dateTimeText = $dateTimeNode ? trim($dateTimeNode->textContent) : '';
        preg_match('/(\d{2}\.\d{2}\.\d{4}),\s*(\d{1,2})\s*Uhr/', $dateTimeText, $matches);

        if ($matches) {
            list(, $date, $time) = $matches;
            $title = $titleNode ? trim($titleNode->textContent) : '';
            $desc = $descNode ? trim($descNode->textContent) : '';
            $tags = $tagsNode ? array_map('trim', explode(',', trim($tagsNode->textContent))) : [];

            // Debug log
            // file_put_contents(
            //     __DIR__ . '/debug.log',
            //     date('[Y-m-d H:i:s] ') . "Found event: $title on $date at $time Uhr\n",
            //     FILE_APPEND
            // );

            // Check if event title already exists
            $exists = false;
            foreach ($events as $event) {
                if ($event['title'] == $title) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                // Add event if title doesn't exist
            $events[] = [
                'id' => $index + 1,
                'title' => $title,
                'description' => $desc,
                'date' => "$time Uhr, $date",
                'place' => 'Basislager Leipzig',
                'tags' => $tags
            ];
            } else {
                // Optionally handle the case when the title already exists
                // echo "Event with this title already exists.";
            }
        }
    }

    return $events; // Remove filterTodayEvents here since we handle it separately
}

function filterTodayEvents($events)
{
    $today = new DateTime();
    $filteredEvents = [];

    foreach ($events as $event) {
        list($time, $date) = explode(', ', $event['date']);
        list($day, $month, $year) = explode('.', $date);
        $eventDate = new DateTime("$year-$month-$day");

        if ($eventDate >= $today) {
            $filteredEvents[] = $event;
        }
    }

    usort($filteredEvents, function ($a, $b) {
        return strtotime(str_replace('.', '-', $a['date'])) - strtotime(str_replace('.', '-', $b['date']));
    });

    return $filteredEvents;
}

function isDayTime()
{
    // For testing evening view, always return false
    // return false;

    // Original code (comment out for testing)
    $hour = (int) date('H');
    return $hour >= 6 && $hour < 17; // Day time between 6 AM and 6 PM
}

function getTodayEvents($events)
{
    $today = new DateTime();
    $todayEvents = [];
    
    // Write to a custom log file in your project
    // file_put_contents(
    //     __DIR__ . '/debug.log',
    //     date('[Y-m-d H:i:s] ') . print_r($events, true) . "\n",
    //     FILE_APPEND
    // );
    
    foreach ($events as $event) {
        list($time, $date) = explode(', ', $event['date']);
        list($day, $month, $year) = explode('.', $date);
        $eventDate = new DateTime("$year-$month-$day");
        
        if ($eventDate->format('Y-m-d') === $today->format('Y-m-d')) {
            $todayEvents[] = $event;
        }
    }

    return $todayEvents;
}

$isDaytime = isDayTime();
$events = fetchEvents();
$todayEvents = getTodayEvents($events);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Basislager Events</title>
    <link rel="stylesheet" href="styles/fonts.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Theme variables */
        :root {
            <?php if ($isDaytime): ?>
            /* Light theme */
            --bg-color: #F5F5F5;
            --text-color: #333333;
            --card-bg: #FFFFFF;
            --accent-color: #008F4C;
            --header-color: #006B3A;
            --card-border: #E0E0E0;
            --tag-bg: #E8F5E9;
            --tag-color: #2E7D32;
            <?php else: ?>
            /* Dark theme */
            --bg-color: #1A1D21;
                --text-color: #FFFFFF;
            --card-bg: #23272B;
            --accent-color: #00FF7F;
            --header-color: #00FF7F;
            --card-border: #00FF7F;
            --tag-bg: #2C3338;
            --tag-color: #00FF7F;
            <?php endif; ?>
            
            /* Add font family variable */
            --primary-font: 'Founders Grotesk', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            
            /* Add emoji font stack */
            --emoji-fonts: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";

            /* Add an array of soft/pastel colors */
            --card-colors: #E8F5E9, #F3E5F5, #E3F2FD, #FFF3E0, #F1F8E9, #E0F7FA, #FCE4EC, #FFF8E1, #EFEBE9, #E8EAF6;

            /* Light mode pastel colors */
            --light-rainbow-1: #E8F5E9;
            --light-rainbow-2: #F3E5F5;
            --light-rainbow-3: #E3F2FD;
            --light-rainbow-4: #FFF3E0;
            --light-rainbow-5: #F1F8E9;
            --light-rainbow-6: #E0F7FA;
            --light-rainbow-7: #FCE4EC;
            --light-rainbow-8: #FFF8E1;
            --light-rainbow-9: #EFEBE9;
            --light-rainbow-10: #E8EAF6;

            /* Dark mode rainbow colors - using more vibrant colors */
            --dark-rainbow-1: #2d6a4f;  /* Darker but still readable green */
            --dark-rainbow-2: #5b21b6;  /* Vibrant purple */
            --dark-rainbow-3: #1e40af;  /* Bright blue */
            --dark-rainbow-4: #92400e;  /* Warm orange */
            --dark-rainbow-5: #3f6212;  /* Forest green */
            --dark-rainbow-6: #0e7490;  /* Cyan */
            --dark-rainbow-7: #9d174d;  /* Rose */
            --dark-rainbow-8: #854d0e;  /* Amber */
            --dark-rainbow-9: #44403c;  /* Warm gray */
            --dark-rainbow-10: #312e81; /* Indigo */
        }

        /* Add fallback colors for older browsers */
        body {
            font-family: Arial, sans-serif;
            /* Fallback font */
            font-family: var(--primary-font, Arial, sans-serif), var(--emoji-fonts);
            background-color:
                <?php echo $isDaytime ? '#F5F5F5' : '#1A1D21' ?>
            ;
            /* Fallback */
            background-color: var(--bg-color);
            color:
                <?php echo $isDaytime ? '#333333' : '#FFFFFF' ?>
            ;
            /* Fallback */
            color: var(--text-color);
            margin: 20px;
            padding: 20px;
            transition: all 0.3s ease;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1 {
            font-size: 42px;
            text-align: center;
            color:
                <?php echo $isDaytime ? '#006B3A' : '#00FF7F' ?>
            ;
            /* Fallback */
            color: var(--header-color);
            margin-bottom: 30px;
        }

        .event-card {
            background:
                <?php echo $isDaytime ? '#FFFFFF' : '#23272B' ?>
            ;
            /* Fallback */
            background: var(--card-bg);
            border: 1px solid
                <?php echo $isDaytime ? '#E0E0E0' : '#00FF7F' ?>
            ;
            /* Fallback */
            border: 1px solid var(--card-border);
            /* padding: 25px; */
            margin: 25px 0;
            border-radius: 8px;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .event-title {
            font-size: 32px;
            margin-top: 20px;
            color: var(--header-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Limit to 2 lines */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .event-date {
            font-size: 28px;
            font-weight: 500;
            color:
                <?php echo $isDaytime ? '#008F4C' : '#00FF7F' ?>
            ;
            /* Fallback */
            color: var(--accent-color);
            margin: 15px 0;
        }

        .event-description {
            font-size: 24px;
            line-height: 1.6;
            margin: 20px 0;
            color: var(--text-color);
            font-weight: normal;
        }

        .event-description strong {
            font-weight: 600;
            /* Use semibold for bold text */
            color: var(--header-color);
        }

        .event-description .emoji {
            display: inline-block;
            font-family: var(--emoji-fonts);
            font-size: 1.1em;
            /* Slightly larger than text */
            line-height: 1;
            vertical-align: -0.1em;
            /* Align with text */
            margin: 0 0.1em;
        }

        .event-tags {
            margin-top: 20px;
        }

        .tag {
            display: inline-block;
            background:
                <?php echo $isDaytime ? '#E8F5E9' : '#2C3338' ?>
            ;
            /* Fallback */
            background: var(--tag-bg);
            color:
                <?php echo $isDaytime ? '#2E7D32' : '#00FF7F' ?>
            ;
            /* Fallback */
            color: var(--tag-color);
            padding: 8px 16px;
            margin: 5px;
            border-radius: 20px;
            font-size: 20px;
        }

        .subtitle {
            font-size: 26px;
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
        }

        .logo-text {
            font-size: 36px;
            color: var(--accent-color);
        }

        /* Make container more spacious */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Improve spacing between events */
        #events-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Evening specific styles */
        .evening-view {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem;
        }

        .evening-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .evening-title {
            font-size: 64px;
            color: var(--header-color);
            margin-bottom: 1rem;
        }

        .evening-subtitle {
            font-size: 32px;
            color: var(--text-color);
            opacity: 0.8;
        }

        .slider-container {
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
        }

        .event-slider {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .evening-event-card {
            background: var(--card-bg);
            border: 2px solid var(--card-border);
            padding: 3rem;
            border-radius: 16px;
            min-height: 500px;
            width: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .evening-event-title {
            font-size: 48px;
            color: var(--header-color);
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .evening-event-time {
            font-size: 36px;
            color: var(--accent-color);
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .evening-event-description {
            font-size: 28px;
            line-height: 1.6;
            margin: 2rem 0;
            color: var(--text-color);
        }

        .evening-event-location {
            font-size: 24px;
            color: var(--accent-color);
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .location-icon {
            font-size: 32px;
        }

        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--header-color);
            color: var(--bg-color);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            outline: none;
        }

        .slider-arrow:hover {
            background: var(--accent-color);
            transform: translateY(-50%) scale(1.1);
        }

        .slider-prev {
            left: -30px;
        }

        .slider-next {
            right: -30px;
        }

        .event-indicator {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .indicator-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--card-border);
            opacity: 0.5;
            transition: all 0.3s ease;
        }

        .indicator-dot.active {
            opacity: 1;
            transform: scale(1.2);
        }

        /* Add these new styles */
        .events-section {
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 36px;
            color: var(--header-color);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--accent-color);
        }

        /* Ensure the events grid fits within the container */
        .events-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem; /* Add some space between cards */
            justify-content: center; /* Center the cards */
            max-width: 100%; /* Ensure it doesn't overflow */
            padding: 0 1rem; /* Add padding to prevent overflow */
            box-sizing: border-box; /* Include padding in width calculation */
        }

        .grid-event-card {
            flex: 1 1 calc(33.333% - 2rem); /* Adjust width to fit three cards per row */
            max-width: calc(33.333% - 2rem); /* Ensure max width is set */
            box-sizing: border-box; /* Include padding and border in width calculation */
            border: none;
            padding: 2.5rem;  /* Increased padding */
            border-radius: 16px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
            min-height: 200px;
        }

        /* Add rainbow colors for cards */
        .grid-event-card:nth-child(1) { background: var(<?php echo $isDaytime ? '--dark-rainbow-1' : '--dark-rainbow-1' ?>); }
        .grid-event-card:nth-child(2) { background: var(<?php echo $isDaytime ? '--dark-rainbow-2' : '--dark-rainbow-2' ?>); }
        .grid-event-card:nth-child(3) { background: var(<?php echo $isDaytime ? '--dark-rainbow-3' : '--dark-rainbow-3' ?>); }
        .grid-event-card:nth-child(4) { background: var(<?php echo $isDaytime ? '--dark-rainbow-4' : '--dark-rainbow-4' ?>); }
        .grid-event-card:nth-child(5) { background: var(<?php echo $isDaytime ? '--dark-rainbow-5' : '--dark-rainbow-5' ?>); }
        .grid-event-card:nth-child(6) { background: var(<?php echo $isDaytime ? '--dark-rainbow-6' : '--dark-rainbow-6' ?>); }
        .grid-event-card:nth-child(7) { background: var(<?php echo $isDaytime ? '--dark-rainbow-7' : '--dark-rainbow-7' ?>); }
        .grid-event-card:nth-child(8) { background: var(<?php echo $isDaytime ? '--dark-rainbow-8' : '--dark-rainbow-8' ?>); }
        .grid-event-card:nth-child(9) { background: var(<?php echo $isDaytime ? '--dark-rainbow-9' : '--dark-rainbow-9' ?>); }
        .grid-event-card:nth-child(10) { background: var(<?php echo $isDaytime ? '--dark-rainbow-10' : '--dark-rainbow-10' ?>); }

        /* Update title styles */
        .grid-event-card .event-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            line-height: 1.2;
            color: <?php echo $isDaytime ? '#333333' : '#ffffff' ?>;
        }

        /* Update date styles */
        .grid-event-card .event-date {
            font-size: 1.8rem;
            margin: 1rem 0;
            font-weight: 500;
            color: <?php echo $isDaytime ? '#333333' : '#ffffff' ?>;
        }

        /* Update tag styles */
        .grid-event-card .event-tags {
            margin-top: 1.5rem;
        }

        .grid-event-card .tag {
            font-size: 1.2rem;
            padding: 0.5rem 1.2rem;
            margin: 0.3rem;
            border-radius: 8px;
            display: inline-block;
            background: rgba(255, 255, 255, <?php echo $isDaytime ? '0.5' : '0.15' ?>);
            color: <?php echo $isDaytime ? '#333333' : '#ffffff' ?>;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Enhanced hover effect */
        .grid-event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .today-events {
            margin-bottom: 4rem;
        }

        .today-event-card {
            background: var(--card-bg);
            border: none;
            padding: 2.5rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .today-event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        /* Update text colors for today's events */
        .today-event-card .event-title,
        .today-event-card .event-date {
            color: <?php echo $isDaytime ? '#333333' : '#ffffff' ?>;
            position: relative;
            z-index: 1;
        }

        /* Update tag styles for today's events */
        .today-event-card .tag {
            background: rgba(255, 255, 255, <?php echo $isDaytime ? '0.5' : '0.15' ?>);
            color: <?php echo $isDaytime ? '#333333' : '#ffffff' ?>;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 1.2rem;
            padding: 0.5rem 1.2rem;
            margin: 0.3rem;
            border-radius: 8px;
            position: relative;
            z-index: 1;
        }

        /* Add new gradient colors for today's events with fallback */
        .today-event-card:nth-child(1) {
            background: #ff9a9e; /* Fallback color */
            background: -webkit-linear-gradient(135deg, #ff9a9e, #fad0c4); /* For Safari 5.1 to 6.0 */
            background: -o-linear-gradient(135deg, #ff9a9e, #fad0c4); /* For Opera 11.1 to 12.0 */
            background: -moz-linear-gradient(135deg, #ff9a9e, #fad0c4); /* For Firefox 3.6 to 15 */
            background: linear-gradient(135deg, #ff9a9e, #fad0c4); /* Standard syntax */
        }

        .today-event-card:nth-child(2) {
            background: #a18cd1; /* Fallback color */
            background: -webkit-linear-gradient(135deg, #a18cd1, #fbc2eb);
            background: -o-linear-gradient(135deg, #a18cd1, #fbc2eb);
            background: -moz-linear-gradient(135deg, #a18cd1, #fbc2eb);
            background: linear-gradient(135deg, #a18cd1, #fbc2eb);
        }

        .today-event-card:nth-child(3) {
            background: #fbc2eb; /* Fallback color */
            background: -webkit-linear-gradient(135deg, #fbc2eb, #a6c1ee);
            background: -o-linear-gradient(135deg, #fbc2eb, #a6c1ee);
            background: -moz-linear-gradient(135deg, #fbc2eb, #a6c1ee);
            background: linear-gradient(135deg, #fbc2eb, #a6c1ee);
        }

        /* Add fallback colors for upcoming events */
        .grid-event-card:nth-child(1) { 
            background: #E8F5E9; /* Fallback color */
            background: var(<?php echo $isDaytime ? '--dark-rainbow-1' : '--dark-rainbow-1' ?>);
        }
        .grid-event-card:nth-child(2) { 
            background: #F3E5F5; /* Fallback color */
            background: var(<?php echo $isDaytime ? '--dark-rainbow-2' : '--dark-rainbow-2' ?>);
        }
        .grid-event-card:nth-child(3) { 
            background: #E3F2FD; /* Fallback color */
            background: var(<?php echo $isDaytime ? '--dark-rainbow-3' : '--dark-rainbow-3' ?>);
        }
        /* Continue for other positions */

        /* Update today's event card styles */
        .today-event-card {
            border: none;
            padding: 2.5rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        /* Update text colors for today's events */
        .today-event-card .event-title,
        .today-event-card .event-date {
            color: <?php echo $isDaytime ? '#333333' : '#ffffff' ?>;
            position: relative;
            z-index: 1;
        }

        /* Enhanced hover effect for today's events */
        .today-event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        /* Update tag styles for today's events */
        .today-event-card .tag {
            background: rgba(255, 255, 255, <?php echo $isDaytime ? '0.5' : '0.15' ?>);
            color: <?php echo $isDaytime ? '#333333' : '#ffffff' ?>;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 1.2rem;
            padding: 0.5rem 1.2rem;
            margin: 0.3rem;
            border-radius: 8px;
            position: relative;
            z-index: 1;
        }

        /* Update text colors for better contrast in dark mode */
        .dark-mode .today-event-card .event-title,
        .dark-mode .today-event-card .event-date,
        .dark-mode .today-event-card .event-description,
        .dark-mode .grid-event-card .event-title,
        .dark-mode .grid-event-card .event-date,
        .dark-mode .grid-event-card .event-description {
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* Update tag styles for dark mode */
        .dark-mode .grid-event-card .tag,
        .dark-mode .today-event-card .tag {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Add a subtle text outline for better readability */
        .dark-mode .event-title {
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        @media (max-width: 768px) {
            .grid-event-card {
                flex: 1 1 calc(50% - 2rem);
                max-width: calc(50% - 2rem);
            }
        }

        @media (max-width: 480px) {
            .grid-event-card {
                flex: 1 1 100%;
                max-width: 100%;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let currentSlide = 0;
            const slides = document.querySelectorAll('.evening-event-card');
            const dots = document.querySelectorAll('.indicator-dot');
            
            function updateSlider() {
                slides.forEach((slide, index) => {
                    slide.style.display = index === currentSlide ? 'flex' : 'none';
                });
                
                dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === currentSlide);
                });
                
                // Update arrow visibility
                document.querySelector('.slider-prev').style.display = currentSlide > 0 ? 'flex' : 'none';
                document.querySelector('.slider-next').style.display = currentSlide < slides.length - 1 ? 'flex' : 'none';
            }
            
            document.querySelector('.slider-prev').addEventListener('click', () => {
                if (currentSlide > 0) {
                    currentSlide--;
                    updateSlider();
                }
            });
            
            document.querySelector('.slider-next').addEventListener('click', () => {
                if (currentSlide < slides.length - 1) {
                    currentSlide++;
                    updateSlider();
                }
            });
            
            // Initialize
            updateSlider();
        });
    </script>
</head>

<body>
    <div class="container">
        <header class="page-header">
            <!-- <div class="logo">
                <span class="logo-text">BL</span>
            </div> -->
            <div class="header-text">
                <h1>Basislager Events</h1>
                <p class="subtitle">Today's Events at Basislager Leipzig</p>
            </div>
        </header>

        <main id="events-container">
            <?php if (empty($events)): ?>
                <div class="event-card">
                    <h2 class="event-title">No upcoming events</h2>
                    <p class="event-description">Check back later for new events.</p>
                </div>
            <?php else: ?>
                <?php if (!empty($todayEvents)): ?>
                    <section class="events-section today-events">
                        <h2 class="section-title">Today's Events</h2>
                        <?php foreach ($todayEvents as $event): ?>
                            <div class="today-event-card">
                                <h2 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                                <div class="event-date"><?php echo htmlspecialchars($event['date']); ?></div>
                                <p class="event-description">
                                    <?php 
                                        $description = htmlspecialchars($event['description']);
                                    
                                    // Remove emojis from text
                                    $description = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $description);
                                    $description = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $description);
                                    $description = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $description);
                                    $description = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $description);
                                    $description = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $description);
                                    
                                    // Apply bold formatting
                                        $description = preg_replace('/\*\*(.*?)\*\*/u', '<strong>$1</strong>', $description);
                                    
                                    // Clean up any double spaces that might be left after emoji removal
                                    $description = preg_replace('/\s+/', ' ', trim($description));
                                    
                                        echo $description;
                                    ?>
                                </p>
                                <div class="event-tags">
                                    <?php foreach ($event['tags'] as $tag): ?>
                                        <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

                <?php
                // Filter out today's events from the main list
                $futureEvents = array_filter($events, function ($event) use ($todayEvents) {
                    foreach ($todayEvents as $todayEvent) {
                        if ($event['title'] === $todayEvent['title']) {
                            return false;
                        }
                    }
                    return true;
                });
                
                if (!empty($futureEvents)): 
                ?>
                    <section class="events-section">
                        <h2 class="section-title">Upcoming Events</h2>
                        <div class="events-grid">
                            <?php foreach ($futureEvents as $event): ?>
                                <div class="grid-event-card">
                                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <div class="event-date"><?php echo htmlspecialchars($event['date']); ?></div>
                                    <!-- <p class="event-description">
                                        <?php 
                                            $description = htmlspecialchars($event['description']);
                                        
                                        // Remove emojis from text
                                        $description = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $description);
                                        $description = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $description);
                                        $description = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $description);
                                        $description = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $description);
                                        $description = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $description);
                                        
                                        // Apply bold formatting
                                                $description = preg_replace('/\*\*(.*?)\*\*/u', '<strong>$1</strong>', $description);
                                        
                                        // Clean up any double spaces that might be left after emoji removal
                                        $description = preg_replace('/\s+/', ' ', trim($description));
                                        
                                                echo $description;
                                            ?>
                                    </p> -->
                            <div class="event-tags">
                                <?php foreach ($event['tags'] as $tag): ?>
                                    <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>