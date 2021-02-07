<?php


namespace DataMapper\Tools;


use DataMapper\Tools\Exceptions\BusinessException,
    DataMapper\Tools\Contracts\ResolveInterface,
    ReflectionClass,
    SimpleXMLElement,
    DOMDocument,
    ZipArchive;

abstract class BaseDataMapper
{
    /**
     * Правила маппинга
     * @var array
     */
    protected $rules = [
        //'ruleCode' => [],
    ];

    /**
     * Если необходимо проверить на XSD, привязка к ключу правила, значение путь к xsd файлу
     * @var array
     */
    protected $xsd = [
        //'ruleCode' => '',
    ];

    /**
     * Корневой XML документ
     * @var string
     */
    protected $xml = '<?xml version="1.0" encoding="UTF-8"?><Items xmlns="http://www.datapump.cig.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.datapump.cig.com"></Items>';

    /**
     * Если необходимо задать корневому узлу параметр xmlns, для данных в  XML
     * @var
     */
    protected $targetNamespace;

    /**
     * @var
     */
    private $document;

    /**
     * @param string $className
     * @return string
     */
    private function getResolveClass(string $className)
    {
        if (class_exists("DataMapper\\Tools\\Resolve\\{$className}")) {
            return "DataMapper\\Tools\\Resolve\\{$className}";
        }

        return $className;
    }

    /**
     * Получаем массив данных по заданному ключу
     *
     * @param string $key
     * @param array $data
     * @return array
     * @throws BusinessException
     */
    public function getData(string $key, array $data): array
    {
        if (!isset($this->rules[$key])) {
            throw new BusinessException(
                'ERROR_RULES',
                'Нельзя сформировать данные для: ' . $key
            );
        }

        $return = [];
        // обходим все поля соответствий
        foreach ($this->rules[$key] as $val) {
            $classResolve = $this->getResolveClass($val['type']);

            if (!class_exists($classResolve)) {
                throw new BusinessException(
                    'NOT_FOUND_RESOLVE_CLASS',
                    'Нет класса формирования данных для типа: ' . $val['type']
                );
            }

            $reflectionClass = new ReflectionClass($classResolve);

            if (!$reflectionClass->implementsInterface(ResolveInterface::class)) {
                throw new BusinessException(
                    'ERROR_TYPE_CLASS',
                    'Класс должен реализовать интерфес ResolveInterface: ' . $val['type']
                );
            }

            try {
                // получаем результат по типу данных
                $result = $classResolve::run($val, $data);
            } catch (BusinessException $e) {
                throw new BusinessException(
                    $e->getErrorCode(),
                    'Блок данных: ' . ($val['blockLabel'] ?? $val['key']) . ' ' . $e->getMessage()
                );
            }

            // формируем массив конечных полей
            $fields = explode('.', $val['field']);

            // если нужно формировать несколько блоков
            if (!empty($val['params']['field'])) {
                foreach ($result as $oneItem) {
                    $returnOne = [];
                    $this->prepareReturnData(
                        explode('.', $val['params']['field']),
                        $oneItem,
                        false,
                        $returnOne
                    );

                    $this->prepareReturnData($fields, $returnOne, true, $return);
                }
            } else {
                $this->prepareReturnData($fields, $result, isset($val['isMulti']), $return);
            }
        }

        return $return;
    }

    /**
     * Строим нужную вложенность по полям
     *
     * @param array $fields
     * @param $result
     * @param $return
     * @return mixed
     */
    private function prepareReturnData(array $fields, $result, $isMulti, &$return, $first = true)
    {
        if (!empty($fields)) {
            $key = array_shift($fields);

            if ($first && isset($result['firstTagAttributes'])) {
                $return[$key]['attributes'] = $result['firstTagAttributes'];
                unset($result['firstTagAttributes']);
            }

            // если еще не достигли последнего уровня вложенности полей
            return $this->prepareReturnData($fields, $result, $isMulti, $return[$key], false);
        }

        // для тех полей где необходимо формировать несколько однотипных блоков
        if ($isMulti) {
            $return[] = $result;
        } else {
            $return = $result;
        }

        return $return;
    }

