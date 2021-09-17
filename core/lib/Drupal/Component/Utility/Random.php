<?php

namespace Drupal\Component\Utility;

/**
 * Defines a utility class for creating random data.
 *
 * @ingroup utility
 */
class Random {

  /**
   * The maximum number of times name() and string() can loop.
   *
   * This prevents infinite loops if the length of the random value is very
   * small.
   *
   * @see \Drupal\Tests\Component\Utility\RandomTest
   */
  const MAXIMUM_TRIES = 100;

  /**
   * A list of unique strings generated by string().
   *
   * @var array
   */
  protected $strings = [];

  /**
   * A list of unique names generated by name().
   *
   * @var array
   */
  protected $names = [];

  /**
   * Generates a random string of ASCII characters of codes 32 to 126.
   *
   * The generated string includes alpha-numeric characters and common
   * miscellaneous characters. Use this method when testing general input
   * where the content is not restricted.
   *
   * @param int $length
   *   Length of random string to generate.
   * @param bool $unique
   *   (optional) If TRUE ensures that the random string returned is unique.
   *   Defaults to FALSE.
   * @param callable $validator
   *   (optional) A callable to validate the string. Defaults to NULL.
   *
   * @return string
   *   Randomly generated string.
   *
   * @see \Drupal\Component\Utility\Random::name()
   */
  public function string($length = 8, $unique = FALSE, $validator = NULL) {
    $counter = 0;

    // Continue to loop if $unique is TRUE and the generated string is not
    // unique or if $validator is a callable that returns FALSE. To generate a
    // random string this loop must be carried out at least once.
    do {
      if ($counter == static::MAXIMUM_TRIES) {
        throw new \RuntimeException('Unable to generate a unique random name');
      }
      $str = '';
      for ($i = 0; $i < $length; $i++) {
        $str .= chr(mt_rand(32, 126));
      }
      $counter++;

      $continue = FALSE;
      if ($unique) {
        $continue = isset($this->strings[$str]);
      }
      if (!$continue && is_callable($validator)) {
        // If the validator callback returns FALSE generate another random
        // string.
        $continue = !call_user_func($validator, $str);
      }
    } while ($continue);

    if ($unique) {
      $this->strings[$str] = TRUE;
    }

    return $str;
  }

  /**
   * Generates a random string containing letters and numbers.
   *
   * The string will always start with a letter. The letters may be upper or
   * lower case. This method is better for restricted inputs that do not
   * accept certain characters. For example, when testing input fields that
   * require machine readable values (i.e. without spaces and non-standard
   * characters) this method is best.
   *
   * @param int $length
   *   Length of random string to generate.
   * @param bool $unique
   *   (optional) If TRUE ensures that the random string returned is unique.
   *   Defaults to FALSE.
   *
   * @return string
   *   Randomly generated string.
   *
   * @see \Drupal\Component\Utility\Random::string()
   */
  public function name($length = 8, $unique = FALSE) {
    $values = array_merge(range(65, 90), range(97, 122), range(48, 57));
    $max = count($values) - 1;
    $counter = 0;

    do {
      if ($counter == static::MAXIMUM_TRIES) {
        throw new \RuntimeException('Unable to generate a unique random name');
      }
      $str = chr(mt_rand(97, 122));
      for ($i = 1; $i < $length; $i++) {
        $str .= chr($values[mt_rand(0, $max)]);
      }
      $counter++;
    } while ($unique && isset($this->names[$str]));

    if ($unique) {
      $this->names[$str] = TRUE;
    }

    return $str;
  }

  /**
   * Generate a string that looks like a word (letters only, alternating consonants and vowels).
   *
   * @param int $length
   *   The desired word length.
   *
   * @return string
   */
  public function word($length) {
    $vowels = ["a", "e", "i", "o", "u"];
    $cons = ["b", "c", "d", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "u", "v", "w", "tr",
      "cr", "br", "fr", "th", "dr", "ch", "ph", "wr", "st", "sp", "sw", "pr",
      "sl", "cl", "sh",
    ];

    $num_vowels = count($vowels);
    $num_cons = count($cons);
    $word = '';

    while (strlen($word) < $length) {
      $word .= $cons[mt_rand(0, $num_cons - 1)] . $vowels[mt_rand(0, $num_vowels - 1)];
    }

    return substr($word, 0, $length);
  }

  /**
   * Generates a random PHP object.
   *
   * @param int $size
   *   The number of random keys to add to the object.
   *
   * @return object
   *   The generated object, with the specified number of random keys. Each key
   *   has a random string value.
   */
  public function object($size = 4) {
    $object = new \stdClass();
    for ($i = 0; $i < $size; $i++) {
      $random_key = $this->name();
      $random_value = $this->string();
      $object->{$random_key} = $random_value;
    }
    return $object;
  }

