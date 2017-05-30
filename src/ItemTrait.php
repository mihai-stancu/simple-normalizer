<?php

/*
 * Copyright (c) 2017 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 */

namespace MS\Normalizer;

trait ItemTrait
{
    use Normalizable;

    /**
     * @param array $data
     * @param array $context
     */
    public function __construct($data = [], array $context = [])
    {
        $this->denormalize($data, $context);
    }
}
