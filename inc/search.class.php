<?php
use Glpi\Application\View\TemplateRenderer;
class PluginTicketfilteredpageSearch extends Search
{
  /**
   * Display search engine for an type
   *
   * @param string  $itemtype Item type to manage
   *
   * @return void
   **/
  public static function show($itemtype)
  {

      $params = self::manageParams($itemtype, $_GET);

      echo "<div class='search_page row'>";
      TemplateRenderer::getInstance()->display('layout/parts/saved_searches.html.twig', [
          'itemtype' => $itemtype,
      ]);
      echo "<div class='col search-container'>";


      self::showGenericSearch($itemtype, $params);
      if ($params['as_map'] == 1) {
          self::showMap($itemtype, $params);
      } elseif ($params['browse'] == 1) {
          $itemtype::showBrowseView($itemtype, $params);
      } else {
           self::showList($itemtype, $params);
      }
      echo "</div>";
      echo "</div>";
  }

  /**
   * Completion of the URL $_GET values with the $_SESSION values or define default values
   *
   * @param string  $itemtype        Item type to manage
   * @param array   $params          Params to parse
   * @param boolean $usesession      Use datas save in session (true by default)
   * @param boolean $forcebookmark   Force trying to load parameters from default bookmark:
   *                                  used for global search (false by default)
   *
   * @return array parsed params
   **/
  public static function manageParams(
      $itemtype,
      $params = [],
      $usesession = true,
      $forcebookmark = false
  ) {
      $default_values = [];

      $default_values["start"]       = 0;
      $default_values["order"]       = "ASC";
      $default_values["sort"]        = 1;
      $default_values["is_deleted"]  = 0;
      $default_values["as_map"]      = 0;
      $default_values["browse"]      = 0;

      if (isset($params['start'])) {
          $params['start'] = (int)$params['start'];
      }

      $default_values["criteria"]     = self::getDefaultCriteria($itemtype);
      $default_values["metacriteria"] = [];

     // Reorg search array
     // start
     // order
     // sort
     // is_deleted
     // itemtype
     // criteria : array (0 => array (link =>
     //                               field =>
     //                               searchtype =>
     //                               value =>   (contains)
     // metacriteria : array (0 => array (itemtype =>
     //                                  link =>
     //                                  field =>
     //                                  searchtype =>
     //                                  value =>   (contains)

      if ($itemtype != AllAssets::getType() && class_exists($itemtype)) {
         // retrieve default values for current itemtype
          $itemtype_default_values = [];
          if (method_exists($itemtype, 'getDefaultSearchRequest')) {
              $itemtype_default_values = call_user_func([$itemtype, 'getDefaultSearchRequest']);
              $itemtype_default_values['criteria'][0]['value'] = 'notold';//фильт для заявок по умолчанию статус нерегенные
          }

         // retrieve default values for the current user
          $user_default_values = SavedSearch_User::getDefault(Session::getLoginUserID(), $itemtype);
          if ($user_default_values === false) {
              $user_default_values = [];
          }

         // we construct default values in this order:
         // - general default
         // - itemtype default
         // - user default
         //
         // The last ones erase values or previous
         // So, we can combine each part (order from itemtype, criteria from user, etc)
          $default_values = array_merge(
              $default_values,
              $itemtype_default_values,
              $user_default_values
          );

          // file_put_contents('../tmp/buffer.txt',PHP_EOL.PHP_EOL. json_encode($params,JSON_UNESCAPED_UNICODE), FILE_APPEND);
            //CUSTOM START//

              unset($_SESSION['glpisearch'][$itemtype]['criteria']);
              $_SESSION['glpisearch'][$itemtype]['criteria'][] = [
                'field' => '12',
                'searchtype' => 'equals',
                'value' => 'notold'
              ];


              if(isset($params['ticket_i_assigned']))
              {

                $_SESSION['glpisearch'][$itemtype]['criteria'][] = [
                  'link' => 'AND',
                  'field' => '5',
                  'searchtype' => 'equals',
                  'value' => 'myself',
                    'hide' => true
                ];

              }
              if(isset($params['ticket_i_author']))
              {

                $_SESSION['glpisearch'][$itemtype]['criteria'][] = [
                  'link' => 'AND',
                  'field' => '4',
                  'searchtype' => 'equals',
                  'value' => 'myself',
                    'hide' => true
                ];

              }


            //CUSTOM END//
      }

     // First view of the page or force bookmark : try to load a bookmark
      if (
          $forcebookmark
          || ($usesession
            && !isset($params["reset"])
            && !isset($_SESSION['glpisearch'][$itemtype]))
      ) {
          $user_default_values = SavedSearch_User::getDefault(Session::getLoginUserID(), $itemtype);

          if ($user_default_values) {
              $_SESSION['glpisearch'][$itemtype] = [];
             // Only get datas for bookmarks
              if ($forcebookmark) {
                  $params = $user_default_values;
              } else {
                  $bookmark = new SavedSearch();
                  $bookmark->load($user_default_values['savedsearches_id']);
              }
          }
      }
     // Force reorder criterias
      if (
          isset($params["criteria"])
          && is_array($params["criteria"])
          && count($params["criteria"])
      ) {
          $tmp                = $params["criteria"];
          $params["criteria"] = [];
          foreach ($tmp as $val) {
              $params["criteria"][] = $val;
          }

      }
//die(json_encode($_SESSION['glpisearch'][$itemtype]['criteria'],JSON_UNESCAPED_UNICODE));
     // transform legacy meta-criteria in criteria (with flag meta=true)
     // at the end of the array, as before there was only at the end of the query
      if (
          isset($params["metacriteria"])
          && is_array($params["metacriteria"])
      ) {
         // as we will append meta to criteria, check the key exists
          if (!isset($params["criteria"])) {
              $params["criteria"] = [];
          }
          foreach ($params["metacriteria"] as $val) {
              $params["criteria"][] = $val + ['meta' => 1];
          }
          $params["metacriteria"] = [];
      }

      if (
          $usesession
          && isset($params["reset"])
      ) {
          if (isset($_SESSION['glpisearch'][$itemtype])) {
            //CUSTOM START очищаем поисковый запрос
            array_slice($_SESSION['glpisearch'][$itemtype]['criteria'],2);
            //CUSTOM end очищаем поисковый запрос
          }
      }

      if (
          is_array($params)
          && $usesession
      ) {
          foreach ($params as $key => $val) {
              $_SESSION['glpisearch'][$itemtype][$key] = $val;
          }
      }

      $saved_params = $params;

      foreach ($default_values as $key => $val) {
          if (!isset($params[$key])) {
              if (
                  $usesession
                  && ($key == 'is_deleted' || $key == 'as_map' || $key == 'browse' || !isset($saved_params['criteria'])) // retrieve session only if not a new request
                  && isset($_SESSION['glpisearch'][$itemtype][$key])
              ) {
                  $params[$key] = $_SESSION['glpisearch'][$itemtype][$key];
              } else {
                  $params[$key]                    = $val;
                  $_SESSION['glpisearch'][$itemtype][$key] = $val;
              }
          }

      }

      return self::cleanParams($params);
  }

