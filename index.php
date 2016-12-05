<?php
$params = require_once 'config.php';

try {
    //Проверка директорий
    validateDirectories([
        __DIR__ . '/' . ltrim($params['uploadDir'], '/'),
        __DIR__ . '/' . ltrim($params['imagesDir'], '/'),
        __DIR__ . '/' . ltrim($params['archiveDir'], '/'),
    ]);

    //Обработка файлов
    processFiles(__DIR__ . '/' . ltrim($params['uploadDir'], '/'), $params);

    echo "Done!\n";
} catch (Exception $e) {
    echo $e->getMessage();
}

/**
 * Проверяет наличие директорий и возможность записи в них
 *
 * @param array $dirs
 *
 * @throws Exception
 */
function validateDirectories($dirs)
{
    if (is_array($dirs) === false) {
        throw new Exception('Argument must be an array');
    }

    foreach ($dirs as $dir) {
        if (is_readable($dir) === false) {
            throw new Exception('Directory ' . $dir . ' doesn\'t exsists or not readable');
        }

        if (is_writable($dir) === false) {
            throw new Exception('Directory ' . $dir . ' not writable');
        }

        if (is_dir($dir) === false) {
            throw new Exception($dir . ' doesn\'t folder');
        }
    }
}

/**
 * Обработка файлов в соответствии с условиями.
 *
 * @param string $dir
 * @param array $params
 */
function processFiles($dir, $params)
{
    $files = glob(rtrim($dir, '/') . '/*');

    if (!empty($files)) {
        foreach ($files as $file) {
            //Если это вложеная директория, рекурсивно вызываем функцию для чтения ее содержимого
            if (is_dir($file) && is_readable($file)) {
                processFiles($file, $params);
            } else {
                //Если изображение, перемещаем его в соответствующую папку
                if (isImage($file, $params) === true){
                    if (copy($file, __DIR__ . '/' . trim($params['imagesDir'], '/') . '/' . pathinfo($file, PATHINFO_BASENAME))){
                        chmod($file, 0777);
                        if (unlink($file) === false) {
                            echo "Cannot remove file " . $file . "\n";
                        }
                    }
                }

                //Если старый фаил, переносим в архив
                if (isOld($file, $params) === true){
                    if (copy($file, __DIR__ . '/' . trim($params['archiveDir'], '/') . '/' . pathinfo($file, PATHINFO_BASENAME))){
                        chmod($file, 0777);
                        if (unlink($file) === false) {
                            echo "Cannot remove file " . $file . "\n";
                        }
                    }
                }
            }
        }
    }
}

/**
 * Проверяет является ли фаил изображением, проверка происходит на основании
 * расширений изображений, которые указаны в конфиге
 *
 * @param string $file
 * @param array $params
 *
 * @return bool
 */
function isImage($file, $params)
{
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    return in_array($extension, $params['imagesExtensions']);
}

/**
 * Проверяет время последней модификации файла, если оно больше указанного
 * в конфиге, возвращает true
 *
 * @param string $file
 * @param array $params
 * @return bool
 */
function isOld($file, $params)
{
    return (time() - filemtime($file)) >= $params['archiveMaxDays']*86400;
}