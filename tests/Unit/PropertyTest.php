<?php

use PHPUnit\Framework\TestCase;

class PropertyTest extends TestCase
{
    /** @var array|null language strings present before the test, restored afterwards */
    private $originalLang;
    private bool $hadLang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hadLang = array_key_exists('lang', $GLOBALS);
        $this->originalLang = $GLOBALS['lang'] ?? null;
    }

    protected function tearDown(): void
    {
        // Other tests rely on $GLOBALS['lang'] being unset until they load English.
        if ($this->hadLang) {
            $GLOBALS['lang'] = $this->originalLang;
        } else {
            unset($GLOBALS['lang']);
        }
        parent::tearDown();
    }

    public function testValidInputIsNormalised(): void
    {
        [$clean, $errors] = Property::validate([
            'reference' => '  IMO-001 ',
            'address' => 'Rua das Flores 12',
            'city' => 'Lisboa',
            'type' => 'house',
            'status' => 'sold',
            'price' => '250 000,50',
            'owner_name' => '',
            'description' => '',
        ]);
        $this->assertSame([], $errors);
        $this->assertSame('IMO-001', $clean['reference']);
        $this->assertSame(250000.5, $clean['price']);
        $this->assertNull($clean['owner_name']);
        $this->assertNull($clean['description']);
        $this->assertSame('house', $clean['type']);
        $this->assertSame('sold', $clean['status']);
    }

    public function testReferenceAndAddressAreRequired(): void
    {
        [, $errors] = Property::validate(['reference' => ' ', 'address' => '']);
        $this->assertSame('property_error_reference_required', $errors['reference']);
        $this->assertSame('property_error_address_required', $errors['address']);
    }

    public function testBadPriceIsRejected(): void
    {
        foreach (['abc', '-5', '1e99'] as $bad) {
            [, $errors] = Property::validate(['reference' => 'A', 'address' => 'B', 'price' => $bad]);
            $this->assertArrayHasKey('price', $errors, "price '$bad' should fail");
        }
    }

    public function testEmptyPriceIsAllowedAndStoredAsNull(): void
    {
        [$clean, $errors] = Property::validate(['reference' => 'A', 'address' => 'B', 'price' => '']);
        $this->assertSame([], $errors);
        $this->assertNull($clean['price']);
    }

    public function testUnknownTypeAndStatusFallBackToSafeValues(): void
    {
        [$clean] = Property::validate(['reference' => 'A', 'address' => 'B', 'type' => 'castle', 'status' => 'haunted']);
        $this->assertSame('other', $clean['type']);
        $this->assertSame('for_sale', $clean['status']);
    }

    public function testLabelFallsBackToEnglishWithoutTranslation(): void
    {
        $GLOBALS['lang'] = [];
        $this->assertSame('For sale', Property::label('status', 'for_sale'));
        $GLOBALS['lang'] = ['property_status_for_sale' => 'À venda'];
        $this->assertSame('À venda', Property::label('status', 'for_sale'));
    }

    public function testOverlongTextIsTruncated(): void
    {
        [$clean] = Property::validate([
            'reference' => str_repeat('R', 80),
            'address' => str_repeat('é', 400),
        ]);
        $this->assertSame(50, mb_strlen($clean['reference']));
        $this->assertSame(255, mb_strlen($clean['address']));
    }
}
