<?php

/*
 * Copyright (c) 2017 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 */

namespace MS\Normalizer;

use MS\ContainerType\Interfaces\Collection;
use MS\ContainerType\Interfaces\Entity;

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
     * @param Entity $object
     * @param array  $context
     *
     * @return array
     */
    public static function normalize(Entity $object, array $context = [])
    {
        $method = !($object instanceof \ArrayAccess) ? 'normalizeObject' : 'normalizeCollection';

        return $object::$method($object, $context);
    }

    /**
     * @param Entity $object
     * @param array  $context
     *
     * @return array
     */
    protected static function normalizeObject(Entity $object, array $context = [])
    {
        $array = [];
        foreach ($object as $key => $value) {
            $array[$key] = static::normalizeProperty($value, $object, $key, $context);
        }

        return array_filter($array);
    }

    /**
     * @param mixed  $value
     * @param Entity $object
     * @param string $property
     * @param array  $context
     *
     * @return mixed
     */
    protected static function normalizeProperty($value, Entity $object, $property, array $context = [])
    {
        if ($value instanceof Entity) {
            $value = $value::normalize($value, $context);
        }

        return is_object($value) ? (array) $value : $value;
    }

    /**
     * @param Collection|Entity[] $collection
     * @param array               $context
     *
     * @return array
     */
    protected static function normalizeCollection(Collection $collection, array $context = [])
    {
        $array = [];
        foreach ($collection as $key => $value) {
            $array[$key] = $value::normalize($value, $context);
        }

        return $array;
    }

    /**
     * @param array|object $array
     * @param Entity       $object
     * @param array        $context
     *
     * @return self
     */
    public static function denormalize($array, Entity $object = null, array $context = [])
    {
        $array = (array) $array;
        $object = $object ?: new static();

        if (empty($array)) {
            return $object;
        }

        $method = !($object instanceof \ArrayAccess) ? 'denormalizeObject' : 'denormalizeCollection';

        return static::$method($array, $object, $context);
    }

    /**
     * @param array|object $array
     * @param Entity       $object
     * @param array        $context
     *
     * @return object
     */
    protected static function denormalizeObject($array, Entity $object, array $context = [])
    {
        foreach ($array as $property => $value) {
            $object->$property = static::denormalizeProperty($value, $object, $property, $context);
        }

        return $object;
    }

    /**
     * @param mixed  $value
     * @param Entity $object
     * @param string $property
     * @param array  $context
     *
     * @return Entity|\ArrayAccess|mixed
     */
    protected static function denormalizeProperty($value, Entity $object, $property, array $context = [])
    {
        if (property_exists($object, $property) and $object->$property instanceof Entity) {
            /** @var Entity $item */
            $item = $object->$property;
            $value = $item::denormalize($value, $item, $context);
        }

        return $value;
    }

    /**
     * @param array      $array
     * @param Collection $collection
     * @param array      $context
     *
     * @return Collection
     */
    protected static function denormalizeCollection($array, Collection $collection, array $context = [])
    {
        /** @var Entity $class */
        $class = static::TYPE;
        foreach ($array as $key => $value) {
            $value = $value instanceof \stdClass ? (array) $value : $value;
            $value = is_array($value) ? $class::denormalize($value, $collection[$key] ?? null, $context) : $value;

            $collection[$key] = $value;
        }

        return $collection;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        /* @var Entity $this */
        return static::normalize($this, ['format' => 'json']);
    }

    /**
     * @return string
     */
    public function serialize()
    {
        /* @var Entity|\ArrayAccess $this */
        return serialize(static::normalize($this, ['format' => 'serialize']));
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        static::denormalize($data, $this, ['format' => 'serialize']);
    }
}
