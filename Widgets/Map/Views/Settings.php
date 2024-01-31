<?php
/**
 * @filesource Widgets/Map/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Map\Views;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * โมดูลสำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Gcms\Adminview
{
    /**
     * module=Map-Settings
     *
     * @return string
     */
    public function render()
    {
        // default
        self::$cfg->map_api_key = isset(self::$cfg->map_api_key) ? self::$cfg->map_api_key : '';
        self::$cfg->map_height = isset(self::$cfg->map_height) ? self::$cfg->map_height : 400;
        self::$cfg->map_zoom = isset(self::$cfg->map_zoom) ? self::$cfg->map_zoom : 14;
        self::$cfg->map_latitude = isset(self::$cfg->map_latitude) ? self::$cfg->map_latitude : '14.132081110519639';
        self::$cfg->map_lantitude = isset(self::$cfg->map_lantitude) ? self::$cfg->map_lantitude : '99.69822406768799';
        self::$cfg->map_info_latitude = isset(self::$cfg->map_info_latitude) ? self::$cfg->map_info_latitude : '14.132081110519639';
        self::$cfg->map_info_lantitude = isset(self::$cfg->map_info_lantitude) ? self::$cfg->map_info_lantitude : '99.69822406768799';
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/Widgets/Map/Models/Settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Config}'
        ));
        // map_api_key
        $fieldset->add('text', array(
            'id' => 'map_api_key',
            'labelClass' => 'g-input icon-google',
            'label' => '{LNG_Google API Key}',
            'itemClass' => 'item',
            'value' => self::$cfg->map_api_key
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Determine the size and position of the map}'
        ));
        $groups = $fieldset->add('groups-table');
        // map_height
        $groups->add('number', array(
            'id' => 'map_height',
            'labelClass' => 'g-input icon-height',
            'label' => '{LNG_Size of} {LNG_Google Map} ({LNG_Height})',
            'itemClass' => 'width',
            'value' => self::$cfg->map_height
        ));
        // map_zoom
        $groups->add('text', array(
            'id' => 'map_zoom',
            'labelClass' => 'g-input icon-search',
            'label' => '{LNG_Zoom}',
            'itemClass' => 'width',
            'readonly' => true,
            'value' => self::$cfg->map_zoom
        ));
        $groups = $fieldset->add('groups-table', array(
            'comment' => '{LNG_Location of the info}',
            'comment' => '{LNG_Click Find me button to configure the map to the current location of the computer, or click the Search button to find the approximate location you need.}'
        ));
        // map_info_latitude
        $groups->add('text', array(
            'id' => 'map_info_latitude',
            'labelClass' => 'g-input icon-location',
            'label' => '{LNG_Latitude}',
            'itemClass' => 'width',
            'pattern' => '[0-9\.]+',
            'value' => self::$cfg->map_info_latitude
        ));
        // map_info_lantitude
        $groups->add('text', array(
            'id' => 'map_info_lantitude',
            'labelClass' => 'g-input icon-location',
            'label' => '{LNG_Longitude}',
            'itemClass' => 'width',
            'pattern' => '[0-9\.]+',
            'value' => self::$cfg->map_info_lantitude
        ));
        $groups->add('button', array(
            'id' => 'find_me',
            'itemClass' => 'width bottom',
            'title' => '{LNG_Find me}',
            'class' => 'button hidden go icon-gps'
        ));
        $groups->add('button', array(
            'id' => 'map_search',
            'itemClass' => 'width bottom',
            'title' => '{LNG_Search}',
            'class' => 'button go icon-search'
        ));
        $fieldset->add('div', array(
            'id' => 'map_canvas',
            'class' => 'item',
            'innerHTML' => 'Google Map',
            'style' => 'height:'.self::$cfg->map_height.'px'
        ));
        $groups = $fieldset->add('groups-table');
        // map_latitude
        $groups->add('text', array(
            'id' => 'map_latitude',
            'labelClass' => 'g-input icon-location',
            'label' => '{LNG_Latitude}',
            'itemClass' => 'width',
            'pattern' => '[0-9\.]+',
            'value' => self::$cfg->map_latitude
        ));
        // map_lantitude
        $groups->add('text', array(
            'id' => 'map_lantitude',
            'labelClass' => 'g-input icon-location',
            'label' => '{LNG_Longitude}',
            'itemClass' => 'width',
            'pattern' => '[0-9\.]+',
            'value' => self::$cfg->map_lantitude
        ));
        // map_info
        $fieldset->add('textarea', array(
            'id' => 'map_info',
            'labelClass' => 'g-input icon-file',
            'label' => '{LNG_Info}',
            'itemClass' => 'item',
            'comment' => '{LNG_Text (HTML) to be displayed at the location of the shop or company}',
            'rows' => 5,
            'value' => isset(self::$cfg->map_info) ? self::$cfg->map_info : ''
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // Javascript
        $form->script('initMapDemo("'.self::$cfg->map_api_key.'", "'.Language::name().'");');
        // คืนค่า HTML
        return $form->render();
    }
}
