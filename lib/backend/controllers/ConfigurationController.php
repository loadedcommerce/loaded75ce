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
use \common\helpers\Translation;
use \yii\helpers\Html;
use backend\models\Configuration;

class ConfigurationController extends Sceleton {

    public $acl = ['TEXT_SETTINGS', 'BOX_HEADING_CONFIGURATION'];
    
    private $group_title;
    private $group_id;
    private $use_trash;
    
    public function __construct ($id,$module=null){
      $check_trash = tep_db_query("show tables like 'configuration_trash'");
      
      $this->use_trash = tep_db_num_rows($check_trash) ? true : false;
      
      Translation::init('configuration');
      
      parent::__construct ($id,$module);
    }

    public function actionIndex()
    {
        global $languages_id, $language;
        
        $groupid = (int) Yii::$app->request->get( 'groupid' );

        $this->group_id = $groupid;

        $this->view->adminTable = array(
            array(
                'title'         => TEXT_TABLE_TITLE,
                'not_important' => 0
            ),
            array(
                'title'         => TEXT_TABLE_VALUE,
                'not_important' => 0
            )
        );

        $sms = \common\helpers\Acl::checkExtension('SMS', 'allowed');
        if ($sms && $groupid == $sms::getConfigurationGroupID()) {
            $this->selectedMenu = array('settings', 'sms_messages', 'configuration?groupid='.$groupid);
        } else {
            $this->selectedMenu = array('settings', 'configuration', "configuration/index?groupid=$groupid");
        }
        
        if ($ext = \common\helpers\Acl::checkExtension('DelayedDespatch', 'allowed')){
            $ext::initConfiguration();
        }
        if ($ext = \common\helpers\Acl::checkExtension('ReferFriend', 'allowed')){
            $ext::initConfiguration();
        }        
        
        if ($this->use_trash){
          $total = tep_db_fetch_array(tep_db_query("select count(*) as total from configuration_trash "));
          if ($total['total'] > 0){
            $this->topButtons[] = '<a href="'.Yii::$app->urlManager->createUrl('configuration/gettrashed').'" class="create_item backup"><i class="icon-file-text"></i>' . ICON_FILE_DOWNLOAD . ' '. TEXT_TRASHED . '</a>';	
          }          
        }

        $cfg_group_query = tep_db_query( "select configuration_group_title from " . TABLE_CONFIGURATION_GROUP . " where configuration_group_id = '" . (int) $groupid . "'" );
        $cfg_group       = tep_db_fetch_array( $cfg_group_query );

        if( isset( $cfg_group['configuration_group_title'] ) ) $this->group_title = $cfg_group['configuration_group_title'];

        $title = Translation::getTranslationValue('GROUP_' . $groupid . '_TITLE', 'configuration', $languages_id);
        if (tep_not_null($title)){
          $this->group_title = $title;
        }
        // #CP - local dir to the template directory where you are uploading the company logo
        /* $template_query = tep_db_query( "select configuration_id, configuration_title, configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'DEFAULT_TEMPLATE'" );
        $template       = tep_db_fetch_array( $template_query );
        $CURR_TEMPLATE  = $template['configuration_value'] . '/';
        $upload_fs_dir  = DIR_FS_TEMPLATES . $CURR_TEMPLATE . DIR_WS_IMAGES;
        $upload_ws_dir  = DIR_WS_TEMPLATES . $CURR_TEMPLATE . DIR_WS_IMAGES;

        // check if the template image directory exists
        if( is_dir( $upload_fs_dir ) ) {
            if( !is_writeable( $upload_fs_dir ) ) {
                $this->view->errorMessage = ERROR_TEMPLATE_IMAGE_DIRECTORY_NOT_WRITEABLE . ' '. $upload_fs_dir;
                $this->view->errorMessageType = 'danger';
            }
        } else {
            $this->view->errorMessage = ERROR_TEMPLATE_IMAGE_DIRECTORY_DOES_NOT_EXIST . ' '. $upload_fs_dir;
            $this->view->errorMessageType = 'danger';
        } */

        $this->navigation[]       = array( 'link' => Yii::$app->urlManager->createUrl( 'configuration/index' ), 'title' => BOX_HEADING_CONFIGURATION . ' :: ' . $this->group_title );
        $this->view->headingTitle = BOX_HEADING_CONFIGURATION .' :: ' . $this->group_title;
        $this->view->group_id     = $this->group_id;

        return $this->render( 'index' );
    }

