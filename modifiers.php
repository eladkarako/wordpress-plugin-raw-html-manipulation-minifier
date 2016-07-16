<?php

/* ╔════════════════════════════════════════════╗
   ║ Modifiers                                  ║
   ╟────────────────────────────────────────────╢
   ║ Are functions that process raw-HTML text,  ║
   ║ and return the processed text.             ║
   ╟────────────────────────────────────────────╢
   ║ If you need to- break a long function      ║
   ║ into a few assistance-functions, then      ║
   ║ place those at assist.php file.            ║
   ╚════════════════════════════════════════════╝
░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ */


  /**
   * quickly cut all script tags and paste them before the end-body tag.
   *
   * @param string $html
   *
   * @return string
   * @link http://icompile.eladkarako.com/php-raw-html-processing-move-all-script-tags-to-the-end-of-body-tag/
   */
  function put_all_scripts_at_end_of_body($html) {
    $elements = [];

    $html = preg_replace_callback("#<script(.*?)>(.*?)</script>#is", function ($arr) use (&$elements) {
      if (!isset($arr[0])) /* no found: no add, no delete */
        return;

      $full = $arr[0];
/*      $attributes = trim($arr[1]); */
/*      $inline = $arr[2]; */

      array_push($elements, $full); /*    store content. */

      return ""; /*                      clean from HTML. */
    }, $html);

    $html = explode("</body>", $html);
    $html = $html[0] . "\n" . implode("\n", $elements) . "\n" . "</body>" . $html[1];

    return $html;
  }


  /**
   * extract both <link...rel="stylesheet".../> and <style..type="text/css"...></style> (IN THE SAME ORDER!!!)
   * removes them from their original location and place them at the head tag.
   * the stack of elements are in the same order in the page (thats why its important to use single
   * regular-expression),
   * this is important since CSS rules are meant to be overriding one-another, so THE ORDER OF LINK AND STYLE IS
   * IMPORTANT!!!
   *                for example:
   *                  <style type=text/css>wwww</style> <link rel=stylesheet a=b
   *                  href=\"http://www.google.com/style.css\"><head></head><body><style
   *                  type=text/css>rrrrrrrr</style></body> will became (plus \n)
   *                  <head>
   *                  <style type=text/css>wwww</style>
   *                  <link rel=stylesheet a=b href="http://www.google.com/style.css">
   *                  <style type=text/css>rrrrrrrr</style>
   *                  </head><body></body>
   *
   * @param string $html
   *
   * @return string
   */
  function put_all_link_css_at_end_of_head($html) {
    $elements = [];

    $html = preg_replace_callback("#(<link[^\>]*?rel=[\',\"]?stylesheet[\',\"]?[^\>]*?>|<style[^\>]*>[^\<]*?</style>)#is", function ($arr) use (&$elements) {
      $full = $arr[0];

      array_push($elements, $full); /*    store content. */

      return ""; /*                      clean from HTML. */
    }, $html);

    $html = explode("</head>", $html);
    $html = $html[0] . "\n" . implode("\n", $elements) . "\n" . "</head>" . $html[1];

    return $html;
  }


  /**
   * make multiple \n collapse to one
   *
   * @param string $html
   *
   * @return string
   */
  function collapse_multiple_line_feed($html) {

    $html = preg_replace("#\n{2,}#is", "\n", $html);

    return $html;
  }


  /**
   * make multiple \n collapse to one
   * remmember that this might effect also stuff like code in pre-tags or code-tags but thats minor change..
   *
   * @param string $html
   *
   * @return string
   */
  function collapse_white_space_inside_tags($html) {
    /* #1 - whitespace collapsing */
    $replacements = [
      "#\<\s+#sm"                     =>  "<"                               /* < link.....                                  to   <link                                  */
    , "#\s+\>#sm"                     =>  ">"                               /* <meta.....   >                               to   <meta.....>                            */
    , "#\s+\/\>#sm"                   =>  "/>"                              /* <meta.....   />                              to   <meta...../>                           */
    , "#\<([^\>]*)\s\s+([^\>]*)\>#"   =>  "<$1 $2>"
    //, "#([^\"\']+\=[\"\'][^\"\']+[\"\'])\s+#sm" => "$1 "    /* <meta name="tags"      content="hello"/>     to   <meta name="tags" content="hello"/>    */
    ];
    
    $html = preg_replace(array_keys($replacements), array_values($replacements), $html);

    return $html;
  }


  function collapse_white_space_between_tags($html) {
    /* #1 - whitespace collapsing */
    $replacements = [
      "#>\n+<#sm"   => "><" /* separation by new line can be omitted */
      , "#>\s+<#sm" => "> <"/* separation by whitespace can not be omitted since it will change the line-breaks shown on the page. */
    ];

    /* #2 - whitespace removing - safe to remove on non-standard ending tags */
    $replacements['#\s*<!--#smi'] = '<!--';
    $replacements['#-->\s*#smi'] = '-->';
    $replacements['#\s*\<br\s*\/{0,1}\>\s*#smi'] = '<br>';
    $replacements['#\s*\<hr\s*\/{0,1}\>\s*#smi'] = '<hr>';

    /* #2 - whitespace removing - safe to remove on standard ending tags */
    $tags = ['tr', 'li', 'script', 'iframe', 'div', 'title', 'pre'];
    foreach ($tags as $tag) {
      $replacements[ "#\s*\<" . $tag . "#i" ] = "<" . $tag;     /* whitespace before the opening tag */
      $replacements[ "#</" . $tag . ">\s*#i" ] = "</" . $tag . ">";     /* whitespace after the ending tag */
    }

    $html = preg_replace(array_keys($replacements), array_values($replacements), $html);

    return $html;
  }



  /**
   * removes the whitespace at start and end of the HTML
   *
   * @param string $html
   *
   * @return string
   */
  function remove_white_space_around_edges($html) {
    $html = trim($html);

    return $html;

  }


  /**
   * remove self closing tag ending and collapse whitespace
   *
   * @param string $html
   *
   * @return string
   */
  function remove_self_end_tag_and_collapse_whitespace($html) {
    $html = preg_replace("/\s*\/\>/smi", ">", $html);

    return $html;
  }


  /**
   * @param $html
   *
   * @return string
   */
  function minify_all_inner_css_in_style_tags($html) {
    $html = preg_replace_callback("#(<style[^\>]*>)([^\<]*?)</style>#msi", function ($arr) {
      if (!array_key_exists(0, $arr) || !array_key_exists(1, $arr) || !array_key_exists(2, $arr)) /* not found */
        return "";

      $full = $arr[0];
      $tag_start = $arr[1];
      $inline = $arr[2];

      $inline = minify_css($inline); /* minify */

      return $tag_start . $inline . '</style>'; /* reassemble */
    }, $html);

    return $html;
  }


  /**
   * @param string $html
   *
   * @return string
   */
  function minify_all_inner_javascript_in_script_tags($html) {
    /* classic type="text/javascript" */
    $html = preg_replace_callback("/(<script[^\>]*?type=[\',\"]?text\/javascript[\',\"]?[^\>]*?\>)([^\<]*?)\<\/script\>/msi", function ($arr) {
      if (!array_key_exists(0, $arr) || !array_key_exists(1, $arr) || !array_key_exists(2, $arr)) /* not found */
        return "";

      $full = $arr[0];
      $tag_start = $arr[1];
      $inline = $arr[2];

      $inline = minify_javascript($inline); /* minify */

      return $tag_start . $inline . '</script>'; /* reassemble */
    }, $html);

    /* implicit HTML5 script tags with no type (there are other kinds which will not be handled) */
    /* there is a non-greedy condition since we want to avoid skipping a script due to CDATA text inside (looks like closing script-tag..) */
    $html = preg_replace_callback("#\<script\s*\>(.*?)\<\/script\>#msi", function ($arr) {
      $full = $arr[0];
      $inline = $arr[1];

      $inline = minify_javascript($inline); /* minify */

      return '<script>' . $inline . '</script>'; /* reassemble */
    }, $html);


    return $html;
  }


  /**
   * make sure meta tags are unique, link tags are unique,
   * collapse duplicates (at any place in the HTML, not have to be near each-other).
   * - it not handle script tags because of jQuery image-lazy-loading lags.
   *
   * @param  string $html - input HTML.
   *
   * @return string       - output HTML, after processing.
   */
  function unify_duplicated_tags($html) {
    $tags = [
      'meta'   => 'content'
      , 'link' => 'href'
    ];

    foreach ($tags as $tag => $attribute) {
      $unique = [];

      $html = preg_replace_callback("#<" . $tag . "[^\>]*?" . $attribute . "\s*=\s*(\'[^\']*\'|\"[^\"]*\"|[^\s]*?)[^\>]*?>#is", function ($arr) use (&$sources, &$unique) {
        $full = $arr[0];
        $attribute_content = $arr[0];

        /* remove \' \" wrapping (if any) */
        $attribute_content = preg_replace(["#^\'([^\']*?)\'$#s", "#^\"([^\"]*?)\"$#s"], ["\\1", "\\1"], $attribute_content);


        if (in_array($attribute_content, $unique))
          $full = ""; /* clean entire tag from HTML */
        else
          array_push($unique, $attribute_content);

        var_dump($unique);

        return $full;
      }, $html);

      unset($unique); /* we don't compare cross-tag's content (theoretically we can have meta's content same as link's href). */
    }

    return $html;
  }


?>
