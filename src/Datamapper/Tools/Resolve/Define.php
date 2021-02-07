<?php


namespace DataMapper\Tools\Resolve;

use DataMapper\Tools\Contracts\ResolveInterface;

class Define extends Base implements ResolveInterface
{
    /**
     * @param array $val
     * @param array $data
     * @return array|mixed
     */
    public static function run(array $val, array $data)
    {
        if (isset($val['params']['attribute'])) {
            return ['attributes' => [$val['params']['attribute'] => $val['value']]];
        }

        if (isset($val['params']['attributesByField'])) {
            $dataVal = [];

            self::setAttributesByField($val, $data, $dataVal);

            return $dataVal;
        }

        return $val['value'];
    }
}
