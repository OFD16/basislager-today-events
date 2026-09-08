<?php
/**
 * Basislager Leipzig - Event Board
 * Dikey (portrait) duvar ekrani icin kiosk gorunumu.
 *
 * ESKI TARAYICI NOTU: ekran eski bir TV, tarayicisi eski surum.
 * Bu yuzden bilerek kullanilmayanlar:
 *   - CSS degiskenleri var(--x)   -> temalar PHP'den duz CSS olarak basiliyor
 *   - flex `gap` (Chrome 84+)     -> margin
 *   - `inset` kisayolu (Chrome 87+) -> top/right/bottom/left
 *   - ES6 (let/const/arrow/backtick) -> JS tamamen ES5
 *   - CSS animasyonuna bagli kaydirma -> scrollTop ile JS'ten suruluyor
 * Animasyonlarin -webkit- karsiliklari da basiliyor.
 */

date_default_timezone_set('Europe/Berlin');

// Sag alttaki tema onizleme butonu. Prod'da false kalmali.
const SHOW_TEST_PANEL = false;

/* --------------------------------------------------------------------------
 * ?debug=1  -> TV'nin tarayicisini olcen teshis sayfasi.
 * Kendisi test ettigi hicbir ozelligi KULLANMAZ (px, flex yok, animasyon yok)
 * ki en eski tarayicida bile acilsin. Telefonla fotograflayip bak.
 * ------------------------------------------------------------------------ */
if (isset($_GET['debug'])) {
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Board check</title>';
  echo '<style>body{background:#fff;color:#000;font-family:Arial,sans-serif;font-size:22px;margin:0;padding:20px}';
  echo 'h2{font-size:30px;margin:24px 0 8px}code{font-size:20px;word-wrap:break-word}';
  echo 'table{border-collapse:collapse;width:100%}td{border-bottom:1px solid #ccc;padding:8px 4px;font-size:24px}';
  echo '.y{color:#0a0;font-weight:bold}.n{color:#c00;font-weight:bold}</style></head><body>';
  echo '<h2>User agent</h2><code id="ua"></code>';
  echo '<h2>Screen</h2><code id="scr"></code>';
  echo '<h2>Features</h2><table id="t"></table>';
  echo '<script>',
    'document.getElementById("ua").innerHTML=navigator.userAgent;',
    'document.getElementById("scr").innerHTML=screen.width+"x"+screen.height+" | window "+',
      '(window.innerWidth||document.documentElement.clientWidth)+"x"+',
      '(window.innerHeight||document.documentElement.clientHeight)+" | dpr "+(window.devicePixelRatio||1);',
    'var d=document.createElement("div");',
    'function css(p,v){try{d.style[p]=v;}catch(e){return false;}return d.style[p]!="" && d.style[p]!=null;}',
    'function prop(p){return p in d.style;}',
    // vmin gercekten uyguluyor mu: olcerek dogrula
    'var probe=document.createElement("div");probe.style.cssText="width:10vmin;height:5px";',
    'document.body.appendChild(probe);var vminPx=probe.offsetWidth;',
    'var rows=[',
      '["vmin/vh birimleri (DUZEN ICIN KRITIK)", vminPx>12],',
      '["  -> olculen 10vmin", vminPx+"px"],',
      '["flexbox (display:flex) (DUZEN ICIN KRITIK)", css("display","flex")],',
      '["eski flexbox (-webkit-box)", css("display","-webkit-box")],',
      '["CSS animation", prop("animation")||prop("webkitAnimation")],',
      '["CSS transform", prop("transform")||prop("webkitTransform")],',
      '["CSS filter (invert)", prop("filter")||prop("webkitFilter")],',
      '["border-radius", prop("borderRadius")],',
      '["rgba() renk", css("color","rgba(0,0,0,.5)")],',
      '["linear-gradient", css("backgroundImage","linear-gradient(#000,#fff)")||css("backgroundImage","-webkit-linear-gradient(#000,#fff)")],',
      '["object-fit (ok gorselleri)", prop("objectFit")],',
      '["mask-image (kenar yumusatma)", prop("maskImage")||prop("webkitMaskImage")],',
      '["CSS degiskeni var(--x)", !!(window.CSS&&CSS.supports&&CSS.supports("--a","0"))],',
      '["flex gap", css("gap","1px")],',
      '["JS: querySelector", !!document.querySelector],',
      '["JS: addEventListener", !!window.addEventListener],',
      '["JS: JSON", !!window.JSON],',
      '["JS: classList", !!d.classList],',
      '["JS: Array.forEach", !!Array.prototype.forEach],',
      '["JS: el.hidden", ("hidden" in d)]',
    '];',
    'var h="";for(var i=0;i<rows.length;i++){var v=rows[i][1];var txt=(v===true)?"<span class=\'y\'>VAR</span>":((v===false)?"<span class=\'n\'>YOK</span>":v);',
    'h+="<tr><td>"+rows[i][0]+"</td><td>"+txt+"</td></tr>";}',
    'document.getElementById("t").innerHTML=h;',
    'probe.parentNode.removeChild(probe);',
  '</script></body></html>';
  exit;
}

/* --------------------------------------------------------------------------
 * Odalar - tek kaynak. Ok gorselleri ve renkler burada.
 * ------------------------------------------------------------------------ */
