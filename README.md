#DataMapper

DataMapper помогает вам преобразовать один формат данные в другой, вы можете получить как массив данных, так и XML.


[![Latest Stable Version](https://poser.pugx.org/dvasilenko/datamapper_tools/v)](//packagist.org/packages/dvasilenko/datamapper_tools) [![Total Downloads](https://poser.pugx.org/dvasilenko/datamapper_tools/downloads)](//packagist.org/packages/dvasilenko/datamapper_tools) [![Latest Unstable Version](https://poser.pugx.org/dvasilenko/datamapper_tools/v/unstable)](//packagist.org/packages/dvasilenko/datamapper_tools) [![License](https://poser.pugx.org/dvasilenko/datamapper_tools/license)](//packagist.org/packages/dvasilenko/datamapper_tools)

## Содержание
1. [Установка](#install)
1. [Использование](#use)
    1. [Создать свой DataMapper](#create-selft-datamapper)
    1. [Настройка своего DataMapper](#config-selft-datamapper)
    1. [Создание своего обработчика типа данных](#create-selft-resolve-type)
    1. [Использование DataMapper](#use-selft-datamapper)
1. [Обработчики данных по умолчанию](#default-resolve-type)
    1. [Дата и время](#datatime-resolve-type)
    1. [Константа](#define-resolve-type)
    1. [Справочник](#directory-resolve-type)
    1. [Простой](#simple-resolve-type)
    
<a name="install"></a>
## Установка
Установка с помощью Composer

```json
composer require dvasilenko/datamapper_tools
```

<a name="use"></a> 
## Использование
Необходимо создать класс описывающий правила преобразования данных, отнаследовавшись от базавого класса BaseDataMapper.

Базово поддерживается 4 типа данных:
- Дата и время
- Константа
- Справочник
- Простой тип

При необходимости можно расширить своими типами данных.

<a name="create-selft-datamapper"></a>
## Создать свой DataMapper

```php
use DataMapper\Tools\BaseDataMapper;

class MyDataMapper extends BaseDataMapper
{
    protected $rules = [
        'ruleCode' => [],
    ];
}
```

<a name="config-selft-datamapper"></a>
## Настройка своего DataMapper

Обращение к элементам вложенного массива осуществляется через точку "."

Например, правила ниже позволяют обратиться к значениям элемента "client" и "order"
```php  

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
 * Правила маппинга (пример использования разных обработчиков данных)
 * @var array
 */
protected $rules = [
    'clientOrder' => [
        ['type' => 'Define', 'value' => '1', 'field' => 'Client.Type', 'params' => ['attribute' => 'id']],
        ['type' => 'Simple', 'key' => 'client.id', 'field' => 'Client.Id'],
        ['type' => 'Simple', 'key' => 'client.name', 'field' => 'Client.Name'],
        ['type' => 'MyNameSpace\\DataMapper\\Resolve\\Fio', 'field' => 'Client.FIO', 'params' => ['name' => 'client.name', 'lastName' => 'client.lastName']],            
        ['type' => 'DateTime', 'key' => 'client.dateBirth', 'field' => 'Client.DateBirth', 'params' => ['format' => 'd.m.Y']],
        [
            'type' => 'Directory',
            'key' => 'client.gender',
            'field' => 'Client.Gender',
            'params' => [
                'conformity' => [
                    'man' => 'M',
                    'woman' => 'F',
                ],
            ],
        ],
        ['type' => 'Simple', 'key' => 'order.id', 'field' => 'Client.Order.Id'],
    ],
];
```

<a name="create-selft-resolve-type"></a>
## Создание своего обработчика типа данных

Класс обработчика должен реализовывать интерфейс ResolveInterface

```php
use DataMapper\Tools\Contracts\ResolveInterface,
    DataMapper\Tools\Resolve\Base;

class Fio extends Base implements ResolveInterface
{
    public static function run(array $val, array $data)
    {
        $name = self::getValByKey($val['params']['name'], $data);
        $lastName = self::getValByKey($val['params']['lastName'], $data);

        return implode(' ', [$lastName, $name]);
    }
}
```

<a name="use-selft-datamapper"></a>
## Использование DataMapper

```php
use MyDataMapper;

$data = [
    'client' => [
        'id' => 1,
        'name' => 'Имя',
        'lastName' => 'Фамилия',
        'dateBirth' => '2000-01-01',
        'gender' => 'man',
    ],
    'order' => [
        'id' => 1,
    ],
];

$uploadData = [
    'client' => $clientData,
    'order' => $orderData,           
];

$dataMapper = new MyDataMapper;
// Если нужен массив данных
$data = $dataMapper->getData('clientOrder', $uploadData);
```

Результат:

    Array
    (
        [Client] => Array
            (
                [Type] => Array
                    (
                        [attributes] => Array
                            (
                                [id] => 1
                            )
                    )
                [Id] => 1
                [Name] => Имя
                [FIO] => Фамилия Имя          
                [DateBirth] => 01.01.2000
                [Gender] => M
                [Order] => Array
                    (
                        [Id] => 1
                    )
            )
    )


Если нужен XML

```php
$data = $dataMapper->getXmlString('clientOrders', $uploadData);
```

Результат:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Items xmlns="http://www.datapump.cig.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="http://www.datapump.cig.com">
    <Client>
        <Type id="1"/>
        <Id>1</Id>
        <Name>Имя</Name>
        <FIO>Фамилия Имя</FIO>
        <DateBirth>01.01.2000</DateBirth>
        <Gender>M</Gender>
        <Order>
            <Id>1</Id>
        </Order>
    </Client>
</Items>
```

<a name="default-resolve-type"></a>
## Обработчики данных по умолчанию
Базово поддерживается 4 типа данных:

<a name="datatime-resolve-type"></a>
- ### Дата и время
Необходимо указать формат времени (поддерживаются форматы нативного класса DateTime).
Если значение передается в числовом виде, то считается, что это TimeStamp, 
так же можно отметить, что значение времени передается в милисекундах:

    'type' => 'DateTime', 'key' => '', 'field' => '', 'params' => ['format' => 'd.m.Y', 'isMillisecond' => true]
         
<a name="define-resolve-type"></a>
- ### Константа
Можно указать, чтоб значение передавалось как часть параметра в ввиде аттрибута:

    'type' => 'Define', 'key' => '', 'field' => '', params => ['attribute' => 'id']

<a name="directory-resolve-type"></a>
- ## Справочник
Ниже перечислены разные варианты, допускается возможность их комбинирования.

Если нужно указать массив соответствий с условием числовых диапазонов:

    'type' => 'Directory', 'key' => '', 'field' => '',  params => [
        'conformityCondition' => [
            ['from' => 1, 'to' => 30, 'value' => 1],
            ['from' => 31, 'to' => 60, 'value' => 2],
            ['from' => 61, 'to' => 90, 'value' => 3],
            ['from' => 91, 'to' => 180, 'value' => 4],
            ['from' => 181, 'to' => 365, 'value' => 5],
            ['from' => 366, 'to' => 1095, 'value' => 6],
            ['from' => 1096, 'value' => 7],
            ['value' => 8],
        ]
    ]

Простой массив соответсвтий:

    'type' => 'Directory', 'key' => '', 'field' => '', 'params' => [
        'conformity' => [
            'man' => 'M',
            'woman' => 'F',
        ],
        'default' => 'M',
    ],

Если нужно предварительно привести к числовому типу данных (int):

    type='Directory', 'key' => '', 'field' => '', 'params' => ['type' => 'int']

Если нужно отсеять по доп. полям:

    type='Directory', 'key' => '', 'field' => '', 'params' => [
        'propertyCode' => '',// код поля, содержащим массив доп. полей
        'propertyId' => '',// наименование нужного поля
        'propertyRowId' => '',// атрибут хранящий значение       
    ],

Можно указать, чтоб значение передавалось как часть параметра в ввиде аттрибута:

    'type' => 'Directory', 'key' => '', 'field' => '', 'params' => ['attribute' => 'id']

<a name="simple-resolve-type"></a>    
- ## Простой тип

Можно указать ключ "funcs", в котором перечислить функции, которыми необходимо обработать полученное значение:
    
    'type' => 'Simple', 'key' => '', 'field' => '', 'funcs' => 'trim|mb_strtoupper'
    
Можно указать, чтоб значение передавалось как часть параметра в ввиде аттрибута:

    type='Simple', key='' field='' params=['attribute' => 'id']
