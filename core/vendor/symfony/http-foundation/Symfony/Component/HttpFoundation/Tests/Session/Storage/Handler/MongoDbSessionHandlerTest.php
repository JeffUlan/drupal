<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Tests\Session\Storage\Handler;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\MongoDbSessionHandler;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class MongoDbSessionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $mongo;
    private $storage;
    public $options;

    protected function setUp()
    {
        if (!class_exists('\Mongo')) {
            $this->markTestSkipped('MongoDbSessionHandler requires the mongo extension.');
        }

        $this->mongo = $this->getMockBuilder('Mongo')
            ->disableOriginalConstructor()
            ->getMock();

        $this->options = array(
            'id_field'   => '_id',
            'data_field' => 'data',
            'time_field' => 'time',
            'database' => 'sf2-test',
            'collection' => 'session-test'
        );

        $this->storage = new MongoDbSessionHandler($this->mongo, $this->options);
    }

    public function testOpenMethodAlwaysReturnTrue()
    {
        $this->assertTrue($this->storage->open('test', 'test'), 'The "open" method should always return true');
    }

    public function testCloseMethodAlwaysReturnTrue()
    {
        $this->assertTrue($this->storage->close(), 'The "close" method should always return true');
    }

    public function testWrite()
    {
        $database = $this->getMockBuilder('MongoDB')
            ->disableOriginalConstructor()
            ->getMock();

        $collection = $this->getMockBuilder('MongoCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mongo->expects($this->once())
            ->method('selectDB')
            ->with($this->options['database'])
            ->will($this->returnValue($database));

        $database->expects($this->once())
            ->method('selectCollection')
            ->with($this->options['collection'])
            ->will($this->returnValue($collection));

        $that = $this;
        $data = array();

        $collection->expects($this->once())
            ->method('update')
            ->will($this->returnCallback(function($criteria, $updateData, $options) use ($that, &$data) {
                $that->assertEquals(array($that->options['id_field'] => 'foo'), $criteria);
                $that->assertEquals(array('upsert' => true, 'multiple' => false), $options);

                $data = $updateData['$set'];
            }));

        $this->assertTrue($this->storage->write('foo', 'bar'));

        $this->assertEquals('bar', $data[$this->options['data_field']]->bin);
        $that->assertInstanceOf('MongoDate', $data[$this->options['time_field']]);
    }

    public function testReplaceSessionData()
    {
        $database = $this->getMockBuilder('MongoDB')
            ->disableOriginalConstructor()
            ->getMock();

        $collection = $this->getMockBuilder('MongoCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mongo->expects($this->once())
            ->method('selectDB')
            ->with($this->options['database'])
            ->will($this->returnValue($database));

        $database->expects($this->once())
            ->method('selectCollection')
            ->with($this->options['collection'])
            ->will($this->returnValue($collection));

        $data = array();

        $collection->expects($this->exactly(2))
            ->method('update')
            ->will($this->returnCallback(function($criteria, $updateData, $options) use (&$data) {
                $data = $updateData;
            }));

        $this->storage->write('foo', 'bar');
        $this->storage->write('foo', 'foobar');

        $this->assertEquals('foobar', $data['$set'][$this->options['data_field']]->bin);
    }

    public function testDestroy()
    {
        $database = $this->getMockBuilder('MongoDB')
            ->disableOriginalConstructor()
            ->getMock();

        $collection = $this->getMockBuilder('MongoCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mongo->expects($this->once())
            ->method('selectDB')
            ->with($this->options['database'])
            ->will($this->returnValue($database));

        $database->expects($this->once())
            ->method('selectCollection')
            ->with($this->options['collection'])
            ->will($this->returnValue($collection));

        $collection->expects($this->once())
            ->method('remove')
            ->with(array($this->options['id_field'] => 'foo'));

        $this->assertTrue($this->storage->destroy('foo'));
    }

    public function testGc()
    {
        $database = $this->getMockBuilder('MongoDB')
            ->disableOriginalConstructor()
            ->getMock();

        $collection = $this->getMockBuilder('MongoCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mongo->expects($this->once())
            ->method('selectDB')
            ->with($this->options['database'])
            ->will($this->returnValue($database));

        $database->expects($this->once())
            ->method('selectCollection')
            ->with($this->options['collection'])
            ->will($this->returnValue($collection));

        $that = $this;

        $collection->expects($this->once())
            ->method('remove')
            ->will($this->returnCallback(function($criteria) use($that) {
                $that->assertInstanceOf('MongoDate', $criteria[$that->options['time_field']]['$lt']);
                $that->assertGreaterThanOrEqual(time() - -1, $criteria[$that->options['time_field']]['$lt']->sec);
            }));

        $this->assertTrue($this->storage->gc(-1));
    }
}