    public function actionPreedit()
    { 
        global $access_levels_id;
        $this->layout = FALSE;

        global $languages_id, $language;


        $param_id   = (int) Yii::$app->request->post( 'param_id' );
        $group_id   = (int) Yii::$app->request->post( 'group_id' );
        $trash   = (boolean) Yii::$app->request->post( 'trash', false );

        $table = TABLE_CONFIGURATION;
        if ($trash && $this->use_trash) {
          $table = "configuration_trash";
        }
        
        $_query = "
                select configuration_id, configuration_title, date_added, configuration_value,configuration_description, use_function, set_function, configuration_key
                 from " . $table . " where configuration_group_id = '$group_id' and configuration_id = '$param_id'";
        $configuration_query = tep_db_query( $_query );
        $configuration       = tep_db_fetch_array( $configuration_query );
        
        $title = Translation::getTranslationValue($configuration['configuration_key'].'_TITLE', 'configuration', $languages_id);
        if (tep_not_null($title)){
          $configuration['configuration_title'] = $title;
        }
        
        if ($trash && $this->use_trash){
          $configuration['configuration_title'] .= '<br>(' . TEXT_TRASHED . ')';
        }

        ?>
        <div class="or_box_head"> <?php echo $configuration['configuration_title']; ?></div>
        <div class="row_or"><?php echo '<div>' . TEXT_INFO_DATE_ADDED .'</div><div>'. \common\helpers\Date::date_short($configuration['date_added']) ; ?></div></div>

        <input name="param_id" type="hidden" value="<?php echo $param_id; ?>">
        <input name="group_id" type="hidden" value="<?php echo $group_id; ?>">

        <div class="btn-toolbar btn-toolbar-order">
            <?php
             if (!$trash){
             ?>
            <button class="btn btn-primary btn-process-order btn-edit" onclick="return editItem( <?php echo "$param_id, $group_id"; ?>)"><?php echo IMAGE_EDIT;?></button>
            <?php
             }
             ?>            
            <?php
            if ($access_levels_id == 1){
                if (!$trash && $this->use_trash){
                  ?>
                  <button class="btn btn-process-order btn-delete" onclick="return trashItem( <?php echo "$param_id, $group_id"; ?>)"><?php echo IMAGE_TRASH;?></button>
                  <?php                  
                } else {
                  if ($this->use_trash){
                    ?>
                    <button class="btn btn-process-order btn-primary" onclick="return restoreItem( <?php echo "$param_id, $group_id"; ?>)"><?php echo IMAGE_RESTORE;?></button>
                    <?php
                  }
                  ?>
                  
                  <button class="btn btn-process-order btn-delete" onclick="return deleteTrashedItem( <?php echo "$param_id, $group_id"; ?>)"><?php echo IMAGE_DELETE;?></button>
                  <?php
                  
                }
            }
            
            //$check_platform_query = tep_db_query("SELECT * FROM " . TABLE_PLATFORMS_CONFIGURATION . " WHERE configuration_key='" . $configuration['configuration_key'] . "'");
            //if ( tep_db_num_rows($check_platform_query) == 0 ) {
            ?>
                  <button class="btn btn-process-order btn-primary" onclick="return installItem( <?php echo $param_id; ?>)"><?php echo IMAGE_INSTALL;?></button>
            <?php
            //}
            
            ?>
        </div>
    <?php
    }

