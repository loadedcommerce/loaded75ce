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

use Yii;
use backend\components\Information;
ini_set('memory_limit', '-1');
/**
 * default controller to handle user requests.
 */
class MenusController extends Sceleton {

    public $acl = ['BOX_HEADING_DESIGN_CONTROLS', 'FILENAME_CMS_MENUS'];

    public function actionIndex() {
        global $language, $HTTP_SESSION_VARS, $languages_id, $languages;

        \common\helpers\Translation::init('admin/design');

        $this->topButtons[] = '<a href="" class="create_item menu-ico">' . TEXT_CREATE_MENU . '</a>';

        $this->view->usePopupMode = false;
        if (Yii::$app->request->isAjax) {
          $this->layout = false;
          $this->view->usePopupMode = true;
        }

        $this->selectedMenu = array('design_controls', 'menus');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('menus/index'), 'title' => HEADING_TITLE);

        $this->view->headingTitle = HEADING_TITLE;

        $selected_platform_id = \common\classes\platform::firstId();
        $try_set_platform = Yii::$app->request->get('platform_id',0);
        if ( $try_set_platform>0 ) {
          foreach (\common\classes\platform::getList(false) as $_platform) {
            if ((int)$try_set_platform==(int)$_platform['id']){
              $selected_platform_id = (int)$try_set_platform;
            }
          }
        }

        if ($_GET['menu']){
          $menu_id = $_GET['menu'];
        } else {
          $sql = tep_db_fetch_array(tep_db_query("select id from " . TABLE_MENUS ." where 1 limit 1"));
          $_GET['menu'] = $menu_id = $sql['id'];
        }



        $sql=tep_db_query("SELECT information_id, info_title, page_title from " . TABLE_INFORMATION ." WHERE visible='1' and languages_id =".(int)$languages_id." and platform_id='".$selected_platform_id."' and affiliate_id=0 order by v_order");

        $info = array();
        while($row=tep_db_fetch_array($sql)){
            if ($row['info_title']) $row['title'] = $row['info_title'];
            elseif ($row['page_title']) $row['title'] = $row['page_title'];
           $info[] = $row;
        }




        $sql = tep_db_query(
          "select c.categories_id, c.parent_id, if(length(cd1.categories_name), cd1.categories_name, cd.categories_name) as categories_name, c.parent_id ".
          "from " . TABLE_CATEGORIES_DESCRIPTION . " cd, " . TABLE_CATEGORIES . " c ".
          " inner join ".TABLE_PLATFORMS_CATEGORIES." pc on pc.categories_id=c.categories_id and pc.platform_id='".$selected_platform_id."' ".
          " left join " . TABLE_CATEGORIES_DESCRIPTION . " cd1 on cd1.categories_id = c.categories_id and cd1.language_id='" . (int)$languages_id ."' and cd1.affiliate_id = '" . (int)$HTTP_SESSION_VARS['affiliate_ref'] . "' ".
          "where c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and cd.affiliate_id = 0 ".
          "order by sort_order, categories_name");

        $categories = array();
        while($row=tep_db_fetch_array($sql)){
            $categories[] = $row;
        }



        $sql = tep_db_query("select * from " . TABLE_MENU_ITEMS ." where platform_id='".$selected_platform_id."' and menu_id='" . $menu_id . "' order by sort_order");

