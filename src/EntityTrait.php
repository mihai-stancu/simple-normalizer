<?php

/*
 * Copyright (c) 2017 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 */

namespace MS\Normalizer;

trait EntityTrait
{
    use Normalizable;

    /**
     * @param array $data
     */
    public function __construct($data = [])
    {
        static::denormalize($data, $this);
    }
}