    public function actionGetparam()
    {
        global $languages_id;
        $this->layout = FALSE;

        $group_id = Yii::$app->request->post( 'group_id' );
        $param_id = Yii::$app->request->post( 'param_id' );

        $_query = "
                select configuration_id, configuration_title, configuration_value,configuration_description, use_function, set_function, configuration_key
                 from " . TABLE_CONFIGURATION . " where configuration_group_id = '$group_id' and configuration_id = '$param_id'";
        $configuration_query = tep_db_query( $_query );
        $configuration       = tep_db_fetch_array( $configuration_query );

        if( !is_array( $configuration ) ) die( "Wrong data" );
        
        $method = trim(strtolower(substr($configuration['set_function'], 0, strpos($configuration['set_function'], '('))));
        
        if( (string) $configuration['set_function']  && method_exists ('backend\models\Configuration', $method)) {

            $_args = preg_replace("/" . $method . "[\s\(]*/i", "", $configuration['set_function']). "'" . htmlspecialchars( $configuration['configuration_value'] ) . "', '" . $configuration['configuration_key'] . "'";
            
            $value_field = call_user_func(array('backend\models\Configuration', $method), $_args);

            /*
            if( strpos( $configuration['set_function'], 'tep_cfg_select_multioption' ) !== FALSE ) {
                eval( '$value_field = ' . $configuration['set_function'] . '"' . htmlspecialchars( $configuration['configuration_value'] ) . '","' . $configuration['configuration_key'] . '");' );
            } else {
                eval( '$value_field = ' . $configuration['set_function'] . '"' . htmlspecialchars( $configuration['configuration_value'] ) . '");' );
            }*/
        } else {
            $value_field = tep_draw_input_field( 'configuration_value', $configuration['configuration_value'], 'class="form-control"' );
        }
        
        $translated_title = Translation::getTranslationValue($configuration['configuration_key'].'_TITLE', 'configuration', $languages_id);

        echo tep_draw_form(
                'save_param_form',
                'configuration/index',
                \common\helpers\Output::get_all_get_params( array( 'action' ) ) . 'action=update',
                'post',
                'id="save_param_form" onSubmit="return saveParam();"' ) .
            tep_draw_hidden_field( 'group_id', $group_id ) .
            tep_draw_hidden_field( 'param_id', $param_id ). 
            tep_draw_hidden_field( 'configuration_key', $configuration['configuration_key'] );
        
        $languages = \common\helpers\Language::get_languages(true);
        
        $title = Translation::getTranslationValue($configuration['configuration_key'].'_TITLE', 'configuration', $languages_id);
        if (tep_not_null($title)){
          $configuration['configuration_title'] = $title;
        }
        $description = Translation::getTranslationValue($configuration['configuration_key'].'_DESC', 'configuration', $languages_id);
        if (tep_not_null($description)){
          $configuration['configuration_description'] = $description;
        }        
        ?>
				<div class="or_box_head"><?php echo $configuration['configuration_title']; ?></div>
				<div class="row_or dataTableContent"><?php echo $configuration['configuration_description']; ?></div>
				<div class="row_or dataTableContent"><?php echo $value_field; ?></div>
        <?php
        if (!tep_not_null($translated_title)){
        ?>
        <br>
        <div class="row_or dataTableContent">
            <div class="tab-pane">
                <div class="tabbable tabbable-custom">
                    <ul class="nav nav-tabs">
                        <?php foreach($languages as $lKey => $lItem) {?>
                        <li <?php if($lKey == 0) {?> class="active"<?php }?> ><a href="#tab_2_<?php echo $lItem['id'];?>" class="flag-span" data-toggle="tab"><?php echo $lItem['image'];?><span><?php echo $lItem['name'];?></span></a></li>
                        <?php }?>
                    </ul>
                    <div class="tab-content">
                        <?php foreach($languages as $lKey => $lItem) {?>
                        <div class="tab-pane<?php if($lKey == 0) {?>  active<?php } ?>" id="tab_2_<?php echo $lItem['id'];?>">
                            <div class="">
                                <label><?php echo Translation::getTranslationValue('TEXT_TITLE', 'admin/main', $lItem['id']);?></label>
                                <?php echo Html::textInput($configuration['configuration_key'].'_TITLE['.$lItem['id'].']', $configuration['configuration_title']);?>
                            </div>
                            <div class="">
                                <label><?php echo Translation::getTranslationValue('TEXT_DESCRIPTION', 'admin/main', $lItem['id']);?></label>
                                <?php echo Html::textarea($configuration['configuration_key'].'_DESC['.$lItem['id'].']', $configuration['configuration_description'])?>
                            </div>                            
                        </div>
                        <?php }?>
                    </div>                    
                </div>                
            </div>        
        </div>
        <?php }?>
				<div class="btn-toolbar btn-toolbar-order">
					<button class="btn btn-no-margin"><?php echo IMAGE_UPDATE;?></button><button class="btn" onclick="return resetStatement()"><?php echo IMAGE_BACK;?></button>					
				</div>
        </form>
    <?php
    }

