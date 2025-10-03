<?php

/**
 * This class allows to create enumerations to use with entities and forms
 * Reserved keywords : cases, from
 */
class KoncertoEnum {
    /** @var array<array-key, mixed> */
    private static $cases = array();

    /**
     * Return the int value of the enum case
     * @param string $name
     * @param array<mixed> $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments = array()) {
        if (count($arguments) > 0) {
            return null;
        }

        self::parseCases();

        $search = array_search($name, self::$cases);

        if (false === $search) {
            return null;
        }

        return $search;
    }

    /**
     * Return the case name from the value
     * @param mixed $value
     * @return ?string
     */
    public static function from($value) {
        self::parseCases();

        if (!array_key_exists($value, self::$cases)) {
            return null;
        }

        return self::$cases[$value];
    }

    /**
     * Return the list of cases as name => value
     * @return array<int, string>
     */
    public static function cases() {
        self::parseCases();

        return self::$cases;
    }

    /**
     * Extract cases from "method" annotations
     * @return void
     */
    private static function parseCases() {
        $class = new ReflectionClass(get_called_class());
        $comment = $class->getDocComment();
        if (false === $comment) {
            return;
        }

        self::$cases = array();
        $lines = explode("\n", $comment);
        foreach ($lines as $line) {
            if (5 === sscanf($line, "%*[^@]@method %s %s %s %[^\n]s", $type, $name, $type, $value)) {
                self::$cases[json_decode($value)] = $name;
            } else if (2 === sscanf($line, "%*[^@]@method int %[^\n]s", $name)) {
                array_push(self::$cases, $name);
            }
        }
    }
}
