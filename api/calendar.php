<?php
/**
 * Ozel gun takvimi - Almanya / Saksonya / Leipzig.
 * index.php ve test-calendar.php buradan besleniyor.
 *
 * Tum tarih aritmetigi strtotime/mktime uzerinden yapiliyor; ay uzunluklari,
 * arti yillar ve yil sinirlari PHP'nin takvimine birakildi.
 * Dogrulama: repo kokundeki `php test-calendar.php`.
 */

/**
 * CSD Leipzig'in tarihi her yil degisiyor ve yil basinda aciklaniyor -
 * hesaplanabilir bir kurali yok (2023 temmuzun 3., 2024-2025 ise 2. cumartesisiydi).
 * Dogruladigin tarihi buraya gir: yil => ['baslangic MM-DD', 'bitis MM-DD'].
 * Listede olmayan yilda CSD temasi HIC acilmaz - yanlis gun gostermektense hic gosterme.
 * Haziran ayi zaten Pride Month olarak isaretli.
 */
const CSD_LEIPZIG = [
  // 2026 => ['07-10', '07-18'],
];

/**
 * Paskalya Pazari (Gregoryen) - Meeus/Jones/Butcher algoritmasi.
 * ext/calendar'a bagimli olmamak icin elle yazildi; test dosyasi bunu
 * PHP'nin easter_days() fonksiyonuna karsi 100 yil boyunca dogruluyor.
 */
function easterDate(int $year): int
{
  $a = $year % 19;
  $b = intdiv($year, 100);
  $c = $year % 100;
  $d = intdiv($b, 4);
  $e = $b % 4;
  $f = intdiv($b + 8, 25);
  $g = intdiv($b - $f + 1, 3);
  $h = (19 * $a + $b - $d - $g + 15) % 30;
  $i = intdiv($c, 4);
  $k = $c % 4;
  $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
  $m = intdiv($a + 11 * $h + 22 * $l, 451);
  $month = intdiv($h + $l - 7 * $m + 114, 31);
  $day   = (($h + $l - 7 * $m + 114) % 31) + 1;
  // 12:00 kullaniliyor: yaz saati gecisinde gun kaymasin.
  return mktime(12, 0, 0, $month, $day, $year);
}

/**
 * Bugun ozel bir gun mu? [tema anahtari, ekranda yazacak etiket] veya null.
 * Sira onemli: ustteki kazanir.
 */
function specialDay(int $ts): ?array
{
  $year  = (int) date('Y', $ts);
  $md    = date('m-d', $ts);
  $today = date('Y-m-d', $ts);

  // --- Paskalya'ya bagli hareketli gunler ---
  $easter = easterDate($year);
  $rel = fn(int $days) => date('Y-m-d', strtotime($days . ' day', $easter));

  // Karfreitag (-2) ... Ostermontag (+1)
  if ($today >= $rel(-2) && $today <= $rel(1)) {
    return ['easter', 'Frohe Ostern'];
  }
  // Christi Himmelfahrt: Paskalya + 39 (her zaman persembe)
  if ($today === $rel(39)) {
    return ['easter', 'Christi Himmelfahrt'];
  }
  // Wave-Gotik-Treffen: Pfingsten hafta sonu, cuma (+47) - pazartesi (+50)
  if ($today >= $rel(47) && $today <= $rel(50)) {
    return ['wgt', 'Wave-Gotik-Treffen Leipzig'];
  }

  // --- CSD Leipzig (elle girilen tarih) ---
  if (isset(CSD_LEIPZIG[$year])) {
    [$from, $to] = CSD_LEIPZIG[$year];
    if ($md >= $from && $md <= $to) {
      return ['pride', 'CSD Leipzig'];
    }
  }

  // --- Sabit gunler ---
  if ($md >= '12-31' || $md <= '01-02') return ['newyear',   'Frohes neues Jahr'];
  if ($md === '02-14')                  return ['valentine', 'Valentinstag'];
  if ($md === '03-08')                  return ['womensday', 'Internationaler Frauentag'];
  if ($md === '05-01')                  return ['labour',    'Tag der Arbeit'];
  if ($md >= '06-01' && $md <= '06-30') return ['pride',     'Pride Month'];
  if ($md === '10-03')                  return ['unity',     'Tag der Deutschen Einheit'];
  if ($md === '10-09')                  return ['lichtfest', 'Lichtfest Leipzig'];
  if ($md === '10-31')                  return ['halloween', 'Halloween / Reformationstag'];
  if ($md >= '12-01' && $md <= '12-26') return ['xmas',      'Frohe Weihnachten'];

  return null;
}
