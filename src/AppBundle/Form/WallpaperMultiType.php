<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class WallpaperMultiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
       $builder->add('usefilename',CheckboxType::class,array("label"=>"Use the name file as title",'required' => false));
       $builder->add('enabled',null,array("label"=>"Enabled",));
       $builder->add('comment',null,array("label"=>"Enabled comments"));
       $builder->add('titlemulti');
       $builder->add('tags',null,array("label"=>"Tags"));
       $builder->add('description',null,array("label"=>"Description"));
       $builder->add("categories",'entity',
                    array(
                          'class' => 'AppBundle:Category',
                          'expanded' => true,
                          "multiple" => "true",
                          'group_by'=> 'section',
                          'by_reference' => false
                        )
                    );
       $builder->add("colors",'entity',
                    array(
                          'class' => 'AppBundle:Color',
                          'expanded' => true,
                          "multiple" => "true",
                          'by_reference' => false
                        )
                    );
       $builder->add('files', 'file', array(
            'label'=>"Images list (multiple images)",
            'multiple' => true, 
            'data_class' => null,
        ));
       $builder->add('save', 'submit',array("label"=>"save"));
    }
    public function getName()
    {
        return 'Wallpaper';
    }
}
?>