const ROOMS = [
  'Kilimanjaro' => ['arrow' => '/images/arrow-top-left.png',     'dir' => 'tl', 'color' => '#FFC94A'],
  'Olymp'       => ['arrow' => '/images/arrow-left.png',         'dir' => 'l',  'color' => '#4CC9F0'],
  'Media Hub'   => ['arrow' => '/images/arrow-bottom-right.png', 'dir' => 'br', 'color' => '#FF5D8F'],
];

/* --------------------------------------------------------------------------
 * Temalar. 'dark' => koyu zemin, ok/firca gorselleri ters cevrilir.
 * 'splat' => arka plandaki firca lekelerinin gorunurlugu.
 * ------------------------------------------------------------------------ */
const THEMES = [
  'day'       => ['label' => 'Normal (day)',              'bg' => '#26D07C', 'bg2' => '#12B368', 'fg' => '#08170F', 'muted' => 'rgba(8,23,15,.68)',     'card' => '#FFFFFF', 'line' => 'rgba(0,0,0,.10)',       'accent' => '#08170F', 'dark' => false, 'splat' => '.26'],
  'night'     => ['label' => 'Normal (night)',            'bg' => '#10101A', 'bg2' => '#20203A', 'fg' => '#F2F3F7', 'muted' => 'rgba(242,243,247,.62)', 'card' => '#1D1D2B', 'line' => 'rgba(255,255,255,.13)', 'accent' => '#26D07C', 'dark' => true,  'splat' => '.20'],
  'pride'     => ['label' => 'Pride / CSD Leipzig',       'bg' => '#14121A', 'bg2' => '#241E33', 'fg' => '#FFFFFF', 'muted' => 'rgba(255,255,255,.64)', 'card' => '#201D2B', 'line' => 'rgba(255,255,255,.14)', 'accent' => '#FF4D6D', 'dark' => true,  'splat' => '.18'],
  'xmas'      => ['label' => 'Weihnachten',               'bg' => '#0C3B2E', 'bg2' => '#08281F', 'fg' => '#F6FFF9', 'muted' => 'rgba(246,255,249,.64)', 'card' => '#10513E', 'line' => 'rgba(255,255,255,.14)', 'accent' => '#E63946', 'dark' => true,  'splat' => '.20'],
  'newyear'   => ['label' => 'Silvester',                 'bg' => '#0A1330', 'bg2' => '#16224C', 'fg' => '#FFF7E0', 'muted' => 'rgba(255,247,224,.62)', 'card' => '#131E45', 'line' => 'rgba(255,214,90,.22)',  'accent' => '#FFD65A', 'dark' => true,  'splat' => '.18'],
  'valentine' => ['label' => 'Valentinstag',              'bg' => '#FFD1DE', 'bg2' => '#FFA9C4', 'fg' => '#46101F', 'muted' => 'rgba(70,16,31,.66)',    'card' => '#FFFFFF', 'line' => 'rgba(70,16,31,.12)',    'accent' => '#E8336D', 'dark' => false, 'splat' => '.22'],
  'easter'    => ['label' => 'Ostern',                    'bg' => '#CFF3E1', 'bg2' => '#FFF0B8', 'fg' => '#17352A', 'muted' => 'rgba(23,53,42,.66)',    'card' => '#FFFFFF', 'line' => 'rgba(23,53,42,.12)',    'accent' => '#E07A2F', 'dark' => false, 'splat' => '.22'],
  'unity'     => ['label' => 'Tag der Deutschen Einheit', 'bg' => '#131313', 'bg2' => '#2A0A0A', 'fg' => '#FFFFFF', 'muted' => 'rgba(255,255,255,.62)', 'card' => '#232323', 'line' => 'rgba(255,206,0,.24)',   'accent' => '#FFCE00', 'dark' => true,  'splat' => '.22'],
  'lichtfest' => ['label' => 'Lichtfest Leipzig',         'bg' => '#1A1206', 'bg2' => '#3A280C', 'fg' => '#FFEBC2', 'muted' => 'rgba(255,235,194,.62)', 'card' => '#2C1F0C', 'line' => 'rgba(255,190,90,.24)',  'accent' => '#FFB44D', 'dark' => true,  'splat' => '.22'],
  'wgt'       => ['label' => 'Wave-Gotik-Treffen',        'bg' => '#070709', 'bg2' => '#16101A', 'fg' => '#E8E4EC', 'muted' => 'rgba(232,228,236,.55)', 'card' => '#14141A', 'line' => 'rgba(166,18,60,.34)',   'accent' => '#C4224C', 'dark' => true,  'splat' => '.24'],
  'halloween' => ['label' => 'Halloween',                 'bg' => '#180E24', 'bg2' => '#2E1544', 'fg' => '#FFE9CF', 'muted' => 'rgba(255,233,207,.62)', 'card' => '#241536', 'line' => 'rgba(255,122,24,.26)',  'accent' => '#FF7A18', 'dark' => true,  'splat' => '.22'],
  'labour'    => ['label' => 'Tag der Arbeit',            'bg' => '#C1121F', 'bg2' => '#8E0D17', 'fg' => '#FFF3F3', 'muted' => 'rgba(255,243,243,.70)', 'card' => '#8E0D17', 'line' => 'rgba(255,255,255,.18)', 'accent' => '#FFD65A', 'dark' => true,  'splat' => '.18'],
  'womensday' => ['label' => 'Frauentag',                 'bg' => '#4A1B6D', 'bg2' => '#6A2691', 'fg' => '#FDEBFF', 'muted' => 'rgba(253,235,255,.64)', 'card' => '#5B2183', 'line' => 'rgba(255,255,255,.16)', 'accent' => '#FF7EB6', 'dark' => true,  'splat' => '.20'],
];

