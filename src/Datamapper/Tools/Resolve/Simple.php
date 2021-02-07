<?php


namespace DataMapper\Tools\Resolve;

use DataMapper\Tools\Contracts\ResolveInterface;

class Simple extends Base implements ResolveInterface
{
    /**
     * @param array $val
     * @param array $data
     * @return array
     */
    public static function run(array $val, array $data)
    {
        $dataVal = self::getValByKey($val['key'], $data);

        if (!is_null($dataVal)) {
            if (isset($val['funcs'])) {
                $funcs = explode('|', $val['funcs']);
                foreach ($funcs as $func) {
                    if (!function_exists($func)) {
                        continue;
                    }

                    if ($func === 'mb_strtoupper') {
                        $dataVal = $func($dataVal, 'UTF-8');
                    } else {
                        $dataVal = $func($dataVal);
                    }
                }
            }
        }

        if (isset($val['params']['attribute'])) {
            return ['attributes' => [$val['params']['attribute'] => $dataVal]];
        }

        if (isset($val['params']['attributesByField'])) {
            $dataVal = ['value' => $dataVal];

            self::setAttributesByField($val, $data, $dataVal);
        }

        return $dataVal;
    }
}
