<?php

namespace HBM\HelperBundle\Service;

/**
 * Taken from WordPress: wp-includes/formatting.php
 */
class SanitizingHelper {

  /**
   * @var array
   */
  private $config;

  /**
   * SanitizingHelper constructor.
   *
   * @param $config
   */
  public function __construct(array $config) {
    $this->config = $config;
  }

  /**
   * @param string|null $lang
   *
   * @return mixed
   */
  private function lang(?string $lang) : ?string {
    if ($lang === NULL) {
      $lang = $this->config['language'];
    }

    return $lang;
  }

  /**
   * @return mixed
   */
  private function sep() {
    return $this->config['sep'];
  }

  /****************************************************************************/

  /**
   * Repair html.
   *
   * @param string|null $html
   * @param array $options
   *
   * @return string
   */
  public function repairHtml(?string $html, array $options = []) : string {
    $defaultOptions = [
      'show-body-only' => TRUE,
      'output-xhtml' => TRUE,
      'quote-ampersand' => FALSE,
      'wrap' => FALSE,
      'char-encoding' => 'utf8',
      'newline' => 'CRLF',
    ];

    $mergedOptions = array_merge($defaultOptions, $options);

    $tidy = new \tidy();
    $htmlTidy = $tidy->repairString($html, $mergedOptions, 'UTF8');

    return str_replace("\r\n", "\n", trim($htmlTidy));
  }

  /**
   * Ensures folder sep according to arguments.
   *
   * @param string|null $path
   * @param bool|null $leading
   * @param bool|null $trailing
   *
   * @return string
   */
  public function ensureSep(?string $path, ?bool $leading = NULL, ?bool $trailing = NULL) : string {
    if ($leading !== NULL) {
      $path = ltrim($path, $this->sep());
    }
    if ($leading === TRUE) {
      $path = $this->sep().$path;
    }

    if ($trailing !== NULL) {
      $path = rtrim($path, $this->sep());
    }
    if ($trailing === TRUE) {
      $path .= $this->sep();
    }

    return $path;
  }

  /**
   * Ensures a folder sep at the end of the path.
   *
   * @param string|null $path
   *
   * @return string
   */
  public function ensureTrailingSep(?string $path) : string {
    return $this->ensureSep($path, NULL, TRUE);
  }

  /**
   * Ensures a folder sep at the beginning of the path.
   *
   * @param string|null $path
   *
   * @return string
   */
  public function ensureLeadingSep(?string $path) : string {
    return $this->ensureSep($path, TRUE);
  }

  /**
   * Ensures a folder sep at the end of the directory and no folder sep at the beginning
   *
   * @param string|null $path
   *
   * @return string
   */
  public function normalizeFolderRelative(?string $path) : string {
    return $this->ensureSep($this->unifySep($path), FALSE, TRUE);
  }

  /**
   * Ensures a folder sep at the beginning and at the end of the directory.
   *
   * @param string|null $path
   *
   * @return string
   */
  public function normalizeFolderAbsolute(?string $path) : string {
    return $this->ensureSep($this->unifySep($path), TRUE, TRUE);
  }

  /**
   * Strips the folder separator from the beginning of the file.
   *
   * @param string|null $path
   *
   * @return string
   */
  public function normalizeFileRelative(?string $path) : string {
    return $this->ensureSep($this->unifySep($path), FALSE);
  }

  /**
   * Strips the folder separator from the beginning of the file.
   *
   * @param string|null $path
   *
   * @return string
   */
  public function normalizeFileAbsolute(?string $path) : string {
    return $this->ensureSep($this->unifySep($path), TRUE);
  }

  /****************************************************************************/

  /**
   * Replace windows folder delimiter.
   *
   * @param string|null $path
   *
   * @return string
   */
  public function unifySep(?string $path) : string {
    return str_replace('\\', $this->sep(), $path);
  }

