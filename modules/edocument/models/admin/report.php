<?php
/**
 * @filesource modules/edocument/models/admin/report.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Admin\Report;

/**
 * โมเดลสำหรับแสดงรายละเอียดการดาวน์โหลด (report.php)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Orm\Field
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'edocument_download D';

    public function getConfig()
    {
        return array(
            'select' => array('D.member_id id', 'U.name', 'U.email', 'U.status', 'D.last_update', 'D.downloads', 'D.document_id'),
            'join' => array(
                array(
                    'LEFT',
                    'Index\User\Model',
                    array(
                        array('U.id', 'D.member_id')
                    )
                )
            )
        );
    }
}
