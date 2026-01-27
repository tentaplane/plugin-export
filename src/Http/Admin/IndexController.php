<?php

declare(strict_types=1);

namespace TentaPress\Export\Http\Admin;

final class IndexController
{
    public function __invoke()
    {
        return view('tentapress-export::index');
    }
}