        $new_categories = array();
        $menu = array();
        while($row=tep_db_fetch_array($sql)){

            $row['name'] = 'item #' . $row['id'];

            if ($row['link_type'] == 'info'){

                $sql1=tep_db_query("SELECT information_id, info_title, page_title from " . TABLE_INFORMATION ." WHERE visible='1' and languages_id =".(int)$languages_id." and information_id='" . $row['link_id'] . "' and platform_id='".$selected_platform_id."' and affiliate_id=0");
                while($row1=tep_db_fetch_array($sql1)){
                    if ($row1['info_title']) $row['name'] = $row1['info_title'];
                    elseif ($row1['page_title']) $row['name'] = $row1['page_title'];
                }

              $sql1=tep_db_query("select title from " . TABLE_MENU_TITLES . " where item_id = " . (int)$row['id'] . " and language_id = " . $languages_id);
              if ($row1=tep_db_fetch_array($sql1)){
                $row['shown'] = $row1['title'];
              }

            } elseif ($row['link_type'] == 'categories'){
                if ($row['link_id'] == '999999999'){
                  $row['name'] = TEXT_ALL_CATEGORIES;
                  $query = tep_db_fetch_array(tep_db_query("select last_modified from " . TABLE_MENUS . " where id = '" . $menu_id . "'"));
                  $sql3 = tep_db_query(
                    "select c.categories_id, c.parent_id, cd.categories_name ".
                    "from " . TABLE_CATEGORIES . " c  ".
                    " inner join ".TABLE_PLATFORMS_CATEGORIES." pc on pc.categories_id=c.categories_id and pc.platform_id='".$selected_platform_id."' ".
                    " left join " . TABLE_CATEGORIES_DESCRIPTION . " cd on c.categories_id = cd.categories_id  ".
                    "where c.date_added > '" . $query['last_modified'] . "' and cd.language_id = '" . $languages_id . "' and cd.affiliate_id=0"
                  );
                  if (tep_db_num_rows($sql3) > 0){
                    while ($item = tep_db_fetch_array($sql3)){
                      $new_categories[] = $item;
                    }
                  }
                } else {
                  $sql1=tep_db_query("SELECT categories_name from " . TABLE_CATEGORIES_DESCRIPTION ." WHERE language_id =".(int)$languages_id." and categories_id='" . $row['link_id'] . "' and affiliate_id=0");
                  if ($row1=tep_db_fetch_array($sql1)){
                    $row['name'] = $row1['categories_name'];
                  }

                  $sql1=tep_db_query("select title from " . TABLE_MENU_TITLES . " where item_id = " . (int)$row['id'] . " and language_id = " . $languages_id);
                  if ($row1=tep_db_fetch_array($sql1)){
                    $row['shown'] = $row1['title'];
                  }
                }
                if (count($new_categories) > 0){
                    if ($row['link_id'] == 999999999)$current = 0;
                    else $current = $row['link_id'];
                    foreach ($new_categories as $item){
                        if ($item['parent_id'] == $current){
                            $menu[] = array(
                              'parent_id' => $row['id'],
                              'link_type' => 'categories',
                              'name' => $item['categories_name'],
                              'link_id' => $item['categories_id'],
                              'new_category' => $item['categories_id'],
                            );
                        }
                    }
                }

            } elseif ($row['link_type'] == 'custom'){

                $sql1=tep_db_query("select title from " . TABLE_MENU_TITLES . " where item_id = " . (int)$row['id'] . " and language_id = " . $languages_id);
                if ($row1=tep_db_fetch_array($sql1)){
                    $row['name'] = $row1['title'];
                }
            } elseif ($row['link_type'] == 'default'){
                if ($row['link_id'] == '8888886'){
                  $row['name'] = TEXT_HOME;
                  
                } elseif ($row['link_id'] == '8888885'){
                  $row['name'] = TEXT_HEADER_CONTACT_US;
                } elseif ($row['link_id'] == '8888887'){
                  $row['name'] = TEXT_SIGN_IN .' / '. TEXT_HEADER_LOGOUT;
                } elseif ($row['link_id'] == '8888888'){
                  $row['name'] = TEXT_MY_ACCOUNT .' / '. TEXT_MY_ACCOUNT;
                } elseif ($row['link_id'] == '8888884'){
                  $row['name'] = TEXT_CHECKOUT;
                } elseif ($row['link_id'] == '8888883'){
                  $row['name'] = TEXT_SHOPPING_CART;
                } elseif ($row['link_id'] == '8888882'){
                  $row['name'] = IMAGE_NEW_PRODUCT;
                } elseif ($row['link_id'] == '8888881'){
                  $row['name'] = BOX_CATALOG_FEATURED;
                } elseif ($row['link_id'] == '8888880'){
                  $row['name'] = TEXT_SPECIALS_PRODUCTS;
                } elseif ($row['link_id'] == '8888879'){
                  $row['name'] = TEXT_GIFT_CARD;
                } elseif ($row['link_id'] == '8888878'){
                  $row['name'] = TEXT_ALL_PRODUCTS;
                } elseif ($row['link_id'] == '8888877'){
                  $row['name'] = TEXT_SITE_MAP;
                }
                //TODO: After added page add to Sceleton-bindActionParams exceptions for non logged users

              $sql1=tep_db_query("select title from " . TABLE_MENU_TITLES . " where item_id = " . (int)$row['id'] . " and language_id = " . $languages_id);
              if ($row1=tep_db_fetch_array($sql1)){
                $row['shown'] = $row1['title'];
              }
            }

            $titles = tep_db_query("select language_id, title from " . TABLE_MENU_TITLES . " where item_id = " . (int)$row['id']);
            $row['titles'] = array();

            while ($item = tep_db_fetch_array($titles)){
                $row['titles'][$item['language_id']] = $item['title'];
            }

            $sql1 = tep_db_fetch_array(tep_db_query("SELECT count(*) as total from " . TABLE_CATEGORIES . " where categories_id='" . $row['link_id'] . "'"));
            $sql2 = tep_db_fetch_array(tep_db_query("SELECT count(*) as total from " . TABLE_INFORMATION . " where information_id='" . $row['link_id'] . "'"));
            if ($row['link_type'] != 'categories' || $sql1['total'] > 0 || $row['link_id'] == '999999999') {
              if ($row['link_type'] != 'info' || $sql2['total'] > 0) {
                $menu[] = $row;
              }
            }
        }