  /**
   * Returns a path where all string parts between the folder separator have been sanitized.
   *
   * @param string|null $path
   * @param bool $case_sensitive
   * @param string|null $lang
   *
   * @return string
   */
  public function sanitizePath(?string $path, bool $case_sensitive = FALSE, ?string $lang = NULL) : string {
    $path_parts = explode($this->sep(), $this->unifySep($path));

    $sanitized_path_parts = array();
    foreach ($path_parts as $path_part) {
      $sanitized_path_part = $this->sanitizeChars($path_part, FALSE, $case_sensitive, $this->lang($lang));

      if ($sanitized_path_part !== '') {
        $sanitized_path_parts[] = $sanitized_path_part;
      }
    }

    return implode($this->sep(), $sanitized_path_parts).$this->sep();
  }

  /**
   * Returns a string where all invalid chars have been sanitized.
   *
   * @param string|null$string
   * @param bool $with_slash
   * @param bool $case_sensitive
   * @param string|null $lang
   *
   * @return string
   */
  public function sanitizeString(?string $string, bool $with_slash = FALSE, bool $case_sensitive = FALSE, ?string $lang = NULL) : string {
    return $this->sanitizeChars($string, $with_slash, $case_sensitive, $this->lang($lang));
  }

  /**
   * Returns a lowercase string where all invalid chars have been sanitized.
   *
   * @param $string
   * @param string $lang
   * @return string
   */
  public function slug($string, $lang = NULL) : string {
    return $this->sanitizeString($string, FALSE, FALSE, $this->lang($lang));
  }

