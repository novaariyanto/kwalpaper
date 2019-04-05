<?php 
namespace AppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Color;
use AppBundle\Form\ColorType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
class ColorController extends Controller
{
    public function api_listAction(Request $request,$token)
    {
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $em=$this->getDoctrine()->getManager();
        $colors=$em->getRepository("AppBundle:Color")->findBy(array('enabled'=>true),array("position"=>"asc"));
        $list=array();
        foreach ($colors as $key => $color) {
            $s["id"]=$color->getId();
            $s["title"]=$color->getTitle();
            $s["code"]="#".$color->getCode();
            $list[]=$s;
        }
        header('Content-Type: application/json'); 
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($list, 'json');
        return new Response($jsonContent);
    }
    public function api_byAction(Request $request,$id,$token)
    {
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $em=$this->getDoctrine()->getManager();
        $wallpaper=$em->getRepository("AppBundle:Wallpaper")->find($id);
        if ($wallpaper==null) {
            throw new NotFoundHttpException("Page not found");  
        }
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $list=array();
        foreach ($wallpaper->getColors() as $key => $color) {
            $s["id"]=$color->getId();
            $s["title"]=$color->getTitle();
            $s["code"]="#".$color->getCode();
            $list[]=$s;
        }
        header('Content-Type: application/json'); 
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($list, 'json');
        return new Response($jsonContent);
    }
    public function addAction(Request $request)
    {
        $color= new Color();
        $form = $this->createForm(new ColorType(),$color);
        $em=$this->getDoctrine()->getManager();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $max=0;
            $colors=$em->getRepository('AppBundle:Color')->findAll();
            foreach ($colors as $key => $value) {
                if ($value->getPosition()>$max) {
                    $max=$value->getPosition();
                }
            }
            $color->setPosition($max+1);
            $em->persist($color);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_color_index'));
        }
        return $this->render("AppBundle:Color:add.html.twig",array("form"=>$form->createView()));
    }
    public function indexAction()
    {
	    $em=$this->getDoctrine()->getManager();
        $colors=$em->getRepository('AppBundle:Color')->findBy(array(),array("position"=>"asc"));
	    return $this->render('AppBundle:Color:index.html.twig',array("colors"=>$colors));    
	}
    public function upAction(Request $request,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $color=$em->getRepository("AppBundle:Color")->find($id);
        if ($color==null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($color->getPosition()>1) {
            $p=$color->getPosition();
            $colors=$em->getRepository('AppBundle:Color')->findAll();
            foreach ($colors as $key => $value) {
                if ($value->getPosition()==$p-1) {
                    $value->setPosition($p);  
                }
            }
            $color->setPosition($color->getPosition()-1);
            $em->flush(); 
        }
        return $this->redirect($this->generateUrl('app_color_index'));
    }
    public function downAction(Request $request,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $color=$em->getRepository("AppBundle:Color")->find($id);
        if ($color==null) {
            throw new NotFoundHttpException("Page not found");
        }
        $max=0;
        $colors=$em->getRepository('AppBundle:Color')->findBy(array(),array("position"=>"asc"));
        foreach ($colors  as $key => $value) {
            $max=$value->getPosition();  
        }
        if ($color->getPosition()<$max) {
            $p=$color->getPosition();
            foreach ($colors as $key => $value) {
                if ($value->getPosition()==$p+1) {
                    $value->setPosition($p);  
                }
            }
            $color->setPosition($color->getPosition()+1);
            $em->flush();  
        }
        return $this->redirect($this->generateUrl('app_color_index'));
    }

    public function deleteAction($id,Request $request){
        $em=$this->getDoctrine()->getManager();

        $color = $em->getRepository("AppBundle:Color")->find($id);
        if($color==null){
            throw new NotFoundHttpException("Page not found");
        }

        $form=$this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->add('Yes', 'submit')
            ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            
            //if (sizeof($color->getAlbums())==0) {
                $em->remove($color);
                $em->flush();

                $colors=$em->getRepository('AppBundle:Color')->findBy(array(),array("position"=>"asc"));

                $p=1;
                foreach ($colors as $key => $value) {
                    $value->setPosition($p); 
                    $p++; 
                }
                $em->flush();

                $this->addFlash('success', 'Operation has been done successfully');
            //}else{
             //   $this->addFlash('danger', 'Operation has been cancelled ,Your album not empty');   
            //}
            return $this->redirect($this->generateUrl('app_color_index'));
        }
        return $this->render('AppBundle:Color:delete.html.twig',array("form"=>$form->createView()));
    }
    public function editAction(Request $request,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $color=$em->getRepository("AppBundle:Color")->find($id);
        if ($color==null) {
            throw new NotFoundHttpException("Page not found");
        }
        $form = $this->createForm(new ColorType(),$color);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($color);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_color_index'));
 
        }
        return $this->render("AppBundle:Color:edit.html.twig",array("form"=>$form->createView()));
    }
}
?>