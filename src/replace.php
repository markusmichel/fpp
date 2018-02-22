<?php

declare(strict_types=1);

namespace Fpp;

use Fpp\ClassKeyword\AbstractKeyword;
use Fpp\ClassKeyword\FinalKeyword;
use Fpp\ClassKeyword\NoKeyword;

const replace = '\Fpp\replace';

function replace(
    Definition $definition,
    ?Constructor $constructor,
    string $template,
    DefinitionCollection $collection,
    ClassKeyword $keyword
): string {
    if ($constructor) {
        $needConstructorAndProperties = true;
        $fqcn = $definition->name();

        if ($definition->namespace()) {
            $fqcn = $definition->namespace() . '\\' . $fqcn;
        }

        $constructorClass = str_replace($definition->namespace() . '\\', '', $constructor->name());

        if (false === strpos($constructorClass, '\\')) {
            $baseClass = $definition->name();
        } else {
            $baseClass = $fqcn;
        }

        if (isScalarConstructor($constructor)) {
            $className = $definition->name();
            $needConstructorAndProperties = false;
        } else {
            $pos = strrpos($constructor->name(), '\\');

            if (false !== $pos) {
                $className = substr($constructor->name(), $pos + 1);
            } else {
                $className = $constructor->name();
            }
        }
    } else {
        $className = $definition->name();
    }

    $template = str_replace('{{namespace_name}}', $definition->namespace(), $template);
    $template = str_replace('{{class_name}}', $className, $template);
    $template = str_replace('{{variable_name}}', lcfirst($definition->name()), $template);
    switch ($keyword->toString()) {
        case AbstractKeyword::VALUE:
            $template = str_replace('{{abstract_final}}', 'abstract ', $template);
            break;
        case FinalKeyword::VALUE:
            $template = str_replace('{{abstract_final}}', 'final ', $template);
            break;
        case NoKeyword::VALUE:
            $template = str_replace('{{abstract_final}}', '', $template);
            break;
    }

    foreach ($definition->derivings() as $deriving) {
        switch ((string) $deriving) {
            case Deriving\AggregateChanged::VALUE:
                    $needConstructorAndProperties = false;
                    $template = str_replace('{{class_extends}}', ' extends \Prooph\Common\Messaging\DomainEvent', $template);
                    $template = str_replace('{{arguments}}', buildArgumentList($constructor, $definition, true), $template);
                    $template = str_replace('{{properties}}', buildProperties($constructor), $template);
                    $template = str_replace('{{message_name}}', buildMessageName($definition), $template);
                    $template = str_replace('{{accessors}}', buildEventAccessors($definition, $collection), $template);
                    $template = str_replace('{{static_constructor_body}}', buildStaticConstructorBodyConvertingToPayload($constructor, $collection, false), $template);
                    $template = str_replace('{{payload_validation}}', buildPayloadValidation($constructor, $collection, false), $template);
                break;
            case Deriving\Command::VALUE:
                $needConstructorAndProperties = false;
                $template = str_replace('{{class_extends}}', ' extends \Prooph\Common\Messaging\Command', $template);
                $template = str_replace('{{arguments}}', buildArgumentList($constructor, $definition, true), $template);
                $template = str_replace("{{properties}}\n", '', $template);
                $template = str_replace('{{message_name}}', buildMessageName($definition), $template);
                $template = str_replace('{{accessors}}', buildPayloadAccessors($definition, $collection), $template);
                $template = str_replace('{{static_constructor_body}}', buildStaticConstructorBodyConvertingToPayload($constructor, $collection, true), $template);
                $template = str_replace('{{payload_validation}}', buildPayloadValidation($constructor, $collection, true), $template);
                break;
            case Deriving\DomainEvent::VALUE:
                $needConstructorAndProperties = false;
                $template = str_replace('{{class_extends}}', ' extends \Prooph\Common\Messaging\DomainEvent', $template);
                $template = str_replace('{{arguments}}', buildArgumentList($constructor, $definition, true), $template);
                $template = str_replace('{{properties}}', buildProperties($constructor), $template);
                $template = str_replace('{{message_name}}', buildMessageName($definition), $template);
                $template = str_replace('{{accessors}}', buildEventAccessors($definition, $collection), $template);
                $template = str_replace('{{static_constructor_body}}', buildStaticConstructorBodyConvertingToPayload($constructor, $collection, true), $template);
                $template = str_replace('{{payload_validation}}', buildPayloadValidation($constructor, $collection, true), $template);
                break;
            case Deriving\Enum::VALUE:
                $needConstructorAndProperties = false;
                if ($constructor) {
                    $template = str_replace('{{enum_value}}', buildReferencedClass($definition->namespace(), $constructor->name()), $template);
                } else {
                    $replace = '';
                    foreach ($definition->constructors() as $constructor) {
                        $class = buildReferencedClass($definition->namespace(), $constructor->name());
                        $replace .= "            $class::VALUE => $class::class,\n";
                    }
                    $template = str_replace('{{enum_options}}', substr($replace, 12, -1), $template);
                }
                break;
            case Deriving\Equals::VALUE:
                if ($constructor) {
                    $template = str_replace('{{equals_body}}', buildEqualsBody($constructor, lcfirst($definition->name()), $collection), $template);
                }
                break;
            case Deriving\FromArray::VALUE:
                $template = str_replace('{{from_array_body}}', buildFromArrayBody($constructor, $definition, $collection), $template);
                break;
            case Deriving\FromScalar::VALUE:
                if (isScalarConstructor($constructor)) {
                    $type = strtolower($constructor->name());
                } else {
                    $argument = $constructor->arguments()[0];
                    $type = strtolower($argument->type());
                }

                $template = str_replace('{{type}}', $type, $template);
                break;
            case Deriving\Query::VALUE:
                $needConstructorAndProperties = false;
                $template = str_replace('{{class_extends}}', ' extends \Prooph\Common\Messaging\Query', $template);
                $template = str_replace('{{arguments}}', buildArgumentList($constructor, $definition, true), $template);
                $template = str_replace('{{properties}}', buildProperties($constructor), $template);
                $template = str_replace('{{message_name}}', buildMessageName($definition), $template);
                $template = str_replace('{{accessors}}', buildPayloadAccessors($definition, $collection), $template);
                $template = str_replace('{{static_constructor_body}}', buildStaticConstructorBodyConvertingToPayload($constructor, $collection, true), $template);
                $template = str_replace('{{payload_validation}}', buildPayloadValidation($constructor, $collection, true), $template);
                break;
            case Deriving\ToArray::VALUE:
                $template = str_replace('{{to_array_body}}', buildToArrayBody($constructor, $definition, $collection), $template);
                break;
            case Deriving\ToScalar::VALUE:
                if (isScalarConstructor($constructor)) {
                    $type = strtolower($constructor->name());
                } else {
                    $argument = $constructor->arguments()[0];
                    $type = strtolower($argument->type());
                }

                $template = str_replace('{{type}}', $type, $template);
                $template = str_replace('{{to_scalar_body}}', buildToScalarBody($constructor, $definition, $collection), $template);
                break;
            case Deriving\ToString::VALUE:
                $template = str_replace('{{to_string_body}}', buildToScalarBody($constructor, $definition, $collection), $template);
                break;
            case Deriving\Uuid::VALUE:
                $needConstructorAndProperties = false;
                break;
        }
    }

    if (isset($fqcn) && $fqcn !== $constructor->name() && ! isScalarConstructor($constructor)) {
        $template = str_replace('{{class_extends}}', ' extends ' . $baseClass, $template);
    }

    $template = str_replace('{{class_extends}}', '', $template);

    if ($constructor && $needConstructorAndProperties) {
        $properties = buildProperties($constructor);
        if ('' !== $properties) {
            $template = str_replace("{{properties}}", buildProperties($constructor), $template);
        }
        $constructor = buildConstructor($constructor, $definition);
        if ('' !== $constructor) {
            $template = str_replace("{{constructor}}", $constructor, $template);
        }
    }

    $template = str_replace("        {{properties}}\n", '', $template);
    $template = str_replace("        {{constructor}}\n", '', $template);

    return $template . "\n";
}
