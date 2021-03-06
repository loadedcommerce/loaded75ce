<?php
/**
 * This file is part of Loaded Commerce.
 * 
 * @link http://www.loadedcommerce.com
 * @copyright Copyright (c) 2017 Global Ecommerce Solutions Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes\modules;

class ModuleStatus{
  public $key;
  public $value_enabled;
  public $value_disabled;

  function __construct($key, $value_enabled, $value_disabled)
  {
    $this->key = $key;
    $this->value_enabled = $value_enabled;
    $this->value_disabled = $value_disabled;
  }
}
