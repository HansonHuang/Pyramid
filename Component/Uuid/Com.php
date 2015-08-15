<?php

namespace Pyramid\Component\Uuid;

class Com {
    public function generate() {
        return strtolower(trim(com_create_guid(), '{}'));
    }
}
