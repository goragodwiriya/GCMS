<?php
/**
 * @filesource modules/edocument/models/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Write;

use Kotchasan\Language;
use Kotchasan\Text;

/**
 * E-Document write model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get a document for editing, or a blank one when $id is 0.
     *
     * @param int    $id
     * @param object $module module with normalized config
     *
     * @return object|null
     */
    public static function get($id, $module)
    {
        if ($id === 0) {
            return (object) [
                'id' => 0,
                'module_id' => (int) $module->id,
                'sender_id' => 0,
                'document_no' => self::documentNo($module->config->format_no, self::nextId()),
                'topic' => '',
                'detail' => '',
                'ext' => '',
                'size' => 0,
                'file' => '',
                'downloads' => 0,
                'reciever' => []
            ];
        }

        $row = static::createQuery()
            ->select('id', 'module_id', 'sender_id', 'document_no', 'topic', 'detail', 'ext', 'size', 'file', 'downloads', 'reciever')
            ->from('edocument')
            ->where([
                ['id', (int) $id],
                ['module_id', (int) $module->id]
            ])
            ->first();

        if (!$row) {
            return null;
        }

        $row->id = (int) $row->id;
        $row->module_id = (int) $row->module_id;
        $row->sender_id = (int) $row->sender_id;
        $row->reciever = \Edocument\Setup\Model::parseReciever($row->reciever);

        return $row;
    }

    /**
     * Document number from the module format, e.g. E-%04d -> E-0012.
     *
     * @param string $format
     * @param int    $id
     *
     * @return string
     */
    public static function documentNo($format, $id)
    {
        if ($format !== '') {
            try {
                return mb_substr(\Kotchasan\Number::printf($format, $id), 0, 20);
            } catch (\Throwable $e) {
                // invalid legacy format: fall back to the plain ID
            }
        }

        return (string) $id;
    }

    /**
     * Is $document_no used by a document other than $id.
     *
     * @param string $document_no
     * @param int    $id
     *
     * @return bool
     */
    public static function documentNoExists($document_no, $id)
    {
        $search = static::createQuery()
            ->select('id')
            ->from('edocument')
            ->where(['document_no', $document_no])
            ->first();

        return $search && (int) $search->id !== (int) $id;
    }

    /**
     * Email the members in the recipient groups about a document.
     * Best effort: a mail failure never fails the save.
     *
     * @param object $module
     * @param array  $save saved `edocument` row
     * @param array  $reciever member statuses
     *
     * @return int number of emails sent
     */
    public static function notify($module, array $save, array $reciever)
    {
        $statuses = array_values(array_filter($reciever, fn($status) => $status >= 0));
        if (empty($statuses)) {
            return 0;
        }

        $query = static::createQuery()
            ->select('name', 'username')
            ->from('user')
            ->where([
                ['status', $statuses],
                ['active', 1]
            ]);

        $url = \Web\Gcms::createUrl($module->module);
        $webTitle = strip_tags((string) self::$cfg->web_title);
        $subject = Language::replace('New documents at :name', [':name' => $webTitle]);
        $document = trim($save['document_no'].' '.$save['topic']);

        $sent = 0;
        foreach ($query->fetchAll() as $item) {
            $email = trim((string) $item->username);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $name = trim((string) $item->name) === '' ? $email : trim((string) $item->name);
            $html = '<p>'.Language::get('Dear').' '.Text::htmlspecialchars($name).'</p>'
            .'<p>'.Language::get('There is a new document for you').' : <b>'.Text::htmlspecialchars($document).'</b></p>'
            .'<p><a href="'.$url.'">'.$url.'</a></p>';
            try {
                $result = \Kotchasan\Email::send($email, '', $subject, $html);
                if (!$result->error()) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        return $sent;
    }

    /**
     * Next `edocument` ID (used to preview a new document number).
     *
     * @return int
     */
    private static function nextId()
    {
        return \Kotchasan\DB::create()->nextId('edocument');
    }
}
