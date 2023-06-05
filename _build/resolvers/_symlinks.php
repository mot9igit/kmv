<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    $dev = MODX_BASE_PATH . 'Extras/kmv/';
    /** @var xPDOCacheManager $cache */
    $cache = $modx->getCacheManager();
    if (file_exists($dev) && $cache) {
        if (!is_link($dev . 'assets/components/kmv')) {
            $cache->deleteTree(
                $dev . 'assets/components/kmv/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_ASSETS_PATH . 'components/kmv/', $dev . 'assets/components/kmv');
        }
        if (!is_link($dev . 'core/components/kmv')) {
            $cache->deleteTree(
                $dev . 'core/components/kmv/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_CORE_PATH . 'components/kmv/', $dev . 'core/components/kmv');
        }
    }
}

return true;