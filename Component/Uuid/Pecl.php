<?php

namespace Pyramid\Component\Uuid;

class Pecl {
    public function generate() {
        return uuid_create(UUID_TYPE_DEFAULT);
    }
}
