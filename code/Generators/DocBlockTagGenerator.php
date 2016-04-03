<?php

use phpDocumentor\Reflection\DocBlock\Tag;

/**
 * Class DocBlockTagGenerator
 */
class DocBlockTagGenerator
{
    /**
     * @var array
     * Available properties to generate docblocks for.
     */
    protected static $propertyTypes = array(
        'Owner',
        'DB',
        'HasOne',
        'BelongsTo',
        'HasMany',
        'ManyMany',
        'BelongsManyMany',
        'Extensions',
    );

    /**
     * All classes that subclass Object
     * @var array
     */
    protected $extensionClasses;

    /**
     * The current class we are working with
     * @var string
     */
    protected $className = '';

    /**
     * List all the generated tags form the various generateSomeORMProperies methods
     * @see $this->getSupportedTagTypes();
     * @var array
     */
    protected $tags = [];

    public function __construct($className)
    {
        $this->className        = $className;
        $this->extensionClasses = ClassInfo::subclassesFor('Object');
        $this->tags             = $this->getSupportedTagTypes();

        $this->generateORMProperties();
    }

    /**
     * todo this is a tad ugly...
     * @param phpDocumentor\Reflection\DocBlock\Tag[] $existingTags
     *
     * @return phpDocumentor\Reflection\DocBlock\Tag[]
     */
    public function getTagsMergedWithExisting($existingTags)
    {
        /**
         * set array keys so we can match existing with generated tags
         */
        $existing = $this->getSupportedTagTypes();
        foreach($existingTags as $tag) {
            $content = $tag->getContent();
            if($tag->getName() == 'property') {
                $existing['properties'][$content] = new Tag($tag->getName(), $content);
            }elseif($tag->getName() == 'method') {
                $existing['methods'][$content] = new Tag($tag->getName(), $content);
            }elseif($tag->getName() == 'mixin') {
                $existing['mixins'][$content] = new Tag($tag->getName(), $content);
            }else{
                $existing['other'][$content] = new Tag($tag->getName(), $content);
            }
        }

        /**
         * Remove the generated tags that already exist
         */
        $tags = $this->tags;
        foreach ($tags as $tagType => $tagList) {
            foreach($tagList as $type => $tag) {
                $content = $tag->getContent();
                if(isset($existing[$tagType][$content])) {
                    unset($tags[$tagType][$content]);
                }
            }
        }

        return $tags;
    }

    public function getPropertyTags()
    {
        return $this->tags['properties'];
    }

    public function getMethodTags()
    {
        return $this->tags['methods'];
    }

    public function getMixinTags()
    {
        return $this->tags['mixins'];
    }

    public function getOtherTags()
    {
        return $this->tags['other'];
    }

    /**
     * @return phpDocumentor\Reflection\DocBlock\Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Returns the generated Tag objects as a string
     * with asterix and newline \n
     * @return string
     */
    public function getTagsAsString()
    {
        $tagString = '';

        foreach($this->tags as $tagType) {
            foreach($tagType as $tag) {
                $tagString .= ' * ' . $tag . "\n";
            }
        }

        return $tagString;
    }

    /**
     * Reset the tag list after each run
     */
    protected function getSupportedTagTypes()
    {
        return array(
            'properties'=> array(),
            'methods'   => array(),
            'mixins'    => array(),
            'other'     => array()
        );
    }

    /**
     * Generates all ORM Properties
     */
    protected function generateORMProperties()
    {
        /*
         * Loop the available types and generate the ORM property.
         */
        foreach (self::$propertyTypes as $type) {
            $function = 'generateORM' . $type . 'Properties';
            $this->{$function}($this->className);
        }
    }

    /**
     * Generate the Owner-properties for extensions.
     *
     * @param string $className
     */
    protected function generateORMOwnerProperties($className)
    {
        $owners = array();
        foreach ($this->extensionClasses as $class) {
            $config = Config::inst()->get($class, 'extensions', Config::UNINHERITED);
            if ($config !== null && in_array($className, $config, null)) {
                $owners[] = $class;
            }
        }
        if (count($owners)) {
            $owners[] = $className;
            $tag = implode("|", $owners) . " \$owner";
            $this->tags['properties'][$tag] = new Tag('property', $tag);
        }
    }