    /**
     * Получаем массив сформированных вложенностей и на основе него строим XML документ
     *
     * @param string $key
     * @param array $data
     */
    public function getXml(string $key, array $data): SimpleXMLElement
    {
        // получам массив данных
        $result = $this->getData($key, $data);

        // корневой узел
        $this->document = new SimpleXMLElement($this->xml);

        if (!empty($result)) {
            $xmlObj = $this->document;
            foreach ($result as $keyNode => $val) {
                // если простой тип, то добавляем свойство и его значение
                if (is_scalar($val)) {
                    $xmlObj->addChild($keyNode, $val, $this->targetNamespace);
                } else {
                    // углубляемся по вложенности данных
                    $this->makeXml($xmlObj, $keyNode, $val, $this->targetNamespace);
                }
            }
        }

        // если есть XSD схема, то проверяем XML на валидность
        $this->isValidXsd($key, $this->document->saveXML());

        return $this->document;
    }

    /**
     * Обходим всю вложенность массива данных и в зависимости от типа данных
     * либо добавляем новые узлы либо конечные значения
     * так же проверяем на присутствие требований выводить аттрибуты
     *
     * @param SimpleXMLElement $xmlObj
     * @param $key
     * @param array $params
     * @param string $targetNamespace
     */
    protected function makeXml(SimpleXMLElement $xmlObj, $key, array $params, string $targetNamespace = null)
    {
        if (!empty($params)) {
            if (is_int($key)) {
                $paramChildObj = $xmlObj;
            } elseif ($key == 'attributes') {
                foreach ($params as $propName => $propValue) {
                    if (is_scalar($propValue)) {
                        $xmlObj->addAttribute($propName, $propValue);
                    }
                }
                return;
            } else {
                $paramChildObj = $xmlObj->addChild($key, '', $targetNamespace);
            }

            foreach ($params as $k => $value) {
                $val = $value;
                if (isset($value['value'])) {
                    $val = $value['value'];
                }

                if (is_scalar($val)) {
                    $paramChildObj2 = $paramChildObj->addChild($k, $val, $targetNamespace);
                    if (isset($value['attributes'])) {
                        $this->makeXml($paramChildObj2, 'attributes', $value['attributes'], $targetNamespace);
                    }
                } else {
                    $this->makeXml($paramChildObj, $k, $val, $targetNamespace);
                }
            }
        }
    }

    /**
     * Получаем xml документ на основе данных соответствий и пакуем его в zip архив
     *
     * @param string $key
     * @param array $data
     * @return string
     * @throws BusinessException
     */
    public function getZippedXml(string $key, array $data): string
    {
        $xml = self::getXmlString($key, $data);

        return self::zipXml($key, $xml);
    }

    /**
     * @param string $key
     * @param array $data
     * @return mixed
     * @throws BusinessException
     */
    public function getXmlString(string $key, array $data)
    {
        $xmlObj = $this->getXml($key, $data);

        return $xmlObj->saveXML();
    }

    /**
     * @param string $key
     * @param string $xml
     * @return string
     * @throws BusinessException
     */
    public function zipXml(string $key, string $xml): string
    {
        // если есть XSD схема, то проверяем XML на валидность
        $this->isValidXsd($key, $xml);

        $name = tempnam(sys_get_temp_dir(), $key);
        $zip = new ZipArchive;
        $res = $zip->open($name, ZipArchive::OVERWRITE); /* усечение, поскольку пустой файл недопустим */
        if (true !== $res) {
            throw new BusinessException('ERROR_CREATE_ZIP', 'Ошибка генерации zip');
        }

        $zip->addFromString("{$key}.xml", $xml);
        $zip->close();

        return file_get_contents($name);
    }

    /**
     * @param string $key
     * @param string $xml
     * @throws BusinessException
     */
    protected function isValidXsd(string $key, string $xml)
    {
        // если есть XSD схема, то проверяем XML на валидность
        if (isset($this->xsd[$key])) {
            try {
                $doc = new DOMDocument();
                $doc->loadXML($xml);
                $isValidXml = $doc->schemaValidate($this->xsd[$key]);
                if (!$isValidXml) {
                    $errors = libxml_get_errors();
                    $errorsStr = '';
                    foreach ($errors as $error) {
                        $errorsStr .= $error->message . "\n";
                    }
                    throw new BusinessException('NOT_VALID_XSD', $errorsStr);
                }
            } catch (\Exception $e) {
                throw new BusinessException('NOT_VALID_XSD', $e->getMessage() . ' ' . $key);
            }
        }
    }
}
