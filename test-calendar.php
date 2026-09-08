<?php
/**
 * Ozel gun takviminin dogrulamasi.   Calistir:  php test-calendar.php
 *
 * Sadece "bu yil dogru mu" degil, onumuzdeki 10 yilin HER GUNU icin
 * mantigin catlamadigini ve hareketli gunlerin dogru gune dustugunu kontrol eder.
 */

date_default_timezone_set('Europe/Berlin');
require __DIR__ . '/api/calendar.php';

$fail = 0;
$checks = 0;

function check(string $what, $got, $want): void
{
  global $fail, $checks;
  $checks++;
  if ($got !== $want) {
    $fail++;
    echo "  FAIL  $what\n        beklenen: " . var_export($want, true) . "\n        gelen   : " . var_export($got, true) . "\n";
  }
}

$FROM = 2026;
$TO   = 2036;

/* ---------------------------------------------------------------------------
 * 1) Paskalya hesabi - PHP'nin ext/calendar'ina karsi 100 yil
 * ------------------------------------------------------------------------- */
echo "1) Paskalya hesabi (easterDate) 2026-2126 arasi referansa karsi\n";
if (function_exists('easter_days')) {
  for ($y = 2026; $y <= 2126; $y++) {
    $ref = date('Y-m-d', mktime(12, 0, 0, 3, 21 + easter_days($y), $y));
    check("Paskalya $y", date('Y-m-d', easterDate($y)), $ref);
  }
  echo "   101 yil karsilastirildi.\n";
} else {
  echo "   ATLANDI (ext/calendar yok)\n";
}

/* ---------------------------------------------------------------------------
 * 2) Hareketli gunler dogru HAFTA GUNUNE dusuyor mu
 *    Karfreitag=Cuma, Ostermontag=Pazartesi, Himmelfahrt=Persembe, WGT=Cuma
 * ------------------------------------------------------------------------- */
echo "2) Hareketli gunlerin hafta gunleri ($FROM-$TO)\n";
for ($y = $FROM; $y <= $TO; $y++) {
  $e = easterDate($y);
  $rel = fn(int $d) => strtotime($d . ' day', $e);

  check("Paskalya $y pazar mi",           date('D', $e),        'Sun');
  check("Karfreitag $y cuma mi",          date('D', $rel(-2)),  'Fri');
  check("Ostermontag $y pazartesi mi",    date('D', $rel(1)),   'Mon');
  check("Himmelfahrt $y persembe mi",     date('D', $rel(39)),  'Thu');
  check("WGT basi $y cuma mi",            date('D', $rel(47)),  'Fri');
  check("Pfingstsonntag $y pazar mi",     date('D', $rel(49)),  'Sun');
  check("WGT sonu $y pazartesi mi",       date('D', $rel(50)),  'Mon');
}

/* ---------------------------------------------------------------------------
 * 3) Sabit gunler her yil dogru temayi aciyor mu (arti yillar dahil)
 * ------------------------------------------------------------------------- */
echo "3) Sabit gunler ($FROM-$TO)\n";
$fixed = [
  '01-01' => 'newyear',
  '12-31' => 'newyear',
  '02-14' => 'valentine',
  '03-08' => 'womensday',
  '05-01' => 'labour',
  '06-15' => 'pride',
  '10-03' => 'unity',
  '10-09' => 'lichtfest',
  '10-31' => 'halloween',
  '12-20' => 'xmas',
];
for ($y = $FROM; $y <= $TO; $y++) {
  foreach ($fixed as $md => $wantTheme) {
    $ts = strtotime("$y-$md 12:00");
    $got = specialDay($ts);
    check("$y-$md temasi", $got[0] ?? null, $wantTheme);
  }
  // 29 Subat sadece arti yillarda var olmali
  check("$y arti yil mi", (bool) date('L', mktime(12, 0, 0, 1, 1, $y)), (bool) ((($y % 4 === 0) && ($y % 100 !== 0)) || $y % 400 === 0));
}

/* ---------------------------------------------------------------------------
 * 4) 10 yilin HER GUNU: hata vermiyor ve donen tema gercekten tanimli mi
 * ------------------------------------------------------------------------- */
echo "4) $FROM-$TO arasi her gun taraniyor\n";
$themeKeys = ['easter', 'wgt', 'pride', 'newyear', 'valentine', 'womensday', 'labour', 'unity', 'lichtfest', 'halloween', 'xmas'];
$days = 0;
$hits = [];
$cursor = strtotime("$FROM-01-01 12:00");
$stop   = strtotime(($TO + 1) . "-01-01 12:00");

while ($cursor < $stop) {
  $days++;
  $r = specialDay($cursor);
  if ($r !== null) {
    check('tanimli tema: ' . $r[0], in_array($r[0], $themeKeys, true), true);
    check('etiket bos degil', $r[1] !== '', true);
    $hits[$r[0]] = ($hits[$r[0]] ?? 0) + 1;
  }
  $cursor = strtotime('+1 day', $cursor);
}
$expectedDays = 0;
for ($y = $FROM; $y <= $TO; $y++) {
  $expectedDays += (int) date('z', mktime(12, 0, 0, 12, 31, $y)) + 1;
}
check("taranan gun sayisi (arti yillar dahil)", $days, $expectedDays);

/* ---------------------------------------------------------------------------
 * 5) Ozel gunlerin BIRBIRINI EZMEDIGI kontrolu - Paskalya/WGT haziranda
 *    Pride ile cakisabiliyor; hangisinin kazandigi belirli olmali.
 * ------------------------------------------------------------------------- */
echo "5) Cakisma kontrolu\n";
for ($y = $FROM; $y <= $TO; $y++) {
  $wgtStart = strtotime('47 day', easterDate($y));
  if (date('m', $wgtStart) === '06') {
    // WGT haziranda -> Pride Month'u ezmeli (kod sirasi boyle)
    check("$y WGT haziranda Pride'i eziyor", specialDay($wgtStart)[0], 'wgt');
  }
}

echo "\n";
if ($fail === 0) {
  echo "TUM TESTLER GECTI  ($checks kontrol, $days gun tarandi)\n";
  exit(0);
}
echo "$fail HATA  ($checks kontrol)\n";
exit(1);