    /**
     * Generate the $db property values.
     *
     * @param DataObject|DataExtension $className
     *
     * @return string
     */
    protected function generateORMDBProperties($className)
    {
        if ($fields = Config::inst()->get($className, 'db', Config::UNINHERITED)) {
            foreach ($fields as $fieldName => $dataObjectName) {
                $prop = 'string';

                $fieldObj = Object::create_from_string($dataObjectName, $fieldName);

                if ($fieldObj instanceof Int || $fieldObj instanceof DBInt) {
                    $prop = 'int';
                } elseif ($fieldObj instanceof Boolean) {
                    $prop = 'boolean';
                } elseif ($fieldObj instanceof Float || $fieldObj instanceof DBFloat || $fieldObj instanceof Decimal) {
                    $prop = 'float';
                }
                $tag = "$prop \$$fieldName";
                $this->tags['properties'][$tag] = new Tag('property', $tag);
            }
        }

        return true;
    }

    /**
     * Generate the $belongs_to property values.
     *
     * @param DataObject|DataExtension $className
     *
     * @return string
     */
    protected function generateORMBelongsToProperties($className)
    {
        if ($fields = Config::inst()->get($className, 'belongs_to', Config::UNINHERITED)) {
            //$this->resultString .= " * \n";
            foreach ($fields as $fieldName => $dataObjectName) {
                $tag = $dataObjectName . " \$$fieldName";
                $this->tags['methods'][$tag] = new Tag('method', $tag);
            }
        }

        return true;
    }

    /**
     * Generate the $has_one property and method values.
     *
     * @param DataObject|DataExtension $className
     *
     * @return string
     */
    protected function generateORMHasOneProperties($className)
    {
        if ($fields = Config::inst()->get($className, 'has_one', Config::UNINHERITED)) {
            foreach ($fields as $fieldName => $dataObjectName) {
                $tag = "int \${$fieldName}ID";
                $this->tags['properties'][$tag] = new Tag('property', $tag);
            }
            foreach ($fields as $fieldName => $dataObjectName) {
                $tag = "{$dataObjectName} {$fieldName}()";
                $this->tags['methods'][$tag] = new Tag('method', $tag);
            }
        }

        return true;
    }

    /**
     * Generate the $has_many method values.
     *
     * @param DataObject|DataExtension $className
     *
     * @return string
     */
    protected function generateORMHasManyProperties($className)
    {
        if ($fields = Config::inst()->get($className, 'has_many', Config::UNINHERITED)) {
            //$this->resultString .= " * \n";
            foreach ($fields as $fieldName => $dataObjectName) {
                $tag = "DataList|{$dataObjectName}[] {$fieldName}()";
                $this->tags['methods'][$tag] = new Tag('method', $tag);
            }
        }

        return true;
    }

    /**
     * Generate the $many_many method values.
     *
     * @param DataObject|DataExtension $className
     *
     * @return string
     */
    protected function generateORMManyManyProperties($className)
    {
        if ($fields = Config::inst()->get($className, 'many_many', Config::UNINHERITED)) {
            foreach ($fields as $fieldName => $dataObjectName) {
                $tag = "ManyManyList|{$dataObjectName}[] {$fieldName}()";
                $this->tags['methods'][$tag] = new Tag('method', $tag);
            }
        }

        return true;
    }

    /**
     * Generate the $belongs_many_many method values.
     *
     * @param DataObject|DataExtension $className
     *
     * @return string
     */
    protected function generateORMBelongsManyManyProperties($className)
    {
        if ($fields = Config::inst()->get($className, 'belongs_many_many', Config::UNINHERITED)) {
            foreach ($fields as $fieldName => $dataObjectName) {
                $tag = "ManyManyList|{$dataObjectName}[] {$fieldName}()";
                $this->tags['methods'][$tag] = new Tag('method', $tag);
            }
        }

        return true;
    }

    /**
     * Generate the mixins for DataExtensions
     *
     * @param DataObject|DataExtension $className
     *
     * @return string
     */
    protected function generateORMExtensionsProperties($className)
    {
        if ($fields = Config::inst()->get($className, 'extensions', Config::UNINHERITED)) {
            //$this->resultString .= " * \n";
            foreach ($fields as $fieldName) {
                $this->tags['mixins'][$fieldName] = new Tag('mixin',$fieldName);
            }
        }

        return true;
    }
}