  /**
   * Generates sentences Latin words, often used as placeholder text.
   *
   * @param int $min_word_count
   *   The minimum number of words in the return string. Total word count
   *   can slightly exceed provided this value in order to deliver
   *   sentences of random length.
   * @param bool $capitalize
   *   Uppercase all the words in the string.
   *
   * @return string
   *   Nonsense latin words which form sentence(s).
   */
  public function sentences($min_word_count, $capitalize = FALSE) {
    // cSpell:disable
    $dictionary = ["abbas", "abdo", "abico", "abigo", "abluo", "accumsan",
      "acsi", "ad", "adipiscing", "aliquam", "aliquip", "amet", "antehabeo",
      "appellatio", "aptent", "at", "augue", "autem", "bene", "blandit",
      "brevitas", "caecus", "camur", "capto", "causa", "cogo", "comis",
      "commodo", "commoveo", "consectetuer", "consequat", "conventio", "cui",
      "damnum", "decet", "defui", "diam", "dignissim", "distineo", "dolor",
      "dolore", "dolus", "duis", "ea", "eligo", "elit", "enim", "erat",
      "eros", "esca", "esse", "et", "eu", "euismod", "eum", "ex", "exerci",
      "exputo", "facilisi", "facilisis", "fere", "feugiat", "gemino",
      "genitus", "gilvus", "gravis", "haero", "hendrerit", "hos", "huic",
      "humo", "iaceo", "ibidem", "ideo", "ille", "illum", "immitto",
      "importunus", "imputo", "in", "incassum", "inhibeo", "interdico",
      "iriure", "iusto", "iustum", "jugis", "jumentum", "jus", "laoreet",
      "lenis", "letalis", "lobortis", "loquor", "lucidus", "luctus", "ludus",
      "luptatum", "macto", "magna", "mauris", "melior", "metuo", "meus",
      "minim", "modo", "molior", "mos", "natu", "neo", "neque", "nibh",
      "nimis", "nisl", "nobis", "nostrud", "nulla", "nunc", "nutus", "obruo",
      "occuro", "odio", "olim", "oppeto", "os", "pagus", "pala", "paratus",
      "patria", "paulatim", "pecus", "persto", "pertineo", "plaga", "pneum",
      "populus", "praemitto", "praesent", "premo", "probo", "proprius",
      "quadrum", "quae", "qui", "quia", "quibus", "quidem", "quidne", "quis",
      "ratis", "refero", "refoveo", "roto", "rusticus", "saepius",
      "sagaciter", "saluto", "scisco", "secundum", "sed", "si", "similis",
      "singularis", "sino", "sit", "sudo", "suscipere", "suscipit", "tamen",
      "tation", "te", "tego", "tincidunt", "torqueo", "tum", "turpis",
      "typicus", "ulciscor", "ullamcorper", "usitas", "ut", "utinam",
      "utrum", "uxor", "valde", "valetudo", "validus", "vel", "velit",
      "veniam", "venio", "vereor", "vero", "verto", "vicis", "vindico",
      "virtus", "voco", "volutpat", "vulpes", "vulputate", "wisi", "ymo",
      "zelus",
    ];
    // cSpell:enable
    $dictionary_flipped = array_flip($dictionary);
    $greeking = '';

    if (!$capitalize) {
      $words_remaining = $min_word_count;
      while ($words_remaining > 0) {
        $sentence_length = mt_rand(3, 10);
        $words = array_rand($dictionary_flipped, $sentence_length);
        $sentence = implode(' ', $words);
        $greeking .= ucfirst($sentence) . '. ';
        $words_remaining -= $sentence_length;
      }
    }
    else {
      // Use slightly different method for titles.
      $words = array_rand($dictionary_flipped, $min_word_count);
      $words = is_array($words) ? implode(' ', $words) : $words;
      $greeking = ucwords($words);
    }
    return trim($greeking);
  }

  /**
   * Generate paragraphs separated by double new line.
   *
   * @param int $paragraph_count
   *
   * @return string
   */
  public function paragraphs($paragraph_count = 12) {
    $output = '';
    for ($i = 1; $i <= $paragraph_count; $i++) {
      $output .= $this->sentences(mt_rand(20, 60)) . "\n\n";
    }
    return $output;
  }

  /**
   * Create a placeholder image.
   *
   * @param string $destination
   *   The absolute file path where the image should be stored.
   * @param string $min_resolution
   *   The minimum resolution for the image. For example, '400x300'.
   * @param string $max_resolution
   *   The maximum resolution for the image. For example, '800x600'.
   *
   * @return string
   *   Path to image file.
   */
  public function image($destination, $min_resolution, $max_resolution) {
    $extension = pathinfo($destination, PATHINFO_EXTENSION);
    $min = explode('x', $min_resolution);
    $max = explode('x', $max_resolution);

    $width = rand((int) $min[0], (int) $max[0]);
    $height = rand((int) $min[1], (int) $max[1]);

    // Make an image split into 4 sections with random colors.
    $im = imagecreate($width, $height);
    for ($n = 0; $n < 4; $n++) {
      $color = imagecolorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
      $x = $width / 2 * ($n % 2);
      $y = $height / 2 * (int) ($n >= 2);
      imagefilledrectangle($im, (int) $x, (int) $y, (int) ($x + $width / 2), (int) ($y + $height / 2), $color);
    }

    // Make a perfect circle in the image middle.
    $color = imagecolorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
    $smaller_dimension = min($width, $height);
    imageellipse($im, (int) ($width / 2), (int) ($height / 2), $smaller_dimension, $smaller_dimension, $color);

    $save_function = 'image' . ($extension == 'jpg' ? 'jpeg' : $extension);
    $save_function($im, $destination);
    return $destination;
  }

}