        $languages = \common\helpers\Language::get_languages();
        $lang = array();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $languages[$i]['logo'] = $languages[$i]['image'];
            $lang[] = $languages[$i];
        }


        $menus = array();
        $sql = tep_db_query("select * from " . TABLE_MENUS . " where 1 order by id");
        while ($row=tep_db_fetch_array($sql)){
          $menus[] = $row;
        }

        $current_menu = array();
        $sql = tep_db_query("select * from " . TABLE_MENUS . " where id = " . (int)$_GET['menu']);
        if ($row=tep_db_fetch_array($sql)){
          $current_menu = $row;
        }

        $default_pages = array(
          array('type_id' => 8888886, 'name' => TEXT_HOME, 'opt_need_login' => false),
          array('type_id' => 8888885, 'name' => TEXT_HEADER_CONTACT_US, 'opt_need_login' => true),
          array('type_id' => 8888888, 'name' => TEXT_MY_ACCOUNT . ' / ' . TEXT_MY_ACCOUNT, 'opt_need_login' => false),
          array('type_id' => 8888887, 'name' => TEXT_SIGN_IN . ' / ' . TEXT_HEADER_LOGOUT, 'opt_need_login' => false),
          array('type_id' => 8888884, 'name' => TEXT_CHECKOUT, 'opt_need_login' => false),
          array('type_id' => 8888883, 'name' => TEXT_SHOPPING_CART, 'opt_need_login' => false),
          array('type_id' => 8888882, 'name' => IMAGE_NEW_PRODUCT, 'opt_need_login' => true),
          array('type_id' => 8888881, 'name' => BOX_CATALOG_FEATURED, 'opt_need_login' => true),
          array('type_id' => 8888880, 'name' => TEXT_SPECIALS_PRODUCTS, 'opt_need_login' => true),
          array('type_id' => 8888879, 'name' => TEXT_GIFT_CARD, 'opt_need_login' => false),
          array('type_id' => 8888878, 'name' => TEXT_ALL_PRODUCTS, 'opt_need_login' => true),
          array('type_id' => 8888877, 'name' => TEXT_SITE_MAP, 'opt_need_login' => true),
        );
        
        $custom_pages = \common\helpers\MenuHelper::getAllCustomPages($selected_platform_id);
        
        return $this->render('index', [
          'default_pages' => $default_pages,
          'current_menu' => $current_menu,
          'custom_pages' => $custom_pages,
          'menus' => $menus,
          'menu' => $menu,
          'info' => $info,
          'categories' => $categories,
          'languages' => $lang,
          'languages_id' => $languages_id,
          'new_categories' => count($new_categories),
          'platforms' => array_map(function($platform){
            $platform['link'] = Yii::$app->urlManager->createUrl(['menus/index','platform_id'=>$platform['id']]);
            return $platform;
          },\common\classes\platform::getList(false)),
          'isMultiPlatforms' => \common\classes\platform::isMulti(),
          'selected_platform_id' => $selected_platform_id,
          'action_url_select_menu' => Yii::$app->urlManager->createUrl(['menus','platform_id'=>$selected_platform_id]),
          'action_url_save_menu' => Yii::$app->urlManager->createUrl(['menus/save','platform_id'=>$selected_platform_id]),
        ]);
    }

    public function actionSave() {

        \common\helpers\Translation::init('admin/design');
        
        $selected_platform_id = \common\classes\platform::firstId();
        $try_set_platform = Yii::$app->request->get('platform_id',0);
        if ( $try_set_platform>0 ) {
          foreach (\common\classes\platform::getList(false) as $_platform) {
            if ((int)$try_set_platform==(int)$_platform['id']){
              $selected_platform_id = (int)$try_set_platform;
            }
          }
        }

        $params = Yii::$app->request->post();

        $new_menu = false;
        $menu_id = tep_db_prepare_input($params['menu_id']);
        $sql_data_array = array(
          'menu_name' => tep_db_prepare_input($params['menu_name']),
        );

        if ($params['menu_name']) {
          if ($menu_id == 0) {
            tep_db_perform(TABLE_MENUS, $sql_data_array);

            $sql = tep_db_query("select id from " . TABLE_MENUS . " where menu_name = '" . tep_db_input(tep_db_prepare_input($params['menu_name'])) . "'");
            if ($row = tep_db_fetch_array($sql)) {
              $menu_id = $row['id'];
            }

            $new_menu = true;
          } else {
            tep_db_perform(TABLE_MENUS, $sql_data_array, 'update', "id = " . (int)$menu_id);
          }


          if ($menu_id != 0 && !$new_menu) {
            $old = array();
            $new = array();
            $sql = tep_db_query("SELECT id from " . TABLE_MENU_ITEMS . " WHERE platform_id='".$selected_platform_id."' and menu_id='" . $menu_id . "'");
            while ($row = tep_db_fetch_array($sql)) {
              $old[] = $row['id'];
            }
            tep_db_perform(TABLE_MENUS, array('last_modified' => 'now()'), 'update', "id = '" . (int)$menu_id . "'");

            $order = 0;
            if (isset($params['list']) && is_array($params['list'])) foreach ($params['list'] as $item) {
              $link_type = tep_db_prepare_input($item['type']);
              $link = tep_db_prepare_input($item['link']);
              $link_id = tep_db_prepare_input($item['type_id']);
              $target_blank = tep_db_prepare_input($item['target_blank']);
              $no_logged = tep_db_prepare_input($item['no_logged']);
              $class = tep_db_prepare_input($item['class']);
              $sub_categories = tep_db_prepare_input($item['sub_categories']);
              $parent_link_type = tep_db_prepare_input($item['parent']['type']);
              $parent_link = tep_db_prepare_input($item['parent']['type_id']);
              $custom_page = tep_db_prepare_input($item['custom_page']);

              if (isset($item['parent']['id'])) {
                $parent_id = tep_db_prepare_input($item['parent']['id']);
              } elseif (isset($item['parent']['type_id'])) {
                $id = tep_db_fetch_array(tep_db_query("
                            select id from " . TABLE_MENU_ITEMS . "
                            where menu_id='" . (int)$menu_id . "' and link_type = '" . tep_db_input($parent_link_type) . "' and link_id = '" . (int)$parent_link . "'
                              and platform_id='".(int)$selected_platform_id."'
                            order by id desc"));
                $parent_id = $id['id'];
              } else {
                $parent_id = 0;
              }
              $sql_data_array = array(
                'menu_id' => $menu_id,
                'parent_id' => $parent_id,
                'platform_id' => $selected_platform_id,
                'link' => $link,
                'link_id' => $link_id,
                'link_type' => $link_type,
                'target_blank' => $target_blank,
                'no_logged' => $no_logged,
                'class' => $class,
                'sub_categories' => $sub_categories,
                'sort_order' => $order,
                'theme_page_id' => (int)$custom_page,
              );
              
              if (isset($item['id'])) {
                tep_db_perform(TABLE_MENU_ITEMS, $sql_data_array, 'update', "id = '" . (int)$item['id'] . "'");
                $new[(int)$item['id']] = $item['id'];

                $id['id'] = $item['id'];
              } else {
                tep_db_perform(TABLE_MENU_ITEMS, $sql_data_array);

                $id = array(
                  'id' => tep_db_insert_id(),
                );
                /*
                $id = tep_db_fetch_array(tep_db_query("
                            select id from " . TABLE_MENU_ITEMS . "
                            where menu_id='" . $menu_id . "' and link_type = '" . $link_type . "' and link_id = '" . $link_id . "'
                              and platform_id='".$selected_platform_id."'
                            order by id desc"));
                */
                $new[(int)$id['id']] = $id['id'];
              }
              
              if ($ext = \common\helpers\Acl::checkExtension('SeoRedirectsNamed', 'allowed')){
                $ext::saveMenuLinks($id['id'], $item['custom']);
              }

                if ($item['titles']) {
                    foreach ($item['titles'] as $title) {
                        $sql_data_array = array(
                          'language_id' => $title['language_id'],
                          'item_id' => $id['id'],
                          'title' => $title['title'],
                        );

                        $sql = tep_db_query("
                            select id from " . TABLE_MENU_TITLES . "
                            where language_id = " . $title['language_id'] . " and item_id = " . $id['id']);
                        if (tep_db_num_rows($sql) > 0) {
                            if ($title['title']) {
                                tep_db_perform(TABLE_MENU_TITLES, $sql_data_array, 'update', "language_id = '" . (int)$title['language_id'] . "' and item_id = " . (int)$id['id']);
                            } else {
                                tep_db_query("delete from " . TABLE_MENU_TITLES . " where language_id = '" . (int)$title['language_id'] . "' and item_id = " . (int)$id['id']);
                            }
                        } else {
                            if ($title['title']) {
                                tep_db_perform(TABLE_MENU_TITLES, $sql_data_array);
                            }
                        }
                    }
                }

              $order++;
            }


            foreach ($old as $id) {
              if (/*!in_array($id, $new)*/ !isset($new[(int)$id])) {
                tep_db_query("delete from " . TABLE_MENU_ITEMS . " where id = '" . (int)$id . "'");
                tep_db_query("delete from " . TABLE_MENU_TITLES . " where item_id = '" . (int)$id . "'");
                if ($ext = \common\helpers\Acl::checkExtension('SeoRedirectsNamed', 'allowed')){
                    $ext::deleteMenuLinks($id);
                }
              }
            }

            $response = MESSAGE_SAVED;
          } else {
            $response = array(MESSAGE_ADDED, $menu_id);
          }
        } else {

          tep_db_query("delete from " . TABLE_MENUS . " where id = '" . (int)$params['menu_id'] . "'");

          $sql = tep_db_query("select id from " . TABLE_MENU_ITEMS . " where menu_id = " . (int)$params['menu_id']);
          while ($row = tep_db_fetch_array($sql)) {
            tep_db_query("delete from " . TABLE_MENU_TITLES . " where item_id = '" . (int)$row['id'] . "'");
            if ($ext = \common\helpers\Acl::checkExtension('SeoRedirectsNamed', 'allowed')){
                $ext::deleteMenuLinks((int)$row['id']);
            }
          }

          tep_db_query("delete from " . TABLE_MENU_ITEMS . " where menu_id = '" . (int)$params['menu_id'] . "'");
          $response = MESSAGE_DELETED;
        }

        return json_encode( $response);
    }


  public function actionSaveName() {

      $params = Yii::$app->request->get();

      $sql_data_array = array(
          'menu_name' => $params['name'],
      );

      tep_db_perform(TABLE_MENUS, $sql_data_array, 'update', "id = " . (int)$params['id']);

      return json_encode( array('name' => $params['name']));
  }

}
