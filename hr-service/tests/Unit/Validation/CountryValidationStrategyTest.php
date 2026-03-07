<?php

namespace Tests\Unit\Validation;

use App\Validation\CountryValidationFactory;
use App\Validation\Strategies\GermanyValidationStrategy;
use App\Validation\Strategies\USAValidationStrategy;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CountryValidationStrategyTest extends TestCase
{
    public function test_usa_strategy_returns_correct_country_code(): void
    {
        $strategy = new USAValidationStrategy();
        $this->assertEquals('USA', $strategy->getCountryCode());
    }

    public function test_germany_strategy_returns_correct_country_code(): void
    {
        $strategy = new GermanyValidationStrategy();
        $this->assertEquals('Germany', $strategy->getCountryCode());
    }

    public function test_usa_strategy_has_ssn_and_address_rules(): void
    {
        $strategy = new USAValidationStrategy();
        $rules = $strategy->rules();

        $this->assertArrayHasKey('ssn', $rules);
        $this->assertArrayHasKey('address', $rules);
        $this->assertArrayNotHasKey('goal', $rules);
        $this->assertArrayNotHasKey('tax_id', $rules);
    }

    public function test_germany_strategy_has_goal_and_tax_id_rules(): void
    {
        $strategy = new GermanyValidationStrategy();
        $rules = $strategy->rules();

        $this->assertArrayHasKey('goal', $rules);
        $this->assertArrayHasKey('tax_id', $rules);
        $this->assertArrayNotHasKey('ssn', $rules);
        $this->assertArrayNotHasKey('address', $rules);
    }

    public function test_usa_strategy_custom_fields(): void
    {
        $strategy = new USAValidationStrategy();
        $fields = $strategy->customFields();

        $this->assertEquals(['ssn', 'address'], $fields);
    }

    public function test_germany_strategy_custom_fields(): void
    {
        $strategy = new GermanyValidationStrategy();
        $fields = $strategy->customFields();

        $this->assertEquals(['goal', 'tax_id'], $fields);
    }

    public function test_usa_strategy_extracts_custom_fields(): void
    {
        $strategy = new USAValidationStrategy();
        $data = [
            'id' => 1,
            'name' => 'John',
            'ssn' => '123-45-6789',
            'address' => '123 Main St',
            'goal' => 'should be ignored',
        ];

        $extracted = $strategy->extractCustomFields($data);

        $this->assertEquals([
            'ssn' => '123-45-6789',
            'address' => '123 Main St',
        ], $extracted);
    }

    public function test_germany_strategy_extracts_custom_fields(): void
    {
        $strategy = new GermanyValidationStrategy();
        $data = [
            'id' => 1,
            'name' => 'Hans',
            'goal' => 'Become manager',
            'tax_id' => 'DE123456789',
            'ssn' => 'should be ignored',
        ];

        $extracted = $strategy->extractCustomFields($data);

        $this->assertEquals([
            'goal' => 'Become manager',
            'tax_id' => 'DE123456789',
        ], $extracted);
    }

    public function test_usa_strategy_list_columns(): void
    {
        $strategy = new USAValidationStrategy();
        $columns = $strategy->listColumns();

        $this->assertContains('ssn', $columns);
        $this->assertContains('address', $columns);
        $this->assertNotContains('goal', $columns);
    }

    public function test_germany_strategy_list_columns(): void
    {
        $strategy = new GermanyValidationStrategy();
        $columns = $strategy->listColumns();

        $this->assertContains('goal', $columns);
        $this->assertContains('tax_id', $columns);
        $this->assertNotContains('ssn', $columns);
    }

    public function test_usa_checklist_rules_contain_required_fields(): void
    {
        $strategy = new USAValidationStrategy();
        $rules = $strategy->checklistRules();

        $this->assertArrayHasKey('ssn', $rules);
        $this->assertArrayHasKey('salary', $rules);
        $this->assertArrayHasKey('address', $rules);

        $this->assertEquals('ssn', $rules['ssn']['field']);
        $this->assertStringContainsString('required', $rules['ssn']['rule']);
    }

    public function test_germany_checklist_rules_contain_required_fields(): void
    {
        $strategy = new GermanyValidationStrategy();
        $rules = $strategy->checklistRules();

        $this->assertArrayHasKey('salary', $rules);
        $this->assertArrayHasKey('goal', $rules);
        $this->assertArrayHasKey('tax_id', $rules);

        $this->assertEquals('tax_id', $rules['tax_id']['field']);
        $this->assertStringContainsString('required', $rules['tax_id']['rule']);
    }

    public function test_factory_creates_usa_strategy(): void
    {
        $strategy = CountryValidationFactory::make('USA');
        $this->assertInstanceOf(USAValidationStrategy::class, $strategy);
    }

    public function test_factory_creates_germany_strategy(): void
    {
        $strategy = CountryValidationFactory::make('Germany');
        $this->assertInstanceOf(GermanyValidationStrategy::class, $strategy);
    }

    public function test_factory_is_case_insensitive(): void
    {
        $strategy1 = CountryValidationFactory::make('usa');
        $strategy2 = CountryValidationFactory::make('USA');
        $strategy3 = CountryValidationFactory::make('germany');
        $strategy4 = CountryValidationFactory::make('GERMANY');

        $this->assertInstanceOf(USAValidationStrategy::class, $strategy1);
        $this->assertInstanceOf(USAValidationStrategy::class, $strategy2);
        $this->assertInstanceOf(GermanyValidationStrategy::class, $strategy3);
        $this->assertInstanceOf(GermanyValidationStrategy::class, $strategy4);
    }

    public function test_factory_throws_for_unsupported_country(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported country: France');

        CountryValidationFactory::make('France');
    }

    public function test_factory_returns_supported_countries(): void
    {
        $countries = CountryValidationFactory::supportedCountries();

        $this->assertContains('USA', $countries);
        $this->assertContains('Germany', $countries);
        $this->assertCount(2, $countries);
    }
}
