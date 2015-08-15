<?php

/**
 * @file
 *
 * Select
 */

namespace Pyramid\Component\Database\SQLite;

use Pyramid\Component\Database\Select as QuerySelect;

class Select extends QuerySelect {

    public function forUpdate($set = true) {
        return $this;
    }

}
