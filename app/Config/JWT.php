<?php

namespace Config;

class JWT
{
  public static $secretKey = 'ASHADU_ALLAH_ILAHA_ILLALLAH_WA_ASHADU_ANNA_MUHAMMADAN_RASULALLAH'; // Ganti dengan key rahasia
  public static $algorithm = 'HS256'; // Algoritma yang digunakan
  public static $tokenExpiry = 54000; // Token berlaku 15 jam (3600*15 = 54000 detik)
}
