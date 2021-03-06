<?php
/**
 * This file is part of Loaded Commerce.
 * 
 * @link http://www.loadedcommerce.com
 * @copyright Copyright (c) 2017 Global Ecommerce Solutions Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\boxes\product;

use Yii;
use yii\base\Widget;

class ImagesAdditional extends Widget
{

  public $id;
  public $params;
  public $settings;
  public $visibility;

  public function init()
  {
    parent::init();
  }

  public function run()
  {

    return $this->render('../../views/images-additional.tpl', [
      'id' => $this->id, 'params' => $this->params, 'settings' => $this->settings,
      'visibility' => $this->visibility,
    ]);
  }
}