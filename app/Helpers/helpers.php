<?php

if (! function_exists('statusTahunBuku')) {
    function statusTahunBuku(int $tahun): string
    {
        if ($tahun >= 2023) {
            return 'Baru';
        }
        return 'Lama';
    }
}