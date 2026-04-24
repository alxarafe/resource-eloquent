<?php

declare(strict_types=1);

namespace Alxarafe\ResourceEloquent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Alxarafe\ResourceEloquent\EloquentTransaction;
use Illuminate\Database\Capsule\Manager as DB;

class EloquentTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('SQLite PDO driver is not available.');
        }
        
        $db = new DB();
        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $db->setAsGlobal();
        $db->bootEloquent();
        
        DB::schema()->create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });
    }

    public function testBeginAndCommit(): void
    {
        $transaction = new EloquentTransaction();
        
        $transaction->begin();
        DB::table('test_table')->insert(['name' => 'Test']);
        $transaction->commit();
        
        $this->assertEquals(1, DB::table('test_table')->count());
    }

    public function testBeginAndRollback(): void
    {
        $transaction = new EloquentTransaction();
        
        $transaction->begin();
        DB::table('test_table')->insert(['name' => 'Test']);
        $transaction->rollback();
        
        $this->assertEquals(0, DB::table('test_table')->count());
    }
    
    public function testWrapSuccess(): void
    {
        $transaction = new EloquentTransaction();
        
        $result = $transaction->wrap(function () {
            DB::table('test_table')->insert(['name' => 'Test wrap']);
            return 'success';
        });
        
        $this->assertEquals('success', $result);
        $this->assertEquals(1, DB::table('test_table')->count());
    }
    
    public function testWrapException(): void
    {
        $transaction = new EloquentTransaction();
        
        $exceptionThrown = false;
        try {
            $transaction->wrap(function () {
                DB::table('test_table')->insert(['name' => 'Test wrap']);
                throw new \RuntimeException('Failed');
            });
        } catch (\RuntimeException $e) {
            $exceptionThrown = true;
        }
        
        $this->assertTrue($exceptionThrown);
        $this->assertEquals(0, DB::table('test_table')->count());
    }
}
