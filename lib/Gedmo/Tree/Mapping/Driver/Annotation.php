<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver,
    Doctrine\Common\Annotations\AnnotationReader,
    Gedmo\Exception\InvalidArgumentException;

/**
 * This is an annotation mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Tree
 * extension.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements Driver
{
    /**
     * Annotation to mark field as one which will store left value
     */
    const ANNOTATION_LEFT = 'Gedmo\Tree\Mapping\TreeLeft';
    
    /**
     * Annotation to mark field as one which will store right value
     */
    const ANNOTATION_RIGHT = 'Gedmo\Tree\Mapping\TreeRight';
    
    /**
     * Annotation to mark relative parent field
     */
    const ANNOTATION_PARENT = 'Gedmo\Tree\Mapping\TreeParent';
    
    /**
     * Annotation to mark node level
     */
    const ANNOTATION_LEVEL = 'Gedmo\Tree\Mapping\TreeLevel';
    
    /**
     * List of types which are valid for timestamp
     * 
     * @var array
     */
    private $_validTypes = array(
        'integer',
        'smallint',
        'bigint'
    );
    
    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata($meta, array $config)
    {
        if ($config) {
            $missingFields = array();
            if (!isset($config['parent'])) {
                $missingFields[] = 'ancestor';
            }
            if (!isset($config['left'])) {
                $missingFields[] = 'left';
            }
            if (!isset($config['right'])) {
                $missingFields[] = 'right';
            }
            if ($missingFields) {
                throw new InvalidArgumentException("Missing properties: " . implode(', ', $missingFields) . " in class - {$meta->name}");
            }
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config) {
        require_once __DIR__ . '/../Annotations.php';
        $reader = new AnnotationReader();
        $reader->setAnnotationNamespaceAlias('Gedmo\Tree\Mapping\\', 'gedmo');
        
        $class = $meta->getReflectionClass();        
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // left
            if ($left = $reader->getPropertyAnnotation($property, self::ANNOTATION_LEFT)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidArgumentException("Unable to find 'left' - [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->_isValidField($meta, $field)) {
                    throw new InvalidArgumentException("Tree left field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                }
                $config['left'] = $field;
            }
            // right
            if ($right = $reader->getPropertyAnnotation($property, self::ANNOTATION_RIGHT)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidArgumentException("Unable to find 'right' - [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->_isValidField($meta, $field)) {
                    throw new InvalidArgumentException("Tree right field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                }
                $config['right'] = $field;
            }
            // ancestor/parent
            if ($parent = $reader->getPropertyAnnotation($property, self::ANNOTATION_PARENT)) {
                $field = $property->getName();
                if (!$meta->isSingleValuedAssociation($field)) {
                    throw new InvalidArgumentException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                }
                $config['parent'] = $field;
            }
            // level
            if ($parent = $reader->getPropertyAnnotation($property, self::ANNOTATION_LEVEL)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidArgumentException("Unable to find 'level' - [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->_isValidField($meta, $field)) {
                    throw new InvalidArgumentException("Tree level field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                }
                $config['level'] = $field;
            }
        }
    }
    
    /**
     * Checks if $field type is valid
     * 
     * @param ClassMetadataInfo $meta
     * @param string $field
     * @return boolean
     */
    protected function _isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->_validTypes);
    }
}