<?php

/*
 * Copyright (c) 2017 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 */

namespace MS\Normalizer;

use MS\ContainerType\Interfaces\Collection;
use MS\ContainerType\Interfaces\Item;

trait Normalizable
{
    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this);
    }

    /**
     * @param array $context
     *
     * @return array
     */
    public function normalize(array $context = [])
    {
        if ($this instanceof Collection) {
            return $this->normalizeCollection($context);
        }

        return $this->normalizeObject($context);
    }

    /**
     * @param array $context
     *
     * @return array
     */
    protected function normalizeObject(array $context = [])
    {
        $array = [];
        foreach ($this as $property => $value) {
            $context['property'] = $property;
            $array[$property] = $this->normalizeProperty($value, $context);
        }

        return array_filter($array);
    }

    /**
     * @param mixed $value
     * @param array $context
     *
     * @return mixed
     */
    protected function normalizeProperty($value, array $context = [])
    {
        if ($value instanceof Item) {
            $value = $value->normalize($context);
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return  $value;
    }

    /**
     * @param array $context
     *
     * @return array
     */
    protected function normalizeCollection(array $context = [])
    {
        $array = [];
        /** @var Item|Collection|static $this */
        foreach ($this as $key => $value) {
            $context['key'] = $key;
            $array[$key] = $value->normalize($context);
        }

        return $array;
    }

    /**
     * @param array|object $array
     * @param array        $context
     *
     * @return Item|Collection|static
     */
    public function denormalize($array, array $context = [])
    {
        $array = (array) $array;
        if (empty($array)) {
            return $this;
        }

        if ($this instanceof Collection) {
            return $this->denormalizeCollection($array, $context);
        }

        return $this->denormalizeObject($array, $context);
    }

    /**
     * @param array|object $array
     * @param array        $context
     *
     * @return Item|static
     */
    protected function denormalizeObject($array, array $context = [])
    {
        foreach ($array as $property => $data) {
            $context['property'] = $property;
            $this->$property = $this->denormalizeProperty($data, $context);
        }

        return $this;
    }

    /**
     * @param mixed $data
     * @param array $context
     *
     * @return Item|Collection|mixed
     */
    protected function denormalizeProperty($data, array $context = [])
    {
        $property = $context['property'];
        if (!property_exists($this, $property) or !($this->$property instanceof Item)) {
            return $data;
        }
        $value = $this->$property;

        /* @var Item|static $value */
        return $value->denormalize($data, $context);
    }

    /**
     * @param array $array
     * @param array $context
     *
     * @return Collection|static
     */
    protected function denormalizeCollection($array, array $context = [])
    {
        $context['class'] = static::TYPE;
        foreach ($array as $key => $data) {
            $context['key'] = $key;
            $this[$key] = $this->denormalizeItem($data, $context);
        }

        return $this;
    }

    /**
     * @param mixed $data
     * @param array $context
     *
     * @return Item|Collection|mixed
     */
    protected function denormalizeItem($data, array $context = [])
    {
        if ($data instanceof \stdClass) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            return $data;
        }

        $key = $context['key'];
        $class = $context['class'];
        $value = isset($value) ? $this[$key] : new $class();

        /* @var Item|static $value */
        return $value->denormalize($data, $context);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        /* @var Item $this */
        return $this->normalize(['format' => 'json']);
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->normalize(['format' => 'serialize']));
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        $this->denormalize($data, ['format' => 'serialize']);
    }
}
