<?php


namespace DataMapper\Tools\Resolve;


use Carbon\Carbon,
    DataMapper\Tools\Contracts\ResolveInterface,
    DataMapper\Tools\Exceptions\BusinessException;

class DateTime extends Base implements ResolveInterface
{
    /**
     * @param array $val
     * @param array $data
     * @return array|int|mixed|string|null
     */
    public static function run(array $val, array $data)
    {
        if (empty($val['params']['format'])) {
            throw new BusinessException('INCORRECT_PARAMS', 'Не задан формат даты params["format" => "..."]');
        }

        $dataVal = self::getValByKey($val['key'], $data);

        if ($dataVal) {
            if (is_int($dataVal)) {
                $dataVal = !(empty($val['params']['isMillisecond'])) ? bcdiv($dataVal, 1000) : $dataVal;
                $dateTime = Carbon::createFromTimestamp($dataVal);
            } else {
                $dateTime = Carbon::parse($dataVal);
            }

            $dataVal = $dateTime->format($val['params']['format']);
        }

        return $dataVal;
    }
}
