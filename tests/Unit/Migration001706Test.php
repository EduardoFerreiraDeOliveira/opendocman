<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/installer/migrations/MigrationInterface.php';
require_once APPLICATION_PATH . '/installer/migrations/Version001706.php';

class Migration001706Test extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testVersionIs176(): void
    {
        $this->assertSame('1.7.6', (new Version001706())->getVersion());
    }

    public function testUpCreatesPropertyTables(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/CREATE TABLE IF NOT EXISTS `odm_property` /'))
            ->once()->andReturn(0);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/CREATE TABLE IF NOT EXISTS `odm_property_perms`/'))
            ->once()->andReturn(0);
        $pdo->shouldReceive('exec')->andReturn(0);
        (new Version001706())->up($pdo, 'odm_');
    }

    public function testUpAddsPropertyIdToData(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/ALTER TABLE `odm_data`.*ADD COLUMN property_id/s'))
            ->once()->andReturn(0);
        $pdo->shouldReceive('exec')->andReturn(0);
        (new Version001706())->up($pdo, 'odm_');
    }

    public function testDownRemovesEverything(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/DROP COLUMN property_id/'))->once()->andReturn(0);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/DROP TABLE IF EXISTS `odm_property_perms`/'))->once()->andReturn(0);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/DROP TABLE IF EXISTS `odm_property`$/'))->once()->andReturn(0);
        (new Version001706())->down($pdo, 'odm_');
    }
}
