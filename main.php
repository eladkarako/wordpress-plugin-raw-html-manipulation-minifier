<?php
  /**
   * Plugin Name:        Minifier
   * Donate link:        https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=7994YX29444PA
   * License:            GPL2
   * Version:            4.1.0
   * Description:        Ruthless HTML-Manipulation At A Level Beyond WordPress-API, Minify Whitespace, Move CSS And Styles To HEAD's End, And Scripts To BODY's End, While Preserving Unmodified PRE-Tag's Content Which May Be Similar To HTML-Source. Many More MODIFIERS Can Be Added. Add Verbose Message At The End Of The HTML-Source (As A HTML-Comment) By Uncommenting The Line With "...get_delta_information..." In The Function Below.
   * Author:             eladkarako
   * Author Email:       The_Author_Value_Above@gmail.com
   * Author URI:         http://icompile.eladkarako.com
   * Plugin URI:         https://github.com/eladkarako/wordpress-plugin-raw-html-manipulation-minifier
   */


/* ╔═════════════════════════════════════════════════════╗
   ║ - Hope You've Enjoyed My Work :]                    ║
   ╟─────────────────────────────────────────────────────╢
   ║ - Feel Free To Modifiy And Distribute it (GPL2).    ║
   ╟─────────────────────────────────────────────────────╢
   ║ - Donations Are A *Nice* Way Of Saying Thank-You :] ║
   ║   But Are NOT Necessary!                            ║
   ║                                                     ║
   ║ I'm Doing It For The Fun Of It :]                   ║
   ║                                                     ║
   ║    - Elad Karako                                    ║
   ║         Tel-Aviv, Israel- July 2016.                ║
   ╚═════════════════════════════════════════════════════╝
░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ */


call_user_func(function () {
  if (is_admin()) return;

  require_once('assist.php');
  require_once('modifiers.php');

/*╔══════════════════╗
  ║ Modify Raw-HTML. ║
  ╚══════════════════╝*/
  add_action('template_redirect', function (){
    @ob_start(function($html){
    /*────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────*/
    /*╔═══════╗
      ║ $html ║
      ╚═══════╝*/
                $html_before = $html . '';                                   /*    used to compare before/after states               */
                $html = protect_specific_tags_from_modifications($html);     /*    protect pre-tags and code-tags original content.  */
                /*-------------------------------------------------------------------------------------------------------------*/
    /*╔═══════════╗
      ║ HEAD only ║
      ╚═══════════╝*/
                $html = preg_replace_callback("#(<\s*head[^>]*>?)(.*?)(<\s*/\s*head[^>]*>?)#ism",function($match_parts){
                  if(false === array_key_exists(0, $match_parts)  )  return ""; /* no match */
                  if(false === array_key_exists(1, $match_parts) ||             /* invalid HEAD content */
                     false === array_key_exists(2, $match_parts) ||
                     false === array_key_exists(3, $match_parts)  )  return $match_parts[0]; /* unmodified content */

                  $opening_tag = $match_parts[1];
                  $inner_html  = $match_parts[2];
                  $closing_tag = $match_parts[3];

                  /* --------------------------------------- */
                  /* -   add "just HEAD modifiers" here      */
                  /* --------------------------------------- */
                  return $opening_tag . $inner_html . $closing_tag;
                }, $html, /*limit=*/ 1);
    /*╔═══════════╗
      ║ BODY only ║
      ╚═══════════╝*/
                $html = preg_replace_callback("#(<\s*body[^>]*>?)(.*?)(<\s*/\s*body[^>]*>?)#ism",function($match_parts){
                  if(false === array_key_exists(0, $match_parts)  )  return ""; /* no match */
                  if(false === array_key_exists(1, $match_parts) ||             /* invalid BODY content */
                     false === array_key_exists(2, $match_parts) ||
                     false === array_key_exists(3, $match_parts)  )  return $match_parts[0]; /* unmodified content */

                  $opening_tag = $match_parts[1];
                  $inner_html  = $match_parts[2];
                  $closing_tag = $match_parts[3];

                  /* --------------------------------------- */
                  /* -   add "just BODY modifiers" here      */
                  /* --------------------------------------- */
                  return $opening_tag . $inner_html . $closing_tag;
                }, $html, /*limit=*/ 1);
    /*╔══════════════════════════╗
      ║ Global (all of the HTML) ║
      ╚══════════════════════════╝*/
                /*#0*/
                $html = put_all_link_css_at_end_of_head($html);              /*    considered Google-PageSpeed Best-Practice         */
                $html = put_all_scripts_at_end_of_body($html);               /*    considered Google-PageSpeed Best-Practice         */
                /*#1*/
                $html = collapse_multiple_line_feed($html);                  /*    saves about 2%                                    */
                $html = collapse_white_space_inside_tags($html);             /*    saves about 5-8%                                  */
                $html = collapse_white_space_between_tags($html);            /*    saves about 5-8%                                  */
                $html = remove_white_space_around_edges($html);              /*    saves about 5-8%                                  */
                /*#2*/
                $html = remove_self_end_tag_and_collapse_whitespace($html);  /*    saves about 1%                                    */
                $html = unify_duplicated_tags($html);                        /*    saves about 1%                                    */
                /*#3*/
                $html = minify_all_inner_css_in_style_tags($html);           /*    saves about 2%                                    */
                $html = minify_all_inner_javascript_in_script_tags($html);   /*    saves about 4%                                    */
               /*******************************
                * add more modifiers here...  *
                *******************************/
                /*-------------------------------------------------------------------------------------------------------------*/
                $html = unprotect_pre_and_code_tags_content_from_change($html);  /*  unprotect (bring back) pre-tags and code-tags original content. */

                // $html = $html . get_delta_information($html_before, $html);   /*  (optional) see delta, compared to raw HTML.                     */
                unset($html_before);                                             /*  cleanup.                                                        */

                $size_final = mb_strlen($html, '8bit');                          /*  the delta actually adds to the html..                           */
                header('X-Y-Content-Length: ' . $size_final . '', true);         /*  write verbose length to HTTP-header.                            */

    /*────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────*/
                return $html;
             });
  }, -9999999);

  add_action('shutdown', function () {
    while (ob_get_level() > 0) @ob_end_flush();
  }, +9999999);
});

?>