<?php

/*
 * Copyright (c) 2017 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 */

namespace MS\Normalizer;

use MS\DataType\Interfaces\CollectionInterface;
use MS\DataType\Interfaces\EntityInterface;

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
     * @param EntityInterface $object
     * @param array           $context
     *
     * @return array
     */
    public static function normalize(EntityInterface $object, array $context = [])
    {
        $method = !($object instanceof \ArrayAccess) ? 'normalizeObject' : 'normalizeCollection';

        return $object::$method($object, $context);
    }

    /**
     * @param EntityInterface $object
     * @param array           $context
     *
     * @return array
     */
    protected static function normalizeObject(EntityInterface $object, array $context = [])
    {
        $array = [];
        foreach ($object as $key => $value) {
            $array[$key] = static::normalizeProperty($value, $object, $key, $context);
        }

        return array_filter($array);
    }

    /**
     * @param mixed           $value
     * @param EntityInterface $object
     * @param string          $property
     * @param array           $context
     *
     * @return mixed
     */
    protected static function normalizeProperty($value, EntityInterface $object, $property, array $context = [])
    {
        if ($value instanceof EntityInterface) {
            $value = $value::normalize($value, $context);
        }

        return is_object($value) ? (array) $value : $value;
    }

    /**
     * @param CollectionInterface|EntityInterface[] $collection
     * @param array                                 $context
     *
     * @return array
     */
    protected static function normalizeCollection(CollectionInterface $collection, array $context = [])
    {
        $array = [];
        foreach ($collection as $key => $value) {
            $array[$key] = $value::normalize($value, $context);
        }

        return $array;
    }

    /**
     * @param array|object    $array
     * @param EntityInterface $object
     * @param array           $context
     *
     * @return self
     */
    public static function denormalize($array, EntityInterface $object = null, array $context = [])
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
     * @param array|object    $array
     * @param EntityInterface $object
     * @param array           $context
     *
     * @return object
     */
    protected static function denormalizeObject($array, EntityInterface $object, array $context = [])
    {
        foreach ($array as $property => $value) {
            $object->$property = static::denormalizeProperty($value, $object, $property, $context);
        }

        return $object;
    }

    /**
     * @param mixed           $value
     * @param EntityInterface $object
     * @param string          $property
     * @param array           $context
     *
     * @return EntityInterface|\ArrayAccess|mixed
     */
    protected static function denormalizeProperty($value, EntityInterface $object, $property, array $context = [])
    {
        if (property_exists($object, $property) and $object->$property instanceof EntityInterface) {
            /** @var EntityInterface $item */
            $item = $object->$property;
            $value = $item::denormalize($value, $item, $context);
        }

        return $value;
    }

    /**
     * @param array               $array
     * @param CollectionInterface $collection
     * @param array               $context
     *
     * @return CollectionInterface
     */
    protected static function denormalizeCollection($array, CollectionInterface $collection, array $context = [])
    {
        /** @var EntityInterface $class */
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
        /* @var EntityInterface $this */
        return static::normalize($this, ['format' => 'json']);
    }

    /**
     * @return string
     */
    public function serialize()
    {
        /* @var EntityInterface|\ArrayAccess $this */
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
