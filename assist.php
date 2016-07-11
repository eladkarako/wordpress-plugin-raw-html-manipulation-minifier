<?php
  /**
   * here you put "assistance" functions so your "modifier will not be huge.
   * you can put here any type of helping function, as long its "context-free" (for example, you give it text, and get
   * variation of it, etc..)
   */

  /**
   * CSS minifier.
   * same logic, minimum whitespace.
   * - it runs using a heuristic-set of rules according to minimum whitespace required by latest W3C standard of CSS,
   * - it takes into account CSS3 pseudo-selectors and tags such as '::before'.
   * - it is flexible enough  to handle any future additions
   * - hassle free.
   *
   * @param string $css - raw input to be minified.
   *
   * @return string
   * @author Elad Karako (eladkarako@gmail.com)
   * @link   http://icompile.eladkarako.com
   */
  function minify_css($css = '') {
    /* safe minification (non/minimal content-related) */
    $css = call_user_func_array(function ($css) {
      $replacements = [
        /* single-line comments (need to be first since newline is the comment-end-indication :( */
          "#\/\/.*\r\n#sm"                   => ''
        , "#\/\/.*\n#sm"                     => ''
        /* surrounding whitespace */
        , "#\r\n+#sm"                        => ''
        , "#\r+#sm"                          => ''
        , "#\n+#sm"                          => ''
        , "#\t+#sm"                          => ''
        /* multiline comments */
        , "#\/\*([^/]+?)\*\/#s"              => ''

        /* CDATA prefix/sufix */
        , "#\/\*\s*\<\!\[cdata\[\s*\*\/#msi" => ''
        , "#\/\*\s*\]\]\>\s*\*\/#msi"        => ''

        /* typo too-much semicolon (SASS old bug) */
        , "/;{2,}/sm"                        => ';'
        /* redundant last-semicolon */
        , "/;}/sm"                           => '}'
      ];

      $css = preg_replace(array_keys($replacements), array_values($replacements), $css);

      return $css;
    }, [$css]);


    /* advanced content-related minification */
    $css = call_user_func_array(function ($css) {
      $css = str_split($css);
      $buffer = [];

      foreach ($css as $index => $char) {
        $val = ord($char);
        $char_prev = array_key_exists($index - 1, $css) ? $css[ $index - 1 ] : '#';       /* '#' is outside of our 'ABC' language definition (Discreet-Mathematics) */
        $char_next = array_key_exists($index + 1, $css) ? $css[ $index + 1 ] : '#';       /* '#' is outside of our 'ABC' language definition (Discreet-Mathematics) */

        "\n" === $char
        ||
        9 === $val /* tab */
        ||
        ' ' === $char && false !== mb_strpos(" ;:{},", $char_prev)
        ||
        ' ' === $char && false !== mb_strpos(" ;:{},", $char_next)
        ||
        array_push($buffer, $char);
      }

      $css = implode('', $buffer);

      return $css;
    }, [$css]);

    return $css;
  }


  /**
   * simple javascript minifier.
   *
   * @param string $javascript - raw input to be minified.
   *
   * @return string
   */
  function minify_javascript($javascript = '') {
    $javascript = minify_css($javascript);

    return $javascript;
  }


  /**
   * step #1.
   *
   *
   * number formatting, just like http://php.net/number_format ,  but using string manipulation,
   * providing, virtually, an unlimited precision.
   *
   * @param float|int|string $number        - the input to be formatted.
   * @param int              $decimals      - amount of digits after.
   * @param string           $dec_point     - normally a dot.
   * @param string           $thousands_sep - specify empty string to avoid formatting.
   *
   * @return string
   * @author Elad Karako (icompile.eladkarako.com)
   * @link   http://icompile.eladkarako.com
   */
  function format_number($number, $decimals = 0, $dec_point = '.', $thousands_sep = ',') {

    $number = (string)$number; /* convert to string to provide an unlimited precision. */
    $number = explode($dec_point, $number);

    /* break float to integer and reminder */
    $remainder = isset($number[1]) ? $number[1] : (string)0; /*  reminder */
    $number = $number[0]; /*                                     integer */

    /* make reminder match decimal length, pad with zeros on the right */
    $remainder .= str_repeat(
      "0",
      max(0, strlen($decimals - $remainder)) /* if reminder length is shorted then needed, pad with zeros */
    );

    $remainder = substr($remainder, 0, $decimals); /* shorten if needed */


    /* format thousands */
    $number = preg_replace_callback('/(\d)(?=(\d{3})+$)/', function ($arr) use ($thousands_sep) {
      return isset($arr[0]) ? ($arr[0] . $thousands_sep) : "";
    }, $number);

    $remainder = preg_replace_callback('/(\d)(?=(\d{3})+$)/', function ($arr) use ($thousands_sep) {
      return isset($arr[0]) ? ($arr[0] . $thousands_sep) : "";
    }, $remainder);

    return (0 === $decimals) ? $number : ($number . $dec_point . $remainder); /* return int if 0 decimals */
  }


  /**
   * @param float $size                - the memory size to format
   * @param bool  $is_full_description (optional) - use *Bytes instead of *b (GigaBytes instead of gb, etc...).
   * @param int   $digits              (optional) - number of digits decimal point, to limit.
   *
   * @return string
   */
  function human_readable_memory_sizes($size, $is_full_description = false, $digits = 20) {
    $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
    $unit_full = ['Bytes', 'KiloByte', 'MegaBytes', 'GigaBytes', 'TeraBytes', 'PetaBytes'];

    $out = $size / pow(1024, ($i = floor(log($size, 1024))));

    $out = sprintf("%." . $digits . "f", $out);

    $out = format_number($out, 2);

    $out .= ' ' . (!$is_full_description ? $unit[ (int)$i ] : $unit_full[ (int)$i ]);

    return $out;
  }


  /**
   * measure the difference,
   * return a formatter HTML comment.
   *
   * @param string $html_before - the raw HTML
   * @param string $html_after  - any state HTML (preferably, at final state..)
   *
   * @return string             - HTML comment you can place at the end of the HTML (for example).
   */
  function get_delta_information($html_before, $html_after) {

    $length_chars_before = mb_strlen($html_before);
    $length_bytes_before = mb_strlen($html_before, '8bit');

    $length_chars_after = mb_strlen($html_after);
    $length_bytes_after = mb_strlen($html_after, '8bit');

    unset($html_before); /* just locally to the function. */
    unset($html_after);  /* just locally to the function. */

    $results = [
      "chars" => [
        "before"  => format_number($length_chars_before),
        "after"   => format_number($length_chars_after),
        "delta"   => format_number($length_chars_before - $length_chars_after),
        "percent" => format_number(100 * (($length_chars_after - $length_chars_before) / $length_chars_before)) . '%'
      ],
      "bytes" => [
        "before"  => human_readable_memory_sizes($length_bytes_before),
        "after"   => human_readable_memory_sizes($length_bytes_after),
        "delta"   => human_readable_memory_sizes($length_bytes_before - $length_bytes_after),
        "percent" => format_number(100 * (($length_bytes_after - $length_bytes_before) / $length_bytes_before)) . '%'
      ]
    ];

    $results = array_merge([], ["all" => $results]); /* adds "all" */

    /* -- */

    $output = base64_decode("CjwhLS0gV29yZFByZXNzIFJhdy1IVE1MLVByb2Nlc3NpbmcgRnJhbWV3b3JrIEZvciBQSFAtRGV2ZWxvcGVycyAvRWxhZCBLYXJha28gKDIwMTUpICAK");
    $output .= json_encode($results);/*, JSON_PRETTY_PRINT);*/
    $output .= "\n-->\n";

    return $output;

  }

  /* o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o */
  /* o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o */
  /* o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o */

  /**
   * those two function solved a very pesky problem,
   * <pre> and <code> tags may include a text that looks like HTML, JavaScript or other "target for modification",
   * to prevent modification from "finding it valid for modification", I'm keeping the original-content, plain-sight,
   * (even compress it to save space), the last step (before returning the HTML) is to reverse the protection,
   *
   * @original_idea_and_implementation: Elad Karako (eladkarako@gmail.com) ;)
   */


  /**
   * the content of the pre-tags and code-tags should be intact,
   * run "protect_pre_and_code_tags_content_from_change" before each starting to modify,
   * and "unprotect_pre_and_code_tags_content_from_change" after you've done modifying the HTML.
   *
   * @param $html
   *
   * @return mixed
   */
  function protect_specific_tags_from_modifications($html) {
    $tags_to_protect = [
      'pre'        => '_p_r_e_'
      , 'code'     => '_c_o_d_e_'
      , 'textarea' => '_t_e_x_t_a_r_e_a_'
    ];

    foreach ($tags_to_protect as $tag => $protected_tag) {
      $html = preg_replace_callback("#<" . $tag . "(.*?)>(.*?)</" . $tag . ">#is", function ($arr) use ($tag, $protected_tag) {
        if (!isset($arr[0])) /* no found: no add, no delete */
          return;

        $full = $arr[0];

        return '<' . $protected_tag . '>' . base64_encode(gzcompress($full)) . '</' . $protected_tag . '>'; /*                      clean from HTML. */
      }, $html);
    }

    return $html;
  }

  /**
   * returning the pre-tags and code-tags original unmodified content, run this after
   * "protect_pre_and_code_tags_content_from_change" and all the HTML-modifying (last before returning the HTML).
   *
   * @param $html
   *
   * @return mixed
   */
  function unprotect_pre_and_code_tags_content_from_change($html) {
    $tags_to_unprotect = [
      '_p_r_e_'
      , '_c_o_d_e_'
      , '_t_e_x_t_a_r_e_a_'
    ];


    foreach ($tags_to_unprotect as $index => $tag) {
      $html = preg_replace_callback("#<" . $tag . ">(.*?)</" . $tag . ">#is", function ($arr) use ($tag) {
        if (!isset($arr[0])) /*no found: no add, no delete*/
          return;

/*        $full = $arr[0]; */
        $inline = $arr[1];

        return gzuncompress(base64_decode($inline)); /*                      clean from HTML. */
      }, $html);
    }

    return $html;
  }


  /* o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o */
  /* o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o */
  /* o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o0O0o */

?>
