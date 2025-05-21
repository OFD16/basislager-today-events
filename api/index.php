<?php

function filterEventsByDate($events, $date)
{
    return array_filter($events, function ($event) use ($date) {
        return substr($event['StartDate'], 0, 10) === $date;
    });
}
function fetchEventsFromAPI()
{
    $url = 'https://basislagerleipzig.spaces.nexudus.com/en/events?page=page&_depth=3&pastEvents=pastEvents';
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer [senin-tokenin-buraya]'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($result, true);
    return $json['CalendarEvents'] ?? [];
}
function getThemeModeTest()
{
    // 30 saniyede bir light / dark şeklinde değiştir
    // return (date("s") % 60) < 30 ? "light" : "dark";
    $hour = date("H");
    return ($hour >= 19 || $hour < 7) ? "dark" : "light";
}
$theme = getThemeModeTest();

function extractRaum($html)
{
    if (preg_match('/Raum:\s*([^<\n]+)/i', $html, $match)) {
        return trim($match[1]);
    }
    return '';
}

function truncateAndClean($text, $limit = 200)
{
    // Markdown-style bold: **text** -> <strong>text</strong>
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);

    // Temizlik ama <strong> tagini koruyarak
    $text = strip_tags($text, '<strong>');

    // Truncate işlemi (HTML-aware truncation yapmıyoruz şimdilik)
    return mb_strimwidth($text, 0, $limit, "...");
}


function renderEventCard($event, $theme, $isUpcoming = false)
{
    $startTime = strtotime($event['StartDate']);
    $hourLabel = date("H:i", $startTime);
    $raum = extractRaum($event['LongDescription']);
    $short = truncateAndClean($event['ShortDescription']);
    $long = strip_tags($event['LongDescription'], '<p><a><strong><br>');

    $showArrow = ($startTime - time()) <= 3600 && ($startTime - time()) >= 0;
    echo "<div class='timeline-row'>";
    echo "<div class='timeline-time'>$hourLabel</div>";
    echo "<div class='event-card $theme'>";
    // if ($showArrow) {
    //     echo "<div class='arrow'><img src='https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/Feather-arrows-chevrons-left.svg/1280px-Feather-arrows-chevrons-left.svg.png' alt='arrow' /></div>";
    // }
    echo "<div class='event-content'>";
    echo "<h3>" . htmlspecialchars($event['Name']) . "</h3>";
    echo "<p class='description'>$short</p>";
    if ($raum)
        echo "<div class='raum-label'><strong>Room:</strong> $raum</div>";
    if ($showArrow)
        echo "<div class='long-description'>$long</div>";
    echo "</div></div></div>";
}

// dummy data
$events = fetchEventsFromAPI();


$today = date("Y-m-d");
$tomorrow = date("Y-m-d", timestamp: strtotime("+1 day"));
$todaysEvents = filterEventsByDate($events, $today);
$tomorrowsEvents = filterEventsByDate($events, $tomorrow);
$displayEvents = [];
$title = "Upcoming Events";

if (count($todaysEvents) > 0) {
    $displayEvents = $todaysEvents;
    $title = "Today's Events";
} elseif (count($tomorrowsEvents) > 0) {
    $displayEvents = $tomorrowsEvents;
    $title = "Tomorrow's Events";
} else {
    $displayEvents = $events;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <!-- <meta http-equiv="refresh" content="5"> -->
    <title>Basislager Events</title>
    <link href="https://fonts.googleapis.com/css2?family=Founders+Grotesk&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Founders Grotesk', sans-serif, "Segoe UI Emoji", "Segoe UI Symbol";
            background:
                <?= $theme === "dark" ? "#101820" : "#26D07C" ?>
            ;
            color:
                <?= $theme === "dark" ? "#eeeeee" : "#111111" ?>
            ;
            margin: 0;
            padding: 40px 20px;
            max-width: 1080px;
            margin: auto;
        }

        h2 {
            font-size: 36px;
            margin-bottom: 30px;
            border-left: 6px solid
                <?= $theme === "dark" ? "#00ffaa" : "#ffffff" ?>
            ;
            padding-left: 16px;
        }

        .timeline-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 40px;
        }

        .timeline-time {
            width: 80px;
            font-size: 20px;
            font-weight: bold;
            color:
                <?= $theme === "dark" ? "#aaa" : "#222" ?>
            ;
            padding-top: 15px;
        }

        .event-card {
            flex: 1;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            background:
                <?= $theme === "dark" ? "linear-gradient(135deg, #1f1f1f, #2b4d50)" : "linear-gradient(135deg, #ffffff, #a8f1eb)" ?>
            ;
            transition: transform 0.3s ease;
            display: flex;
        }

        .event-card:hover {
            transform: scale(1.01);
        }

        .arrow {
            width: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color:
                <?= $theme === "dark" ? "#28585c" : "#ffffff" ?>
            ;
            animation: blink 1.2s infinite;
        }

        .arrow img {
            width: 40px;
            height: auto;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        .event-content {
            padding: 20px 25px;
            flex: 1;
        }

        .event-content h3 {
            font-size: 24px;
            margin-top: 0;
        }

        .description {
            font-size: 18px;
            margin: 10px 0;
        }

        .raum-label {
            background-color:
                <?= $theme === "dark" ? "#00ffaa" : "#163237" ?>
            ;
            color:
                <?= $theme === "dark" ? "#163237" : "#ffffff" ?>
            ;
            display: inline-block;
            padding: 4px 12px;
            border-radius: 10px;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .long-description {
            font-size: 16px;
            opacity: 0.85;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <h2><?= $title ?></h2>
    <?php foreach ($displayEvents as $event)
        renderEventCard($event, $theme); ?>
</body>

</html>