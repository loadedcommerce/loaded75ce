
  <div id="payment_method">
    <div class="payment-method">
      <div class="heading-4">{$smarty.const.PAYMENT_METHOD}</div>

      {foreach $selection as $i}
        <div class="item payment_item payment_class_{$i.id}"  {if $i.hide_row} style="display: none"{/if}>
          {if isset($i.methods)}
            {foreach $i.methods as $m}
                <div class="item-radio">
                    <label>
                      <input type="radio" name="payment" value="{$m.id}"{if $i.hide_input} style="display: none"{/if}{if $m.checked} checked{/if}/>
                      <span>{$m.module}</span>
                    </label>
                </div>
            {/foreach}
          {else}
          <div class="item-radio">
            <label>
              <input type="radio" name="payment" value="{$i.id}"{if $i.hide_input} style="display: none"{/if}{if $i.checked} checked{/if}/>
              <span>{$i.module}</span>
            </label>
          </div>
          {/if}
          {foreach $i.fields as $j}
            <div class="sub-item">
              <label>
                <span>{$j.title}</span>
                {$j.field}
              </label>
            </div>
          {/foreach}
        </div>
      {/foreach}

    </div>



    {if (\common\helpers\Acl::checkExtension('CouponsAndVauchers', 'checkoutCouponVoucher'))}
        {\common\extensions\CouponsAndVauchers\CouponsAndVauchers::checkoutCouponVoucher($credit_modules)}
    {/if}
    {if $credit_modules.ot_gv && $is_logged_customer && $credit_modules.credit_amount>0 }
      <div class="discount-box">
        <div>
          <span class="title">{$smarty.const.TEXT_CREDIT_AMOUNT_INFO}</span> {$credit_modules.credit_amount_formatted}
          <span class="title" style="margin-left: 20px">{$smarty.const.TEXT_CREDIT_AMOUNT_ASK_USE}</span> <input type="checkbox" name="cot_gv" {if $credit_modules.cot_gv_active } checked="checked" {/if} class="credit-on-off">
        </div>
        <div class="js_cot_gv_dep" style="padding-bottom: 20px">
          <p>{$smarty.const.TEXT_CREDIT_AMOUNT_CUSTOM_USE}</p>
          <button type="button" class="btn-3 js_discount_apply">{$smarty.const.TEXT_APPLY}</button>
          <div class="inp"><input type="text" autocomplete="off" name="cot_gv_amount" value="{$credit_modules.custom_gv_amount}"></div>
        </div>
      </div>
    {/if}
  </div>