  /**
   * To be continued: http://unicode.e-workers.de/unicode.php
   *
   * TODO: Continue at "Großes G mit Zirkumflex"
   *
   * @param string|null $string
   * @param boolean $withSlash
   * @param boolean $caseSensitive
   * @param string|null $lang
   *
   * @return string
   */
  private function sanitizeChars(?string $string, bool $withSlash = FALSE, bool $caseSensitive = FALSE, ?string $lang = NULL) : string {
    $langs = array(
      '@' => array('de' => '-at-',     'en' => '-at-'),
      '&' => array('de' => '-und-',    'en' => '-and-'),
      '#' => array('de' => '-nummer-', 'en' => '-number-'),

      '€' => array('de' => '-euro-',   'en' => '-euro-'),
      '¢' => array('de' => '-cent-',   'en' => '-cent-'),
      '£' => array('de' => '-pfund-',  'en' => '-pound-'),
      '¥' => array('de' => '-yen-',    'en' => '-yen-'),

      '©' => array('de' => '-copyright-',         'en' => '-copyright-'),
      '®' => array('de' => '-eingetragene-marke-','en' => '-registered-trade-mark-'),

      '¼' => array('de' => '-viertel-',    'en' => '-quater-'),
      '½' => array('de' => '-halb-',       'en' => '-half-'),
      '¾' => array('de' => '-dreiviertel-','en' => '-three-quater-'),
    );

    if (!$caseSensitive) {
      $string = mb_strtolower($string, 'UTF-8');
    } else {
      $string = utf8_encode($string);
    }

    if (!$withSlash) {
      $string = str_replace('/', '-', $string);
    }

    $search_replace = array();
    $search_replace[] = array('search' => ' ', 'replace' => '-');

    // TRANS
    if ($lang !== NULL) {
      foreach ($langs as $tmp_key => $tmp_value) {
        $search_replace[] = array('search' => $tmp_key, 'replace' => $tmp_value[$lang]);
      }
    }

    /**********************************************************************/

    // UMLAUT
    $search_replace[] = array('search' => 'ä', 'replace' => 'ae');
    $search_replace[] = array('search' => 'ö', 'replace' => 'oe');
    $search_replace[] = array('search' => 'ü', 'replace' => 'ue');
    $search_replace[] = array('search' => 'ß', 'replace' => 'ss');

    // LETTERS
    $search_replace[] = array('search' => array('à', 'â', 'á', 'ã', 'å', 'æ', 'ā', 'ă', 'ą'),	'replace' => 'a');
    $search_replace[] = array('search' => array('ç', 'ć', 'ĉ', 'ċ', 'č'),						          'replace' => 'c');
    $search_replace[] = array('search' => array('ď', 'đ'),										                'replace' => 'd');
    $search_replace[] = array('search' => array('è', 'ê', 'é', 'ë', 'ē', 'ĕ', 'ė', 'ę', 'ě'),	'replace' => 'e');
    $search_replace[] = array('search' => array('ì', 'î', 'í', 'ĩ', 'ï'),						          'replace' => 'i');
    $search_replace[] = array('search' => array('ð'),											                    'replace' => 'd'); // eth
    $search_replace[] = array('search' => array('ñ'),											                    'replace' => 'n');
    $search_replace[] = array('search' => array('ò', 'ô', 'ó', 'õ', 'ø'),						          'replace' => 'o');
    $search_replace[] = array('search' => array('ù', 'û', 'ú', 'ũ'),							            'replace' => 'u');
    $search_replace[] = array('search' => array('þ'),											                    'replace' => 'th'); // thorn
    $search_replace[] = array('search' => array('ÿ', 'ý'),										                'replace' => 'y');
    $search_replace[] = array('search' => array('š'),											                    'replace' => 's');
    $search_replace[] = array('search' => array('ž'),											                    'replace' => 'z');
    $search_replace[] = array('search' => array('þ'),											                    'replace' => 'b');
    $search_replace[] = array('search' => array('ƒ'),											                    'replace' => 'f');

    /**********************************************************************/

    // UPPER
    if ($caseSensitive) {
      // UMLAUT
      $search_replace[] = array('search' => 'Ä', 'replace' => 'Ae');
      $search_replace[] = array('search' => 'Ö', 'replace' => 'Oe');
      $search_replace[] = array('search' => 'Ü', 'replace' => 'Ue');

      // LETTERS
      $search_replace[] = array('search' => array('À', 'Â', 'Á', 'Ã', 'Å', 'Æ', 'Ā', 'Ă', 'Ą'),	'replace' => 'A');
      $search_replace[] = array('search' => array('Ç', 'Ć', 'Ĉ', 'Ċ', 'Č'),						          'replace' => 'C');
      $search_replace[] = array('search' => array('Ď', 'Đ'),										                'replace' => 'D');
      $search_replace[] = array('search' => array('È', 'Ê', 'É', 'Ë', 'Ē', 'Ĕ', 'Ė', 'Ę', 'Ě'),	'replace' => 'E');
      $search_replace[] = array('search' => array('Ì', 'Î', 'Í', 'Ĩ', 'Ï'),						          'replace' => 'I');
      $search_replace[] = array('search' => array('Ð'),											                    'replace' => 'D'); // Eth
      $search_replace[] = array('search' => array('Ñ'),											                    'replace' => 'N');
      $search_replace[] = array('search' => array('Ò', 'Ô', 'Ó', 'Õ', 'Ø'),						          'replace' => 'O');
      $search_replace[] = array('search' => array('Ù', 'Û', 'Ú', 'Ũ'),							            'replace' => 'U');
      $search_replace[] = array('search' => array('Ý'),											                    'replace' => 'Y');
      $search_replace[] = array('search' => array('Þ'),											                    'replace' => 'Th'); // Thorn
      $search_replace[] = array('search' => array('Š'),											                    'replace' => 'S');
      $search_replace[] = array('search' => array('Ž'),											                    'replace' => 'Z');
    }

    /**********************************************************************/

    foreach ($search_replace as $data) {
      $string = str_replace($data['search'], $data['replace'], $string);
    }

    $search_replace = array(
      array('search' => '/^(-*)/', 'replace' => ''),                  // Replace starting hyphens
      array('search' => '/(-*)$/', 'replace' => ''),                  // Remove trailing hyphens
      array('search' => '/(-+)/', 'replace' => '-')                   // Merge multiple hyphens to one
    );

    foreach ($search_replace as $data) {
      $string = preg_replace($data['search'], $data['replace'], $string);
    }

    /**********************************************************************/

    $valid_characters = 'abcdefghijklmnopqrstuvwxyz'.'0123456789'.'-_.';
    if ($caseSensitive) {
      $valid_characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    if ($withSlash) {
      $valid_characters .= '/';
    }
    $valid_characters = str_split($valid_characters);

    $letters = str_split($string);
    foreach ($letters as $key => $value) {
      if (!\in_array($value, $valid_characters, TRUE)) {
        $letters[$key] = '';
      }
    }

    return implode('', $letters);
  }

}
