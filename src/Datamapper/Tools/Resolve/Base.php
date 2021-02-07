<?php


namespace DataMapper\Tools\Resolve;


abstract class Base
{
    /**
     * @param string $key
     * @param array $data
     * @return array|mixed|null
     */
    protected static function getValByKey(string $key, array $data = [])
    {
        $keys = explode('.', $key);

        $dataVal = $data;
        foreach ($keys as $key) {
            $dataVal = $dataVal[$key] ?? null;
        }

        return $dataVal;
    }

    /**
     * @param array $val
     * @param array $data
     * @param $dataVal
     */
    protected static function setAttributesByField(array $val, array $data, &$dataVal)
    {
        if (isset($val['params']['attributesByField'])) {
            foreach ($val['params']['attributesByField'] as $attributeKey => $field) {
                $fieldKey = $field['fieldKey'] ?? $field;

                $attrVal = self::getValByKey($fieldKey, $data);
                // для сопоставления справочника
                if (isset($field['conformity'])) {
                    $attrVal = $field['conformity'][$attrVal] ?? null;
                }
                // значение по умолчанию, иначе параметр не будет передан!
                if (is_null($attrVal) && isset($field['default'])) {
                    $attrVal = $field['default'];
                }

                $dataVal['attributes'][$attributeKey] = $attrVal;
            }
        }
    }
}
