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

/**
 * VirtualGiftCard controller
 */
class VirtualGiftCardController extends Sceleton {

    public $acl = ['BOX_HEADING_MARKETING_TOOLS', 'BOX_HEADING_GV_ADMIN', 'BOX_VIRTUAL_GIFT_CARD'];

    function __construct($id, $module = null) {
        if (false === \common\helpers\Acl::checkExtension('CouponsAndVauchers', 'allowed')) {
            $this->redirect(array('/'));
        }
        parent::__construct($id, $module);
    }

    public function actionIndex() {

        $this->selectedMenu = array('marketing', 'gv_admin', 'virtual-gift-card');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('virtual-gift-card/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        $check_product = tep_db_fetch_array(tep_db_query("select products_id from " . TABLE_PRODUCTS . " where products_model = 'VIRTUAL_GIFT_CARD'"));
        $products_id = $check_product['products_id'];
        if (!($products_id > 0)) {
            $sql_data_array = array(
                'products_date_added' => 'now()',
                'products_seo_page_name' => 'virtual-gift-card',
                'products_old_seo_page_name' => '',
                'products_quantity' => 7777,
                'products_weight' => 0,
                'products_status' => 0,
                'products_model' => 'VIRTUAL_GIFT_CARD');
            tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
            $products_id = tep_db_insert_id();
            tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int) $products_id . "', '0')");
        }

        $languages = \common\helpers\Language::get_languages();

        $currencies = new \common\classes\currencies();

        $defaultCurrenciy = $currencies->currencies[DEFAULT_CURRENCY]['id'];
        $currenciesTabs = [];
        if (USE_MARKET_PRICES == 'True') {
            foreach ($currencies->currencies as $key => $value) {
                $gift_card_price_query = tep_db_query("select products_price from " . TABLE_VIRTUAL_GIFT_CARD_PRICES . " where products_id = '" . (int) $products_id . "' and currencies_id = '" . (int) $currencies->currencies[$key]['id'] . "' order by products_price");
                $content = '';
                while ($gift_card_price = tep_db_fetch_array($gift_card_price_query)) {
                    $content .= '<div>' . tep_draw_input_field('products_price[' . $currencies->currencies[$key]['id'] . '][]', $gift_card_price['products_price']) . '<input type="button" value=" - " onClick="delete_row_price(this)" class="infoBoxButton"></div>';
                }
                $content .= '<div id="gift_card_price_' . $currencies->currencies[$key]['id'] . '"></div><div align="right"><input type="button" value="+" onClick="add_row_price(\'gift_card_price_' . $currencies->currencies[$key]['id'] . '\', \'' .
                        htmlspecialchars('<div>' . tep_draw_input_field('products_price[' . $currencies->currencies[$key]['id'] . '][]', '') . '<input type="button" value=" - " onClick="delete_row_price(this)" class="infoBoxButton"></div>')
                        . '\')" class="infoBoxButton"></div>';
                $currenciesTabs[$currencies->currencies[$key]['id']] = [
                    'title' => $currencies->currencies[$key]['title'],
                    'content' => $content,
                ];
            }
        } else {
            $gift_card_price_query = tep_db_query("select products_price from " . TABLE_VIRTUAL_GIFT_CARD_PRICES . " where products_id = '" . (int)$products_id . "' and currencies_id = '" . (int)$currencies->currencies[DEFAULT_CURRENCY]['id'] . "' order by products_price");
            $content = '';
            while ($gift_card_price = tep_db_fetch_array($gift_card_price_query)) {
                $content .= '<div>' . tep_draw_input_field('products_price[' . $currencies->currencies[DEFAULT_CURRENCY]['id'] . '][]', $gift_card_price['products_price'], 'class="form-control"') . '<input type="button" value=" - " onClick="delete_row_price(this)" class="infoBoxButton"></div>';
            }
              $content .= '<div id="gift_card_price_' . $currencies->currencies[DEFAULT_CURRENCY]['id'] . '"></div><div class="vgc_add_line"><input type="button" value="+" onClick="add_row_price(\'gift_card_price_' . $currencies->currencies[DEFAULT_CURRENCY]['id'] . '\', \'' .
                    htmlspecialchars('<div>' . tep_draw_input_field('products_price[' . $currencies->currencies[DEFAULT_CURRENCY]['id'] . '][]', '') . '<input type="button" value=" - " onClick="delete_row_price(this)" class="infoBoxButton"></div>') 
                    . '\')" class="infoBoxButton"></div>';
            $currenciesTabs[$currencies->currencies[DEFAULT_CURRENCY]['id']] = [
                    'title' => $currencies->currencies[DEFAULT_CURRENCY]['title'],
                    'content' => $content,
                ];
        }

