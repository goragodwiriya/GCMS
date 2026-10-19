<?php
/**
 * @filesource modules/board/controllers/categories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Categories;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API Board Categories Controller (DataTable)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * Get custom parameters for table
     *
     * @param Request $request
     * @param object  $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'module_id' => $request->get('module_id')->toInt()
        ];
    }

    /**
     * Check authorization
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!$login || !ApiController::isAdmin($login)) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * Query data for DataTable
     */
    protected function toDataTable($params, $login = null)
    {
        return Model::toDataTable($params);
    }

    /**
     * Format data list — decode JSON topic/detail/icon for display
     *
     * @param array  $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $lng = Language::name();
        $data = [];
        foreach ($datas as $row) {
            // Decode topic JSON
            $topic = json_decode($row->topic ?: '{}', true) ?: [];
            foreach ($topic as $key => $value) {
                $topic[$key] = '<div class="one_line"><img src="'.WEB_URL.'language/'.$key.'.gif" alt="'.$key.'"> '.htmlspecialchars($value).'</div>';
            }
            // Send as an object with `html` so TableManager can insert innerHTML
            $row->topic = ['html' => implode('', $topic)];

            // Decode detail JSON
            $detail = json_decode($row->detail ?: '{}', true) ?: [];
            foreach ($detail as $key => $value) {
                $detail[$key] = '<div class="one_line"><img src="'.WEB_URL.'language/'.$key.'.gif" alt="'.$key.'"> '.htmlspecialchars($value).'</div>';
            }
            // Send as an object with `html` so TableManager can insert innerHTML
            $row->detail = ['html' => implode('', $detail)];

            // Decode icon
            $icons = json_decode($row->icon ?: '{}', true) ?: [];
            $iconPath = $icons[$lng] ?? reset($icons) ?: '';
            $row->icon = ($iconPath && file_exists(ROOT_PATH.$iconPath))
                ? WEB_URL.$iconPath.'?'.time()
                : WEB_URL.'images/no-image.webp';

            // Decode config and render compact settings icons
            $config = json_decode($row->config ?: '{}', true) ?: [];
            $settings = [];

            if (!empty($config['can_post']) && is_array($config['can_post'])) {
                $settings[] = '<span class="icon-newtopic notext" title="{LNG_Posting} '.$this->cfgToStr($config['can_post']).'"></span>';
            }
            if (!empty($config['can_reply']) && is_array($config['can_reply'])) {
                $settings[] = '<span class="icon-chat reply1 notext" title="{LNG_Comment} '.$this->cfgToStr($config['can_reply']).'"></span>';
            }
            if (!empty($config['can_view']) && is_array($config['can_view'])) {
                $settings[] = '<span class="icon-visited color-red notext" title="{LNG_Viewing} '.$this->cfgToStr($config['can_view']).'"></span>';
            }
            if (!empty($config['moderator']) && is_array($config['moderator'])) {
                $settings[] = '<span class="icon-customer color-blue notext" title="{LNG_Moderator} '.$this->cfgToStr($config['moderator']).'"></span>';
            }
            if (!empty($config['img_upload_type']) && is_array($config['img_upload_type'])) {
                $settings[] = '{LNG_Type} <b>'.implode(', ', $config['img_upload_type']).'</b> '.Language::get('IMG_LAW', '', $config['img_law']);
            }

            $row->config = ['html' => '<span class="nowrap">'.implode(' ', $settings).'</span>', 'i18n' => true];

            $data[] = $row;
        }
        return $data;
    }

    /**
     * Convert member-status ids to readable labels.
     *
     * @param array $cfg
     *
     * @return string
     */
    private function cfgToStr(array $cfg): string
    {
        $ret = [];
        foreach ($cfg as $item) {
            if ((int) $item === -1) {
                $ret[] = '{LNG_Guest}';
            } elseif (isset(self::$cfg->member_status[(int) $item])) {
                $ret[] = self::$cfg->member_status[(int) $item];
            }
        }
        return implode(', ', $ret);
    }

    /**
     * Handle edit action — redirect to the single-category form
     *
     * @param Request $request
     * @param object  $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $row = json_decode($request->post('row')->toJson());
        if ($row) {
            return $this->redirectResponse('/board-category?id='.$row->id.'&module_id='.$row->module_id);
        }
    }

    /**
     * Handle delete action — remove category record(s) and their icon files
     *
     * @param Request $request
     * @param object  $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!$login || !ApiController::isAdmin($login)) {
            return $this->errorResponse('Permission required', 403);
        }

        $module = \Index\Module\Model::getModuleWithConfig('board', $request->post('module_id')->toInt());
        if (!$module) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }

        foreach ($ids as $id) {
            \Board\Category\Model::remove($id);
        }

        \Index\Log\Model::add(0, 'board', 'Board', 'Delete Category ID(s) : '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.count($ids).' category(s) successfully');
    }

    /**
     * Handle status action (published|1, published|0)
     *
     * @param Request $request
     * @param object  $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleStatusAction(Request $request, $login)
    {
        if (!$login || !ApiController::isAdmin($login)) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        $status = $request->request('status')->filter('a-z0-9_');

        if (preg_match('/^([a-z]+)_([0-1])$/', $status, $match)) {
            $column = $match[1];
            $value = (int) $match[2];

            if (in_array($column, ['published'])) {
                \Board\Categories\Model::updateStatus($ids, $column, $value);
                \Index\Log\Model::add(0, 'board', 'Board', 'Update Category ID(s): '.implode(', ', $ids).' '.$column.'='.$value, $login->id);
                return $this->redirectResponse('reload', 'Updated successfully', 200, 0, 'table');
            }
        }

        return $this->errorResponse('Invalid status action', 400);
    }
}
