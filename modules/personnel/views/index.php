<?php
/**
 * @filesource modules/personnel/views/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Index;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Personnel Frontend Views
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render personnel list page (hierarchical org-chart style)
     *
     * @param object $index Module data
     *
     * @return object
     */
    public function render($index)
    {
        $items = [];
        $departments = [];
        $levels = \Personnel\Category\Model::levelOptions();
        foreach (\Personnel\Category\Model::toOptions($index->module_id, 'department') as $department) {
            $departments[$department['value']] = Text::htmlspecialchars($department['text']);
            $items[$department['value']] = [];
            foreach ($levels as $level) {
                $items[$department['value']][$level['value']] = [];
            }
        }

        // Group personnel by department and level
        foreach ($index->items as $item) {
            if (file_exists(ROOT_PATH.DATA_FOLDER.'personnel/'.$item->picture)) {
                $item->image_url = WEB_URL.DATA_FOLDER.'personnel/'.$item->picture;
            } else {
                $item->image_url = WEB_URL.'images/no-image.webp';
            }
            $items[$item->department][$item->level][] = $item;
        }

        // Render person-item template per level
        $html = '';
        foreach ($items as $department => $levels) {
            $levelHtml = '';
            foreach ($levels as $level => $persons) {
                if (empty($persons)) {
                    continue;
                }
                $listitem = Template::create($index->owner, $index->module, 'person-item');
                foreach ($persons as $person) {
                    $listitem->add([
                        '/{ID}/' => $person->id,
                        '/{NAME}/' => Text::htmlspecialchars($person->name),
                        '/{DEPARTMENT}/' => $departments[$department] ?? '{LNG_Not specified}',
                        '/{POSITION}/' => empty($person->position) ? '{LNG_Not specified}' : Text::htmlspecialchars($person->position),
                        '/{PHONE}/' => empty($person->phone) ? '' : Text::htmlspecialchars($person->phone),
                        '/{EMAIL}/' => empty($person->email) ? '' : Text::htmlspecialchars($person->email),
                        '/{DETAIL}/' => empty($person->detail) ? '' : Text::htmlspecialchars($person->detail),
                        '/{IMAGE}/' => $person->image_url,
                        '/{LEVEL}/' => $level
                    ]);
                }
                if ($listitem->hasItem()) {
                    $levelHtml .= '<section class="personnel-level level-'.$level.'"><div class="personnel-row">'.$listitem->render().'</div></section>';
                }
            }
            if (empty($levelHtml)) {
                continue;
            }
            $dep_title = isset($departments[$department]) ? $departments[$department] : '{LNG_Uncategorized}';
            $html .= '<section class="personnel-department department-'.$department.'"><h2 class="level-title">'.$dep_title.'</h2>'.$levelHtml.'</section>';
        }

        // Department tabs
        $deptTabs = '';
        $baseUrl = Gcms::createUrl($index->module);
        $deptTabs .= '<a'.($index->department === 0 ? ' class="active"' : '').' href="'.$baseUrl.'">{LNG_All}</a>';
        foreach ($departments as $department => $deptName) {
            $active = ($index->department === $department) ? ' class="active"' : '';
            $deptTabs .= '<a'.$active.' href="'.$baseUrl.'?department='.$department.'">'.$deptName.'</a>';
        }

        // template list.html
        $template = Template::create($index->owner, $index->module, 'list');

        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = $baseUrl;
        }

        // get detail
        $detail = \Index\Index\Model::getDetail($index->index_id, $index->module_id);

        $template->add([
            '/{TOPIC}/' => $index->topic,
            '/{DESCRIPTION}/' => $index->description,
            '/{DETAIL}/' => Gcms::highlighter($detail),
            '/{MODULE}/' => $index->module,
            '/{DEPT_TABS}/' => $deptTabs,
            '/{PERSONNEL_LIST}/' => $html ?: '<div class="list-empty"><div class="list-empty-icon icon-customer"></div><h3>{LNG_No personnel found}</h3></div>'
        ]);

        $index->detail = $template->render();
        return $index;
    }
}
