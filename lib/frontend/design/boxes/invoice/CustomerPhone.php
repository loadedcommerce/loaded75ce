<?php
/**
 * This file is part of Loaded Commerce.
 * 
 * @link http://www.loadedcommerce.com
 * @copyright Copyright (c) 2017 Global Ecommerce Solutions Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\invoice;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class CustomerPhone extends Widget
{

  public $file;
  public $params;
  public $settings;

  public function init()
  {
    parent::init();
  }

  public function run()
  {
    if ($this->params["order"]->customer['telephone']) {
      $heading = '';
      if ($this->settings[0]['show_heading']) {
        $heading = '<div style="font-size:14px;font-weight:bold;padding-top:10px;text-transform:uppercase;">' . ENTRY_TELEPHONE_NUMBER . ':</div>';
      }
      return $heading . $this->params["order"]->customer['telephone'];
    } else {
      return '';
    }
  }
}