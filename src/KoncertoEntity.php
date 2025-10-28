<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

/**
 * Helper class for ORM
 */
class KoncertoEntity
{
    /**
     * Instantiate entity from array
     *
     * @param  array<string, bool|float|int|string|null> $data
     * @return KoncertoEntity
     */
    public function hydrate($data)
    {
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
     * Transform entity to array
     *
     * @return array<string, bool|float|int|string|null>
     */
    public function serialize()
    {
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
     * Perist entity (create or update)
     *
     * @return ?KoncertoEntity
     */
    public function persist()
    {
        // @todo - get entityName and entityManager from entity internal annotation
        $dsn = Koncerto::getConfig('entityManager.default');
        if (null === $dsn) {
            return null;
        }
        $pdo = new PDO($dsn);

        $data = $this->serialize();
        $fields = array_keys($data);
        $placeholders = array_map(
            function ($field) {
                return ':' . $field;
            },
            $fields
        );

        $className = get_class($this);
        $entityName = strtolower($className);

        $id = $this->getId();
        if (null === $id) {
            return null;
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
            $entity = $this->find($className, strval($data['id']));
            if (null === $entity) {
                return null;
            }
            $updates = array_map(
                function ($field, $placeholder) {
                    return sprintf('%s = %s', $field, $placeholder);
                },
                $fields,
                $placeholders
            );

            $query = $pdo->prepare(
                sprintf(
                    'UPDATE %s SET %s WHERE %s = :%s',
                    $entityName,
                    implode(',', $updates),
                    $id,
                    $id
                )
            );
        }

        $query->execute($data);

        if (!array_key_exists($id, $data) || empty($data[$id])) {
            if (false !== $pdo->lastInsertId()) {
                $data = array($id => $pdo->lastInsertId());
            } else {
                $data = array();
            }
        } else {
            $data = array();
        }

        return $this->hydrate($data);
    }

    /**
     * Remove entity
     *
     * @return boolean
     */
    public function remove()
    {
        // @todo - get entityName and entityManager from entity internal annotation
        $dsn = Koncerto::getConfig('entityManager.default');
        if (null === $dsn) {
            return false;
        }
        $pdo = new PDO($dsn);

        $data = $this->serialize();

        $entityName = strtolower(get_class($this));

        $id = $this->getId();
        if (null === $id) {
            return false;
        }

        $query = $pdo->prepare(
            sprintf(
                'DELETE FROM %s WHERE %s = :%s',
                $entityName,
                $id,
                $id
            )
        );

        return $query->execute(array($id => $data[$id]));
    }

    /**
     * Find entities by class and primary key or criterias
     *
     * @param class-string $class
     * @param array<string, string>|string|int $criteria
     * @return KoncertoEntity|KoncertoEntity[]|null
     */
    public static function find($class, $criteria = array())
    {
        // @todo - get entityName and entityManager from entity internal annotation
        $dsn = Koncerto::getConfig('entityManager.default');
        if (null === $dsn) {
            return array();
        }
        $pdo = new PDO($dsn);

        $classFile = sprintf('_entity/%s.php', $class);
        if (!is_file($classFile)) {
            return array();
        }

        include_once $classFile;
        if (!class_exists($class)) {
            return array();
        }

        $entityName = strtolower($class);
        $entityClass = new $class();

        $where = '1 = 1';
        $values = array();

        $findById = is_string($criteria) || is_numeric($criteria);

        if ($findById) {
            /** @var KoncertoEntity $entityClass */
            $id = $entityClass->getId();
            $values = array($id => $criteria);
            $where = sprintf(
                '%s = :%s',
                $id,
                $id
            );
        }

        if (is_array($criteria) && count($criteria) > 0) {
            $conditions = array();
            $values = array();
            foreach ($criteria as $field => $condition) {
                // @todo - allow more conditions (equal, not equal, is null, etc)
                array_push(
                    $conditions,
                    sprintf(
                        '%s %s',
                        $field,
                        ' = :' . $field
                    )
                );
                $values[$field] = $condition;
            }
            $where = implode(' AND ', $conditions);
        }

        $query = $pdo->prepare(
            sprintf(
                'SELECT * FROM %s WHERE %s',
                $entityName,
                $where
            )
        );

        $query->execute($values);

        $result = $query->fetchAll(PDO::FETCH_CLASS, $class);

        if ($findById && count($result) > 1) {
            throw new Exception(sprintf(
                'NonUniqueResult %s for entity %s',
                json_encode($criteria),
                $entityName
            ));
        }

        if ($findById) {
            $result = empty($result) ? null : $result[0];
        }

        return $result;
    }

    /**
     * Get ID column from internal comments
     *
     * @return ?string
     */
    private function getId()
    {
        $className = get_class($this);
        $entity = Koncerto::cache('entities', $className, array(), '_entity');
        if (is_array($entity) && array_key_exists('id', $entity) && is_string($entity['id'])) {
            return $entity['id'];
        }

        $ref = new ReflectionClass($this);
        $props = $ref->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $internal = Koncerto::getInternal($prop->getDocComment());
            if (array_key_exists('key', $internal)) {
                Koncerto::cache('entities', null, array($className => array('id' => $prop->getName())));

                return $prop->getName();
            }
        }

        return null;
    }

    /**
     * Get column type from @var comments
     *
     * @param  string $comment
     * @return string
     */
    private function getType($comment)
    {
        $type = 'string';

        $lines = explode("\n", $comment);
        foreach ($lines as $line) {
            $line = trim($line);
            // @phpstan-ignore argument.sscanf
            if (2 === sscanf($line, "%*[^@]@var %[^\n]s", $varType)) {
                $type = (string)$varType;

                break;
            }
        }

        return $type;
    }
}
