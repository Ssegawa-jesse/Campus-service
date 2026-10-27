<?php

if (! function_exists('media_url')) {
    /**
     * Link to a cover or photo stored on the public disk, served by MediaController.
     * A relative link, so it works on localhost, 127.0.0.1 or any other address.
     */
    function media_url(string $path): string
    {
        [$folder, $file] = array_pad(explode('/', $path, 2), 2, '');

        return route('media', ['folder' => $folder, 'file' => $file], absolute: false);
    }
}
