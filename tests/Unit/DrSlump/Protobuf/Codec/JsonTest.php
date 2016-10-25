<?php

namespace Tests\Unit\DrSlumpProtobuf\Codec;

use DrSlump\Protobuf\Codec\Json;
use tests\AddressBook;
use tests\Person;
use tests\Repeated;
use tests\Simple;
use Tests\Annotated;

/**
 * Class JsonTest
 */
class JsonTest extends PHPUnit_Framework_TestCase
{

    public function testSerializeSimpleMessage()
    {
        $codec = new Json();
        $simple = new Simple();
        $simple->string = 'FOO';
        $simple->int32 = 1000;
        $codec = $codec->encode($simple);
        self::assertEquals('{"int32":1000,"string":"FOO"}', $codec);
    }

    public function testSerializeWithRepeatedFields()
    {
        $codec = new Json();
        $repeated = new Repeated();
        $repeated->addString('one');
        $repeated->addString('two');
        $repeated->addString('three');
        $json = $codec->encode($repeated);
        self::assertEquals('{"string":["one","two","three"]}', $json);


        $repeated = new Repeated();
        $repeated->addInt(1);
        $repeated->addInt(2);
        $repeated->addInt(3);
        $json = $codec->encode($repeated);
        self::assertEquals('{"int":[1,2,3]}', $json);

        $repeated = new Repeated();
        $nested = new Repeated\Nested();
        $nested->setId(1);
        $repeated->addNested($nested);
        $nested = new Repeated\Nested();
        $nested->setId(2);
        $repeated->addNested($nested);
        $nested = new Repeated\Nested();
        $nested->setId(3);
        $repeated->addNested($nested);
        $json = $codec->encode($repeated);
        self::assertEquals('{"nested":[{"id":1},{"id":2},{"id":3}]}', $json);
    }

    public function testSerializeComplexMessage()
    {

        $book = new AddressBook();
        $person = new Person();
        $person->name = 'John Doe';
        $person->id = 2051;
        $person->email = 'john.doe@gmail.com';
        $phone = new Person\PhoneNumber;
        $phone->number = '1231231212';
        $phone->type = Person\PhoneType::HOME;
        $person->addPhone($phone);
        $phone = new Person\PhoneNumber;
        $phone->number = '55512321312';
        $phone->type = Person\PhoneType::MOBILE;
        $person->addPhone($phone);
        $book->addPerson($person);

        $person = new Person();
        $person->name = 'Iván Montes';
        $person->id = 23;
        $person->email = 'drslump@pollinimini.net';
        $phone = new Person\PhoneNumber;
        $phone->number = '3493123123';
        $phone->type = Person\PhoneType::WORK;
        $person->addPhone($phone);
        $book->addPerson($person);

        $json = (new Json())->encode($book);

        $expected = '{
                "person":[
                    {
                        "name":"John Doe",
                        "id":2051,
                        "email":"john.doe@gmail.com",
                        "phone":[
                            {"number":"1231231212","type":1},
                            {"number":"55512321312","type":0}
                        ]
                    },
                    {
                        "name":"Iv\u00e1n Montes",
                        "id":23,
                        "email":"drslump@pollinimini.net",
                        "phone":[{"number":"3493123123","type":2}]
                    }
                ]
            }';

        $expected = preg_replace('/\n\s*/', '', $expected);
        self::assertEquals($expected, $json);
    }

    public function testSerializeAnnotatedSimpleMessage()
    {
        $simple = new Tests\Annotated\Simple();
        $simple->foo = 'FOO';
        $simple->bar = 1000;
        $json = (new Json())->encode($simple);
        self::assertEquals('{"foo":"FOO","bar":1000}', $json);
    }

    public function testSerializeAnnotatedMessageWithRepeatedFields()
    {
        $codec = new Json();
        $repeated = new Annotated\Repeated();
        $repeated->string = array('one', 'two', 'three');
        $json = $codec->encode($repeated);
        self::assertEquals('{"string":["one","two","three"]}', $json);


        $repeated = new Annotated\Repeated();
        $repeated->int = array(1, 2, 3);
        $json = $codec->encode($repeated);
        self::assertEquals('{"int":[1,2,3]}', $json);

        $repeated = new Annotated\Repeated();
        $repeated->nested = array();
        $nested = new Annotated\RepeatedNested();
        $nested->id = 1;
        $repeated->nested[] = $nested;
        $nested = new Annotated\RepeatedNested();
        $nested->id = 2;
        $repeated->nested[] = $nested;
        $nested = new Annotated\RepeatedNested();
        $nested->id = 3;
        $repeated->nested[] = $nested;
        $json = $codec->encode($repeated);
        self::assertEquals('{"nested":[{"id":1},{"id":2},{"id":3}]}', $json);
    }

    public function testUnserializeSimpleMessage()
    {

        $codec = new Json();
        $json = '{"string":"FOO","int32":1000}';
        /** @var Simple $simple */
        $simple = $codec->decode(new Simple(), $json);
        self::assertInstanceOf(Simple::class, $simple);
        self::assertEquals('FOO', $simple->string);
        self::assertEquals(1000, $simple->int32);
    }

    public function testUnserializeRepeatedFields()
    {

        $json = '{"string":["one","two","three"]}';
        $codec = new Json();
        /** @var Repeated $repeated */
        $repeated = $codec->decode(new Repeated(), $json);
        self::assertEquals(array('one', 'two', 'three'), $repeated->getString());

        $json = '{"int":[1,2,3]}';
        $repeated = $codec->decode(new Repeated(), $json);
        self::assertInstanceOf(Repeated::class, $repeated);
        self::assertEquals(array(1, 2, 3), $repeated->getInt());

        $json = '{"nested":[{"id":1},{"id":2},{"id":3}]}';
        $repeated = $codec->decode(new Repeated(), $json);
        self::assertInstanceOf(Repeated::class, $repeated);

        foreach ($repeated->getNestedList() as $i => $nested) {
            self::assertEquals($i + 1, $nested->getId());
        }
    }

    public function testUnserializeComplexMessage()
    {
        $json = '{
                "person":[
                    {
                        "name":"John Doe",
                        "id":2051,
                        "email":"john.doe@gmail.com",
                        "phone":[
                            {"number":"1231231212","type":1},
                            {"number":"55512321312","type":0}
                        ]
                    },
                    {
                        "name":"Iv\u00e1n Montes",
                        "id":23,
                        "email":"drslump@pollinimini.net",
                        "phone":[{"number":"3493123123","type":2}]
                    }
                ]
            }';

        $json = preg_replace('/\n\s*/', '', $json);
        $codec = new Json();
        /** @var AddressBook $complex */
        $complex = $codec->decode(new AddressBook(), $json);
        self::assertCount(2, $complex->person);
        self::assertEquals('John Doe', $complex->getPerson(0)->name);
        self::assertEquals('Iván Montes', $complex->getPerson(1)->name);
        self::assertEquals('55512321312', $complex->getPerson(0)->getPhone(1)->number);
    }
}