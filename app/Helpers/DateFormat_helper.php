<?php

if (!function_exists('format_tanggal_lokal')) {
  function format_tanggal_lokal($dateStr)
  {
    $hari = [
      'Sun' => 'Min',
      'Mon' => 'Sen',
      'Tue' => 'Sel',
      'Wed' => 'Rab',
      'Thu' => 'Kam',
      'Fri' => 'Jum',
      'Sat' => 'Sab'
    ];

    $bulan = [
      1 => 'Jan',
      2 => 'Feb',
      3 => 'Mar',
      4 => 'Apr',
      5 => 'Mei',
      6 => 'Jun',
      7 => 'Jul',
      8 => 'Agu',
      9 => 'Sep',
      10 => 'Okt',
      11 => 'Nov',
      12 => 'Des'
    ];

    $timestamp = strtotime($dateStr);
    $hariSingkat = date('D', $timestamp);
    $tanggal = date('j', $timestamp);
    $bulanNum = date('n', $timestamp);
    $tahun = date('Y', $timestamp);

    return "{$hari[$hariSingkat]}, {$tanggal} {$bulan[$bulanNum]} {$tahun}";
  }
}
