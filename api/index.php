<?php
function filterEventsByDate($events, $date)
{
  return array_filter($events, fn($event) => substr($event['StartDate'], 0, 10) === $date);
}

function fetchEventsFromAPI()
{
  $url = 'https://basislagerleipzig.spaces.nexudus.com/en/events?page=page&_depth=3&pastEvents=pastEvents';
  $headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    // 'Authorization: Bearer asdasdas'
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

function extractRaum($html)
{
  if (preg_match('/Raum:\s*([^<\n]+)/i', $html, $match)) {
    return trim($match[1]);
  }
  return '';
}

function getThemeModeTest()
{
  $hour = date("H");
  return ($hour >= 19 || $hour < 7) ? "dark" : "light";
  // return "dark"; // Test için her zaman dark modu kullan
}

function renderEventCard($event, $theme)
{
  $short = $event['ShortDescription'] ?? '';
  $long = $event['LongDescription'] ?? '';
  $raum = extractRaum($long);
  $startTime = strtotime($event['StartDate']);
  $hourLabel = date("H:i", $startTime);
  $timeDiff = $startTime - time();
  $showArrow = $timeDiff <= 7200 && $timeDiff > 0;

  $resource_name = $event['Resource']['Name'] ?? '';
  if (stripos($resource_name, 'Kilimanjaro') !== false) {
    $raum = 'Kilimanjaro';
  } elseif (stripos($resource_name, 'Olymp') !== false) {
    $raum = 'Olymp';
  } elseif (stripos($resource_name, 'Media Hub') !== false) {
    $raum = 'Media Hub';
  } else {
    $raum = 'Unknown';
  }

  $raumClass = '';
  $arrowEmoji = '';
  if (stripos($raum, 'Kilimanjaro') !== false) {
    $raumClass = 'kilimanjaro';
    $arrowEmoji = '↖'; // :arrow_upper_left:
  } elseif (stripos($raum, 'Olymp') !== false) {
    $raumClass = 'olymp';
    $arrowEmoji = '←'; // :arrow_left:
  } elseif (stripos($raum, 'Media Hub') !== false) {
    $raumClass = 'mediahub';
    $arrowEmoji = '⤵'; // :arrow_heading_down:
  }

  $arrowImage = ''; // default boş
  if (stripos($raum, 'Kilimanjaro') !== false) {
    $raumClass = 'kilimanjaro';
    $arrowImage = 'images/arrow-top-left.png';
  } elseif (stripos($raum, 'Olymp') !== false) {
    $raumClass = 'olymp';
    $arrowImage = 'images/arrow-left.png';
  } elseif (stripos($raum, 'Media Hub') !== false) {
    $raumClass = 'mediahub';
    $arrowImage = 'images/arrow-bottom-right.png';
  }


  echo "<div class='timeline-row'>";
  // echo "<div class='timeline-time'>$hourLabel</div>";
  echo "<div class='timeline-time'>";
  echo "$hourLabel";
  if (!empty($arrowImage)) {
    echo "<div class='arrow-image'>";
    echo "<img src='$arrowImage' alt='direction arrow' />";
    echo "</div>";
  }

  echo "</div>";

  echo "<div class='event-card $theme'>";
  echo "<div class='event-content'>";
  echo "<h3>" . htmlspecialchars($event['Name']) . "</h3>";
  echo "<p class='description'>$short</p>";

  echo "<div class='raum-label $raumClass'>";
  echo "<strong>$raum</strong>";
  // if ($showArrow)
  //   echo "<span class='arrow-pulse'></span>";
  echo "</div>";

  // if (true)
  //     echo "<div class='long-description'>$long</div>";

  echo "</div></div></div>";
}


// Ana Akış
$theme = getThemeModeTest();
$events = fetchEventsFromAPI();
$today = date("Y-m-d");
$todaysEvents = filterEventsByDate($events, $today);
$displayEvents = $todaysEvents ?: $events;
$title = "Upcoming Events";

$kilimanjaroAlert = false;
$olympAlert = false;
$mediahubAlert = false;

foreach ($displayEvents as $event) {
  $start = strtotime($event['StartDate']);
  $timeLeft = $start - time();
  if ($timeLeft < 7200 && $timeLeft > 0) {
    if (stripos($raum, 'kilimanjaro') !== false)
      $kilimanjaroAlert = true;
    if (stripos($raum, 'olymp') !== false)
      $olympAlert = true;
    if (stripos($raum, 'mediahub') !== false)
      $mediahubAlert = true;
  }
}

// Arka plana rastgele brush görselleri yerleştirme
function renderBrushSplats($count = 4)
{
  global $theme;
  $src = $theme === "dark" ? "images/brush.png" : "images/brush.png"; // fark varsa ayrı seçebilirsin

  for ($i = 0; $i < $count; $i++) {
    $top = rand(0, 80);     // % olarak yukarıdan
    $left = rand(0, 80);    // % olarak soldan
    $size = rand(200, 500); // px
    $rotate = rand(0, 360); // derece

    echo "<div class='brush-splat' style='
      top: {$top}%;
      left: {$left}%;
      width: {$size}px;
      height: {$size}px;
      transform: rotate({$rotate}deg);
      background-image: url($src);
    '></div>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Basislager Events</title>
  <link rel="stylesheet" href="styles/fonts.css">
  <link rel="stylesheet" href="styles.css">
  <style>
    @font-face {
      font-family: 'Founders Grotesk';
      src: url('fonts/FoundersGrotesk-Regular.woff2') format('woff2'),
        url('fonts/FoundersGrotesk-Regular.woff') format('woff');
      font-weight: normal;
      font-style: normal;
    }

    @font-face {
      font-family: 'Founders Grotesk';
      src: url('fonts/FoundersGrotesk-Bold.woff2') format('woff2'),
        url('fonts/FoundersGrotesk-Bold.woff') format('woff');
      font-weight: bold;
      font-style: normal;
    }

    body {
      font-family: 'Founders Grotesk', 'Work Sans', sans-serif;
      background:
        <?= $theme === "dark" ? "#1e1e2f" : "#26D07C" ?>
      ;
      /* background-color: #26d07c; */
      /* background-image: url("data:image/svg+xml,%3Csvg width='64' height='64' viewBox='0 0 64 64' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M8 16c4.418 0 8-3.582 8-8s-3.582-8-8-8-8 3.582-8 8 3.582 8 8 8zm0-2c3.314 0 6-2.686 6-6s-2.686-6-6-6-6 2.686-6 6 2.686 6 6 6zm33.414-6l5.95-5.95L45.95.636 40 6.586 34.05.636 32.636 2.05 38.586 8l-5.95 5.95 1.414 1.414L40 9.414l5.95 5.95 1.414-1.414L41.414 8zM40 48c4.418 0 8-3.582 8-8s-3.582-8-8-8-8 3.582-8 8 3.582 8 8 8zm0-2c3.314 0 6-2.686 6-6s-2.686-6-6-6-6 2.686-6 6 2.686 6 6 6zM9.414 40l5.95-5.95-1.414-1.414L8 38.586l-5.95-5.95L.636 34.05 6.586 40l-5.95 5.95 1.414 1.414L8 41.414l5.95 5.95 1.414-1.414L9.414 40z' fill='%23000000' fill-opacity='1' fill-rule='evenodd'/%3E%3C/svg%3E"); */
      color:
        <?= $theme === "dark" ? "#f0f0f0" : "#222" ?>
      ;

      margin: 0;
      padding: 40px 40px;
      /* Yüksek sağ-sol padding */
      max-width: 1080px;
      margin: auto;
      font-size: 26px;
    }

    .brush-splat {
      position: fixed;
      background-image: url('images/brush.png');
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
      opacity: 1;
      pointer-events: none;
      z-index: -1;
      filter:
        <?= $theme === "dark" ? 'invert(1)' : 'none' ?>
      ;
    }


    .timeline-time {
      width: 110px;
      text-align: center;
      font-weight: bold;
      color:
        <?= $theme === "dark" ? "#aaa" : "#222" ?>
      ;
    }

    .timeline-time .hour {
      font-size: 36px;
      margin-bottom: 10px;
    }

    .arrow-image img {
      width: 120px;
      height: 120px;
      object-fit: contain;
      opacity: 0.9;
      filter:
        <?= $theme === "dark" ? 'invert(1)' : 'none' ?>
      ;
    }


    h2 {
      font-size: 56px;
      margin-bottom: 30px;
      border-left: 6px solid
        <?= $theme === "dark" ? "#00ffaa" : "#333" ?>
      ;
      padding-left: 16px;
    }

    .timeline-row {
      display: flex;
      align-items: flex-start;
      margin-bottom: 40px;
    }

    .timeline-time {
      width: 110px;
      font-size: 36px;
      font-weight: bold;
      padding-top: 15px;
      color:
        <?= $theme === "dark" ? "#f0f0f0" : "#222" ?>
      ;
      padding-right: 30px;
      /* BOX'tan uzaklaştırma */
      text-align: right;
    }

    .event-card {
      flex: 1;
      background-color:
        <?= $theme === "dark" ? "#2c2c3e" : "#fff" ?>
      ;
      border: 1px solid
        <?= $theme === "dark" ? "#444" : "#E0E0E0" ?>
      ;
      border-radius: 20px;
      box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
      padding: 24px;
      margin-left: 0;
      /* Zaten padding sağlandı */
    }

    .event-content h3 {
      font-size: 42px;
      margin-top: 0;
    }

    .description {
      font-size: 28px;
      margin: 16px 0;
    }

    .raum-label {
      display: inline-flex;
      align-items: center;
      gap: 14px;
      padding: 10px 18px;
      border-radius: 12px;
      font-size: 24px;
      margin-bottom: 16px;
      font-weight: bold;
    }

    .kilimanjaro {
      background-color: #ffd166;
      color: #000;
    }

    .olymp {
      background-color: #06d6a0;
      color: #000;
    }

    .mediahub {
      background-color: #ef476f;
      color: #fff;
    }

    .arrow-pulse {
      display: inline-block;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background-color: red;
      animation: pulse 1s infinite ease-in-out;
    }

    @keyframes pulse {
      0% {
        transform: scale(1);
        opacity: 1;
      }

      50% {
        transform: scale(1.5);
        opacity: 0.5;
      }

      100% {
        transform: scale(1);
        opacity: 1;
      }
    }
  </style>

</head>

<body>
  <!-- Brush Splats - Sabit konumlandırılmış -->
  <div class="brush-splat" style="top: 2%; right: 0%; width: 300px; height: 300px; transform: rotate(15deg);"></div>
  <div class="brush-splat" style="top: 10%; right: 0%; width: 400px; height: 400px; transform: rotate(-30deg);"></div>
  <div class="brush-splat" style="bottom: 0%; left: 20%; width: 500px; height: 500px; transform: rotate(180deg);"></div>
  <div class="brush-splat" style="bottom: 8%; right: 10%; width: 350px; height: 350px; transform: rotate(80deg);">
  </div>


  <h2><?= $title ?></h2>

  <?php foreach ($displayEvents as $event) {
    renderEventCard($event, $theme);
  } ?>

  <footer style="
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    text-align: center;
    font-size: 20px;
    padding: 10px 0;
    background-color: transparent;
    color: <?= $theme === 'dark' ? '#fff' : '#222' ?>;
  ">
    Developed by Ömer Faruk Demirsoy
  </footer>


</body>

</html>