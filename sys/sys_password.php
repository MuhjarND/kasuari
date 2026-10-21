<?php

function kasuari_password_matches($password, $hash)
{
    return password_verify($password, (string) $hash)
        || hash_equals((string) $hash, base64_encode(md5($password)));
}
