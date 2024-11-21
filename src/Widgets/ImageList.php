<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Storage;

class ImageList extends Widget
{
    protected $path;
    protected $server;
    protected $width = 100;
    protected $height = 100;

    /**
     * Dump constructor.
     *
     * @param  array|object|string  $content
     * @param  string|null  $padding
     */
    public function __construct($path, $server = '', $width = 100, $height = 100)
    {
        $this->path = $path;
        $this->server = $server;
        $this->width = $width;
        $this->height = $height;
    }

    public function render()
    {
        if (empty($this->path)) {
            return '';
        }

        $path = Helper::array($this->path);
        $server = $this->server;
        $width = $this->width;
        $height = $this->height;

        return collect($path)->transform(function ($path) use ($server, $width, $height) {
            if (url()->isValidUrl($path)) {
                $src = $path;
            } elseif ($server) {
                $src = rtrim($server, '/').'/'.ltrim($path, '/');
            } else {
                $disk = config('admin.upload.disk');

                if (config("filesystems.disks.{$disk}")) {
                    $src = Storage::disk($disk)->url($path);
                } else {
                    return '';
                }
            }

            return "<img data-action='preview-img' src='$src' style='max-width:{$width}px;max-height:{$height}px' class='img' />";
        })->implode('&nbsp;');
    }
}