        return $this->render('index', [
                    'products_id' => $products_id,
                    'languages' => $languages,
                    'currenciesTabs' => $currenciesTabs,
                    'defaultCurrenciy' => $defaultCurrenciy,
        ]);
    }

    public function actionSubmit() {

        \common\helpers\Translation::init('admin/virtual-gift-card');
        
        $check_product = tep_db_fetch_array(tep_db_query("select products_id from " . TABLE_PRODUCTS . " where products_model = 'VIRTUAL_GIFT_CARD'"));
        $products_id = $check_product['products_id'];
        if (!($products_id > 0)) {
            $sql_data_array = array(
                'products_date_added' => 'now()',
                'products_seo_page_name' => 'virtual-gift-card',
                'products_old_seo_page_name' => '',
                'products_quantity' => 7777,
                'products_weight' => 0,
                'products_status' => 0,
                'products_model' => 'VIRTUAL_GIFT_CARD');
            tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
            $products_id = tep_db_insert_id();
            tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int) $products_id . "', '0')");
        }

        $languages = \common\helpers\Language::get_languages();

        $currencies = new \common\classes\currencies();

        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
            $sql_data_array = array('products_id' => $products_id,
                'products_name' => tep_db_prepare_input($_POST['products_name'][$languages[$i]['id']]),
                'affiliate_id' => '0');
            $check = tep_db_fetch_array(tep_db_query("select products_id from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int) $products_id . "' and language_id = '" . (int) $languages[$i]['id'] . "' and affiliate_id = '0'"));
            if ($check['products_id']) {
                tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array, 'update', "products_id = '" . (int) $products_id . "' and language_id = '" . (int) $languages[$i]['id'] . "' and affiliate_id = '0'");
            } else {
                $sql_data_array['language_id'] = $languages[$i]['id'];
                tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array);
            }
        }

        tep_db_query("delete from " . TABLE_VIRTUAL_GIFT_CARD_PRICES . " where products_id = '" . (int) $products_id . "'");
        foreach ($currencies->currencies as $key => $value) {
            if (is_array($_POST['products_price'][$currencies->currencies[$key]['id']]) && count($_POST['products_price'][$currencies->currencies[$key]['id']]) > 0) {
                foreach ($_POST['products_price'][$currencies->currencies[$key]['id']] as $price) {
                    if ($price > 0) {
                        $sql_data_array = array(
                            'products_id' => $products_id,
                            'currencies_id' => $currencies->currencies[$key]['id'],
                            'products_price' => $price);
                        tep_db_perform(TABLE_VIRTUAL_GIFT_CARD_PRICES, $sql_data_array);
                    }
                }
            }
        }

        $message = TEXT_PRODUCT_UPDATED_NOTICE;
        $messageType = 'success';
        ?>
        <div class="popup-box-wrap pop-mess">
            <div class="around-pop-up"></div>
            <div class="popup-box">
                <div class="pop-up-close pop-up-close-alert"></div>
                <div class="pop-up-content">
                    <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                    <div class="popup-content pop-mess-cont pop-mess-cont-<?= $messageType ?>">
                        <?= $message ?>
                    </div> 
                </div>  
                <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK; ?></span></div>
                </div>
            </div> 
            <script>
                $('body').scrollTop(0);
                $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function () {
                    $(this).parents('.pop-mess').remove();
                });
            </script>
        </div>


        <?php
        echo '<script> window.location.href="' . Yii::$app->urlManager->createUrl('virtual-gift-card/') . '";</script>';
    }

}