  /**
   * Print generic search form
   *
   * Params need to parsed before using Search::manageParams function
   *
   * @param class-string<CommonDBTM> $itemtype  Type to display the form
   * @param array  $params    Array of parameters may include sort, is_deleted, criteria, metacriteria
   *
   * @return void
   **/
  public static function showGenericSearch($itemtype, array $params)
  {
      /** @var array $CFG_GLPI */
      global $CFG_GLPI;

     // Default values of parameters
      $p['sort']         = '';
      $p['is_deleted']   = 0;
      $p['as_map']       = 0;
      $p['browse']       = 0;
      $p['criteria']     = [];
      $p['metacriteria'] = [];
      if (class_exists($itemtype)) {
          $p['target']       = $_SERVER['PHP_SELF'];
      } else {
          $p['target']       = Toolbox::getItemTypeSearchURL($itemtype);
      }
      $p['showreset']    = true;
      $p['showbookmark'] = true;
      $p['showfolding']  = true;
      $p['mainform']     = true;
      $p['prefix_crit']  = '';
      $p['addhidden']    = [];
      $p['showaction']   = true;
      $p['actionname']   = 'search';
      $p['actionvalue']  = _sx('button', 'Search');
      foreach ($params as $key => $val) {
          $p[$key] = $val;
      }
      $reset = '';
      if(isset($params['ticket_i_assigned']))
      {
        $reset = 'ticket_i_assigned=1';
      }
      if(isset($params['ticket_i_author']))
      {
        $reset = 'ticket_i_author=1';
      }
     // Itemtype name used in JS function names, etc
      $normalized_itemtype = strtolower(str_replace('\\', '', $itemtype));
      $rand_criteria = mt_rand();
      $main_block_class = '';
      $card_class = 'search-form card card-sm mb-4';
      if ($p['mainform'] && $p['showaction']) {
          echo "<form name='searchform$normalized_itemtype' class='search-form-container' method='get' action='" . $p['target'] . "'>";
      } else {
          $main_block_class = "sub_criteria";
          $card_class = 'border d-inline-block ms-1';
      }
      $display = $_SESSION['glpifold_search'] ? 'style="display: none;"' : '';
      echo "<div class='$card_class' $display>";

      echo "<div id='searchcriteria$rand_criteria' class='$main_block_class' >";
      $nbsearchcountvar      = 'nbcriteria' . $normalized_itemtype . mt_rand();
      $searchcriteriatableid = 'criteriatable' . $normalized_itemtype . mt_rand();
     // init criteria count
      echo Html::scriptBlock("
       var $nbsearchcountvar = " . count($p['criteria']) . ";
    ");

      echo "<div class='list-group list-group-flush list-group-hoverable criteria-list pt-2' id='$searchcriteriatableid'>";

     // Display normal search parameters
      $i = 0;
      foreach (array_keys($p['criteria']) as $i) {
          self::displayCriteria([
              'itemtype' => $itemtype,
              'num'      => $i,
              'p'        => $p
          ]);

      }

      echo "<a id='more-criteria$rand_criteria' role='button'
          class='normalcriteria fold-search list-group-item p-2 border-0'
          style='display: none;'></a>";

      echo "</div>"; // .list

     // Keep track of the current savedsearches on reload
      if (isset($_GET['savedsearches_id'])) {
          echo Html::input("savedsearches_id", [
              'type' => "hidden",
              'value' => $_GET['savedsearches_id'],
          ]);
      }

      echo "<div class='card-footer d-flex search_actions'>";
      $linked = self::getMetaItemtypeAvailable($itemtype);
      echo "<button id='addsearchcriteria$rand_criteria' class='btn btn-sm btn-outline-secondary me-1' type='button'>
             <i class='ti ti-square-plus'></i>
             <span class='d-none d-sm-block'>" . __s('rule') . "</span>
          </button>";
          /*
            CUSTOM START
            Если  админ  то показываем кнопки глобального поиска в списке заявок
          */
          if(in_array(Profile_User::getUserProfiles(Session::getLoginUserID()),array(['4'=>'4'],['3'=>'3'])))
          {
            if (count($linked)) {
                echo "<button id='addmetasearchcriteria$rand_criteria' class='btn btn-sm btn-outline-secondary me-1' type='button'>
                      <i class='ti ti-circle-plus'></i>
                      <span class='d-none d-sm-block'>" . __s('global rule') . "</span>
                   </button>";
            }
            echo "<button id='addcriteriagroup$rand_criteria' class='btn btn-sm btn-outline-secondary me-1' type='button'>
                   <i class='ti ti-code-plus'></i>
                   <span class='d-none d-sm-block'>" . __s('group') . "</span>
                </button>";
          }
          /*
            CUSTOM END
            Если  админ  то показываем кнопки глобального поиска в списке заявок
          */
      $json_p = json_encode($p);

      if ($p['mainform']) {
          if ($p['showaction']) {
              // Display submit button
              echo '<button class="btn btn-sm btn-primary me-1" type="submit" name="' . htmlspecialchars($p['actionname']) . '">
              <i class="ti ti-list-search"></i>
              <span class="d-none d-sm-block">' . $p['actionvalue'] . '</span>
              </button>';
          }
          if ($p['showbookmark'] || $p['showreset']) {
              if ($p['showbookmark']) {
                  SavedSearch::showSaveButton(
                      SavedSearch::SEARCH,
                      $itemtype,
                      isset($_GET['savedsearches_id'])
                  );
              }

              if ($p['showreset']) {
                  echo "<a class='btn btn-ghost-secondary btn-icon btn-sm me-1 search-reset'
                      data-bs-toggle='tooltip' data-bs-placement='bottom'
                      href='"
                  . $p['target']
                  . (strpos($p['target'], '?') ? '&amp;' : '?')
                  . "reset=reset&$reset' title=\"" . __s('Blank') . "\"
                ><i class='ti ti-circle-x'></i></a>";
              }
          }
      }
      echo "</div>"; //.search_actions

     // idor checks
      $idor_display_criteria       = Session::getNewIDORToken($itemtype);
      $idor_display_meta_criteria  = Session::getNewIDORToken($itemtype);
      $idor_display_criteria_group = Session::getNewIDORToken($itemtype);

      $itemtype_escaped = addslashes($itemtype);
      $JS = <<<JAVASCRIPT
       $('#addsearchcriteria$rand_criteria').on('click', function(event) {
          event.preventDefault();
          $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {
             'action': 'display_criteria',
             'itemtype': '$itemtype_escaped',
             'num': $nbsearchcountvar,
             'p': $json_p,
             '_idor_token': '$idor_display_criteria'
          })
          .done(function(data) {
             $(data).insertBefore('#more-criteria$rand_criteria');
             $nbsearchcountvar++;
          });
       });

       $('#addmetasearchcriteria$rand_criteria').on('click', function(event) {
          event.preventDefault();
          $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {
             'action': 'display_meta_criteria',
             'itemtype': '$itemtype_escaped',
             'meta': true,
             'num': $nbsearchcountvar,
             'p': $json_p,
             '_idor_token': '$idor_display_meta_criteria'
          })
          .done(function(data) {
             $(data).insertBefore('#more-criteria$rand_criteria');
             $nbsearchcountvar++;
          });
       });

