<?php
/** Generate a URL-safe random slug (default 8 chars) */
function make_slug(int $len = 8): string
{
    return substr(
        str_replace(['+','/','='], '', base64_encode(random_bytes(10))),
        0,
        $len
    );
}
?>
