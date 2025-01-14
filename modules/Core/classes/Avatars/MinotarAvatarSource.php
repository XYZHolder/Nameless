<?php
declare(strict_types=1);

/**
 * Minotar avatar source class
 *
 * @package Modules\Core\Avatars
 * @author Aberdeener
 * @version 2.0.0-pr12
 * @license MIT
 */
class MinotarAvatarSource extends AvatarSourceBase {

    public function __construct() {
        $this->_name = 'Minotar';
        $this->_base_url = 'https://minotar.net/';
        $this->_perspectives_map = [
            'face' => 'helm',
            'head' => 'cube',
        ];
    }

    /**
     * Get raw URL with placeholders to format.
     * - `{identifier} = UUID / username`
     * - `{size} = size in pixels`
     *
     * @param string $perspective Perspective to use in url.
     *
     * @return string URL with placeholders to format.
     */
    public function getUrlToFormat(string $perspective): string {
        return $this->_base_url . $this->getRelativePerspective($perspective) . '/{identifier}/{size}.png';
    }
}
