<?php

namespace Dcat\Admin\Grid\Displayers;

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Storage;

class Gallery extends AbstractDisplayer
{

//    public function display(array $args = ['server' => '', 'width' => 100, 'height' => 100])
    public function display($server = '', $width = 100, $height = 100)
    {
        if ($this->value instanceof Arrayable) {
            $this->value = $this->value->toArray();
        }

        $groupId = $this->grid->getTableId().'_'.$this->getKey().'_'.$this->column->getName().'_gallery_group';

        $src = []; // 避免 $src 未定义

        foreach (Helper::array($this->value) as $k => $v) {
            if (url()->isValidUrl($v) || mb_strpos($v, 'data:image') === 0) {
                $src[] = $v;
            } elseif ($server) {
                $src[] = rtrim($server, '/') .'/'. ltrim($v, '/');
            } else {
                $src[] = Storage::disk(config('admin.upload.disk'))->url($v);
            }

        }
        return Admin::view('admin::grid.displayer.gallery', ['src' => $src, 'width' => $width, 'height' => $height, 'id' => $groupId]);
    }
}