/* --------------------------------------------------------------------------
 * Veri
 * ------------------------------------------------------------------------ */
function fetchEvents(): array
{
  $url = 'https://basislagerleipzig.spaces.nexudus.com/en/events?page=page&_depth=3&pastEvents=pastEvents';
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 10,
  ]);
  $body = curl_exec($ch);
  if ($body === false) {
    return [];
  }
  return json_decode($body, true)['CalendarEvents'] ?? [];
}

function roomOf(array $event): ?string
{
  $name = $event['Resource']['Name'] ?? '';
  foreach (ROOMS as $room => $_) {
    if (stripos($name, $room) !== false) {
      return $room;
    }
  }
  return null;
}

/** Bazi eventlerde EndDate == StartDate geliyor; o zaman 2 saat varsay. */
function endOf(array $event): int
{
  $start = strtotime($event['StartDate']);
  $end   = strtotime($event['EndDate'] ?? '') ?: 0;
  return $end > $start ? $end : $start + 7200;
}

function clip(string $text, int $max = 190): string
{
  $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
  if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $max) {
    return rtrim(mb_substr($text, 0, $max, 'UTF-8')) . '…';
  }
  return $text;
}

function e(?string $s): string
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

require __DIR__ . '/calendar.php';

/** api/birthdays.json -> [{ "name": "...", "date": "MM-DD", "role": "..." }] */
function birthdaysToday(int $ts): array
{
  $file = __DIR__ . '/birthdays.json';
  if (!is_readable($file)) {
    return [];
  }
  $all = json_decode((string) file_get_contents($file), true) ?: [];
  $md  = date('m-d', $ts);
  return array_values(array_filter($all, fn($p) => ($p['date'] ?? '') === $md));
}

/* --------------------------------------------------------------------------
 * Tema CSS'i - var(--x) yok, her tema duz kural olarak basiliyor.
 * ------------------------------------------------------------------------ */
function themeCss(string $key, array $t): string
{
  $s   = '[data-theme="' . $key . '"]';
  $out = '';

  $out .= "$s{background:{$t['bg']};color:{$t['fg']}}";
  $out .= "$s .bg{background:radial-gradient(circle at 20% 12%,{$t['bg2']} 0,rgba(0,0,0,0) 62%),"
    . "radial-gradient(circle at 88% 82%,{$t['bg2']} 0,rgba(0,0,0,0) 64%)}";
  $out .= "$s .splat{opacity:{$t['splat']}}";

  $out .= "$s .brand,$s .clock .d,$s .when .day,$s .card p,$s .empty,$s .tpanel h4{color:{$t['muted']}}";
  $out .= "$s .banner:not(.bday),$s .card,$s .tbtn,$s .tpanel{background:{$t['card']};border-color:{$t['line']}}";
  $out .= "$s .legend{background:{$t['bg']};border-top-color:{$t['line']}}";
  $out .= "$s .tbtn,$s .tpanel,$s .tpanel button{color:{$t['fg']}}";
  $out .= "$s .tpanel button{border-color:{$t['line']}}";

  $out .= "$s .banner:not(.bday) .glyph,$s .badge{color:{$t['accent']}}";
  $out .= "$s .badge,$s .banner.special{border-color:{$t['accent']}}";
  $out .= "$s .tpanel button.on{background:{$t['accent']};border-color:{$t['accent']};color:"
    . ($t['dark'] ? '#10100F' : '#FFFFFF') . '}';

  if ($t['dark']) {
    $out .= "$s .splat,$s .arrow img,$s .legend .mini{-webkit-filter:invert(100%);filter:invert(100%)}";
  }
  return $out . "\n";
}

/* --------------------------------------------------------------------------
 * Ana akis
 * ------------------------------------------------------------------------ */
// ?at=2026-12-24 -> o gunu simule et (ozel gunleri sunucu tarafinda denemek icin)
$now    = isset($_GET['at']) ? (strtotime($_GET['at']) ?: time()) : time();
$events = fetchEvents();

usort($events, fn($a, $b) => strcmp($a['StartDate'], $b['StartDate']));

$today = date('Y-m-d', $now);
$live  = array_values(array_filter(
  $events,
  fn($ev) => substr($ev['StartDate'], 0, 10) === $today && endOf($ev) > $now
));

$showingToday = $live !== [];
if (!$showingToday) {
  // Bugun bir sey yoksa siradaki 6 event - tum ay degil.
  $live = array_slice(array_values(array_filter($events, fn($ev) => endOf($ev) > $now)), 0, 6);
}

$special   = specialDay($now);
$birthdays = birthdaysToday($now);

$hour  = (int) date('G', $now);
$theme = $special[0] ?? (($hour >= 19 || $hour < 7) ? 'night' : 'day');

