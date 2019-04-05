<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
class WallpaperType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
       $builder->add('title',null,array("label"=>"Title"));
       $builder->add('tags',null,array("label"=>"Tags"));
       $builder->add('description',null,array("label"=>"Description"));
       $builder->add('title',null,array("label"=>"Title"));
       $builder->add('enabled',null,array("label"=>"Enabled"));
       $builder->add('comment',null,array("label"=>"Enabled comments"));
       $builder->add("categories",'entity',
                    array(
                          'class' => 'AppBundle:Category',
                          'expanded' => true,
                          "multiple" => "true",
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
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $article = $event->getData();
            $form = $event->getForm();
            if ($article and null !== $article->getId()) {
                 $form->add("file",null,array("label"=>"","required"=>false));
            }else{
                 $form->add("file",null,array("label"=>"","required"=>true));
            }
        });
       $builder->add('save', 'submit',array("label"=>"save"));
    }
    public function getName()
    {
        return 'Wallpaper';
    }
}
?>