    public function actionGetgroupcontent()
    {
        global $languages_id;
        $this->layout            = FALSE;
        $customers_query_numrows = 1;

        $draw    = Yii::$app->request->get( 'draw' );
        $start   = Yii::$app->request->get( 'start' );
        $length  = Yii::$app->request->get( 'length' );
        $groupid = Yii::$app->request->get( 'groupid' );

        $responseList = array();
        $extra_html   = '';

        $search           = '';
        $search_condition = " where 1 ";
        if( isset( $_GET['search'] ) && tep_not_null( $_GET['search'] ) ) {
            if( is_array( $_GET['search'] ) ) {
                if( isset( $_GET['search']['value'] ) ) {
                    if( trim( $_GET['search']['value'] ) != '' ) {
                        $keywords         = tep_db_input( tep_db_prepare_input( $_GET['search']['value'] ) );
                        $search_condition = " where (configuration_title like '%" . $keywords . "%' or configuration_description like '%" . $keywords . "%' )";
                    }
                }
            }
        }
        if (!\common\helpers\Acl::checkExtension('VatOnOrder', 'allowed')) {
            $search_condition .= " and configuration_key NOT IN ('ACCOUNT_COMPANY', 'ACCOUNT_COMPANY_VAT_ID') ";
        }
        if (!\common\helpers\Acl::checkExtension('UserGroups', 'allowed')) {
            $search_condition .= " and configuration_key NOT IN ('CUSTOMERS_GROUPS_ENABLE', 'DEFAULT_USER_GROUP', 'DEFAULT_USER_LOGIN_GROUP', 'ENABLE_CUSTOMER_GROUP_CHOOSE') ";
        }

        if( isset( $_GET['order'][0]['column'] ) && $_GET['order'][0]['dir'] ) {
            switch( $_GET['order'][0]['column'] ) {
                case 0:
                    $orderBy = "sort_order";
                    //$orderBy = "configuration_title " . tep_db_prepare_input( $_GET['order'][0]['dir'] );
                    break;
                case 1:
                    $orderBy = "configuration_description " . tep_db_prepare_input( $_GET['order'][0]['dir'] );
                    break;
                default:
                    $orderBy = "sort_order";
                    break;
            }
        } else {
            $orderBy = "sort_order";
        }

        $_query = "select configuration_id, configuration_title, configuration_value, use_function, configuration_key
                   from " . TABLE_CONFIGURATION . "

                    $search_condition
                    and configuration_group_id = '" . (int) $groupid . "'
                    order by $orderBy ";


        $current_page_number = ( $start / $length ) + 1;
        $db_split      = new \splitPageResults( $current_page_number, $length, $_query, $configuration_query_numrows, 'configuration_id' );

        $configuration_query = tep_db_query( $_query );
        while( $configuration = tep_db_fetch_array( $configuration_query ) ) {

            if( tep_not_null( $configuration['use_function'] ) ) {
                $use_function = $configuration['use_function'];
                if( preg_match( '/->/', $use_function ) ) {

                    $class_method = explode( '->', $use_function );

                    if( !is_object( ${$class_method[0]} ) ) {

                        ${$class_method[0]} = new $class_method[0]();
                    }

                    $cfgValue = tep_call_function( $class_method[1], $configuration['configuration_value'], ${$class_method[0]} );
                } else {
                    if (method_exists ('backend\models\Configuration', $use_function)){
                      $cfgValue = call_user_func(array('backend\models\Configuration', $use_function), $configuration['configuration_value']);
                    } else if (function_exists($use_function)){
                      $cfgValue = tep_call_function( $use_function, $configuration['configuration_value'] );
                    } else if (is_callable ($use_function)){
                        $cfgValue = call_user_func( $use_function, $configuration['configuration_value'] );
                    }
                }
            } else {
                $_t = Translation::getTranslationValue(strtoupper(str_replace(" ", "_", $configuration['configuration_value'])), 'configuration', $languages_id);
                $_t = (tep_not_null($_t) ? $_t : $configuration['configuration_value']);              
                $cfgValue = $_t;
            }

            $cfg_extra_query = tep_db_query( "select configuration_key, configuration_description, date_added, last_modified, use_function, set_function from " . TABLE_CONFIGURATION . " where configuration_id = '" . (int) $configuration['configuration_id'] . "'" );
            $cfg_extra       = tep_db_fetch_array( $cfg_extra_query );

            $cInfo_array = array_merge( $configuration, $cfg_extra );

            if( $configuration['configuration_key'] == 'STORE_COUNTRY' ) {
                $cfgValue = \common\helpers\Country::get_country_name( $configuration['configuration_value'] );
            }

            if( $configuration['configuration_key'] == 'DOWNLOADS_CONTROLLER_ORDERS_STATUS' || $configuration['configuration_key'] == 'AFFILIATE_PAYMENT_ORDER_MIN_STATUS' || $configuration['configuration_key'] == 'VENDOR_PAYMENT_ORDER_MIN_STATUS' ) {
                $extra_html = \common\helpers\Order::get_status_name( $cfgValue );
                $cfgValue = '';
            } elseif( $configuration['configuration_key'] == 'DEFAULT_USER_GROUP' || $configuration['configuration_key'] == 'DEFAULT_USER_LOGIN_GROUP' ) {
                $extra_html = \common\helpers\Group::get_user_group_name( $cfgValue );
                $cfgValue = '';
            } else {
                $extra_html = htmlspecialchars( $cfgValue );
            }

            if( strip_tags( trim( strtolower( $extra_html ) ) ) === strip_tags( trim( strtolower( $cfgValue ) ) ) ) $extra_html = '';

            $title = Translation::getTranslationValue($configuration['configuration_key'].'_TITLE', 'configuration', $languages_id);
            if (!tep_not_null($title)) {
              $title = $cInfo_array['configuration_title'];
            }
            
            $responseList[] = array(
                $title . "<input class='cell_identify' type='hidden' value='" . $cInfo_array['configuration_id'] . "' />",
                $cfgValue . " $extra_html "
                //$cInfo_array['configuration_value']
            );
        }
       
        $configuration_query_numrows1 = 0;
        if ($this->use_trash) {
          $_query = "select configuration_id, configuration_title, configuration_value, use_function, configuration_key
                     from configuration_trash 
                      $search_condition
                      and configuration_group_id = '" . (int) $groupid . "'
                      order by $orderBy ";
                      
          $current_page_number = ( $start / $length ) + 1;
          $db_split      = new \splitPageResults( $current_page_number, $length, $_query, $configuration_query_numrows1, 'configuration_id' );

          
          $configuration_query = tep_db_query( $_query );
          if (tep_db_num_rows($configuration_query)){

              $responseList[] = array(
                  '<span class="modules_divider"></span>',
                  '<span class="modules_divider"></span>'
              );              
              
              while( $configuration = tep_db_fetch_array( $configuration_query ) ) {
                $title = Translation::getTranslationValue($configuration['configuration_key'].'_TITLE', 'configuration', $languages_id);
                if (!tep_not_null($title)) {
                  $title = $configuration['configuration_title'];
                }              
                $responseList[] = array(
                    '<div><div class="dis_module">' . $title . "<input class='cell_identify' type='hidden' value='" . $configuration['configuration_id'] . "'  data-trash = 'true' />" . '</div></div>',
                    '<div class="dis_module">' . $configuration['configuration_value'] .'</div>'
                );
              }
          }          
        }
        
                    

        $response = array(
            'draw'            => $draw,
            'recordsTotal'    => $configuration_query_numrows + $configuration_query_numrows1,
            'recordsFiltered' => $configuration_query_numrows + $configuration_query_numrows1,
            'data'            => $responseList
        );

        echo json_encode( $response );
    }

