<?php
/**
 * @filesource modules/index/models/notify.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Notify;

/**
 * Sends a LINE notification to a module's moderators/approvers when a
 * configured event (new topic, new reply, new comment, ...) happens.
 * Shared by any frontend module that exposes a "line_notifications" setting
 * (an array of event values) plus a role config (e.g. "moderator" or
 * "can_approve") used to pick which member statuses should be notified.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Notify members whose status is in $config[$moderatorConfigKey] and who
     * have a line_uid set, provided $notifyValue is present in the module's
     * line_notifications setting.
     *
     * @param array  $config             Effective (category-aware) module config (array)
     * @param int    $notifyValue        The line_notifications value that triggers this event
     * @param string $moderatorConfigKey Config key holding the notifiable statuses (e.g. "moderator", "can_approve")
     * @param string $label              Event label shown in the message (e.g. "New topic", "New comment")
     * @param string $sender             Display name of the poster
     * @param string $topic              Topic/article title
     * @param string $url                Link to the item
     *
     * @return void
     */
    public static function notifyModerators(array $config, $notifyValue, $moderatorConfigKey, $label, $sender, $topic, $url)
    {
        $notifications = isset($config['line_notifications']) && is_array($config['line_notifications']) ? $config['line_notifications'] : [];
        if (!in_array($notifyValue, $notifications, true)) {
            return;
        }

        $moderatorStatuses = isset($config[$moderatorConfigKey]) && is_array($config[$moderatorConfigKey]) ? $config[$moderatorConfigKey] : [];
        $moderatorStatuses = array_values(array_filter(array_map('intval', $moderatorStatuses), function ($status) {
            // -1 = guest, never has an account/line_uid to notify
            return $status !== -1;
        }));
        if (empty($moderatorStatuses)) {
            return;
        }

        $lineUids = [];
        $rows = static::createQuery()
            ->select('line_uid')
            ->from('user')
            ->where(['active', 1])
            ->where(['status', $moderatorStatuses])
            ->where(['line_uid', '!=', ''])
            ->fetchAll();
        foreach ($rows as $row) {
            if (!empty($row->line_uid)) {
                $lineUids[] = $row->line_uid;
            }
        }

        if (empty($lineUids)) {
            return;
        }

        $message = implode("\n", array_filter([
            trim((string) $sender).' '.trim((string) $label).':',
            trim((string) $topic),
            trim((string) $url)
        ], function ($line) {
            return $line !== '';
        }));

        \Gcms\Line::sendTo($lineUids, $message);
    }
}
