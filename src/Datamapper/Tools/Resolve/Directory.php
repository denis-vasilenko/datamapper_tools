<?php


namespace DataMapper\Tools\Resolve;

use DataMapper\Tools\Contracts\ResolveInterface;

class Directory extends Base implements ResolveInterface
{
    /**
     * @param array $val
     * @param array $data
     * @return array|int|mixed|null
     */
    public static function run(array $val, array $data)
    {
        $dataVal = self::getValByKey($val['key'], $data);

        // если нужно предварительно привести к определенному типу данных
        if (isset($val['params']['type'])) {
            switch ($val['params']['type']) {
                case 'int':
                    $dataVal = (int)$dataVal;
                    break;
            }
        }

        // если нужно отсеять по доп полям
        if (
            isset($val['params']['propertyCode']) &&
            isset($val['params']['propertyId']) &&
            is_array($dataVal)
        ) {
            foreach ($dataVal as $propKey => $propVal) {
                if (
                    isset($propVal[$val['params']['propertyCode']]) &&
                    $propVal[$val['params']['propertyCode']] == $val['params']['propertyId']
                ) {
                    $dataVal = $propVal[$val['params']['propertyRowId']] ?? null;
                    break;
                }
            }

            if (!is_scalar($dataVal)) {
                $dataVal = null;
            }
        }

        // массив соответствий с условием
        if (isset($val['params']['conformityCondition'])) {
            $dataValCondition = null;
            foreach ($val['params']['conformityCondition'] as $conformityCondition) {
                if (isset($conformityCondition['from']) && isset($conformityCondition['to'])) {
                    if ($conformityCondition['from'] < $dataVal && $conformityCondition['to'] > $dataVal) {
                        $dataValCondition = $conformityCondition['value'] ?? null;
                    }
                } elseif (isset($conformityCondition['from']) && !isset($conformityCondition['to'])) {
                    if ($conformityCondition['from'] < $dataVal) {
                        $dataValCondition = $conformityCondition['value'] ?? null;
                    }
                } elseif (!isset($conformityCondition['from']) && isset($conformityCondition['to'])) {
                    if ($conformityCondition['to'] > $dataVal) {
                        $dataValCondition = $conformityCondition['value'] ?? null;
                    }
                } elseif (!isset($conformityCondition['from']) && !isset($conformityCondition['to'])) {
                    $dataValCondition = $conformityCondition['value'] ?? null;
                }
            }

            $dataVal = $dataValCondition;
        }

        // массив соответсвтий
        if (isset($val['params']['conformity'])) {
            $dataVal = $val['params']['conformity'][$dataVal] ?? null;

            if (is_null($dataVal) && isset($val['params']['default'])) {
                $dataVal = $val['params']['default'];
            }
        }

        if (isset($val['params']['attribute'])) {
            return ['attributes' => [$val['params']['attribute'] => $dataVal]];
        }

        return $dataVal;
    }
}