    public function actionSaveparam()
    {
        global $languages_id, $language;


        $this->layout = FALSE;
        $error        = FALSE;
        $message      = '';
        $messageType  = 'success';
        $html         = "";

        $group_id            = (int) Yii::$app->request->post( 'group_id' );
        $configuration_id    = (int) Yii::$app->request->post( 'param_id' );
        $configuration_key   = Yii::$app->request->post( 'configuration_key' );
        $configuration_value = Yii::$app->request->post( 'configuration_value' );
        $configuration = Yii::$app->request->post( 'configuration');

        if( is_array( $configuration_value ) ) {
            $configuration_value = implode( ", ", $configuration_value );
            $configuration_value = preg_replace( "/, --none--/", "", $configuration_value );
        } elseif (is_array( $configuration )){
          $configuration_value = $configuration[$configuration_key];
        }

        tep_db_query( "update " . TABLE_CONFIGURATION . "
          set configuration_value = '" . tep_db_input( tep_db_prepare_input( $configuration_value ) ) . "', last_modified = now()
          where configuration_key = '" . tep_db_input( $configuration_key ) . "'" );

        if (is_array($_POST)){
          foreach($_POST as $translation_key => $value){
            if (strpos($translation_key, 'TITLE') !== false || strpos($translation_key, 'DESC') !== false){
              if (is_array($value)){
                foreach($value as $language_id => $translation_value){
                  Translation::setTranslationValue($translation_key, 'configuration', $language_id, $translation_value);
                }
              } else {
                list($language_id, $translation_value) = each($value);
                Translation::setTranslationValue($translation_key, 'configuration', $language_id, $translation_value);               
              }
            }
          }
        }

        // TODO Check if there were no MySql errors
        if( TRUE ) {
            $message = TEXT_PARAM_CHANGE_SUCCESS;
        }

        if( $error === TRUE ) {
            $messageType = 'warning';
        }

        if( $message != '' ) {
            ?>
            <div class="popup-box-wrap pop-mess">
            <div class="around-pop-up"></div>
            <div class="popup-box">
                <div class="pop-up-close pop-up-close-alert"></div>
                <div class="pop-up-content">
                    <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                    <div class="popup-content pop-mess-cont pop-mess-cont-<?php echo $messageType?>">
                        <?php echo $message?>
                    </div>   
                </div> 
                <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK;?></span></div>
                </div>
            </div>
            <script>
            $('body').scrollTop(0);
            $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                $(this).parents('.pop-mess').remove();
                //location.reload();
                resetStatement();
            });
        </script>
        </div>
        
            <?php echo $html ?>
        <?php
        }