       $('#addcriteriagroup$rand_criteria').on('click', function(event) {
          event.preventDefault();
          $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {
             'action': 'display_criteria_group',
             'itemtype': '$itemtype_escaped',
             'meta': true,
             'num': $nbsearchcountvar,
             'p': $json_p,
             '_idor_token': '$idor_display_criteria_group'
          })
          .done(function(data) {
             $(data).insertBefore('#more-criteria$rand_criteria');
             $nbsearchcountvar++;
          });
       });
JAVASCRIPT;

      if ($p['mainform']) {
          $JS .= <<<JAVASCRIPT
       var toggle_fold_search = function(show_search) {
          $('#searchcriteria{$rand_criteria}').closest('.search-form').toggle(show_search);
       };

       // Init search_criteria state
       var search_criteria_visibility = window.localStorage.getItem('show_full_searchcriteria');
       if (search_criteria_visibility !== undefined && search_criteria_visibility == 'false') {
          $('.fold-search').click();
       }

       $(document).on("click", ".remove-search-criteria", function() {
          // force removal of tooltip
          var tooltip = bootstrap.Tooltip.getInstance($(this)[0]);
          if (tooltip !== null) {
             tooltip.dispose();
          }

          var rowID = $(this).data('rowid');
          $('#' + rowID).remove();
          $('#searchcriteria{$rand_criteria} .criteria-list .list-group-item:first-child').addClass('headerRow').show();
       });
JAVASCRIPT;
      }
      echo Html::scriptBlock($JS);

      if (count($p['addhidden'])) {
          foreach ($p['addhidden'] as $key => $val) {
              echo Html::hidden($key, ['value' => $val]);
          }
      }

      if ($p['mainform']) {
         // For dropdown
          echo Html::hidden('itemtype', ['value' => $itemtype]);
         // Reset to start when submit new search
          echo Html::hidden('start', ['value'    => 0]);
      }

      echo "</div>"; // #searchcriteria
      echo "</div>"; // .card
      if ($p['mainform'] && $p['showaction']) {
          Html::closeForm();
      }
  }
}