// Tarayicinin saat dilimine guvenmiyoruz (eski TV'lerde cogu zaman yanlis).
// Sunucudan epoch + Berlin ofseti gonderip saati JS'te oradan hesapliyoruz.
$berlinOffsetMin = intdiv((new DateTimeZone('Europe/Berlin'))->getOffset(new DateTime('@' . $now)), 60);



/* Animasyonlar bir kez yazilir, bir de -webkit- olarak basilir. */
$keyframes = <<<CSS
@keyframes drift{0%{transform:translate(0,0) rotate(0deg)}50%{transform:translate(4vmin,-5vmin) rotate(12deg)}100%{transform:translate(0,0) rotate(0deg)}}
@keyframes fall{0%{transform:translate(0,0) rotate(0deg);opacity:0}8%{opacity:1}92%{opacity:1}100%{transform:translate(6vmin,112vh) rotate(340deg);opacity:0}}
@keyframes rise{0%{opacity:0;transform:translate(0,4vmin)}100%{opacity:1;transform:translate(0,0)}}
@keyframes nudgeTl{0%{transform:translate(0,0)}50%{transform:translate(-.9vmin,-.9vmin)}100%{transform:translate(0,0)}}
@keyframes nudgeL{0%{transform:translate(0,0)}50%{transform:translate(-1.3vmin,0)}100%{transform:translate(0,0)}}
@keyframes nudgeBr{0%{transform:translate(0,0)}50%{transform:translate(.9vmin,.9vmin)}100%{transform:translate(0,0)}}
@keyframes pop{0%{transform:scale(1) rotate(-6deg)}50%{transform:scale(1.22) rotate(6deg)}100%{transform:scale(1) rotate(-6deg)}}
@keyframes blink{0%{opacity:1}50%{opacity:.45}100%{opacity:1}}
@keyframes shimmer{0%{background-position:0 50%}50%{background-position:100% 50%}100%{background-position:0 50%}}
CSS;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Basislager Events</title>
  <link rel="stylesheet" href="/fonts/stylesheet.css">
  <style>
    /* ---------- Iskelet ---------- */
    * {
      -webkit-box-sizing: border-box;
      box-sizing: border-box;
    }

    [hidden] {
      display: none !important;
    }

    html,
    body {
      height: 100%;
      margin: 0;
      padding: 0;
      overflow: hidden;
    }

    body {
      display: flex;
      flex-direction: column;
      font-family: 'Founders Grotesk', 'Helvetica Neue', Arial, sans-serif;
      background: #26D07C;
      color: #08170F;
      -webkit-font-smoothing: antialiased;
    }

    /* Arka plan: yumusak degrade + suruklenen firca lekeleri */
    .bg {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      z-index: 0;
      overflow: hidden;
      pointer-events: none;
    }

    .splat {
      position: absolute;
      background-image: url('/images/brush.png');
      background-repeat: no-repeat;
      background-position: center;
      background-size: contain;
      opacity: .26;
    }

    .s1 {
      top: -6vmin;
      right: -8vmin;
      width: 58vmin;
      height: 58vmin;
      -webkit-animation: drift 46s ease-in-out infinite;
      animation: drift 46s ease-in-out infinite;
    }

    .s2 {
      bottom: 4vmin;
      left: -12vmin;
      width: 70vmin;
      height: 70vmin;
      -webkit-animation: drift 62s ease-in-out infinite reverse;
      animation: drift 62s ease-in-out infinite reverse;
    }

    .s3 {
      top: 40%;
      right: -14vmin;
      width: 52vmin;
      height: 52vmin;
      -webkit-animation: drift 54s ease-in-out infinite 6s;
      animation: drift 54s ease-in-out infinite 6s;
    }

    /* Parcaciklar (kar, konfeti, kalp...) */
    .fx {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      z-index: 1;
      overflow: hidden;
      pointer-events: none;
    }

    .p {
      position: absolute;
      top: -8vmin;
      font-family: 'Segoe UI Symbol', 'Arial Unicode MS', 'DejaVu Sans', sans-serif;
      -webkit-animation: fall linear infinite;
      animation: fall linear infinite;
    }

    /* ---------- Header ---------- */
    .top {
      position: relative;
      z-index: 2;
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      padding: 4.5vmin 4.5vmin 2.5vmin;
    }

    .brand {
      font-size: 3.2vmin;
      font-weight: 600;
      letter-spacing: .38vmin;
      text-transform: uppercase;
    }

    .title {
      margin: .6vmin 0 0;
      font-size: 6.4vmin;
      font-weight: 700;
      line-height: 1;
    }

    .clock {
      margin-left: 3vmin;
      text-align: right;
      line-height: 1;
    }

    .clock .t {
      font-size: 8vmin;
      font-weight: 700;
    }

    .clock .d {
      margin-top: .8vmin;
      font-size: 2.9vmin;
      text-transform: uppercase;
      letter-spacing: .2vmin;
    }

    /* ---------- Banner'lar ---------- */
    .banners {
      position: relative;
      z-index: 2;
      padding: 0 4.5vmin;
    }

    .banner {
      display: flex;
      align-items: center;
      margin: 0 0 2vmin;
      padding: 2.4vmin 3vmin;
      border: .3vmin solid rgba(0, 0, 0, .1);
      border-radius: 2.4vmin;
      background: #fff;
      font-size: 3.2vmin;
      font-weight: 600;
    }

    .banner .glyph {
      /* Founders Grotesk'te sembol yok - sistem sembol fontuna dus */
      font-family: 'Segoe UI Symbol', 'Arial Unicode MS', 'DejaVu Sans', sans-serif;
      margin-right: 2.4vmin;
      font-size: 4.4vmin;
      line-height: 1;
    }

    .banner.special {
      border-left-width: .9vmin;
      border-left-style: solid;
    }

    [data-theme="pride"] .banner.special {
      background: linear-gradient(90deg, #E40303, #FF8C00, #FFED00, #008026, #24408E, #732982);
      color: #fff;
      border-color: rgba(255, 255, 255, .25);
    }

    [data-theme="pride"] .banner.special .glyph {
      color: #fff;
    }

    /* Dogum gunu - temadan bagimsiz, hep ayni kutlama rengi */
    .bday {
      color: #2A0E1E;
      border-color: rgba(0, 0, 0, .12);
      background: #FF8FB1;
      background: linear-gradient(100deg, #FFD65A, #FF8FB1 46%, #B491FF);
      background-size: 200% 100%;
      -webkit-animation: shimmer 7s ease-in-out infinite;
      animation: shimmer 7s ease-in-out infinite;
    }

    .bday .glyph {
      color: #2A0E1E !important;
      -webkit-animation: pop 2.2s ease-in-out infinite;
      animation: pop 2.2s ease-in-out infinite;
    }

    .bday .who {
      font-size: 3.8vmin;
      font-weight: 700;
    }

    .bday .sub {
      margin-top: .4vmin;
      font-size: 2.4vmin;
      line-height: 1.25;
    }

    .bday .hb {
      text-transform: uppercase;
      letter-spacing: .24vmin;
      font-weight: 700;
    }

    .bday .role {
      /* unvanlar uzun olabiliyor - buyuk harf yapmiyoruz ki tasmasin */
      opacity: .72;
      font-weight: 400;
    }

    /* ---------- Event listesi ---------- */
    .view {
      position: relative;
      z-index: 2;
      flex: 1;
      min-height: 0;
      overflow-x: hidden;
      overflow-y: auto;
      padding: 1vmin 4.5vmin 0;
      -webkit-mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0) 0, #000 3.5vmin, #000 92%, rgba(0, 0, 0, 0) 100%);
      mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0) 0, #000 3.5vmin, #000 92%, rgba(0, 0, 0, 0) 100%);
    }

    .view::-webkit-scrollbar {
      width: 0;
      height: 0;
    }

    .row {
      display: flex;
      align-items: stretch;
      margin-bottom: 3.2vmin;
    }

    /* Giris animasyonu: desteklenmezse kart yine gorunur kalir (opacity:0 yok) */
    .enter {
      -webkit-animation: rise .7s ease-out backwards;
      animation: rise .7s ease-out backwards;
    }

    .when {
      flex: none;
      width: 20vmin;
      margin-right: 2.5vmin;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding-top: 1.6vmin;
    }

    .when .day {
      margin-bottom: .6vmin;
      font-size: 2.2vmin;
      font-weight: 600;
      letter-spacing: .18vmin;
      text-transform: uppercase;
    }

    .when .hh {
      font-size: 5vmin;
      font-weight: 700;
      line-height: 1;
    }

    /* Ok gorselleri - orijinal PNG'ler, sadece yerlesim ve hareket */
    .arrow {
      position: relative;
      margin-top: 1.8vmin;
      width: 13vmin;
      height: 13vmin;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .arrow .disc {
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      border-radius: 50%;
      opacity: .28;
    }

    .arrow img {
      position: relative;
      /* sabit kutu: farkli en-boy oranlarini gorsel olarak esitler */
      max-width: 76%;
      max-height: 76%;
      object-fit: contain;
    }

    .arrow.tl img {
      -webkit-animation: nudgeTl 2.6s ease-in-out infinite;
      animation: nudgeTl 2.6s ease-in-out infinite;
    }

    .arrow.l img {
      -webkit-animation: nudgeL 2.6s ease-in-out infinite;
      animation: nudgeL 2.6s ease-in-out infinite;
    }

    .arrow.br img {
      -webkit-animation: nudgeBr 2.6s ease-in-out infinite;
      animation: nudgeBr 2.6s ease-in-out infinite;
    }

    .card {
      flex: 1;
      min-width: 0;
      position: relative;
      padding: 3vmin 3.4vmin;
      border: .3vmin solid rgba(0, 0, 0, .1);
      border-left: .9vmin solid #ccc;
      border-radius: 2.6vmin;
      background: #fff;
    }

    .card h3 {
      margin: 0;
      font-size: 4.4vmin;
      font-weight: 700;
      line-height: 1.12;
    }

    .card p {
      margin: 1.6vmin 0 0;
      font-size: 2.8vmin;
      line-height: 1.4;
    }

    .meta {
      margin-top: 2.2vmin;
      font-size: 0;
    }

    .meta>* {
      margin-right: 1.6vmin;
      vertical-align: middle;
    }

    .room {
      display: inline-block;
      padding: .9vmin 2vmin;
      border-radius: 1.4vmin;
      font-size: 2.5vmin;
      font-weight: 700;
      color: #10100F;
      background: #ccc;
    }

    .badge {
      display: inline-block;
      padding: .9vmin 1.8vmin;
      border: .3vmin solid #333;
      border-radius: 1.4vmin;
      font-size: 2.2vmin;
      font-weight: 700;
      letter-spacing: .22vmin;
      text-transform: uppercase;
    }

    .badge.live {
      color: #fff !important;
      background: #E63946;
      border-color: #E63946 !important;
      -webkit-animation: blink 1.8s ease-in-out infinite;
      animation: blink 1.8s ease-in-out infinite;
    }

    .empty {
      padding: 8vmin 2vmin;
      text-align: center;
      font-size: 4vmin;
    }

    /* ---------- Alt bar: oda lejandi ---------- */
    .legend {
      position: relative;
      z-index: 2;
      display: flex;
      justify-content: space-around;
      align-items: center;
      padding: 2.4vmin 3vmin;
      border-top: .3vmin solid rgba(0, 0, 0, .1);
      background: #26D07C;
    }

    .legend .item {
      display: flex;
      align-items: center;
      font-size: 2.6vmin;
      font-weight: 600;
    }

    .legend .item>* {
      margin-right: 1.4vmin;
    }

    .legend .swatch {
      width: 2.4vmin;
      height: 2.4vmin;
      border-radius: 50%;
    }

    .legend .mini {
      width: auto;
      height: 3vmin;
      object-fit: contain;
    }

    /* ---------- Test paneli ---------- */
    .tbtn {
      position: fixed;
      right: 2.5vmin;
      bottom: 2.5vmin;
      z-index: 20;
      width: 7vmin;
      height: 7vmin;
      padding: 0;
      border: 0;
      border-radius: 50%;
      background: #fff;
      font-size: 3.2vmin;
      cursor: pointer;
      opacity: .4;
    }

    .tbtn:hover {
      opacity: 1;
    }

    .tpanel {
      position: fixed;
      right: 2.5vmin;
      bottom: 11vmin;
      z-index: 20;
      width: 46vmin;
      max-height: 74vh;
      overflow-y: auto;
      padding: 2.4vmin;
      border: .3vmin solid rgba(0, 0, 0, .1);
      border-radius: 2.4vmin;
      background: #fff;
    }

    .tpanel h4 {
      margin: 0 0 1.4vmin;
      font-size: 2.4vmin;
      text-transform: uppercase;
      letter-spacing: .2vmin;
    }

    .tpanel .grid {
      margin-bottom: 1.4vmin;
    }

    .tpanel button {
      margin: 0 1vmin 1vmin 0;
      padding: 1.2vmin 1.8vmin;
      border: .3vmin solid rgba(0, 0, 0, .1);
      border-radius: 1.2vmin;
      background: transparent;
      font: inherit;
      font-size: 2.2vmin;
      cursor: pointer;
    }

    <?= $keyframes ?>

    <?= str_replace(['@keyframes', 'transform:'], ['@-webkit-keyframes', '-webkit-transform:'], $keyframes) ?>

    /* ---------- Temalar (PHP'den, CSS degiskeni kullanmadan) ---------- */
    <?php foreach (THEMES as $key => $t) {
      echo themeCss($key, $t);
    } ?>
  </style>
</head>

<body data-theme="<?= e($theme) ?>">

  <div class="bg">
    <div class="splat s1"></div>
    <div class="splat s2"></div>
    <div class="splat s3"></div>
  </div>
  <div class="fx" id="fx"></div>

  <div class="top">
    <div>
      <div class="brand">Basislager Leipzig</div>
      <h1 class="title"><?= $showingToday ? "Today's Events" : 'Upcoming Events' ?></h1>
    </div>
    <div class="clock">
      <div class="t" id="clock"><?= date('H:i', $now) ?></div>
      <div class="d" id="cdate"><?= date('D, d.m.Y', $now) ?></div>
    </div>
  </div>

  <div class="banners">
    <div class="banner special" id="banner-special" <?= $special ? '' : 'hidden' ?>>
      <span class="glyph">&#9733;</span>
      <span id="banner-special-text"><?= e($special[1] ?? '') ?></span>
    </div>

    <div class="banner bday" id="banner-bday" <?= $birthdays ? '' : 'hidden' ?>>
      <span class="glyph">&#10022;</span>
      <div>
        <div class="who" id="bday-who"><?= e(implode(' & ', array_column($birthdays, 'name'))) ?></div>
        <div class="sub" id="bday-sub">
          <span class="hb">Happy Birthday</span><?php
          $role = count($birthdays) === 1 ? ($birthdays[0]['role'] ?? '') : '';
          if ($role !== '') {
            echo '<span class="role"> &middot; ' . e($role) . '</span>';
          }
          ?>
        </div>
      </div>
    </div>
  </div>

  <div class="view" id="view">
    <div class="track" id="track">
      <?php if (!$live): ?>
        <div class="empty">No upcoming events.</div>
      <?php endif; ?>

      <?php foreach ($live as $i => $ev):
        $room  = roomOf($ev);
        $color = $room ? ROOMS[$room]['color'] : '#CCCCCC';
        $start = strtotime($ev['StartDate']);
        $isNow = $start <= $now && endOf($ev) > $now;
        $isNext = !$isNow && $i === 0;
        $delay = min($i * 120, 900);
        $cardStyle = 'border-left-color:' . $color;
        if ($isNow) {
          $cardStyle .= ';box-shadow:0 0 0 .6vmin ' . $color;
        }
        ?>
        <div class="row enter"
          style="-webkit-animation-delay:<?= $delay ?>ms;animation-delay:<?= $delay ?>ms">
          <div class="when">
            <?php if (!$showingToday): ?>
              <div class="day"><?= date('D d.m', $start) ?></div>
            <?php endif; ?>
            <div class="hh"><?= date('H:i', $start) ?></div>
            <?php if ($room): ?>
              <div class="arrow <?= e(ROOMS[$room]['dir']) ?>">
                <span class="disc" style="background:<?= e($color) ?>"></span>
                <img src="<?= e(ROOMS[$room]['arrow']) ?>" alt="Direction to <?= e($room) ?>">
              </div>
            <?php endif; ?>
          </div>

          <div class="card" style="<?= e($cardStyle) ?>">
            <h3><?= e($ev['Name']) ?></h3>
            <?php $desc = clip($ev['ShortDescription'] ?? $ev['LongDescription'] ?? ''); ?>
            <?php if ($desc !== ''): ?><p><?= e($desc) ?></p><?php endif; ?>
            <div class="meta">
              <span class="room" style="background:<?= e($color) ?>"><?= e($room ?? 'Basislager') ?></span>
              <?php if ($isNow): ?><span class="badge live">Now</span>
              <?php elseif ($isNext): ?><span class="badge">Next</span><?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="legend">
    <?php foreach (ROOMS as $room => $conf): ?>
      <div class="item">
        <span class="swatch" style="background:<?= e($conf['color']) ?>"></span>
        <img class="mini" src="<?= e($conf['arrow']) ?>" alt="">
        <span><?= e($room) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (SHOW_TEST_PANEL): ?>
    <button class="tbtn" id="tbtn" title="Preview themes">&#9881;</button>
    <div class="tpanel" id="tpanel" hidden>
      <h4>Special days</h4>
      <div class="grid" id="theme-grid"></div>
      <h4>Birthday</h4>
      <div class="grid"><button id="bday-toggle">Toggle badge</button></div>
      <h4>Live</h4>
      <div class="grid"><button id="reset">Back to real</button></div>
    </div>
  <?php endif; ?>

  <script>
    /* ES5 - eski tarayici icin let/const/arrow/template literal yok. */
    (function () {
      'use strict';

      var REAL_BDAY = <?= $birthdays ? 'true' : 'false' ?>;
      var LABELS = <?= json_encode(array_map(fn($t) => $t['label'], THEMES)) ?>;

      /* ---- Saat: cihazin saat dilimi degil, Berlin ----
         Sunucudan gelen epoch'a sayfa acildigindan beri gecen sure eklenir,
         sonra Berlin ofseti uygulanip getUTC* ile okunur. Boylece TV'nin
         saat dilimi yanlis ayarli olsa bile ekranda Leipzig saati durur. */
      var SRV_MS = <?= $now ?> * 1000;
      var TZ_OFF = <?= $berlinOffsetMin ?>;
      var LOAD_MS = new Date().getTime();
      var DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      var clock = document.getElementById('clock');
      var cdate = document.getElementById('cdate');

      function pad(n) { return (n < 10 ? '0' : '') + n; }

      function tick() {
        var d = new Date(SRV_MS + (new Date().getTime() - LOAD_MS) + TZ_OFF * 60000);
        clock.innerHTML = pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes());
        cdate.innerHTML = DAYS[d.getUTCDay()] + ', ' + pad(d.getUTCDate()) + '.' +
          pad(d.getUTCMonth() + 1) + '.' + d.getUTCFullYear();
      }
      tick();
      setInterval(tick, 15000);

      /* ---- Otomatik kaydirma ----
         CSS animasyonu yerine scrollTop: eski tarayicida da calisir. */
      var view = document.getElementById('view');
      var track = document.getElementById('track');
      var timer = null, pos = 0, half = 0;

      function setupScroll() {
        if (timer) { clearInterval(timer); timer = null; }
        pos = 0;
        view.scrollTop = 0;
        track.style.paddingTop = '0px';

        if (track.offsetHeight <= view.clientHeight + 10) {
          // Az event varsa listeyi dikeyde ortala - alttaki bosluk kotu duruyor
          track.style.paddingTop = Math.floor((view.clientHeight - track.offsetHeight) / 2) + 'px';
          return;
        }

        if (!track.getAttribute('data-cloned')) {
          var copy = track.cloneNode(true);
          var kids = copy.getElementsByTagName('div');
          for (var i = 0; i < kids.length; i++) {
            kids[i].className = kids[i].className.replace('enter', '');
          }
          while (copy.firstChild) { track.appendChild(copy.firstChild); }
          track.setAttribute('data-cloned', '1');
        }

        half = track.offsetHeight / 2;
        timer = setInterval(function () {
          pos += 0.45; // ~28 px/sn
          if (pos >= half) { pos -= half; }
          view.scrollTop = pos;
        }, 16);
      }
      setTimeout(setupScroll, 1400);

      /* ---- Parcacik efektleri ---- */
      var FX = {
        xmas: { chars: ['❄', '❅', '❆'], colors: ['#FFFFFF'], n: 26, size: [18, 42] },
        newyear: { chars: [], colors: ['#FFD65A', '#FF6B6B', '#4CC9F0', '#FFFFFF'], n: 34, size: [8, 16] },
        valentine: { chars: ['♥'], colors: ['#E8336D', '#FF8FB1'], n: 20, size: [20, 44] },
        pride: { chars: [], colors: ['#E40303', '#FF8C00', '#FFED00', '#008026', '#24408E', '#732982'], n: 34, size: [10, 20] },
        easter: { chars: ['✿', '❁'], colors: ['#F4A259', '#7BD3A0', '#F7D65A'], n: 20, size: [18, 38] },
        halloween: { chars: ['✦', '☾'], colors: ['#FF7A18', '#B491FF'], n: 22, size: [16, 36] },
        lichtfest: { chars: ['✦'], colors: ['#FFB44D', '#FFE0A8'], n: 24, size: [10, 26] },
        wgt: { chars: ['✝', '✦'], colors: ['#C4224C', '#6B6B75'], n: 18, size: [14, 32] },
        womensday: { chars: ['✿'], colors: ['#FF7EB6', '#FFFFFF'], n: 20, size: [16, 34] },
        unity: { chars: [], colors: ['#111111', '#DD0000', '#FFCE00'], n: 24, size: [10, 18] },
        labour: { chars: [], colors: ['#FFD65A', '#FFFFFF'], n: 20, size: [10, 18] },
        birthday: { chars: [], colors: ['#FFD65A', '#FF8FB1', '#B491FF', '#4CC9F0'], n: 36, size: [8, 16] }
      };

      var fxBox = document.getElementById('fx');

      function rand(a, b) { return a + Math.random() * (b - a); }

      function particles(key) {
        fxBox.innerHTML = '';
        var c = FX[key];
        if (!c) { return; }
        var frag = document.createDocumentFragment();
        for (var i = 0; i < c.n; i++) {
          var p = document.createElement('div');
          var size = rand(c.size[0], c.size[1]);
          var color = c.colors[Math.floor(Math.random() * c.colors.length)];
          var dur = rand(7, 16) + 's';
          var delay = '-' + rand(0, 14) + 's';

          p.className = 'p';
          p.style.left = rand(-2, 100) + '%';
          p.style.color = color;
          p.style.animationDuration = dur;
          p.style.webkitAnimationDuration = dur;
          p.style.animationDelay = delay;
          p.style.webkitAnimationDelay = delay;

          if (c.chars.length) {
            p.innerHTML = c.chars[Math.floor(Math.random() * c.chars.length)];
            p.style.fontSize = size + 'px';
          } else {
            p.style.width = size + 'px';
            p.style.height = Math.round(size * rand(0.6, 1)) + 'px';
            p.style.background = color;
            p.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
          }
          frag.appendChild(p);
        }
        fxBox.appendChild(frag);
      }

      /* ---- Gorunumu uygula ---- */
      var bannerS = document.getElementById('banner-special');
      var bannerSText = document.getElementById('banner-special-text');
      var bannerB = document.getElementById('banner-bday');

      function apply(theme) {
        document.body.setAttribute('data-theme', theme);
        particles(bannerB.hidden ? theme : 'birthday');
        if (theme === 'day' || theme === 'night') {
          bannerS.hidden = true;
        } else {
          bannerSText.innerHTML = LABELS[theme] || theme;
          bannerS.hidden = false;
        }
        setTimeout(setupScroll, 60);
      }

      particles(REAL_BDAY ? 'birthday' : document.body.getAttribute('data-theme'));

      /* ---- Test paneli ---- */
      var tbtn = document.getElementById('tbtn');
      if (tbtn) {
        var panel = document.getElementById('tpanel');
        var grid = document.getElementById('theme-grid');
        var keys = [];
        for (var k in LABELS) {
          if (LABELS.hasOwnProperty(k)) { keys.push(k); }
        }

        for (var j = 0; j < keys.length; j++) {
          (function (key) {
            var b = document.createElement('button');
            b.innerHTML = LABELS[key];
            b.onclick = function () {
              apply(key);
              for (var m = 0; m < grid.childNodes.length; m++) {
                if (grid.childNodes[m].className !== undefined) {
                  grid.childNodes[m].className = '';
                }
              }
              b.className = 'on';
            };
            grid.appendChild(b);
          })(keys[j]);
        }

        tbtn.onclick = function () { panel.hidden = !panel.hidden; };

        document.getElementById('bday-toggle').onclick = function () {
          bannerB.hidden = !bannerB.hidden;
          if (!bannerB.hidden && !REAL_BDAY) {
            document.getElementById('bday-who').innerHTML = 'Anna &amp; Jonas';
            document.getElementById('bday-sub').innerHTML = '<span class="hb">Happy Birthday</span><span class="role"> &middot; Basislager Team</span>';
          }
          particles(bannerB.hidden ? document.body.getAttribute('data-theme') : 'birthday');
          setTimeout(setupScroll, 60);
        };

        document.getElementById('reset').onclick = function () { location.reload(); };
      }

      /* ---- 10 dakikada bir tazele ---- */
      setTimeout(function () { location.reload(); }, 600000);
    })();
  </script>
</body>

</html>
