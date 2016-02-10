<?php namespace BoundedContext\Laravel\Sourced\Aggregate\State;

use BoundedContext\Contracts\Command\Command;
use BoundedContext\Contracts\Sourced\Aggregate\State\Snapshot\Snapshot;
use BoundedContext\Laravel\Serializer\Deserializer;

class Factory implements \BoundedContext\Contracts\Sourced\Aggregate\State\Factory
{
    protected $deserializer;

    protected $state_class;
    protected $state_projection_class;

    public function __construct(Deserializer $deserializer)
    {
        $this->deserializer = $deserializer;
    }

    public function with(Command $command)
    {
        $command_class = get_class($command);

        $aggregate_prefix = substr($command_class, 0, strpos($command_class, "Command"));

        $this->state_class = $aggregate_prefix . 'State';
        $this->state_projection_class = $aggregate_prefix . 'Projection';

        return $this;
    }

    private function parse_doc_comment(\ReflectionProperty $property)
    {
        $doc_comment = $property->getDocComment();
        $doc_comment = trim(preg_replace('/\r?\n *\* *\//', '', $doc_comment));

        $comments = [];
        preg_match_all('/@([a-z]+)\s+(.*?)\s*(?=$|@[a-z]+\s)/s', $doc_comment, $comments);

        return array_combine($comments[1], $comments[2]);
    }

    public function snapshot(Snapshot $snapshot)
    {
        $projection_class = new \ReflectionClass($this->state_projection_class);
        $projection = $projection_class->newInstanceArgs();

        $schema = $snapshot->schema();

        if($schema->serialize() == [])
        {
            return new $this->state_class(
                $snapshot->id(),
                $snapshot->version(),
                $projection
            );
        }

        $projection_object = new \ReflectionObject(
            $projection
        );

        $properties = $projection_object->getProperties();
        foreach ($properties as $property)
        {
            $comment = $this->parse_doc_comment($property);

            $property_class_name = $comment['var'];
            $property_name = $property->name;

            $property_class = new \ReflectionClass($property_class_name);

            if($property_class->implementsInterface('BoundedContext\Contracts\Index\Index'))
            {
                $index = $property_class->newInstance();

                $index_object = new \ReflectionObject($index);
                $index_object_property = $index_object->getProperty('of');
                $index_object_property->setAccessible(true);

                $of_class_name = $index_object_property->getValue($index);
                $of_class = new \ReflectionClass($of_class_name);

                $property_elements = $snapshot->schema()->$property_name;
                if(count($property_elements) > 0)
                {
                    foreach($property_elements as $property_element)
                    {
                        $of_class_parameters = $of_class->getConstructor()->getParameters();

                        $parameter_properties = [];

                        for($i = 0; $i < count($of_class_parameters); $i++)
                        {
                            $key = $of_class_parameters[$i]->name;
                            $value = $property_element[$key];

                            $class = $of_class_parameters[$i]->getClass()->name;

                            $parameter_properties[$key] = $this->deserializer->deserialize(
                                $class,
                                $value
                            );
                        }

                        $of_class_instance = $of_class->newInstanceArgs($parameter_properties);
                        $index->add($of_class_instance);
                    }
                }

                $projection->$property_name = $index;

            } else {

                $projection->$property_name = $this->deserializer->deserialize(
                    $property_class_name,
                    $snapshot->schema()->$property_name
                );
            }
        }

        return new $this->state_class(
            $snapshot->id(),
            $snapshot->version(),
            $projection
        );
    }
}
