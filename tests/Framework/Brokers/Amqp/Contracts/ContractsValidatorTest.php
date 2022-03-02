<?php

namespace Chassis\Tests\Framework\Brokers\Amqp\Contracts;

use Chassis\Framework\Brokers\Amqp\Contracts\ContractsValidator;
use Chassis\Framework\Brokers\Amqp\Contracts\Exceptions\ContractsValidatorException;
use Chassis\Tests\BaseTestCase;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ContractsValidatorTest extends BaseTestCase
{

    private Validator $validator;
    private ContractsValidator $subject;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(Validator::class);
        $this->subject = new ContractsValidator(
            $this->validator
        );
    }

    public function testGetAllSchemaNames()
    {
        $expected = [
            ContractsValidator::CHANNEL,
            ContractsValidator::OPERATION,
            ContractsValidator::MESSAGE,
        ];
        $this->assertSame($expected, ContractsValidator::getAllSchemaNames());
    }

    public function testLoadValidatorPathNotExist()
    {
        $this->validator->expects($this->never())->method('resolver');
        $this->subject->loadValidators('/not/exist');
    }

    public function testLoadValidatorSuccess()
    {
        $schemaResolver = $this->createMock(SchemaResolver::class);
        $this->validator->expects($this->exactly(3))->method('resolver')->willReturn($schemaResolver);
        $this->subject->loadValidators($this->setupDirWithSchemaFiles()->url());
    }

    public function testValidateSuccess()
    {
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->expects($this->once())->method('isValid')->willReturn(true);
        $this->validator->expects($this->once())->method('validate')->willReturn($validationResult);
        $this->assertTrue($this->subject->validate([], ContractsValidator::CHANNEL));
    }

    public function testValidateThrowException()
    {
        $this->expectException(ContractsValidatorException::class);
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->expects($this->once())->method('isValid')->willReturn(false);
        $validationError = $this->createMock(ValidationError::class);
        $validationResult->expects($this->once())->method('error')->willReturn($validationError);
        $this->validator->expects($this->once())->method('validate')->willReturn($validationResult);
        $this->subject->validate([], ContractsValidator::CHANNEL);
    }

    private function setupDirWithSchemaFiles(): vfsStreamDirectory
    {
        return vfsStream::setup('pathDir', null, [
            ContractsValidator::OPERATION => '{}',
            ContractsValidator::MESSAGE => '{}',
            ContractsValidator::CHANNEL => '{}',
        ]);
    }
}
