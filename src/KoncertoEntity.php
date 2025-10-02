<?php

/**
 * Helper class for ORM
 */
class KoncertoEntity
{
    /**
     * @param array<string, mixed> $data
     * @return KoncertoEntity
     */
    public function hydrate($data) {
        $id = $this->getId();

        $ref = new ReflectionClass($this);
        $props = $ref->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $propName = $prop->getName();
            $emptyKey = null !== $id && array_key_exists($id, $data) && empty($data[$propName]);
            if (!$emptyKey && array_key_exists($propName, $data)) {
                $comment = $prop->getDocComment();
                if (false === $comment) {
                    $comment = '';
                }
                $propType = $this->getType($comment);
                $value = $data[$propName];
                if ('?' === substr($propType, 0, 1)) {
                    $propType = substr($propType, 1);
                    if (empty($value)) {
                        $value = null;
                        $propType = 'null';
                    }
                }
                switch ($propType) {
                    case 'bool':
                    case 'boolean':
                        $value = filter_var($value, FILTER_VALIDATE_BOOL);
                        break;
                    case 'float':
                        $value = floatval($value);
                        break;
                    case 'int':
                    case 'integer':
                        $value = intval($value);
                        break;
                    case 'string':
                        $value = strval($value);
                        break;
                }
                $this->$propName = $value;
            }
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize() {
        $obj = array();

        $ref = new ReflectionClass($this);
        $props = $ref->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $propName = $prop->getName();
            $obj[$propName] = $this->$propName;
        }

        return $obj;
    }

    /**
     * @return KoncertoEntity
     */
    public function persist() {
        // @todo - get entityName and entityManager from entity internal annotation
        $pdo = new PDO(Koncerto::getConfig('entityManager.default'));

        $data = $this->serialize();
        $fields = array_keys($data);
        $placeholders = array_map(function ($field) { return ':' . $field; }, $fields);

        $entityName = strtolower(get_class($this));

        $id = $this->getId();
        if (null === $id) {
            return;
        }

        if (empty($data[$id])) {
            $data[$id] = null;

            $query = $pdo->prepare(
                sprintf(
                    'INSERT INTO %s (%s) VALUES (%s)',
                    $entityName,
                    implode(',', $fields),
                    implode(',', $placeholders)
                )
            );
        } else {
            $updates = array_map(function ($field, $placeholder) {
                return sprintf('%s = %s', $field, $placeholder);
            }, $fields, $placeholders);

            $query = $pdo->prepare(
                sprintf(
                    'UPDATE %s SET %s WHERE %s = %s',
                    $entityName,
                    implode(',', $updates),
                    $id,
                    $data[$id]
                )
            );
        }

        $query->execute($data);

        $data = array();
        if (!array_key_exists($id, $data) || empty($data[$id])) {
            $data = array($id => $pdo->lastInsertId());
        }

        return $this->hydrate($data);
    }

    /**
     * Get ID column from @internal comments
     * @return ?string
     */
    private function getId() {
        $ref = new ReflectionClass($this);
        $props = $ref->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $comment = $prop->getDocComment();
            if (false === $comment) {
                $comment = '';
            }
            $lines = explode("\n", $comment);
            foreach ($lines as $line) {
                $line = trim($line);
                if (2 === sscanf($line, "%*[^@]@internal %[^\n]s", $json)) {
                    $internal = (array)json_decode((string)$json, true);
                    if (!array_key_exists('key', $internal)) {
                        return null;
                    }

                    return $prop->getName();
                }
            }
        }
    }

    /**
     * Get column type from @var comments
     * @param string $comment
     * @return string
     */
    private function getType($comment) {
        $type = 'string';

        $lines = explode("\n", $comment);
        foreach ($lines as $line) {
            $line = trim($line);
            if (2 === sscanf($line, "%*[^@]@var %[^\n]s", $varType)) {
                $type = (string)$varType;

                break;
            }
        }

        return $type;
    }
}
