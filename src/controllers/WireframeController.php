<?php

/** Halaman preview wireframe statis: dokumentasi visual, tanpa query database. */
final class WireframeController
{
    public static function index(): void
    {
        view('wireframe', [
            'title' => 'Lo-Fi Wireframe Preview',
            'activePage' => 'wireframe',
        ], 'wireframe');
    }
}