        $this->actionGetParam();
    }
    
    public function actionTrash(){
        global $languages_id, $language;

        if (!$this->use_trash) return;
        
        $group_id            = (int) Yii::$app->request->post( 'group_id' );
        $configuration_id    = (int) Yii::$app->request->post( 'param_id' );
        tep_db_query("replace into configuration_trash select * from ". TABLE_CONFIGURATION . " where configuration_id = $configuration_id");
        tep_db_query("delete from ". TABLE_CONFIGURATION . " where configuration_id = $configuration_id");
        // TODO Check if there were no MySql errors
        if( TRUE ) {
            $message = TEXT_PARAM_CHANGE_SUCCESS;
        }

        if( $error === TRUE ) {
            $messageType = 'warning';
        }

        if( $message != '' ) {
            ?>
            <div class="popup-box-wrap pop-mess">
            <div class="around-pop-up"></div>
            <div class="popup-box">
                <div class="pop-up-close pop-up-close-alert"></div>
                <div class="pop-up-content">
                    <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                    <div class="popup-content pop-mess-cont pop-mess-cont-<?php echo $messageType?>">
                        <?php echo $message?>
                    </div>   
                </div> 
                <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK;?></span></div>
                </div>
            </div>
            <script>
            $('body').scrollTop(0);
            $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                $(this).parents('.pop-mess').remove();
                resetStatement();
            });
        </script>
        </div>
        <?php
        }

        $this->actionGetParam();        
    }
    
    public function actionRestoreTrashed(){
        global $languages_id, $language;

        if (!$this->use_trash) return;
        
        $group_id            = (int) Yii::$app->request->post( 'group_id' );
        $configuration_id    = (int) Yii::$app->request->post( 'param_id' );
        
        tep_db_query("replace into " . TABLE_CONFIGURATION . " select * from configuration_trash where configuration_id = $configuration_id");
        tep_db_query("delete from configuration_trash where configuration_id = $configuration_id");
        // TODO Check if there were no MySql errors
        if( TRUE ) {
            $message = TEXT_PARAM_CHANGE_SUCCESS;
        }

        if( $error === TRUE ) {
            $messageType = 'warning';
        }

        if( $message != '' ) {
            ?>
            <div class="popup-box-wrap pop-mess">
            <div class="around-pop-up"></div>
            <div class="popup-box">
                <div class="pop-up-close pop-up-close-alert"></div>
                <div class="pop-up-content">
                    <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                    <div class="popup-content pop-mess-cont pop-mess-cont-<?php echo $messageType?>">
                        <?php echo $message?>
                    </div>   
                </div> 
                <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK;?></span></div>
                </div>
            </div>
            <script>
            $('body').scrollTop(0);
            $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                $(this).parents('.pop-mess').remove();
                resetStatement();
            });
        </script>
        </div>
        <?php
        }        
      
  //      $this->actionGetParam(); 
    }
    
    public function actionDeleteTrashed(){
        global $languages_id, $language;

        if (!$this->use_trash) return;
        
        $group_id            = (int) Yii::$app->request->post( 'group_id' );
        $configuration_id    = (int) Yii::$app->request->post( 'param_id' );
        
        tep_db_query("delete from configuration_trash where configuration_id = $configuration_id");
        // TODO Check if there were no MySql errors
        if( TRUE ) {
            $message = TEXT_PARAM_CHANGE_SUCCESS;
        }

        if( $error === TRUE ) {
            $messageType = 'warning';
        }

        if( $message != '' ) {
            ?>
            <div class="popup-box-wrap pop-mess">
            <div class="around-pop-up"></div>
            <div class="popup-box">
                <div class="pop-up-close pop-up-close-alert"></div>
                <div class="pop-up-content">
                    <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                    <div class="popup-content pop-mess-cont pop-mess-cont-<?php echo $messageType?>">
                        <?php echo $message?>
                    </div>   
                </div> 
                <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK;?></span></div>
                </div>
            </div>
            <script>
            $('body').scrollTop(0);
            $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                $(this).parents('.pop-mess').remove();
                resetStatement();
            });
        </script>
        </div>
        <?php
        }      
    }
    
    public function actionGettrashed(){

      if (!$this->use_trash) return;

      $filename  =  'trashed_configuration_keys_' . strftime( '%Y%_b%d_%H%I' ) . '.sql';
      $mime_type = 'text/plain';

      header( 'Content-Type: ' . $mime_type );
      header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
      header( 'Pragma: no-cache' );
    
      
      $_query = tep_db_query("select * from configuration_trash where 1");
      if (tep_db_num_rows($_query)){
        
        ob_start();
        echo "/*move to configuration_trash from configuration*/" . "\r\n" . "\r\n";
        
        while ($row = tep_db_fetch_array($_query)){
          echo "replace into configuration_trash select * from " . TABLE_CONFIGURATION . " where configuration_id = '" . $row['configuration_id'] . "'" . "\r\n";
        }
        
        tep_db_data_seek($_query, 0);
        
        echo "\r\n" . "/*delete from configuration*/" . "\r\n" . "\r\n";
        while ($row = tep_db_fetch_array($_query)){
          echo "delete from " . TABLE_CONFIGURATION . " where configuration_id = '" . $row['configuration_id'] . "'" . "\r\n";
        }        
        
        $buf = ob_get_contents();
        ob_end_clean();
        @file_put_contents(DIR_FS_CATALOG."sql/".$filename, $buf);
        echo $buf;
        
      }
      
      
      
      
      exit();
    }
    
    public function actionInstallKey() {
        $configuration_id = (int) Yii::$app->request->post( 'id' );
        $cfg_query = tep_db_query( "select * from " . TABLE_CONFIGURATION . " where configuration_id = '" . $configuration_id . "'" );
        $sql_data_array = tep_db_fetch_array( $cfg_query );
        unset($sql_data_array['configuration_id']);
        $platform_query = tep_db_query("select * from " . TABLE_PLATFORMS . " where 1");
        while ($platform = tep_db_fetch_array($platform_query)) {
            $sql_data_array['platform_id'] = $platform['platform_id'];
            $sql_data_array['date_added'] = 'now()';
            $sql_data_array['last_modified'] = 'now()';
            $check_query = tep_db_query("SELECT * FROM " . TABLE_PLATFORMS_CONFIGURATION . " WHERE configuration_key='" . tep_db_input($sql_data_array['configuration_key']) . "' and platform_id='" . (int)$sql_data_array['platform_id'] . "'");
            if (tep_db_num_rows($check_query) > 0) {
                $check = tep_db_fetch_array($check_query);
                tep_db_perform( TABLE_PLATFORMS_CONFIGURATION, $sql_data_array, 'update', "configuration_id = '" . (int)$check['configuration_id'] . "'" );
            } else {
                tep_db_perform( TABLE_PLATFORMS_CONFIGURATION, $sql_data_array );
            }
        }
    }
} 