<?php
/**
 * @filesource modules/index/models/upload.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Upload;

use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Shared single-image upload/remove handler for frontend post forms
 * (board topics/replies, document comments, ...). Resizes into the site's
 * configured stored_img_type/stored_img_size, replacing the previous file.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Validate and store an uploaded image, or remove the existing one.
     * Populates $save[$fieldName] on success/removal, or $errors[$fieldName] on failure.
     * No-op if $uploadTypes is empty (upload not enabled for this module/category).
     *
     * @param Request     $request         The HTTP request
     * @param string      $fieldName       Posted file field name (e.g. "picture")
     * @param array       $uploadTypes     Allowed source extensions (module config)
     * @param string      $dir             Absolute target directory (must end with "/")
     * @param string      $filenameBase    Deterministic filename without extension
     *                                     (e.g. "board-5-12"); pass '' to generate a
     *                                     unique time-based name (used when the
     *                                     record ID isn't known yet, i.e. new records)
     * @param string|null $existingFile    Currently stored filename, if any
     * @param bool        $removeRequested True to delete the existing file and clear the field
     * @param array       $save            Save data, populated by reference
     * @param array       $errors          Errors, populated by reference
     *
     * @return void
     */
    public static function processImage(Request $request, $fieldName, array $uploadTypes, $dir, $filenameBase, $existingFile, $removeRequested, array &$save, array &$errors)
    {
        if (empty($uploadTypes)) {
            return;
        }

        if ($removeRequested) {
            if (!empty($existingFile) && is_file($dir.$existingFile)) {
                unlink($dir.$existingFile);
            }
            $save[$fieldName] = '';
            return;
        }

        foreach ($request->getUploadedFiles() as $item => $file) {
            if ($item !== $fieldName) {
                continue;
            }
            if ($file->hasUploadFile()) {
                if (!File::makeDirectory($dir)) {
                    $errors[$fieldName] = Language::replace('Directory %s cannot be created or is read-only.', str_replace(ROOT_PATH, '', $dir));
                    return;
                }
                try {
                    if ($filenameBase !== '') {
                        // Deterministic name (record ID already known, e.g. editing)
                        $filename = $filenameBase.self::$cfg->stored_img_type;
                    } else {
                        // Record ID not known yet (new record): unique time-based name
                        $mktime = time();
                        $filename = $mktime.self::$cfg->stored_img_type;
                        while (is_file($dir.$filename)) {
                            $mktime++;
                            $filename = $mktime.self::$cfg->stored_img_type;
                        }
                    }
                    $file->resizeImage($uploadTypes, $dir, $filename, self::$cfg->stored_img_size);
                    if (!empty($existingFile) && $existingFile !== $filename && is_file($dir.$existingFile)) {
                        unlink($dir.$existingFile);
                    }
                    $save[$fieldName] = $filename;
                } catch (\Exception $exc) {
                    $errors[$fieldName] = Language::get($exc->getMessage());
                }
            } elseif ($err = $file->getErrorMessage()) {
                $errors[$fieldName] = $err;
            }
        }
    }
}
