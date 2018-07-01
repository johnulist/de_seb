{*
 * 2018 DevExpert OÜ
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * DevExpert OÜ hereby grants you a personal, non-transferable,
 * non-exclusive licence to use the SEB banklink.
 * DevExpert OÜ shall at all times retain ownership of
 * the Software as originally downloaded by you and all
 * subsequent downloads of the Software by you.
 * The Software (and the copyright, and other intellectual
 * property rights of whatever nature in the
 * Software, including any modifications made thereto)
 * are and shall remain the property of DevExpert OÜ.
 * This EULA agreement, and any dispute arising out of or in
 * connection with this EULA agreement, shall
 * be governed by and construed in accordance with the laws of Estonia.
 *
 * @author    DevExpert OÜ
 * @copyright 2018 DevExpert OÜ
 * @license   End User License Agreement (EULA)
 * @link      https://devexpert.ee
 * @package   de_seb
 * @version   1.0.0
*}
{extends file='page.tpl'}
{block name="page_content"}
    <p class="alert alert-success">
        {l s='Your Purchase is confirmed!' mod='de_seb'}
    </p>
    <p>{l s='Please click below if you are not redirected' mod='de_seb'}<p>
    <form id="banklink_form" action='{$banklink_url|escape:'html'}' target='_self' method='POST'>
        {foreach from=$banklink_form key=name item=value}
            <input type="hidden" name="{$name|escape:'html'}" value="{$value|escape:'html'}"/>
        {/foreach}
        <button class="button btn btn-default button-medium" type='submit'>
            {l s='Start a payment' mod='de_seb'}
        </button>
    </form>
{/block}