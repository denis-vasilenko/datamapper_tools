<?php


namespace DataMapper\Tools\Contracts;


interface ResolveInterface
{
    /**
     * @param array $val
     * @param array $data
     * @return mixed
     */
    public static function run(array $val, array $data);
}
