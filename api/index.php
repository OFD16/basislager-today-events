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
    $eventElements = $xpath->query("//li[contains(@class, 'flex') and contains(@class, 'flex-col')]");

    $events = [];
    foreach ($eventElements as $index => $element) {
        $dateTimeNode = $xpath->query(".//span[contains(@class, 'text-fs9')]", $element)->item(0);
        $titleNode = $xpath->query(".//span[contains(@class, 'text-fs7')]", $element)->item(0);
        $descNode = $xpath->query(".//span[contains(@class, 'text-fs7')]", $element)->item(1);
        $tagsNode = $xpath->query(".//span[contains(@class, 'text-fs9')]", $element)->item(1);

        $dateTimeText = $dateTimeNode ? trim($dateTimeNode->textContent) : '';
        preg_match('/(\d{2}\.\d{2}\.\d{4}),\s*(\d{1,2})\s*Uhr/', $dateTimeText, $matches);

        if ($matches) {
            list(, $date, $time) = $matches;
            $title = $titleNode ? trim($titleNode->textContent) : '';
            $desc = $descNode ? trim($descNode->textContent) : '';
            $tags = $tagsNode ? array_map('trim', explode(',', trim($tagsNode->textContent))) : [];

            $events[] = [
                'id' => $index + 1,
                'title' => $title,
                'description' => $desc,
                'date' => "$time Uhr, $date",
                'place' => 'Basislager Leipzig',
                'tags' => $tags
            ];
        }
    }

    return filterTodayEvents($events);
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
$events = fetchEvents();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basislager Events</title>
    <link rel="stylesheet" href="styles.css">

    <link rel="stylesheet" href="styles.css"> <!-- Link to external CSS -->
    <style>
        /* Basic styling for old browsers */
        body {
            font-family: Arial, sans-serif;
            background-color: #1A1D21;
            color: #C8C8C8;
            margin: 20px;
            padding: 20px;
        }

        h1 {
            font-size: 24px;
            text-align: center;
            color: #00FF7F;
        }

        h2 {
            font-size: 20px;
            margin-top: 20px;
            color: #FFF4E4;
            border-bottom: 2px solid #00FF7F;
            padding-bottom: 5px;
        }

        p {
            font-size: 14px;
            line-height: 1.6;
        }

        .event {
            background: #23272B;
            border: 1px solid #00FF7F;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            box-shadow: 2px 2px 5px rgba(0, 255, 127, 0.2);
        }

        .event time {
            font-weight: bold;
            color: #FFF4E4;
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="page-header">
            <div class="logo">
                <span class="logo-text">BL</span>
            </div>
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
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <h2 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                        <div class="event-date"><?php echo htmlspecialchars($event['date']); ?></div>
                        <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                        <div class="event-tags">
                            <?php foreach ($event['tags'] as $tag): ?>
                                <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>