<?php

declare(strict_types=1);

namespace Fpp\Deriving;

use Fpp\Constructor;
use Fpp\Deriving as FppDeriving;

class Query implements FppDeriving
{
    const VALUE = 'Query';

    public function forbidsDerivings(): array
    {
        return [
            AggregateChanged::VALUE,
            Command::VALUE,
            DomainEvent::VALUE,
            Enum::VALUE,
            Equals::VALUE,
            FromArray::VALUE,
            FromScalar::VALUE,
            FromString::VALUE,
            ToArray::VALUE,
            ToScalar::VALUE,
            ToString::VALUE,
            Uuid::VALUE,
        ];
    }

    /**
     * @param Constructor[] $constructors
     * @return bool
     */
    public function fulfillsConstructorRequirements(array $constructors): bool
    {
        if (count($constructors) !== 1) {
            return false;
        }

        if (0 === count($constructors[0]->arguments())) {
            return false;
        }

        return true;
    }

    public function __toString(): string
    {
        return self::VALUE;
    }
}
