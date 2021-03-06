<?php
/**
 * This file is part of Loaded Commerce.
 * 
 * @link http://www.loadedcommerce.com
 * @copyright Copyright (c) 2017 Global Ecommerce Solutions Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use common\helpers\Seo;
use Yii;

class PropertiesController extends Sceleton {
    
    public $acl = ['BOX_HEADING_CATALOG', 'BOX_CATALOG_PROPERTIES'];
    
  private $properties_types_array = array();

  function __construct($id, $module=null) {
    global $language;
    \common\helpers\Translation::init('admin/properties');
    \common\helpers\Translation::init('admin/main');

    $this->properties_types_array[''] = TEXT_PLEASE_CHOOSE;
    $this->properties_types_array['text'] = TEXT_TEXT;
    $this->properties_types_array['number'] = TEXT_NUMBER;
    $this->properties_types_array['interval'] = TEXT_NUMBER_INTERVAL;
    $this->properties_types_array['flag'] = TEXT_PR_FLAG;
    $this->properties_types_array['file'] = TEXT_PR_FILE;

    parent::__construct($id, $module);
  }

  public function actionIndex() {
    global $language;

    $this->navigation[]       = array( 'link' => Yii::$app->urlManager->createUrl('properties/index'), 'title' => TEXT_PROPERTIES_TITLE );
    $this->view->headingTitle = TEXT_PROPERTIES_TITLE;

    $this->selectedMenu = array( 'catalog', 'properties' );

    $pID = Yii::$app->request->get('pID', 0);
    $parID = Yii::$app->request->get('parID', 0);

    $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl(['properties/edit', 'parID' => $parID]) . '" class="create_item"><i class="icon-file-text"></i>' . ucwords(TEXT_CREATE_NEW_PROPERTY) . '</a>';
    $this->topButtons[] = '<a href="'.Yii::$app->urlManager->createUrl(['properties/category', 'parID' => $parID]).'" class="create_item addprbtn"><i class="icon-folder-close-alt"></i>' . ucwords(TEXT_CREATE_NEW_CATEGORY) . '</a>';

    $this->view->PropertyTable = array(
      array(
        'title' => TABLE_HEADING_CATEGORIES_PROPERTIES,
        'not_important' => 0,
      ),
      array(
        'title' => TABLE_HEADING_PROPERTIES_TYPE,
        'not_important' => 0,
      ),
    );

    $messages = $_SESSION['messages'];
    unset($_SESSION['messages']);

    return $this->render('index', array('messages' => $messages, 'pID' => $pID, 'parID' => $parID));
  }

  public function actionList() {
    global $languages_id;
    $draw = Yii::$app->request->get('draw', 1);
    $start = Yii::$app->request->get('start', 0);
    $length = Yii::$app->request->get('length', 10);
    $formFilter = Yii::$app->request->get('filter', array());
    parse_str($formFilter, $filter);

    $search = '';
    if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
      $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
      $search .= " and (properties_name like '%" . $keywords . "%')";
    }

    $current_page_number = ($start / $length) + 1;
    $responseList = array();

    if ($filter['parID'] > 0) {
      $parent_query = tep_db_query("select parent_id from " . TABLE_PROPERTIES . " where properties_id = '" . (int)$filter['parID'] . "'");
      if ($parent = tep_db_fetch_array($parent_query)) {
        $responseList[] = array(
          '<span class="parent_cats"><i class="icon-circle"></i><i class="icon-circle"></i><i class="icon-circle"></i></span><input class="cell_identify" type="hidden" value="' . $parent['parent_id'] . '"><input class="cell_type" type="hidden" value="parent">',
          '',
        );
      }
    }

    $properties_query_raw = "select p.properties_id, p.properties_type, pd.properties_name, pd.properties_image, p.date_added, p.last_modified from " . TABLE_PROPERTIES . " p, " . TABLE_PROPERTIES_DESCRIPTION . " pd where p.properties_id = pd.properties_id and pd.language_id = '" . (int)$languages_id . "' and parent_id = '" . (int)$filter['parID'] . "' " . $search . " order by (p.properties_type = 'category') desc, p.sort_order, pd.properties_name";
    $properties_split = new \splitPageResults($current_page_number, $length, $properties_query_raw, $properties_query_numrows);
    $properties_query = tep_db_query($properties_query_raw);
    while ($properties = tep_db_fetch_array($properties_query)) {
      if ($properties['properties_type'] == 'category') {
        $responseList[] = array(
          '<div class="handle_cat_list state-disabled"><span class="handle"><i class="icon-hand-paper-o"></i></span><div class="cat_name"><b>' . $properties['properties_name'] . '</b><input class="cell_identify" type="hidden" value="' . $properties['properties_id'] . '"><input class="cell_type" type="hidden" value="category"></div></div>',
          '',
        );
      } else {
        $image = \common\helpers\Image::info_image($properties['properties_image'], $properties['properties_name'], 50, 50);
        $responseList[] = array(
          '<div class="handle_cat_list state-disabled"><span class="handle"><i class="icon-hand-paper-o"></i></span><div class="prod_name">' . (tep_not_null($image) && $image != TEXT_IMAGE_NONEXISTENT ? '<span class="prodImgC">' . $image . '</span>' : '<span class="cubic"></span>')  . '<span class="prodNameC">' . $properties['properties_name'] . '</span>' . '<input class="cell_identify" type="hidden" value="' . $properties['properties_id'] . '"><input class="cell_type" type="hidden" value="property"></div></div>',
          $this->properties_types_array[$properties['properties_type']],
        );
      }
    }

    $response = array(
        'draw' => $draw,
        'recordsTotal' => $properties_query_numrows,
        'recordsFiltered' => $properties_query_numrows,
        'data' => $responseList
    );
    echo json_encode($response);
  }

  public function actionStatusactions() {
    global $language, $languages_id;

    $parent_id = Yii::$app->request->post('parent_id', 0);
    $properties_id = Yii::$app->request->post('properties_id', 0);
    $this->layout = false;

    if ($properties_id > 0) {
      $properties = tep_db_fetch_array(tep_db_query("select p.properties_id, p.properties_type, pd.properties_name, p.date_added, p.last_modified from " . TABLE_PROPERTIES . " p, " . TABLE_PROPERTIES_DESCRIPTION . " pd where p.properties_id = pd.properties_id and pd.language_id = '" . (int)$languages_id . "' and p.parent_id = '" . (int)$parent_id . "' and p.properties_id = '" . (int)$properties_id . "'"));
      $pInfo = new \objectInfo($properties, false);

      if ($pInfo->properties_id > 0) {
        echo '<div class="or_box_head">' . $pInfo->properties_name . '</div>';
        echo '<div class="btn-toolbar btn-toolbar-order">';
        echo '<a href="' . Yii::$app->urlManager->createUrl(['properties/' . ($pInfo->properties_type == 'category' ? 'category' : 'edit'), 'pID' => $properties_id]) . '"><button class="btn btn-edit btn-no-margin">' . IMAGE_EDIT . '</button></a>';
        echo '<button class="btn btn-delete" onclick="propertyDeleteConfirm(' . $properties_id . ')">' . IMAGE_DELETE . '</button>';
        echo '</div>';
      }
    }
  }

  function actionEdit()
  {
    global $languages_id, $language;

    $this->navigation[]       = array( 'link' => Yii::$app->urlManager->createUrl('properties/edit'), 'title' => TEXT_PROPERTIES_TITLE );
    $this->view->headingTitle = TEXT_PROPERTIES_TITLE;

    $this->selectedMenu = array( 'catalog', 'properties' );

    $this->view->usePopupMode = false;
    if (Yii::$app->request->isAjax) {
      $this->layout = false;
      $this->view->usePopupMode = true;
    }

    $properties_id = Yii::$app->request->get('pID', 0);
    $properties = tep_db_fetch_array(tep_db_query("select * from " . TABLE_PROPERTIES . " where properties_id = '" . (int)$properties_id . "'"));
    if (!$properties) {
      $properties['parent_id'] = Yii::$app->request->get('parID', 0);
    }
    $pInfo = new \objectInfo($properties, false);

    $this->view->properties_types = $this->properties_types_array;

    $this->view->multi_choices[''] = TEXT_PLEASE_CHOOSE;
    $this->view->multi_choices['0'] = TEXT_SINGLE;
    $this->view->multi_choices['1'] = TEXT_MULTIPLE;

    $this->view->multi_lines[''] = TEXT_PLEASE_CHOOSE;
    $this->view->multi_lines['0'] = TEXT_SINGLE_LINE;
    $this->view->multi_lines['1'] = TEXT_MULTILINE;

    $this->view->decimals[''] = TEXT_PLEASE_CHOOSE;
    $this->view->decimals['0'] = '1234';
    $this->view->decimals['1'] = '1234.5';
    $this->view->decimals['2'] = '1234.56';
    $this->view->decimals['3'] = '1234.567';
    $this->view->decimals['4'] = '1234.5678';
    $this->view->decimals['5'] = '1234.56789';
    $this->view->decimals['9'] = '1234.56789xxx';


    $default_language_id = $languages_id;
    $languages = \common\helpers\Language::get_languages();
    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
      $languages[$i]['logo'] = $languages[$i]['image'];
      if ($languages[$i]['code'] == DEFAULT_LANGUAGE) {
        $default_language_id = $languages[$i]['id'];
      }
    }

    if (strlen(\common\helpers\Properties::get_properties_description($properties_id, $default_language_id)) > 0) {
      $this->view->additional_info = 1;
    }

    $this->view->properties_values = array();
    $this->view->properties_values_sorted_ids = array();
    $properties_values_query = tep_db_query("select values_id, properties_id, language_id, values_text, values_number, values_number_upto, values_alt from " . TABLE_PROPERTIES_VALUES . " where properties_id = '" . (int)$properties_id . "' order by " . ($properties['properties_type'] == 'number' || $properties['properties_type'] == 'interval' ? 'values_number' : 'values_text'));
    while($properties_values = tep_db_fetch_array($properties_values_query)) {
      if ($properties['properties_type'] == 'number' || $properties['properties_type'] == 'interval') {
        $properties_values['values'] = (float)number_format($properties_values['values_number'], $properties['decimals'],'.','');
        $properties_values['values_number_upto'] = (float)number_format($properties_values['values_number_upto'], $properties['decimals'],'.','');
      } else {
        $properties_values['values'] = $properties_values['values_text'];
      }
      $this->view->properties_values[$properties_values['language_id']][$properties_values['values_id']] = $properties_values;

      if ($properties_values['language_id'] == $default_language_id) {
        $this->view->properties_values_sorted_ids[$properties_values['values_id']] = $properties_values['values_id'];
      }
    }

    return $this->render('edit.tpl', ['languages' => $languages, 'default_language' => DEFAULT_LANGUAGE, 'pInfo' => $pInfo]);
  }

  function actionCategory()
  {
    global $languages_id, $language;

    $this->navigation[]       = array( 'link' => Yii::$app->urlManager->createUrl('properties/category'), 'title' => TEXT_PROPERTIES_TITLE );
    $this->view->headingTitle = TEXT_PROPERTIES_TITLE;

    $this->selectedMenu = array( 'catalog', 'properties' );

    $this->view->usePopupMode = false;
    if (Yii::$app->request->isAjax) {
      $this->layout = false;
      $this->view->usePopupMode = true;
    }

    $properties_id = Yii::$app->request->get('pID', 0);
    $properties = tep_db_fetch_array(tep_db_query("select * from " . TABLE_PROPERTIES . " where properties_id = '" . (int)$properties_id . "'"));
    if (!$properties) {
      $properties['parent_id'] = Yii::$app->request->get('parID', 0);
    }
    $pInfo = new \objectInfo($properties, false);

    $default_language_id = $languages_id;
    $languages = \common\helpers\Language::get_languages();
    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
      $languages[$i]['logo'] = $languages[$i]['image'];
      if ($languages[$i]['code'] == DEFAULT_LANGUAGE) {
        $default_language_id = $languages[$i]['id'];
      }
    }

    if (strlen(\common\helpers\Properties::get_properties_description($properties_id, $default_language_id)) > 0) {
      $this->view->additional_info = 1;
    }

    return $this->render('category.tpl', ['languages' => $languages, 'default_language' => DEFAULT_LANGUAGE, 'pInfo' => $pInfo]);
  }

  function actionSave()
  {
    global $language, $languages_id;

    $parent_id = Yii::$app->request->post('parent_id', 0);
    $properties_id = Yii::$app->request->post('properties_id', 0);
    $properties_type = Yii::$app->request->post('properties_type', 'text');
    $multi_choice = Yii::$app->request->post('multi_choice', 0);
    $multi_line = Yii::$app->request->post('multi_line', 0);
    $decimals = Yii::$app->request->post('decimals', 0);
    $display_product = Yii::$app->request->post('display_product', 0);
    $display_listing = Yii::$app->request->post('display_listing', 0);
    $display_filter = Yii::$app->request->post('display_filter', 0);
    $display_search = Yii::$app->request->post('display_search', 0);
    $display_compare = Yii::$app->request->post('display_compare', 0);
    $same_all_languages = Yii::$app->request->post('same_all_languages', 0);
    $additional_info = tep_db_prepare_input(Yii::$app->request->post('additional_info', 0));
    $properties_name = tep_db_prepare_input(Yii::$app->request->post('properties_name', array()));
    $properties_description = tep_db_prepare_input(Yii::$app->request->post('properties_description', array()));
    $properties_seo_page_name = tep_db_prepare_input(Yii::$app->request->post('properties_seo_page_name', array()));
    $properties_image_loaded = Yii::$app->request->post('properties_image_loaded', array());
    $properties_units_title = tep_db_prepare_input(Yii::$app->request->post('properties_units_title', array()));

    $values = tep_db_prepare_input(Yii::$app->request->post('values', array()));
    $values_upto = tep_db_prepare_input(Yii::$app->request->post('values_upto', array()));
    $values_alt = tep_db_prepare_input(Yii::$app->request->post('values_alt', array()));
    $upload_docs = tep_db_prepare_input(Yii::$app->request->post('upload_docs', array()));

    $sql_data_array = array('properties_type' => $properties_type,
                            'multi_choice' => $multi_choice,
                            'multi_line' => $multi_line,
                            'decimals' => $decimals,
                            'display_product' => $display_product,
                            'display_listing' => $display_listing,
                            'display_filter' => $display_filter,
                            'display_search' => $display_search,
                            'display_compare' => $display_compare,
    );

    if ($properties_id == 0) {
      $insert_sql_data = array('parent_id' => $parent_id, 'date_added' => 'now()');
      $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
      tep_db_perform(TABLE_PROPERTIES, $sql_data_array);
      $properties_id = tep_db_insert_id();
    } else {
      $update_sql_data = array('last_modified' => 'now()');
      $sql_data_array = array_merge($sql_data_array, $update_sql_data);
      tep_db_perform(TABLE_PROPERTIES, $sql_data_array, 'update', "properties_id = '" . (int)$properties_id . "'");
    }

    $default_language_id = $languages_id;
    $languages = \common\helpers\Language::get_languages();
    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
      if ($languages[$i]['code'] == DEFAULT_LANGUAGE) {
        $default_language_id = $languages[$i]['id'];
      }
    }
    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
      if (trim($properties_name[$languages[$i]['id']]) == '' || $same_all_languages) $properties_name[$languages[$i]['id']] = $properties_name[$default_language_id];
      if (!$additional_info) $properties_description[$languages[$i]['id']] = '';
      elseif (trim($properties_description[$languages[$i]['id']]) == '' || $same_all_languages) $properties_description[$languages[$i]['id']] = $properties_description[$default_language_id];

      if (trim($properties_seo_page_name[$languages[$i]['id']]) == '') {
        $properties_seo_page_name[$languages[$i]['id']] = Seo::makeSlug($properties_name[$languages[$i]['id']]);
      }
      if (trim($properties_seo_page_name[$languages[$i]['id']]) == '') {
        $properties_seo_page_name[$languages[$i]['id']] = $properties_id;
      }
      if (trim($properties_seo_page_name[$languages[$i]['id']]) == '' || $same_all_languages) $properties_seo_page_name[$languages[$i]['id']] = $properties_seo_page_name[$default_language_id];

      if (trim($properties_units_title[$languages[$i]['id']]) == '' || $same_all_languages) $properties_units_title[$languages[$i]['id']] = $properties_units_title[$default_language_id];
      if (tep_not_null($properties_units_title[$languages[$i]['id']])) {
        $check = tep_db_fetch_array(tep_db_query("select properties_units_id from " . TABLE_PROPERTIES_UNITS . " where properties_units_title = '" . tep_db_input($properties_units_title[$languages[$i]['id']]) . "'"));
        if ($check['properties_units_id'] > 0) {
          $properties_units_id = $check['properties_units_id'];
        } else {
          tep_db_perform(TABLE_PROPERTIES_UNITS, array('properties_units_title' => $properties_units_title[$languages[$i]['id']]));
          $properties_units_id = tep_db_insert_id();
        }
      }

      $sql_data_array = array('properties_name'        => $properties_name[$languages[$i]['id']],
                              'properties_description' => $properties_description[$languages[$i]['id']],
                              'properties_seo_page_name' => $properties_seo_page_name[$languages[$i]['id']],
                              'properties_units_id' => intval($properties_units_id),
      );
      $check = tep_db_fetch_array(tep_db_query("select count(*) as properties_description_exists from " . TABLE_PROPERTIES_DESCRIPTION . " where properties_id = '" . (int)$properties_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'"));
      if ($check['properties_description_exists']) {
        tep_db_perform(TABLE_PROPERTIES_DESCRIPTION, $sql_data_array, 'update', "properties_id = '" . (int)$properties_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'" );
      } else {
        $insert_sql_data = array('properties_id' => $properties_id,
                                 'language_id' => $languages[$i]['id']);
        $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
        tep_db_perform(TABLE_PROPERTIES_DESCRIPTION, $sql_data_array);
      }

      if ((trim($properties_image_loaded[$languages[$i]['id']]) == '' || $same_all_languages) && trim($properties_image_loaded[$default_language_id]) != '') {
        $properties_image_loaded[$languages[$i]['id']] = $properties_image_loaded[$default_language_id];
      }
      if (tep_not_null($properties_image_loaded[$languages[$i]['id']])) {
        $path = \Yii::getAlias('@webroot');
        $path .= DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        $tmp_name = $path . $properties_image_loaded[$languages[$i]['id']];
        $new_name = DIR_FS_CATALOG_IMAGES . $properties_id . '-' . $properties_image_loaded[$languages[$i]['id']];
        @copy($tmp_name, $new_name);
        @unlink($tmp_name);
        tep_db_query("update " . TABLE_PROPERTIES_DESCRIPTION . " set properties_image = '" . tep_db_input($properties_id . '-' . $properties_image_loaded[$languages[$i]['id']]) . "' where properties_id = '" . (int)$properties_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'");
      }
    }

    $all_values_id = array();
    if (in_array($properties_type, array('text', 'number', 'interval', 'file'))) {
      foreach ($values as $val_id => $val) {
        if (trim($values[$val_id][$default_language_id]) == '' && trim($upload_docs[$val_id][$default_language_id]) == '') {
          continue; // Skip empty lines
        }
        if (strstr($val_id, 'new')) {
          $max_value = tep_db_fetch_array(tep_db_query("select max(values_id) + 1 as next_id from " . TABLE_PROPERTIES_VALUES));
          $values_id = $max_value['next_id'];
          if ( !($values_id > 0) ) $values_id = 1;
        } elseif ($val_id > 0) {
          $values_id = $val_id;
        } else {
          continue;
        }
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
// {{
          if ($properties_type == 'file') {
            if ((trim($upload_docs[$val_id][$languages[$i]['id']]) == '' || $same_all_languages) && trim($upload_docs[$val_id][$default_language_id]) != '') {
              $upload_docs[$val_id][$languages[$i]['id']] = $upload_docs[$val_id][$default_language_id];
            }
            if ($upload_docs[$val_id][$languages[$i]['id']] != '') {
              $path = \Yii::getAlias('@webroot');
              $path .= DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
              $tmp_name = $path . $upload_docs[$val_id][$languages[$i]['id']];
              $new_name = DIR_FS_CATALOG_IMAGES . $properties_id . '-' . $upload_docs[$val_id][$languages[$i]['id']];
              @copy($tmp_name, $new_name);
              @unlink($tmp_name);
              $values[$val_id][$languages[$i]['id']] = $properties_id . '-' . $upload_docs[$val_id][$languages[$i]['id']];
            }
          }
// }}
          if (trim($values[$val_id][$languages[$i]['id']]) == '' || $same_all_languages) $values[$val_id][$languages[$i]['id']] = $values[$val_id][$default_language_id];
          if ($properties_type == 'interval') {
            if (trim($values_upto[$val_id][$languages[$i]['id']]) == '' || $same_all_languages) $values_upto[$val_id][$languages[$i]['id']] = $values_upto[$val_id][$default_language_id];
          } else {
            $values_upto[$val_id][$languages[$i]['id']] = '';
          }
          if (trim($values_alt[$val_id][$languages[$i]['id']]) == '' || $same_all_languages) $values_alt[$val_id][$languages[$i]['id']] = $values_alt[$val_id][$default_language_id];
          $sql_data_array = array('values_text'        => $values[$val_id][$languages[$i]['id']],
                                  'values_number'      => round((float)$values[$val_id][$languages[$i]['id']], (int)$decimals),
                                  'values_number_upto' => round((float)$values_upto[$val_id][$languages[$i]['id']], (int)$decimals),
                                  'values_alt'         => $values_alt[$val_id][$languages[$i]['id']],
          );
          $check = tep_db_fetch_array(tep_db_query("select count(*) as properties_values_exists from " . TABLE_PROPERTIES_VALUES . " where values_id = '" . (int)$values_id . "' and properties_id = '" . (int)$properties_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'"));
          if ($check['properties_values_exists']) {
            tep_db_perform(TABLE_PROPERTIES_VALUES, $sql_data_array, 'update', "values_id = '" . (int)$values_id . "' and properties_id = '" . (int)$properties_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'" );
          } else {
            $insert_sql_data = array('values_id' => $values_id,
                                     'properties_id' => $properties_id,
                                     'language_id' => $languages[$i]['id']);
            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
            tep_db_perform(TABLE_PROPERTIES_VALUES, $sql_data_array);
          }
        }
        $all_values_id[] = $values_id;
      }
    }
    $properties_values_query = tep_db_query("select values_id from " . TABLE_PROPERTIES_VALUES . " where properties_id = '" . (int)$properties_id . "' and values_id not in ('" . implode("','", $all_values_id) . "')");
    while ($properties_values = tep_db_fetch_array($properties_values_query)) {
      tep_db_query("delete from " . TABLE_PROPERTIES_VALUES . " where properties_id = '" . (int)$properties_id . "' and values_id = '" . (int)$properties_values['values_id'] . "'");
      tep_db_query("delete from " . TABLE_PROPERTIES_TO_PRODUCTS . " where properties_id = '" . (int)$properties_id . "' and values_id = '" . (int)$properties_values['values_id'] . "'");
    }

    if (Yii::$app->request->isAjax) {
      $this->layout = false;
      $this->view->properties_tree = \common\helpers\Properties::get_properties_tree('0', '&nbsp;&nbsp;&nbsp;&nbsp;', '', false);
      return $this->render('properties_box.tpl', ['properties_id' => $properties_id]);
    } else {
      return $this->redirect(Yii::$app->urlManager->createUrl(['properties/index', 'parID' => $parent_id, 'pID' => $properties_id]));
    }
  }

  public function actionSortOrder()
  {
    $categories_sorted = Yii::$app->request->post('category', array());
    foreach ($categories_sorted as $sort_order => $properties_id) {
      tep_db_query("update " . TABLE_PROPERTIES . " set sort_order = '" . (int)$sort_order . "' where properties_id = '" . (int)$properties_id . "'");
    }
    $properties_sorted = Yii::$app->request->post('property', array());
    foreach ($properties_sorted as $sort_order => $properties_id) {
      tep_db_query("update " . TABLE_PROPERTIES . " set sort_order = '" . (int)($sort_order + count($categories_sorted)) . "' where properties_id = '" . (int)$properties_id . "'");
    }
  }

  public function actionConfirmdelete() {
    global $language, $languages_id;

    $this->layout = false;

    $properties_id = Yii::$app->request->post('properties_id');

    if ($properties_id > 0) {
      $properties = tep_db_fetch_array(tep_db_query("select p.properties_id, pd.properties_name, p.date_added, p.last_modified from " . TABLE_PROPERTIES . " p, " . TABLE_PROPERTIES_DESCRIPTION . " pd where p.properties_id = pd.properties_id and pd.language_id = '" . (int)$languages_id . "' and p.properties_id = '" . (int)$properties_id . "'"));
      $pInfo = new \objectInfo($properties, false);

      echo tep_draw_form('properties', FILENAME_PROPERTIES, \common\helpers\Output::get_all_get_params(array('pID', 'action')) . 'dID=' . $pInfo->properties_id . '&action=deleteconfirm', 'post', 'id="item_delete" onSubmit="return propertyDelete();"');

      echo '<div class="or_box_head">' . $pInfo->properties_name . '</div>';
      echo TEXT_DELETE_INTRO . '<br>';
      echo '<div class="btn-toolbar btn-toolbar-order">';
      echo '<button type="submit" class="btn btn-primary btn-no-margin">' . IMAGE_CONFIRM . '</button>';
      echo '<button class="btn btn-cancel" onClick="return resetStatement(' . (int)$properties_id . ')">' . IMAGE_CANCEL . '</button>';      

      echo tep_draw_hidden_field('properties_id', $properties_id);
      echo '</div></form>';
    }
  }

  public function actionDelete() {
    global $language;

    $properties_id = Yii::$app->request->post('properties_id', 0);
    if ($properties_id > 0) {
      \common\helpers\Properties::remove_property($properties_id);
      echo 'reset';
    }
  }

  /**
  * Autocomplette
  */
  public function actionUnits() {
    $term = tep_db_prepare_input(Yii::$app->request->get('term'));

    $search = "1";
    if (!empty($term)) {
      $search = "properties_units_title like '%" . tep_db_input($term) . "%'";
    }

    $response = [];
    $units_query = tep_db_query("select properties_units_title from " . TABLE_PROPERTIES_UNITS . " where " . $search . " group by properties_units_title order by properties_units_title");
    while ($units = tep_db_fetch_array($units_query)) {
      $response[] = $units['properties_units_title'];
    }
    echo json_encode($response);
  }
}