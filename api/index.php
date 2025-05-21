<?php
// Log to file
debug_log("Script started");

function debug_log($msg) {
    // file_put_contents("debug.log", date("Y-m-d H:i:s") . " - " . $msg . "\n", FILE_APPEND);
}

function getEvents() {
    $url = 'https://basislagerleipzig.spaces.nexudus.com/en/events?page=page&_depth=3&pastEvents=pastEvents';

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer 6zya-_5VgbgVHoZTAAAQO8PRb0w2eK9TZINr69Xf-GiTt7MwJOd-gubY5NA0WKgb2R7obCoNBPZcIcF56psa1DTPCQxo1nQyWdCOVkf_bQJw0RApZQWOWcO2txPYt1crOhOu9Swbzyx3ryvhWY1uaw1M2XT-BdjMxv5NmeUmu-7ju7lhYVO_iLbfaEkpoT4MleSbEKgmvHQqV0QaZcdtmBRSqEQa-Giyl0Zy6p95wPtXLAaCqOh8Isckzn5MDeLfiYO3qz_fDvz7JEQcn3-DWIOKAgj0649kNG3aBKGvsynMeuo7pwr-f7QwC8aN5KEjK1-TexFIhaQ1AJgPXdeiYmBjcEk'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);

    if ($result === false) {
        debug_log("CURL error: " . curl_error($ch));
    } else {
        debug_log("API data retrieved");
    }

    curl_close($ch);
    $json = json_decode($result, true);

    if ($json === null) {
        debug_log("JSON decode error. Raw data:\n" . $result);
    }

    return $json;
}

function filterEvents($events, $targetDate) {
    $filtered = [];
    foreach ($events as $event) {
        $eventDate = date("Y-m-d", strtotime($event['StartDate']));
        if ($eventDate === $targetDate) {
            $filtered[] = $event;
        }
    }
    return $filtered;
}

function printEvents($title, $events) {
    if (!empty($title)) echo "<h2>$title</h2>";
    $now = time();
    foreach ($events as $event) {
        $startTime = strtotime($event['StartDate']);
        $showArrow = ($startTime - $now) <= 3600 && ($startTime - $now) >= 0;

        // Markdown-style bold to <strong>
        $desc = htmlspecialchars($event['ShortDescription']);
        $desc = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $desc);

        echo "<div class='event-card'>";
        if ($showArrow) {
            echo "<div class='arrow'>&lArr;</div>";
        }
        echo "<div class='event-content'>";
        echo "<h3>" . htmlspecialchars($event['Name']) . "</h3>";
        echo "<p class='description'>$desc</p>";
        echo "<p class='date'><strong>Date:</strong> " . date("d.m.Y H:i", $startTime) . "</p>";
        echo "</div></div>";
    }
}

$today = date("Y-m-d");
$tomorrow = date("Y-m-d", strtotime("+1 day"));

$data = getEvents();
$allEvents = isset($data['CalendarEvents']) ? $data['CalendarEvents'] : [];
debug_log("Total events: " . count($allEvents));

$todaysEvents = filterEvents($allEvents, $today);
$tomorrowsEvents = filterEvents($allEvents, $tomorrow);

$hour = date("H");
$theme = ($hour >= 19 || $hour < 7) ? "dark" : "light";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Events</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1080px;
            margin: 0 auto;
            padding: 40px 20px;
            background-color: <?= $theme === "dark" ? "#121212" : "#fefefe" ?>;
            color: <?= $theme === "dark" ? "#f0f0f0" : "#101010" ?>;
        }
        h2 {
            font-size: 36px;
            margin-bottom: 40px;
            border-left: 5px solid <?= $theme === "dark" ? "#00ffaa" : "#007acc" ?>;
            padding-left: 16px;
        }
        .event-card {
            display: flex;
            margin-bottom: 50px;
            border-radius: 20px;
            overflow: hidden;
            background: <?= $theme === "dark" ? "linear-gradient(135deg, #1f1f1f, #333)" : "linear-gradient(135deg, #ffffff, #e6f7ff)" ?>;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        .event-card:hover {
            transform: scale(1.02);
        }
        .arrow {
            width: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: <?= $theme === "dark" ? "#282828" : "#cdeaff" ?>;
            font-size: 40px;
            color: <?= $theme === "dark" ? "#00ffaa" : "#007acc" ?>;
            animation: blink 1.2s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .event-content {
            padding: 30px;
            flex: 1;
        }
        .event-content h3 {
            font-size: 28px;
            margin: 0 0 20px;
        }
        .description {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .date {
            font-size: 16px;
            font-weight: bold;
            color: <?= $theme === "dark" ? "#bbb" : "#333" ?>;
        }
    </style>
</head>
<body>
<?php
if (count($todaysEvents) > 0) {
    printEvents("Today's Events", $todaysEvents);
} elseif (count($tomorrowsEvents) > 0) {
    printEvents("Tomorrow's Events", $tomorrowsEvents);
} elseif (count($allEvents) > 0) {
    printEvents("Upcoming Events", array_slice($allEvents, 0, 5));
} else {
    echo "<h2>No events found.</h2>";
    debug_log("No events displayed.");
}
?>
</body>
</html>